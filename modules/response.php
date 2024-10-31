<?php
/**
** A base module for [response]
**/

/* form_tag handler */

add_action( 'sbf_init', 'sbf_add_form_tag_response', 10, 0 );

function sbf_add_form_tag_response() {
	sbf_add_form_tag( 'response',
		'sbf_response_form_tag_handler',
		array(
			'display-block' => true,
		)
	);
}

function sbf_response_form_tag_handler( $tag ) {
	if ( $contact_form = sbf_get_current_contact_form() ) {
		return $contact_form->form_response_output();
	}
}
