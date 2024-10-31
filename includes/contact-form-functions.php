<?php

function sbf_contact_form( $id ) {
	return SBF_ContactForm::get_instance( $id );
}

function sbf_get_contact_form_by_old_id( $old_id ) {
	global $wpdb;

	$q = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_old_sbf_unit_id'"
		. $wpdb->prepare( " AND meta_value = %d", $old_id );

	if ( $new_id = $wpdb->get_var( $q ) ) {
		return sbf_contact_form( $new_id );
	}
}

function sbf_get_contact_form_by_title( $title ) {
	$page = get_page_by_title( $title, OBJECT, SBF_ContactForm::post_type );

	if ( $page ) {
		return sbf_contact_form( $page->ID );
	}

	return null;
}

function sbf_get_current_contact_form() {
	if ( $current = SBF_ContactForm::get_current() ) {
		return $current;
	}
}

function sbf_is_posted() {
	if ( ! $contact_form = sbf_get_current_contact_form() ) {
		return false;
	}

	return $contact_form->is_posted();
}

function sbf_get_hangover( $name, $default = null ) {
	if ( ! sbf_is_posted() ) {
		return $default;
	}

	$submission = SBF_Submission::get_instance();

	if ( ! $submission
	or $submission->is( 'mail_sent' ) ) {
		return $default;
	}

	$data = $default;
	if (isset( $_POST[$name] )) {
		if ($default == '') {
			$data = wp_unslash( sanitize_text_field($_POST[$name]) );
		} else {
			$data = array_map( 'sanitize_text_field', $_POST[$name] );
		}
	}

	return $data;
}

function sbf_get_validation_error( $name ) {
	if ( ! $contact_form = sbf_get_current_contact_form() ) {
		return '';
	}

	return $contact_form->validation_error( $name );
}

function sbf_get_validation_error_reference( $name ) {
	$contact_form = sbf_get_current_contact_form();

	if ( $contact_form and $contact_form->validation_error( $name ) ) {
		return sprintf(
			'%1$s-ve-%2$s',
			$contact_form->unit_tag(),
			$name
		);
	}
}

function sbf_get_message( $status ) {
	if ( ! $contact_form = sbf_get_current_contact_form() ) {
		return '';
	}

	return $contact_form->message( $status );
}

function sbf_form_controls_class( $type, $default = '' ) {
	$type = trim( $type );
	$default = array_filter( explode( ' ', $default ) );

	$classes = array_merge( array( 'sbf-form-control', 'wpcf7-form-control' ), $default );

	$typebase = rtrim( $type, '*' );
	$required = ( '*' == substr( $type, -1 ) );

	$classes[] = 'sbf-' . $typebase;
	$classes[] = 'wpcf7-' . $typebase;

	if ( $required ) {
		$classes[] = 'sbf-validates-as-required';
	}

	$classes = array_unique( $classes );

	return implode( ' ', $classes );
}

function sbf_contact_form_tag_func( $atts, $content = null, $code = '' ) {
	if ( is_feed() ) {
		return '[salesbox-crm-form]';
	}

	if ( 'salesbox-crm-form' == $code ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
				'title' => '',
				'html_id' => '',
				'html_name' => '',
				'html_class' => '',
				'output' => 'form',
			),
			$atts, 'sbf'
		);

		$id = (int) $atts['id'];
		$title = trim( $atts['title'] );

		if ( ! $contact_form = sbf_contact_form( $id ) ) {
			$contact_form = sbf_get_contact_form_by_title( $title );
		}

	} else {
		if ( is_string( $atts ) ) {
			$atts = explode( ' ', $atts, 2 );
		}

		$id = (int) array_shift( $atts );
		$contact_form = sbf_get_contact_form_by_old_id( $id );
	}

	if ( ! $contact_form ) {
		return sprintf(
			'[salesbox-crm-form 404 "%s"]',
			esc_html( __( 'Not Found', 'salesbox-crm-form' ) )
		);
	}

	return $contact_form->form_html( $atts );
}

function sbf_save_contact_form( $args = '', $context = 'save' ) {
	$args = wp_parse_args( $args, array(
		'id' => -1,
		'title' => null,
		'locale' => null,
		'form' => null,
		'mail' => null,
		'mail_2' => null,
		'messages' => null,
		'additional_settings' => null,
		'salesbox_fields' => null
	) );

	$args = wp_unslash( $args );

	$args['id'] = (int) $args['id'];

	if ( -1 == $args['id'] ) {
		$contact_form = SBF_ContactForm::get_template();
	} else {
		$contact_form = sbf_contact_form( $args['id'] );
	}

	if ( empty( $contact_form ) ) {
		return false;
	}

	if ( null !== $args['title'] ) {
		$contact_form->set_title( $args['title'] );
	}

	if ( null !== $args['locale'] ) {
		$contact_form->set_locale( $args['locale'] );
	}

	$properties = array();

	if ( null !== $args['form'] ) {
		$properties['form'] = sbf_sanitize_form( $args['form'] );
	}

	if ( null !== $args['mail'] ) {
		$properties['mail'] = sbf_sanitize_mail( $args['mail'] );
		$properties['mail']['active'] = true;
	}

	if ( null !== $args['mail_2'] ) {
		$properties['mail_2'] = sbf_sanitize_mail( $args['mail_2'] );
	}

	if ( null !== $args['messages'] ) {
		$properties['messages'] = sbf_sanitize_messages( $args['messages'] );
	}

	if ( null !== $args['additional_settings'] ) {
		$properties['additional_settings'] = sbf_sanitize_additional_settings(
			$args['additional_settings']
		);
	}

	if ( null !== $args['salesbox_fields'] ) {
		$properties['salesbox_config'] = sbf_sanitize_salesbox_config($args['salesbox_fields'], $args['responsible_user_id']);

		$properties['form'] = sbf_get_form_from_salesbox_fields($properties['salesbox_config']['fields']);
	}

	$contact_form->set_properties( $properties );

	do_action( 'sbf_save_contact_form', $contact_form, $args, $context );

	if ( 'save' == $context ) {
		$contact_form->save();
	}

	return $contact_form;
}

