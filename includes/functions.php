<?php

function sbf_plugin_path( $path = '' ) {
	return path_join( SBF_PLUGIN_DIR, trim( $path, '/' ) );
}

function sbf_plugin_url( $path = '' ) {
	$url = plugins_url( $path, SBF_PLUGIN );

	if ( is_ssl()
	and 'http:' == substr( $url, 0, 5 ) ) {
		$url = 'https:' . substr( $url, 5 );
	}

	return $url;
}

function sbf_upload_dir( $type = false ) {
	$uploads = wp_get_upload_dir();

	$uploads = apply_filters( 'sbf_upload_dir', array(
		'dir' => $uploads['basedir'],
		'url' => $uploads['baseurl'],
	) );

	if ( 'dir' == $type ) {
		return $uploads['dir'];
	} if ( 'url' == $type ) {
		return $uploads['url'];
	}

	return $uploads;
}

function sbf_verify_nonce( $nonce, $action = 'wp_rest' ) {
	return wp_verify_nonce( $nonce, $action );
}

function sbf_create_nonce( $action = 'wp_rest' ) {
	return wp_create_nonce( $action );
}

function sbf_array_flatten( $input ) {
	if ( ! is_array( $input ) ) {
		return array( $input );
	}

	$output = array();

	foreach ( $input as $value ) {
		$output = array_merge( $output, sbf_array_flatten( $value ) );
	}

	return $output;
}

function sbf_flat_join( $input ) {
	$input = sbf_array_flatten( $input );
	$output = array();

	foreach ( (array) $input as $value ) {
		$output[] = trim( (string) $value );
	}

	return implode( ', ', $output );
}

function sbf_support_html5() {
	return (bool) apply_filters( 'sbf_support_html5', true );
}

function sbf_support_html5_fallback() {
	return (bool) apply_filters( 'sbf_support_html5_fallback', false );
}

function sbf_use_really_simple_captcha() {
	return apply_filters( 'sbf_use_really_simple_captcha',
		SBF_USE_REALLY_SIMPLE_CAPTCHA );
}

function sbf_validate_configuration() {
	return apply_filters( 'sbf_validate_configuration',
		SBF_VALIDATE_CONFIGURATION );
}

function sbf_autop_or_not() {
	return (bool) apply_filters( 'sbf_autop_or_not', SBF_AUTOP );
}

function sbf_load_js() {
	return apply_filters( 'sbf_load_js', SBF_LOAD_JS );
}

function sbf_load_css() {
	return apply_filters( 'sbf_load_css', SBF_LOAD_CSS );
}

function sbf_format_atts( $atts ) {
	$html = '';

	$prioritized_atts = array( 'type', 'name', 'value' );

	foreach ( $prioritized_atts as $att ) {
		if ( isset( $atts[$att] ) ) {
			$value = trim( $atts[$att] );
			$html .= sprintf( ' %s="%s"', $att, esc_attr( $value ) );
			unset( $atts[$att] );
		}
	}

	foreach ( $atts as $key => $value ) {
		$key = strtolower( trim( $key ) );

		if ( ! preg_match( '/^[a-z_:][a-z_:.0-9-]*$/', $key ) ) {
			continue;
		}

		$value = trim( $value );

		if ( '' !== $value ) {
			$html .= sprintf( ' %s="%s"', $key, esc_attr( $value ) );
		}
	}

	$html = trim( $html );

	return $html;
}

function sbf_link( $url, $anchor_text, $args = '' ) {
	$defaults = array(
		'id' => '',
		'class' => '',
	);

	$args = wp_parse_args( $args, $defaults );
	$args = array_intersect_key( $args, $defaults );
	$atts = sbf_format_atts( $args );

	$link = sprintf( '<a href="%1$s"%3$s>%2$s</a>',
		esc_url( $url ),
		esc_html( $anchor_text ),
		$atts ? ( ' ' . $atts ) : '' );

	return $link;
}

function sbf_get_request_uri() {
	static $request_uri = '';

	if ( empty( $request_uri ) ) {
		$request_uri = add_query_arg( array() );
	}

	return esc_url_raw( $request_uri );
}

function sbf_register_post_types() {
	if ( class_exists( 'SBF_ContactForm' ) ) {
		SBF_ContactForm::register_post_type();
		return true;
	} else {
		return false;
	}
}

