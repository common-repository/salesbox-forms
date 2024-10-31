<?php

add_filter( 'sbf_spam', 'sbf_disallowed_list', 10, 2 );

function sbf_disallowed_list( $spam, $submission ) {
	if ( $spam ) {
		return $spam;
	}

	$target = sbf_array_flatten( $submission->get_posted_data() );
	$target[] = $submission->get_meta( 'remote_ip' );
	$target[] = $submission->get_meta( 'user_agent' );
	$target = implode( "\n", $target );

	$word = sbf_check_disallowed_list( $target );

	$word = sbf_apply_filters_deprecated(
		'sbf_submission_is_blacklisted',
		array( $word, $submission ),
		'5.3',
		'sbf_submission_has_disallowed_words'
	);

	$word = apply_filters(
		'sbf_submission_has_disallowed_words',
		$word,
		$submission
	);

	if ( $word ) {
		if ( is_bool( $word ) ) {
			$reason = __( "Disallowed words are used.", 'salesbox-crm-form' );
		} else {
			$reason = sprintf(
				__( "Disallowed words (%s) are used.", 'salesbox-crm-form' ),
				implode( ', ', (array) $word )
			);
		}

		$submission->add_spam_log( array(
			'agent' => 'disallowed_list',
			'reason' => $reason,
		) );
	}

	$spam = (bool) $word;

	return $spam;
}

function sbf_check_disallowed_list( $target ) {
	$mod_keys = trim( get_option( 'disallowed_keys' ) );

	if ( '' === $mod_keys ) {
		return false;
	}

	foreach ( explode( "\n", $mod_keys ) as $word ) {
		$word = trim( $word );
		$length = strlen( $word );

		if ( $length < 2 or 256 < $length ) {
			continue;
		}

		$pattern = sprintf( '#%s#i', preg_quote( $word, '#' ) );

		if ( preg_match( $pattern, $target ) ) {
			return $word;
		}
	}

	return false;
}

function sbf_blacklist_check( $target ) {
	sbf_deprecated_function(
		__FUNCTION__,
		'5.3',
		'sbf_check_disallowed_list'
	);

	return sbf_check_disallowed_list( $target );
}