function sbf_sanitize_form( $input, $default = '' ) {
	if ( null === $input ) {
		return $default;
	}

	$output = trim( $input );
	return $output;
}

function sbf_sanitize_mail( $input, $defaults = array() ) {
	$input = wp_parse_args( $input, array(
		'active' => false,
		'subject' => '',
		'sender' => '',
		'recipient' => '',
		'body' => '',
		'additional_headers' => '',
		'attachments' => '',
		'use_html' => false,
		'exclude_blank' => false,
	) );

	$input = wp_parse_args( $input, $defaults );

	$output = array();
	$output['active'] = (bool) $input['active'];
	$output['subject'] = trim( $input['subject'] );
	$output['sender'] = trim( $input['sender'] );
	$output['recipient'] = trim( $input['recipient'] );
	$output['body'] = trim( $input['body'] );
	$output['additional_headers'] = '';

	$headers = str_replace( "\r\n", "\n", $input['additional_headers'] );
	$headers = explode( "\n", $headers );

	foreach ( $headers as $header ) {
		$header = trim( $header );

		if ( '' !== $header ) {
			$output['additional_headers'] .= $header . "\n";
		}
	}

	$output['additional_headers'] = trim( $output['additional_headers'] );
	$output['attachments'] = trim( $input['attachments'] );
	$output['use_html'] = (bool) $input['use_html'];
	$output['exclude_blank'] = (bool) $input['exclude_blank'];

	return $output;
}

function sbf_sanitize_messages( $input, $defaults = array() ) {
	$output = array();

	foreach ( sbf_messages() as $key => $val ) {
		if ( isset( $input[$key] ) ) {
			$output[$key] = trim( $input[$key] );
		} elseif ( isset( $defaults[$key] ) ) {
			$output[$key] = $defaults[$key];
		}
	}

	return $output;
}

function sbf_sanitize_additional_settings( $input, $default = '' ) {
	if ( null === $input ) {
		return $default;
	}

	$output = trim( $input );
	return $output;
}

function sbf_sanitize_salesbox_config( $fields, $responsible_user_id ) {
	$output = array();
	$output['fields'] = array();

	$salesbox_config = SBF_ContactFormTemplate::salesbox_config();

	$salesbox_fields = $salesbox_config['fields'];

	$responsible_users = isset($salesbox_config['responsible_users']) ? $salesbox_config['responsible_users'] : [];

	$responsible_user_key = array_search($responsible_user_id, array_column($responsible_users, 'uuid'));
	if ($responsible_user_key > -1) {
		$output['responsible_user'] = $responsible_users[$responsible_user_key];
	} else {
		unset($output['responsible_user']);
	}
	// error_log(print_r($input, true));

	foreach ( $fields as $key => $val ) {
		$field_key = array_search($key, array_column($salesbox_fields, 'name'));
		// error_log(print_r($field_key, true));
		if ($field_key > -1) {
			$field = $salesbox_fields[$field_key];
			$field['selected'] = isset($val['selected']) ? $val['selected'] : false;
			$field['required'] = isset($val['required']) ? $val['required'] : false;
			$field['displayName'] = isset($val['displayName']) ? $val['displayName'] : '';
			array_push($output['fields'], $field);
		}
	}

	// error_log(print_r($output, true));

	return $output;
}

function sbf_get_form_from_salesbox_fields( $fields ) {
	$output = "";

	$submit_button = "";

	foreach ($fields as $field) {
		if ($field['type'] == 'submit') {
			$submit_button_text = isset($field['displayName']) ? $field['displayName'] : 'Submit';
			$submit_button = "[submit \"$submit_button_text\"]";
		}
		if (isset($field['selected']) && $field['selected']) {
			$field_label = (isset($field['displayName']) && $field['displayName']) ? $field['displayName'] : $field['label'];
			$field_name = 'NAME_' . $field['name'];
			$field_type = $field['type'];
			if (isset($field['required']) && $field['required']) {
				$field_type .= "*";
			}

			$field_code = "";

			switch ($field['type']) {
				case 'select': 
					$select_options_string = "";
					foreach ($field["options"] as $select_option) {
						$select_options_string .= ' "' . $select_option["text"] . '"';
					}

					$multiple_indicator = (isset($field['isMultipleChoice']) && $field['isMultipleChoice']) ? 'multiple' : '';

					$field_code = "[$field_type $field_name include_blank $multiple_indicator $select_options_string]";
					break;

				case 'checkbox': 
					$checkbox_options_string = "";
					foreach ($field["options"] as $option) {
						$checkbox_options_string .= ' "' . $option["text"] . '"';
					}

					$exclusive_indicator = (isset($field['isMultipleChoice']) && $field['isMultipleChoice']) ? '' : 'exclusive';

					$field_code = "[$field_type $field_name $exclusive_indicator $checkbox_options_string]";
					break;
					
				default: 
					$field_code = "[$field_type $field_name]";
			}

			$output .= "
				<label> 
					$field_label
					$field_code
				</label>

			";
		}
	}

	$output .= $submit_button;
	error_log($output);
	return $output;
}