function sbf_fetch_salesbox_fields() {
	$fields = [];
	$option = (array) SBF::get_option( 'salesbox_crm' );

	if (isset($option['token']) && isset($option['enterprise_id'])) {
		$enterprise_id = $option['enterprise_id'];
		$token = $option['token'];
		$fetch_url = "https://production.salesbox.com/advance-search-v3.0/wordPressFormFields?token={$token}&enterpriseID={$enterprise_id}";
		error_log($fetch_url);
		$response = wp_remote_get( $fetch_url );
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code($response) != 200 ) {
			error_log('Error fetch_fields_v2');
			error_log(print_r($response, true));
		} else {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			$regular_fields = array_merge($data['companyFields'], $data['contactFields'], $data['prospectFields']);
			
			$company_custom_fields = array_map(function ($item) {
				$item['value'] = $item['uuid'] . '_COMPANY';
				$item['isCustomField'] = true;
				return $item;
			}, $data['companyCustomFields']);

			$contact_custom_fields = array_map(function ($item) {
				$item['value'] = $item['uuid'] . '_CONTACT';
				$item['isCustomField'] = true;
				return $item;
			}, $data['contactCustomFields']);

			$prospect_custom_fields = array_map(function ($item) {
				$item['value'] = $item['uuid'] . '_PROSPECT';
				$item['isCustomField'] = true;
				return $item;
			}, $data['prospectCustomFields']);

			$custom_fields = array_merge($company_custom_fields, $contact_custom_fields, $prospect_custom_fields);
			$all_fields = array_merge($regular_fields, $custom_fields);

			foreach ($all_fields as $field_item) {
				$field = array();
				$field['label'] = $field_item['title'];
				$field['name'] = $field_item['value'];

				if (in_array($field['name'], ['FIRST_NAME', 'LAST_NAME', 'ACCOUNT_NAME'])) {
					$field['alwaysRequired'] = true;
				} else {
					$field['alwaysRequired'] = false;
				};

				$field['isMultipleChoice'] = isset($field_item['multipleChoice']) && $field_item['multipleChoice'];

				switch ($field_item['type']) {
					case 'TEXT': // done
						$field['type'] = 'text';
						break;
					case 'NUMBER': // done
						$field['type'] = 'number';
						break;
					case 'DROPDOWN': // done
						$field['type'] = 'select';
						$field['options'] = $field_item['possibleValueList'];
						break;
					case 'OBJECT': // done
						$field['type'] = 'select';
						$field['options'] = $field_item['possibleValueList'];
						if (isset($field_item['productList'])) {
							$field['productList'] = $field_item['productList'];
							$field['options'] = $field_item['productList'];
 						}
						break;
					case 'URL': // done
						$field['type'] = 'url';
						break;
					case 'TEXT_BOX': // done
						$field['type'] = 'textarea';
						break;
					case 'DATE': // done
						$field['type'] = 'date';
						break;
					case 'CHECK_BOXES': // done
						$field['type'] = 'checkbox';
						$field['options'] = $field_item['possibleValueList'];
						break;
					case 'PRODUCT_TAG': 
						$field['type'] = 'select';
						$field['options'] = $field_item['possibleValueList'];
						break;
				}

				array_push($fields, $field);
			}

			$submit_button_field = array();
			$submit_button_field['label'] = 'Submit';
			$submit_button_field['type'] = 'submit';
			$submit_button_field['name'] = 'SUBMIT_BUTTON';
			$submit_button_field['alwaysRequired'] = true;

			array_push($fields, $submit_button_field);
		}
	}
	
	return $fields;
}

function sbf_fetch_responsible_users() {
	$data = array();
	$option = (array) SBF::get_option( 'salesbox_crm' );

	if (isset($option['token']) && isset($option['enterprise_id'])) {
		$enterprise_id = $option['enterprise_id'];
		$token = $option['token'];

		error_log("https://production.salesbox.com/enterprise-v3.0/user/listUserLite?token={$token}&enterpriseID={$enterprise_id}");

		$response = wp_remote_get( "https://production.salesbox.com/enterprise-v3.0/user/listUserLite?token={$token}&enterpriseID={$enterprise_id}" );
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code($response) != 200 ) {
			error_log('Error sbf_fetch_responsible_users');
			error_log(print_r($response, true));
		} else {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if (isset($data['userLiteDTOList'])) {
				$data = $data['userLiteDTOList'];
			}
		}
	}
	
	return $data;
}

