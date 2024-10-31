<?php

add_action( 'parse_request', 'sbf_control_init', 20, 0 );

function sbf_control_init() {
	if ( SBF_Submission::is_restful() ) {
		return;
	}

	if ( isset( $_POST['_sbf'] ) ) {
		$contact_form = sbf_contact_form( (int) $_POST['_sbf'] );

		if ( $contact_form ) {
			$contact_form->submit();
		}
	}
}

add_action(
	'wp_enqueue_scripts',
	function () {
		$assets = array();
		$asset_file = sbf_plugin_path( 'includes/js/index.asset.php' );

		if ( file_exists( $asset_file ) ) {
			$assets = include( $asset_file );
		}

		$assets = wp_parse_args( $assets, array(
			'src' => sbf_plugin_url( 'includes/js/index.js' ),
			'dependencies' => array(
				'wp-api-fetch',
				'wp-polyfill',
			),
			'version' => SBF_VERSION,
			'in_footer' => ( 'header' !== sbf_load_js() ),
		) );

		wp_register_script(
			'salesbox-crm-form',
			$assets['src'],
			$assets['dependencies'],
			$assets['version'],
			$assets['in_footer']
		);

		wp_register_script(
			'salesbox-crm-form-html5-fallback',
			sbf_plugin_url( 'includes/js/html5-fallback.js' ),
			array( 'jquery-ui-datepicker' ),
			SBF_VERSION,
			true
		);

		if ( sbf_load_js() ) {
			sbf_enqueue_scripts();
		}

		wp_register_style(
			'salesbox-crm-form',
			sbf_plugin_url( 'includes/css/styles.css' ),
			array(),
			SBF_VERSION,
			'all'
		);

		wp_register_style(
			'salesbox-crm-form-rtl',
			sbf_plugin_url( 'includes/css/styles-rtl.css' ),
			array(),
			SBF_VERSION,
			'all'
		);

		wp_register_style(
			'jquery-ui-smoothness',
			sbf_plugin_url(
				'includes/js/jquery-ui/themes/smoothness/jquery-ui.min.css'
			),
			array(),
			'1.12.1',
			'screen'
		);

		if ( sbf_load_css() ) {
			sbf_enqueue_styles();
		}
	},
	10, 0
);

function sbf_enqueue_scripts() {
	wp_enqueue_script( 'salesbox-crm-form' );

	$sbf = array();

	if ( defined( 'WP_CACHE' ) and WP_CACHE ) {
		$sbf['cached'] = 1;
	}

	wp_localize_script( 'salesbox-crm-form', 'sbf', $sbf );

	do_action( 'sbf_enqueue_scripts' );
}

function sbf_script_is() {
	return wp_script_is( 'salesbox-crm-form' );
}

function sbf_enqueue_styles() {
	wp_enqueue_style( 'salesbox-crm-form' );

	if ( sbf_is_rtl() ) {
		wp_enqueue_style( 'salesbox-crm-form-rtl' );
	}

	do_action( 'sbf_enqueue_styles' );
}

function sbf_style_is() {
	return wp_style_is( 'salesbox-crm-form' );
}

/* HTML5 Fallback */

add_action( 'wp_enqueue_scripts', 'sbf_html5_fallback', 20, 0 );

function sbf_html5_fallback() {
	if ( ! sbf_support_html5_fallback() ) {
		return;
	}

	if ( sbf_script_is() ) {
		wp_enqueue_script( 'salesbox-crm-form-html5-fallback' );
	}

	if ( sbf_style_is() ) {
		wp_enqueue_style( 'jquery-ui-smoothness' );
	}
}
