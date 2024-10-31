<?php

add_action( 'sbf_admin_menu', 'sbf_admin_init_bulk_cv', 10, 0 );

function sbf_admin_init_bulk_cv() {
	if ( ! sbf_validate_configuration()
	or ! current_user_can( 'sbf_edit_contact_forms' ) ) {
		return;
	}

	$result = SBF::get_option( 'bulk_validate' );
	$last_important_update = '5.1.5';

	if ( ! empty( $result['version'] )
	and version_compare( $last_important_update, $result['version'], '<=' ) ) {
		return;
	}

	add_filter( 'sbf_admin_menu_change_notice',
		'sbf_admin_menu_change_notice_bulk_cv', 10, 1 );

	add_action( 'sbf_admin_warnings',
		'sbf_admin_warnings_bulk_cv', 5, 3 );
}

function sbf_admin_menu_change_notice_bulk_cv( $counts ) {
	$counts['sbf'] += 1;
	return $counts;
}

function sbf_admin_warnings_bulk_cv( $page, $action, $object ) {
	if ( 'sbf' === $page and 'validate' === $action ) {
		return;
	}
}

add_action( 'sbf_admin_load', 'sbf_load_bulk_validate_page', 10, 2 );

function sbf_load_bulk_validate_page( $page, $action ) {
	if ( 'sbf' != $page
	or 'validate' != $action
	or ! sbf_validate_configuration()
	or 'POST' != $_SERVER['REQUEST_METHOD'] ) {
		return;
	}

	check_admin_referer( 'sbf-bulk-validate' );

	if ( ! current_user_can( 'sbf_edit_contact_forms' ) ) {
		wp_die( __( "You are not allowed to validate configuration.", 'salesbox-crm-form' ) );
	}

	$contact_forms = SBF_ContactForm::find();

	$result = array(
		'timestamp' => time(),
		'version' => SBF_VERSION,
		'count_valid' => 0,
		'count_invalid' => 0,
	);

	foreach ( $contact_forms as $contact_form ) {
		$config_validator = new SBF_ConfigValidator( $contact_form );
		$config_validator->validate();
		$config_validator->save();

		if ( $config_validator->is_valid() ) {
			$result['count_valid'] += 1;
		} else {
			$result['count_invalid'] += 1;
		}
	}

	SBF::update_option( 'bulk_validate', $result );

	$redirect_to = add_query_arg(
		array(
			'message' => 'validated',
		),
		menu_page_url( 'sbf', false )
	);

	wp_safe_redirect( $redirect_to );
	exit();
}

function sbf_admin_bulk_validate_page() {
	$contact_forms = SBF_ContactForm::find();
	$count = SBF_ContactForm::count();

	$submit_text = sprintf(
		_n(
			/* translators: %s: number of contact forms */
			"Validate %s contact form now",
			"Validate %s contact forms now",
			$count, 'salesbox-crm-form'
		),
		number_format_i18n( $count )
	);

?>

<?php
/*
<div class="wrap">

<h1><?php echo esc_html( __( 'Validate Configuration', 'salesbox-crm-form' ) ); ?></h1>

<form method="post" action="">
	<input type="hidden" name="action" value="validate" />
	<?php wp_nonce_field( 'sbf-bulk-validate' ); ?>
	<p><input type="submit" class="button" value="<?php echo esc_attr( $submit_text ); ?>" /></p>
</form>

<?php
	echo sbf_link(
		__( 'https://salesbox.com/configuration-validator-faq/', 'salesbox-crm-form' ),
		__( 'FAQ about Configuration Validator', 'salesbox-crm-form' )
	);
?>

</div>
*/
?>
<?php
}
