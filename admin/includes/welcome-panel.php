<?php

function sbf_welcome_panel() {
	$classes = 'welcome-panel';

	$vers = (array) get_user_meta( get_current_user_id(),
		'sbf_hide_welcome_panel_on', true );

	if ( sbf_version_grep( sbf_version( 'only_major=1' ), $vers ) ) {
		$classes .= ' hidden';
	}

?>
<?php
}

add_action( 'wp_ajax_sbf-update-welcome-panel',
	'sbf_admin_ajax_welcome_panel', 10, 0 );

function sbf_admin_ajax_welcome_panel() {
	check_ajax_referer( 'sbf-welcome-panel-nonce', 'welcomepanelnonce' );

	$vers = get_user_meta( get_current_user_id(),
		'sbf_hide_welcome_panel_on', true );

	if ( empty( $vers ) or ! is_array( $vers ) ) {
		$vers = array();
	}

	if ( empty( $_POST['visible'] ) ) {
		$vers[] = sbf_version( 'only_major=1' );
	}

	$vers = array_unique( $vers );

	update_user_meta( get_current_user_id(),
		'sbf_hide_welcome_panel_on', $vers );

	wp_die( 1 );
}