function sbf_register_salesbox_config() {
	$salesbox_config = array();
	$fields = sbf_fetch_salesbox_fields();
	$responsible_users = sbf_fetch_responsible_users();
	$salesbox_config['fields'] = $fields;
	$salesbox_config['responsible_users'] = $responsible_users;
	SBF::update_option( 'salesbox_config', $salesbox_config);
	error_log(print_r($salesbox_config, true));
}

function sbf_version( $args = '' ) {
	$defaults = array(
		'limit' => -1,
		'only_major' => false,
	);

	$args = wp_parse_args( $args, $defaults );

	if ( $args['only_major'] ) {
		$args['limit'] = 2;
	}

	$args['limit'] = (int) $args['limit'];

	$ver = SBF_VERSION;
	$ver = strtr( $ver, '_-+', '...' );
	$ver = preg_replace( '/[^0-9.]+/', ".$0.", $ver );
	$ver = preg_replace( '/[.]+/', ".", $ver );
	$ver = trim( $ver, '.' );
	$ver = explode( '.', $ver );

	if ( -1 < $args['limit'] ) {
		$ver = array_slice( $ver, 0, $args['limit'] );
	}

	$ver = implode( '.', $ver );

	return $ver;
}

function sbf_version_grep( $version, array $input ) {
	$pattern = '/^' . preg_quote( (string) $version, '/' ) . '(?:\.|$)/';

	return preg_grep( $pattern, $input );
}

function sbf_enctype_value( $enctype ) {
	$enctype = trim( $enctype );

	if ( empty( $enctype ) ) {
		return '';
	}

	$valid_enctypes = array(
		'application/x-www-form-urlencoded',
		'multipart/form-data',
		'text/plain',
	);

	if ( in_array( $enctype, $valid_enctypes ) ) {
		return $enctype;
	}

	$pattern = '%^enctype="(' . implode( '|', $valid_enctypes ) . ')"$%';

	if ( preg_match( $pattern, $enctype, $matches ) ) {
		return $matches[1]; // for back-compat
	}

	return '';
}

function sbf_rmdir_p( $dir ) {
	if ( is_file( $dir ) ) {
		$file = $dir;

		if ( @unlink( $file ) ) {
			return true;
		}

		$stat = stat( $file );

		if ( @chmod( $file, $stat['mode'] | 0200 ) ) { // add write for owner
			if ( @unlink( $file ) ) {
				return true;
			}

			@chmod( $file, $stat['mode'] );
		}

		return false;
	}

	if ( ! is_dir( $dir ) ) {
		return false;
	}

	if ( $handle = opendir( $dir ) ) {
		while ( false !== ( $file = readdir( $handle ) ) ) {
			if ( $file == "."
			or $file == ".." ) {
				continue;
			}

			sbf_rmdir_p( path_join( $dir, $file ) );
		}

		closedir( $handle );
	}

	if ( false !== ( $files = scandir( $dir ) )
	and ! array_diff( $files, array( '.', '..' ) ) ) {
		return rmdir( $dir );
	}

	return false;
}

/* From _http_build_query in wp-includes/functions.php */
function sbf_build_query( $args, $key = '' ) {
	$sep = '&';
	$ret = array();

	foreach ( (array) $args as $k => $v ) {
		$k = urlencode( $k );

		if ( ! empty( $key ) ) {
			$k = $key . '%5B' . $k . '%5D';
		}

		if ( null === $v ) {
			continue;
		} elseif ( false === $v ) {
			$v = '0';
		}

		if ( is_array( $v ) or is_object( $v ) ) {
			array_push( $ret, sbf_build_query( $v, $k ) );
		} else {
			array_push( $ret, $k . '=' . urlencode( $v ) );
		}
	}

	return implode( $sep, $ret );
}

/**
 * Returns the number of code units in a string.
 *
 * @see http://www.w3.org/TR/html5/infrastructure.html#code-unit-length
 *
 * @return int|bool The number of code units, or false if mb_convert_encoding is not available.
 */
function sbf_count_code_units( $string ) {
	static $use_mb = null;

	if ( is_null( $use_mb ) ) {
		$use_mb = function_exists( 'mb_convert_encoding' );
	}

	if ( ! $use_mb ) {
		return false;
	}

	$string = (string) $string;
	$string = str_replace( "\r\n", "\n", $string );

	$encoding = mb_detect_encoding( $string, mb_detect_order(), true );

	if ( $encoding ) {
		$string = mb_convert_encoding( $string, 'UTF-16', $encoding );
	} else {
		$string = mb_convert_encoding( $string, 'UTF-16', 'UTF-8' );
	}

	$byte_count = mb_strlen( $string, '8bit' );

	return floor( $byte_count / 2 );
}

