<?php
/*
Plugin Name: Salesbox forms
Plugin URI: https://salesbox.com/
Description: Salesbox CRM lead generation form
Author: Salesbox
Text Domain: salesbox-crm-form
Domain Path: /languages/
Version: 1.1.3
*/

define( 'SBF_VERSION', '1.1.3' );

define( 'SBF_REQUIRED_WP_VERSION', '5.5' );

define( 'SBF_TEXT_DOMAIN', 'salesbox-crm-form' );

define( 'SBF_PLUGIN', __FILE__ );

define( 'SBF_PLUGIN_BASENAME', plugin_basename( SBF_PLUGIN ) );

define( 'SBF_PLUGIN_NAME', trim( dirname( SBF_PLUGIN_BASENAME ), '/' ) );

define( 'SBF_PLUGIN_DIR', untrailingslashit( dirname( SBF_PLUGIN ) ) );

define( 'SBF_PLUGIN_MODULES_DIR', SBF_PLUGIN_DIR . '/modules' );

if ( ! defined( 'SBF_LOAD_JS' ) ) {
	define( 'SBF_LOAD_JS', true );
}

if ( ! defined( 'SBF_LOAD_CSS' ) ) {
	define( 'SBF_LOAD_CSS', true );
}

if ( ! defined( 'SBF_AUTOP' ) ) {
	define( 'SBF_AUTOP', true );
}

if ( ! defined( 'SBF_USE_PIPE' ) ) {
	define( 'SBF_USE_PIPE', true );
}

if ( ! defined( 'SBF_ADMIN_READ_CAPABILITY' ) ) {
	define( 'SBF_ADMIN_READ_CAPABILITY', 'edit_posts' );
}

if ( ! defined( 'SBF_ADMIN_READ_WRITE_CAPABILITY' ) ) {
	define( 'SBF_ADMIN_READ_WRITE_CAPABILITY', 'publish_pages' );
}

if ( ! defined( 'SBF_VERIFY_NONCE' ) ) {
	define( 'SBF_VERIFY_NONCE', false );
}

if ( ! defined( 'SBF_USE_REALLY_SIMPLE_CAPTCHA' ) ) {
	define( 'SBF_USE_REALLY_SIMPLE_CAPTCHA', false );
}

if ( ! defined( 'SBF_VALIDATE_CONFIGURATION' ) ) {
	define( 'SBF_VALIDATE_CONFIGURATION', true );
}

// Deprecated, not used in the plugin core. Use sbf_plugin_url() instead.
define( 'SBF_PLUGIN_URL',
	untrailingslashit( plugins_url( '', SBF_PLUGIN ) )
);

require_once SBF_PLUGIN_DIR . '/load.php';
