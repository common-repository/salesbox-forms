<?php

class SBF_ContactFormTemplate {

	public static function get_default( $prop = 'form' ) {
		if ( 'form' == $prop ) {
			$template = self::form();
		} elseif ( 'mail' == $prop ) {
			$template = self::mail();
		} elseif ( 'mail_2' == $prop ) {
			$template = self::mail_2();
		} elseif ( 'messages' == $prop ) {
			$template = self::messages();
		} elseif ( 'salesbox_fields' == $prop ) {
			$template = self::salesbox_fields();
		} elseif ( 'salesbox_config' == $prop ) {
			$template = self::salesbox_config();
		} else {
			$template = null;
		}

		return apply_filters( 'sbf_default_template', $template, $prop );
	}

	public static function form() {
		$template = sprintf(
			'
<label> %2$s
    [text* your-name] </label>

<label> %3$s
    [email* your-email] </label>

<label> %4$s
    [text* your-subject] </label>

<label> %5$s %1$s
    [textarea your-message] </label>

[submit "%6$s"]',
			__( '(optional)', 'salesbox-crm-form' ),
			__( 'Your name', 'salesbox-crm-form' ),
			__( 'Your email', 'salesbox-crm-form' ),
			__( 'Subject', 'salesbox-crm-form' ),
			__( 'Your message', 'salesbox-crm-form' ),
			__( 'Submit', 'salesbox-crm-form' )
		);

		return trim( $template );
	}

	public static function mail() {
		$template = array(
			'subject' => sprintf(
				/* translators: 1: blog name, 2: [your-subject] */
				_x( '%1$s "%2$s"', 'mail subject', 'salesbox-crm-form' ),
				'[_site_title]',
				'[your-subject]'
			),
			'sender' => sprintf(
				'%s <%s>',
				'[_site_title]',
				self::from_email()
			),
			'body' =>
				sprintf(
					/* translators: %s: [your-name] <[your-email]> */
					__( 'From: %s', 'salesbox-crm-form' ),
					'[prospectName] <[prospectEmail]>'
				) . "\n"
				. sprintf(
					/* translators: %s: [your-subject] */
					__( 'Subject: %s', 'salesbox-crm-form' ),
					'New prospect'
				) . "\n\n"
				. __( 'Message Body:', 'salesbox-crm-form' )
				. "\n" . '[message]' . "\n\n"
				. '-- ' . "\n"
				. sprintf(
					/* translators: 1: blog name, 2: blog URL */
					__( 'This e-mail was sent from a contact form on %1$s (%2$s)', 'salesbox-crm-form' ),
					'[_site_title]',
					'[_site_url]'
				),
			'recipient' => '[_site_admin_email]',
			'additional_headers' => 'Reply-To: [prospectEmail]',
			'attachments' => '',
			'use_html' => 0,
			'exclude_blank' => 0,
		);

		return $template;
	}

	public static function mail_2() {
		$template = array(
			'active' => false,
			'subject' => sprintf(
				/* translators: 1: blog name, 2: [your-subject] */
				_x( '%1$s "%2$s"', 'mail subject', 'salesbox-crm-form' ),
				'[_site_title]',
				'[your-subject]'
			),
			'sender' => sprintf(
				'%s <%s>',
				'[_site_title]',
				self::from_email()
			),
			'body' =>
				__( 'Message Body:', 'salesbox-crm-form' )
				. "\n" . '[your-message]' . "\n\n"
				. '-- ' . "\n"
				. sprintf(
					/* translators: 1: blog name, 2: blog URL */
					__( 'This e-mail was sent from a contact form on %1$s (%2$s)', 'salesbox-crm-form' ),
					'[_site_title]',
					'[_site_url]'
				),
			'recipient' => '[prospectEmail]',
			'additional_headers' => sprintf(
				'Reply-To: %s',
				'[_site_admin_email]'
			),
			'attachments' => '',
			'use_html' => 0,
			'exclude_blank' => 0,
		);

		return $template;
	}

	public static function from_email() {
		$admin_email = get_option( 'admin_email' );
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );

		if ( sbf_is_localhost() ) {
			return $admin_email;
		}

		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		if ( strpbrk( $admin_email, '@' ) == '@' . $sitename ) {
			return $admin_email;
		}

		return 'wordpress@' . $sitename;
	}

	public static function messages() {
		$messages = array();

		foreach ( sbf_messages() as $key => $arr ) {
			$messages[$key] = $arr['default'];
		}

		return $messages;
	}

	public static function salesbox_fields($isAll = false) {
		$salesbox_fields = array();
		$salesbox_config = SBF::get_option( 'salesbox_config' );
		if ($salesbox_config) {
			if ($isAll) {
				$salesbox_fields = $salesbox_config['fields'];
			} else {
				$salesbox_fields = array_filter($salesbox_config['fields'], function ($var) {
					return isset($var["defaultSelected"]) && $var["defaultSelected"];
				});
			}
		}

		return $salesbox_fields;
	}

	public static function salesbox_config() {
		$salesbox_config = SBF::get_option( 'salesbox_config' );
		// error_log(print_r($salesbox_config, true));

		return $salesbox_config;
	}
}

function sbf_messages() {
	$messages = array(
		'mail_sent_ok' => array(
			'description'
				=> __( "Sender's message was sent successfully", 'salesbox-crm-form' ),
			'default'
				=> __( "Thank you for your message. It has been sent.", 'salesbox-crm-form' ),
		),

		'mail_sent_ng' => array(
			'description'
				=> __( "Sender's message failed to send", 'salesbox-crm-form' ),
			'default'
				=> __( "There was an error trying to send your message. Please try again later.", 'salesbox-crm-form' ),
		),

		'validation_error' => array(
			'description'
				=> __( "Validation errors occurred", 'salesbox-crm-form' ),
			'default'
				=> __( "One or more fields have an error. Please check and try again.", 'salesbox-crm-form' ),
		),

		'contact_company_error' => array(
			'description'
				=> __( "Contact company errors occured", 'salesbox-crm-form' ),
			'default'
				=> __( "You need to add Company Name or both Contact FirstName and Contact LastName.", 'salesbox-crm-form' ),
		),

		'spam' => array(
			'description'
				=> __( "Submission was referred to as spam", 'salesbox-crm-form' ),
			'default'
				=> __( "There was an error trying to send your message. Please try again later.", 'salesbox-crm-form' ),
		),

		'accept_terms' => array(
			'description'
				=> __( "There are terms that the sender must accept", 'salesbox-crm-form' ),
			'default'
				=> __( "You must accept the terms and conditions before sending your message.", 'salesbox-crm-form' ),
		),

		'invalid_required' => array(
			'description'
				=> __( "There is a field that the sender must fill in", 'salesbox-crm-form' ),
			'default'
				=> __( "The field is required.", 'salesbox-crm-form' ),
		),

		'invalid_too_long' => array(
			'description'
				=> __( "There is a field with input that is longer than the maximum allowed length", 'salesbox-crm-form' ),
			'default'
				=> __( "The field is too long.", 'salesbox-crm-form' ),
		),

		'invalid_too_short' => array(
			'description'
				=> __( "There is a field with input that is shorter than the minimum allowed length", 'salesbox-crm-form' ),
			'default'
				=> __( "The field is too short.", 'salesbox-crm-form' ),
		)
	);

	return apply_filters( 'sbf_messages', $messages );
}