function sbf_is_localhost() {
	$server_name = strtolower( $_SERVER['SERVER_NAME'] );
	return in_array( $server_name, array( 'localhost', '127.0.0.1' ) );
}

function sbf_deprecated_function( $function, $version, $replacement ) {
	if ( WP_DEBUG ) {
		if ( function_exists( '__' ) ) {
			trigger_error(
				sprintf(
					/* translators: 1: PHP function name, 2: version number, 3: alternative function name */
					__( '%1$s is <strong>deprecated</strong> since Salesbox forms version %2$s! Use %3$s instead.', 'salesbox-crm-form' ),
					$function, $version, $replacement
				),
				E_USER_DEPRECATED
			);
		} else {
			trigger_error(
				sprintf(
					'%1$s is <strong>deprecated</strong> since Salesbox forms version %2$s! Use %3$s instead.',
					$function, $version, $replacement
				),
				E_USER_DEPRECATED
			);
		}
	}
}

function sbf_apply_filters_deprecated( $tag, $args, $version, $replacement ) {
	if ( ! has_filter( $tag ) ) {
		return $args[0];
	}

	if ( WP_DEBUG ) {
		trigger_error(
			sprintf(
				/* translators: 1: WordPress hook name, 2: version number, 3: alternative hook name */
				__( '%1$s is <strong>deprecated</strong> since Salesbox forms version %2$s! Use %3$s instead.', 'salesbox-crm-form' ),
				$tag, $version, $replacement
			),
			E_USER_DEPRECATED
		);
	}

	return apply_filters_ref_array( $tag, $args );
}

function sbf_doing_it_wrong( $function, $message, $version ) {
	if ( WP_DEBUG ) {
		if ( function_exists( '__' ) ) {
			if ( $version ) {
				$version = sprintf(
					/* translators: %s: Salesbox forms version number. */
					__( '(This message was added in Salesbox forms version %s.)', 'salesbox-crm-form' ),
					$version
				);
			}

			trigger_error(
				sprintf(
					/* translators: Developer debugging message. 1: PHP function name, 2: Explanatory message, 3: Salesbox forms version number. */
					__( '%1$s was called incorrectly. %2$s %3$s', 'salesbox-crm-form' ),
					$function,
					$message,
					$version
				),
				E_USER_NOTICE
			);
		} else {
			if ( $version ) {
				$version = sprintf(
					'(This message was added in Salesbox forms version %s.)',
					$version
				);
			}

			trigger_error(
				sprintf(
					'%1$s was called incorrectly. %2$s %3$s',
					$function,
					$message,
					$version
				),
				E_USER_NOTICE
			);
		}
	}
}

function sbf_log_remote_request( $url, $request, $response ) {
	$log = sprintf(
		/* translators: 1: response code, 2: message, 3: body, 4: URL */
		__( 'HTTP Response: %1$s %2$s %3$s from %4$s', 'salesbox-crm-form' ),
		(int) wp_remote_retrieve_response_code( $response ),
		wp_remote_retrieve_response_message( $response ),
		wp_remote_retrieve_body( $response ),
		$url
	);

	$log = apply_filters( 'sbf_log_remote_request',
		$log, $url, $request, $response
	);

	if ( $log ) {
		trigger_error( $log );
	}
}

function sbf_anonymize_ip_addr( $ip_addr ) {
	if ( ! function_exists( 'inet_ntop' )
	or ! function_exists( 'inet_pton' ) ) {
		return $ip_addr;
	}

	$packed = inet_pton( $ip_addr );

	if ( false === $packed ) {
		return $ip_addr;
	}

	if ( 4 == strlen( $packed ) ) { // IPv4
		$mask = '255.255.255.0';
	} elseif ( 16 == strlen( $packed ) ) { // IPv6
		$mask = 'ffff:ffff:ffff:0000:0000:0000:0000:0000';
	} else {
		return $ip_addr;
	}

	return inet_ntop( $packed & inet_pton( $mask ) );
}

function sbf_is_file_path_in_content_dir( $path ) {
	if ( 0 === strpos( realpath( $path ), realpath( WP_CONTENT_DIR ) ) ) {
		return true;
	}

	if ( defined( 'UPLOADS' )
	and 0 === strpos( realpath( $path ), realpath( ABSPATH . UPLOADS ) ) ) {
		return true;
	}

	return false;
}
