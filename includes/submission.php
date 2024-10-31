<?php

add_action( 'sbf_before_send_mail', 'sbf_add_new_prospect', 10, 1 ); 
function sbf_add_new_prospect( $contact_form ) {
    $title = $contact_form->title();
    $submission = SBF_Submission::get_instance();  

    if ( $submission ) {
        $posted_data = $submission->get_posted_data();

		$results = array();
		$responsible_user_name = null;

		$form_properties = $contact_form -> get_properties();

		$salesbox_config = $form_properties['salesbox_config'];

		if (isset($salesbox_config['responsible_user'])) {
			$responsible_user_name = $salesbox_config['responsible_user']['email'];
		};

		if ($salesbox_config) {
			$fields = $salesbox_config['fields'];
			
			foreach ($posted_data as $field_key => $value) {
				$key = substr($field_key, 5);
				
				$value_text = null;
				$value_ids = null;
				if (is_array($value)) {
					$value_ids = array();
					
					$field_array_key = array_search($key, array_column($fields, 'name'));

					

					if ($field_array_key !== false) {
						$field = $fields[$field_array_key];
						$options = isset($field['options']) ? $field['options'] : array();

						
						foreach ($value as $option_value) {
							$option_array_key = array_search($option_value, array_column($options, 'text'));

							if ($option_array_key !== false) {
								array_push($value_ids, $options[$option_array_key]['uuid']);
							}
						}
						
					}
				} else {
					$value_text = $value;
				}

				$result_item = array(
					"key" => $key,
					"valueText" => $value_text,
					"valueIds" => $value_ids
				);
				
				array_push($results, $result_item);
			}
		}

		$body = array(
			"result" => $results,
			"username" => $responsible_user_name
		);

		$body_json = json_encode($body);

		error_log(print_r($body_json, true));

		$req_data = [
			'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
			"body" => $body_json,
			'data_format' => 'body'
		];
		$option = (array) SBF::get_option( 'salesbox_crm' );

		if (isset($option['token']) && isset($option['enterprise_id'])) {
			$enterprise_id = $option['enterprise_id'];
			$token = $option['token'];

			$new_prospect_api_endpoint = "https://production.salesbox.com/lead-v3.0/processWordPressData?token={$token}&enterpriseID={$enterprise_id}";
			error_log('$new_prospect_api_endpoint '. $new_prospect_api_endpoint);
        	$response = wp_remote_post( $new_prospect_api_endpoint, $req_data );
			error_log(print_r($response, true));
		}

		
    }       
}

class SBF_Submission {

	private static $instance;

	private $contact_form;
	private $status = 'init';
	private $posted_data = array();
	private $posted_data_hash = null;
	private $skip_spam_check = false;
	private $uploaded_files = array();
	private $skip_mail = false;
	private $response = '';
	private $invalid_fields = array();
	private $meta = array();
	private $consent = array();
	private $spam_log = array();

	public static function get_instance( $contact_form = null, $args = '' ) {
		if ( $contact_form instanceof SBF_ContactForm ) {
			if ( empty( self::$instance ) ) {
				self::$instance = new self( $contact_form, $args );
				self::$instance->proceed();
				return self::$instance;
			} else {
				return null;
			}
		} else {
			if ( empty( self::$instance ) ) {
				return null;
			} else {
				return self::$instance;
			}
		}
	}

