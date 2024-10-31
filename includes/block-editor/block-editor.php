<?php

add_action( 'init', 'sbf_init_block_editor_assets', 10, 0 );

function sbf_init_block_editor_assets() {
	$assets = array();

	$asset_file = sbf_plugin_path(
		'includes/block-editor/index.asset.php'
	);

	if ( file_exists( $asset_file ) ) {
		$assets = include( $asset_file );
	}

	$assets = wp_parse_args( $assets, array(
		'src' => sbf_plugin_url( 'includes/block-editor/index.js' ),
		'dependencies' => array(
			'wp-api-fetch',
			'wp-components',
			'wp-compose',
			'wp-blocks',
			'wp-element',
			'wp-i18n',
		),
		'version' => SBF_VERSION,
	) );

	wp_register_script(
		'salesbox-crm-form-block-editor',
		$assets['src'],
		$assets['dependencies'],
		$assets['version']
	);

	wp_set_script_translations(
		'salesbox-crm-form-block-editor',
		'salesbox-crm-form'
	);

	register_block_type(
		'salesbox-crm-form/contact-form-selector',
		array(
			'editor_script' => 'salesbox-crm-form-block-editor',
		)
	);
}
