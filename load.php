<?php

require_once SBF_PLUGIN_DIR . '/includes/functions.php';
require_once SBF_PLUGIN_DIR . '/includes/l10n.php';
require_once SBF_PLUGIN_DIR . '/includes/formatting.php';
require_once SBF_PLUGIN_DIR . '/includes/pipe.php';
require_once SBF_PLUGIN_DIR . '/includes/form-tag.php';
require_once SBF_PLUGIN_DIR . '/includes/form-tags-manager.php';
require_once SBF_PLUGIN_DIR . '/includes/shortcodes.php';
require_once SBF_PLUGIN_DIR . '/includes/capabilities.php';
require_once SBF_PLUGIN_DIR . '/includes/contact-form-template.php';
require_once SBF_PLUGIN_DIR . '/includes/contact-form.php';
require_once SBF_PLUGIN_DIR . '/includes/contact-form-functions.php';
require_once SBF_PLUGIN_DIR . '/includes/mail.php';
require_once SBF_PLUGIN_DIR . '/includes/special-mail-tags.php';
require_once SBF_PLUGIN_DIR . '/includes/submission.php';
require_once SBF_PLUGIN_DIR . '/includes/upgrade.php';
require_once SBF_PLUGIN_DIR . '/includes/integration.php';
require_once SBF_PLUGIN_DIR . '/includes/config-validator.php';
require_once SBF_PLUGIN_DIR . '/includes/rest-api.php';
require_once SBF_PLUGIN_DIR . '/includes/block-editor/block-editor.php';

if ( is_admin() ) {
	require_once SBF_PLUGIN_DIR . '/admin/admin.php';
} else {
	require_once SBF_PLUGIN_DIR . '/includes/controller.php';
}

class SBF {

	public static function load_modules() {
		self::load_module( 'acceptance' );
		self::load_module( 'checkbox' );
		self::load_module( 'salesbox-crm' );
		self::load_module( 'count' );
		self::load_module( 'date' );
		self::load_module( 'disallowed-list' );
		self::load_module( 'file' );
		self::load_module( 'hidden' );
		self::load_module( 'listo' );
		self::load_module( 'number' );
		self::load_module( 'quiz' );
		self::load_module( 'response' );
		self::load_module( 'select' );
		self::load_module( 'submit' );
		self::load_module( 'text' );
		self::load_module( 'textarea' );
	}

	protected static function load_module( $mod ) {
		$dir = SBF_PLUGIN_MODULES_DIR;

		if ( empty( $dir ) or ! is_dir( $dir ) ) {
			return false;
		}

		$files = array(
			path_join( $dir, $mod . '/' . $mod . '.php' ),
			path_join( $dir, $mod . '.php' ),
		);

		foreach ( $files as $file ) {
			if ( file_exists( $file ) ) {
				include_once $file;
				return true;
			}
		}

		return false;
	}

	public static function get_option( $name, $default = false ) {
		$option = get_option( 'sbf' );

		if ( false === $option ) {
			return $default;
		}

		if ( isset( $option[$name] ) ) {
			return $option[$name];
		} else {
			return $default;
		}
	}

	public static function update_option( $name, $value ) {
		$option = get_option( 'sbf' );
		$option = ( false === $option ) ? array() : (array) $option;
		$option = array_merge( $option, array( $name => $value ) );
		update_option( 'sbf', $option );
	}
}

add_action( 'plugins_loaded', 'sbf', 10, 0 );

function sbf() {
	SBF::load_modules();

	/* Shortcodes */
	add_shortcode( 'salesbox-crm-form', 'sbf_contact_form_tag_func' );
	add_shortcode( 'contact-form', 'sbf_contact_form_tag_func' );
}

add_action( 'init', 'sbf_init', 10, 0 );

function sbf_init() {
	sbf_get_request_uri();
	sbf_register_post_types();

	do_action( 'sbf_init' );
}

add_action( 'admin_init', 'sbf_upgrade', 10, 0 );

function sbf_upgrade() {
	$old_ver = SBF::get_option( 'version', '0' );
	$new_ver = SBF_VERSION;

	if ( $old_ver == $new_ver ) {
		return;
	}

	do_action( 'sbf_upgrade', $new_ver, $old_ver );

	SBF::update_option( 'version', $new_ver );
}

/* Install and default settings */

add_action( 'activate_' . SBF_PLUGIN_BASENAME, 'sbf_install', 10, 0 );

function sbf_install() {
	if ( $opt = get_option( 'sbf' ) ) {
		sbf_register_salesbox_config();
		return;
	}

	sbf_register_post_types();
	sbf_upgrade();
	sbf_register_salesbox_config();

	if ( get_posts( array( 'post_type' => 'sbf_contact_form' ) ) ) {
		return;
	}

	$contact_form = SBF_ContactForm::get_template(
		array(
			'title' =>
				/* translators: title of your first contact form. %d: number fixed to '1' */
				sprintf( __( 'Salesbox Form %d', 'salesbox-crm-form' ), 1 ),
		)
	);

	$contact_form->save();

	SBF::update_option( 'bulk_validate',
		array(
			'timestamp' => time(),
			'version' => SBF_VERSION,
			'count_valid' => 1,
			'count_invalid' => 0,
		)
	);
}