	public static function is_restful() {
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	private function __construct( SBF_ContactForm $contact_form, $args = '' ) {
		$args = wp_parse_args( $args, array(
			'skip_mail' => false,
		) );

		$this->contact_form = $contact_form;
		$this->skip_mail = (bool) $args['skip_mail'];
	}

	private function proceed() {
		$contact_form = $this->contact_form;

		switch_to_locale( $contact_form->locale() );

		$this->setup_meta_data();
		$this->setup_posted_data();

		if ( $this->is( 'init' ) and ! $this->validate() ) {
			$this->set_status( 'validation_failed' );
			$this->set_response( $contact_form->message( 'validation_error' ) );
		}

		if ( $this->is( 'init' ) and ! $this->validate_contact_company() ) {
			$this->set_status( 'validation_failed' );
			$this->set_response( $contact_form->message( 'contact_company_error' ) );
		}

		if ( $this->is( 'init' ) and ! $this->accepted() ) {
			$this->set_status( 'acceptance_missing' );
			$this->set_response( $contact_form->message( 'accept_terms' ) );
		}

		if ( $this->is( 'init' ) and $this->spam() ) {
			$this->set_status( 'spam' );
			$this->set_response( $contact_form->message( 'spam' ) );
		}

		if ( $this->is( 'init' ) and ! $this->unship_uploaded_files() ) {
			$this->set_status( 'validation_failed' );
			$this->set_response( $contact_form->message( 'validation_error' ) );
		}

		if ( $this->is( 'init' ) ) {
			$abort = ! $this->before_send_mail();

			if ( $abort ) {
				if ( $this->is( 'init' ) ) {
					$this->set_status( 'aborted' );
				}

				if ( '' === $this->get_response() ) {
					$this->set_response( $contact_form->filter_message(
						__( "Sending mail has been aborted.", 'salesbox-crm-form' ) )
					);
				}
			} elseif ( $this->mail() ) {
				$this->set_status( 'mail_sent' );
				$this->set_response( $contact_form->message( 'mail_sent_ok' ) );

				do_action( 'sbf_mail_sent', $contact_form );
			} else {
				$this->set_status( 'mail_failed' );
				$this->set_response( $contact_form->message( 'mail_sent_ng' ) );

				do_action( 'sbf_mail_failed', $contact_form );
			}
		}

		restore_previous_locale();

		$this->remove_uploaded_files();
	}

	public function get_status() {
		return $this->status;
	}

	public function set_status( $status ) {
		if ( preg_match( '/^[a-z][0-9a-z_]+$/', $status ) ) {
			$this->status = $status;
			return true;
		}

		return false;
	}

	public function is( $status ) {
		return $this->status == $status;
	}

	public function get_response() {
		return $this->response;
	}

	public function set_response( $response ) {
		$this->response = $response;
		return true;
	}

	public function get_contact_form() {
		return $this->contact_form;
	}

	public function get_invalid_field( $name ) {
		if ( isset( $this->invalid_fields[$name] ) ) {
			return $this->invalid_fields[$name];
		} else {
			return false;
		}
	}

	public function get_invalid_fields() {
		return $this->invalid_fields;
	}

	public function get_meta( $name ) {
		if ( isset( $this->meta[$name] ) ) {
			return $this->meta[$name];
		}
	}

	private function setup_meta_data() {
		$timestamp = time();

		$remote_ip = $this->get_remote_ip_addr();

		$remote_port = isset( $_SERVER['REMOTE_PORT'] )
			? (int) $_SERVER['REMOTE_PORT'] : '';

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? substr( $_SERVER['HTTP_USER_AGENT'], 0, 254 ) : '';

		$url = $this->get_request_url();

		$unit_tag = isset( $_POST['_sbf_unit_tag'] )
			? sanitize_text_field($_POST['_sbf_unit_tag']) : '';

		$container_post_id = isset( $_POST['_sbf_container_post'] )
			? (int) $_POST['_sbf_container_post'] : 0;

		$current_user_id = get_current_user_id();

		$do_not_store = $this->contact_form->is_true( 'do_not_store' );

		$this->meta = array(
			'timestamp' => $timestamp,
			'remote_ip' => $remote_ip,
			'remote_port' => $remote_port,
			'user_agent' => $user_agent,
			'url' => $url,
			'unit_tag' => $unit_tag,
			'container_post_id' => $container_post_id,
			'current_user_id' => $current_user_id,
			'do_not_store' => $do_not_store,
		);

		return $this->meta;
	}

	public function get_posted_data( $name = '' ) {
		if ( ! empty( $name ) ) {
			if ( isset( $this->posted_data[$name] ) ) {
				return $this->posted_data[$name];
			} else {
				return null;
			}
		}

		return $this->posted_data;
	}

	private function setup_posted_data() {
		$posted_data = array_map(function ($item) {
			if (gettype($item) == 'string') {
				return sanitize_text_field($item);
			}  
			
			if (gettype($item) == 'array') {
				return array_map('sanitize_text_field', $item);
			}

			return null;
		}, (array) $_POST);

		$posted_data = array_filter($posted_data, function( $key ) {
			return '_' !== substr( $key, 0, 1 );
		}, ARRAY_FILTER_USE_KEY );

		error_log("posted_data");
		error_log(print_r($posted_data, true));

		$posted_data = wp_unslash( $posted_data );
		$posted_data = $this->sanitize_posted_data( $posted_data );

		$tags = $this->contact_form->scan_form_tags();

		foreach ( (array) $tags as $tag ) {
			if ( empty( $tag->name ) ) {
				continue;
			}

			$type = $tag->type;
			$name = $tag->name;
			$pipes = $tag->pipes;

			if ( sbf_form_tag_supports( $type, 'do-not-store' ) ) {
				unset( $posted_data[$name] );
				continue;
			}

			$value_orig = $value = '';

			if ( isset( $posted_data[$name] ) ) {
				$value_orig = $value = $posted_data[$name];
			}

			if ( SBF_USE_PIPE
			and $pipes instanceof SBF_Pipes
			and ! $pipes->zero() ) {
				if ( is_array( $value_orig ) ) {
					$value = array();

					foreach ( $value_orig as $v ) {
						$value[] = $pipes->do_pipe( $v );
					}
				} else {
					$value = $pipes->do_pipe( $value_orig );
				}
			}

			if ( sbf_form_tag_supports( $type, 'selectable-values' ) ) {
				$value = (array) $value;

				if ( $tag->has_option( 'free_text' )
				and isset( $posted_data[$name . '_free_text'] ) ) {
					$last_val = array_pop( $value );

					list( $tied_item ) = array_slice(
						SBF_USE_PIPE ? $tag->pipes->collect_afters() : $tag->values,
						-1, 1
					);

					$tied_item = html_entity_decode( $tied_item, ENT_QUOTES, 'UTF-8' );

					if ( $last_val === $tied_item ) {
						$value[] = sprintf( '%s %s',
							$last_val,
							$posted_data[$name . '_free_text']
						);
					} else {
						$value[] = $last_val;
					}

					unset( $posted_data[$name . '_free_text'] );
				}
			}

			$value = apply_filters( "sbf_posted_data_{$type}", $value,
				$value_orig, $tag );

			$posted_data[$name] = $value;

			if ( $tag->has_option( 'consent_for:storage' )
			and empty( $posted_data[$name] ) ) {
				$this->meta['do_not_store'] = true;
			}
		}

		$this->posted_data = apply_filters( 'sbf_posted_data', $posted_data );

		$this->posted_data_hash = wp_hash(
			sbf_flat_join( array_merge(
				array(
					$this->get_meta( 'remote_ip' ),
					$this->get_meta( 'remote_port' ),
					$this->get_meta( 'unit_tag' ),
				),
				$this->posted_data
			) ),
			'sbf_submission'
		);

		return $this->posted_data;
	}

	private function sanitize_posted_data( $value ) {
		if ( is_array( $value ) ) {
			$value = array_map( array( $this, 'sanitize_posted_data' ), $value );
		} elseif ( is_string( $value ) ) {
			$value = wp_check_invalid_utf8( $value );
			$value = wp_kses_no_null( $value );
		}

		return $value;
	}

	public function get_posted_data_hash() {
		return $this->posted_data_hash;
	}

	private function get_remote_ip_addr() {
		$ip_addr = '';

		if ( isset( $_SERVER['REMOTE_ADDR'] )
		and WP_Http::is_ip_address( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip_addr = $_SERVER['REMOTE_ADDR'];
		}

		return apply_filters( 'sbf_remote_ip_addr', $ip_addr );
	}

	private function get_request_url() {
		$home_url = untrailingslashit( home_url() );

		if ( self::is_restful() ) {
			$referer = isset( $_SERVER['HTTP_REFERER'] )
				? trim( $_SERVER['HTTP_REFERER'] ) : '';

			if ( $referer
			and 0 === strpos( $referer, $home_url ) ) {
				return esc_url_raw( $referer );
			}
		}

		$url = preg_replace( '%(?<!:|/)/.*$%', '', $home_url )
			. sbf_get_request_uri();

		return $url;
	}

	private function validate() {
		if ( $this->invalid_fields ) {
			return false;
		}

		require_once SBF_PLUGIN_DIR . '/includes/validation.php';
		$result = new SBF_Validation();

		$tags = $this->contact_form->scan_form_tags( array(
		  'feature' => '! file-uploading',
		) );

		foreach ( $tags as $tag ) {
			$type = $tag->type;
			$result = apply_filters( "sbf_validate_{$type}", $result, $tag );
		}

		$result = apply_filters( 'sbf_validate', $result, $tags );

		$this->invalid_fields = $result->get_invalid_fields();

		return $result->is_valid();
	}

	private function validate_contact_company() {
		$submission = SBF_Submission::get_instance();  

		$posted_data = null;
    if ( $submission ) {
        $posted_data = $submission->get_posted_data();
		}

		$results = array();
		$responsible_user_name = null;

		$form_properties = $this-> contact_form -> get_properties();

		$salesbox_config = $form_properties['salesbox_config'];

		if ($posted_data['NAME_ACCOUNT_NAME'] == '' && ($posted_data['NAME_FIRST_NAME'] == '' || $posted_data['NAME_LAST_NAME'] == '')) {
			return false;
		}
		return true;
	}

	private function accepted() {
		return apply_filters( 'sbf_acceptance', true, $this );
	}

	public function add_consent( $name, $conditions ) {
		$this->consent[$name] = $conditions;
		return true;
	}

	public function collect_consent() {
		return (array) $this->consent;
	}

	private function spam() {
		$spam = false;

		$skip_spam_check = apply_filters( 'sbf_skip_spam_check',
			$this->skip_spam_check,
			$this
		);

		if ( $skip_spam_check ) {
			return $spam;
		}

		if ( $this->contact_form->is_true( 'subscribers_only' )
		and current_user_can( 'sbf_submit', $this->contact_form->id() ) ) {
			return $spam;
		}

		$user_agent = (string) $this->get_meta( 'user_agent' );

		if ( strlen( $user_agent ) < 2 ) {
			$spam = true;

			$this->add_spam_log( array(
				'agent' => 'sbf',
				'reason' => __( "User-Agent string is unnaturally short.", 'salesbox-crm-form' ),
			) );
		}

		if ( ! $this->verify_nonce() ) {
			$spam = true;

			$this->add_spam_log( array(
				'agent' => 'sbf',
				'reason' => __( "Submitted nonce is invalid.", 'salesbox-crm-form' ),
			) );
		}

		return apply_filters( 'sbf_spam', $spam, $this );
	}

	public function add_spam_log( $args = '' ) {
		$args = wp_parse_args( $args, array(
			'agent' => '',
			'reason' => '',
		) );

		$this->spam_log[] = $args;
	}

	public function get_spam_log() {
		return $this->spam_log;
	}

	private function verify_nonce() {
		if ( ! $this->contact_form->nonce_is_active() ) {
			return true;
		}

		return sbf_verify_nonce( sanitize_text_field($_POST['_wpnonce']) );
	}

	/* Mail */

	private function before_send_mail() {
		$abort = false;

		do_action_ref_array( 'sbf_before_send_mail', array(
			$this->contact_form,
			&$abort,
			$this,
		) );

		return ! $abort;
	}

	private function mail() {
		$contact_form = $this->contact_form;

		$skip_mail = apply_filters( 'sbf_skip_mail',
			$this->skip_mail, $contact_form
		);

		if ( $skip_mail ) {
			return true;
		}
		// SKIP MAIL AUTOMATICALLY
		return true;

		$result = SBF_Mail::send( $contact_form->prop( 'mail' ), 'mail' );

		if ( $result ) {
			$additional_mail = array();

			if ( $mail_2 = $contact_form->prop( 'mail_2' )
			and $mail_2['active'] ) {
				$additional_mail['mail_2'] = $mail_2;
			}

			$additional_mail = apply_filters( 'sbf_additional_mail',
				$additional_mail, $contact_form
			);

			foreach ( $additional_mail as $name => $template ) {
				SBF_Mail::send( $template, $name );
			}

			return true;
		}

		return false;
	}

	public function uploaded_files() {
		return $this->uploaded_files;
	}

	public function add_uploaded_file( $name, $file_path ) {
		if ( ! sbf_is_name( $name ) ) {
			return false;
		}

		if ( ! @is_file( $file_path ) or ! @is_readable( $file_path ) ) {
			return false;
		}

		$this->uploaded_files[$name] = $file_path;

		if ( empty( $this->posted_data[$name] ) ) {
			$this->posted_data[$name] = md5_file( $file_path );
		}
	}

	public function remove_uploaded_files() {
		foreach ( (array) $this->uploaded_files as $name => $path ) {
			sbf_rmdir_p( $path );

			if ( $dir = dirname( $path )
			and false !== ( $files = scandir( $dir ) )
			and ! array_diff( $files, array( '.', '..' ) ) ) {
				// remove parent dir if it's empty.
				rmdir( $dir );
			}
		}
	}

	private function unship_uploaded_files() {
		return true;
	}

}
