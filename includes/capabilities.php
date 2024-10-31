<?php

add_filter( 'map_meta_cap', 'sbf_map_meta_cap', 10, 4 );

function sbf_map_meta_cap( $caps, $cap, $user_id, $args ) {
	$meta_caps = array(
		'sbf_edit_contact_form' => SBF_ADMIN_READ_WRITE_CAPABILITY,
		'sbf_edit_contact_forms' => SBF_ADMIN_READ_WRITE_CAPABILITY,
		'sbf_read_contact_form' => SBF_ADMIN_READ_CAPABILITY,
		'sbf_read_contact_forms' => SBF_ADMIN_READ_CAPABILITY,
		'sbf_delete_contact_form' => SBF_ADMIN_READ_WRITE_CAPABILITY,
		'sbf_delete_contact_forms' => SBF_ADMIN_READ_WRITE_CAPABILITY,
		'sbf_manage_integration' => 'manage_options',
		'sbf_submit' => 'read',
	);

	$meta_caps = apply_filters( 'sbf_map_meta_cap', $meta_caps );

	$caps = array_diff( $caps, array_keys( $meta_caps ) );

	if ( isset( $meta_caps[$cap] ) ) {
		$caps[] = $meta_caps[$cap];
	}

	return $caps;
}
