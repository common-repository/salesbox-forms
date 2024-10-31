<?php

function sbf_current_action() {
	if ( isset( $_REQUEST['action'] ) and -1 != $_REQUEST['action'] ) {
		return sanitize_text_field($_REQUEST['action']);
	}

	if ( isset( $_REQUEST['action2'] ) and -1 != $_REQUEST['action2'] ) {
		return sanitize_text_field($_REQUEST['action2']);
	}

	return false;
}

function sbf_admin_has_edit_cap() {
	return current_user_can( 'sbf_edit_contact_forms' );
}

function sbf_add_tag_generator( $name, $title, $elm_id, $callback, $options = array() ) {
	$tag_generator = SBF_TagGenerator::get_instance();
	return $tag_generator->add( $name, $title, $callback, $options );
}
