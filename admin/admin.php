<?php

require_once SBF_PLUGIN_DIR . '/admin/includes/admin-functions.php';
require_once SBF_PLUGIN_DIR . '/admin/includes/help-tabs.php';
require_once SBF_PLUGIN_DIR . '/admin/includes/tag-generator.php';
require_once SBF_PLUGIN_DIR . '/admin/includes/welcome-panel.php';
require_once SBF_PLUGIN_DIR . '/admin/includes/config-validator.php';
// require_once SBF_PLUGIN_DIR . '/admin/includes/salesbox.php';

// error_log('Test error log');

function print_filters_for( $hook = '' ) {
    global $wp_filter;
    if( empty( $hook ) || !isset( $wp_filter[$hook] ) )
        return;

	
    return print_r( $wp_filter[$hook] );
}


add_action( 'admin_init', 'sbf_admin_init', 10, 0 );

function sbf_admin_init() {
	do_action( 'sbf_admin_init' );
}

add_action( 'admin_menu', 'sbf_admin_menu', 9, 0 );

function sbf_admin_menu() {
	do_action( 'sbf_admin_menu' );

	add_menu_page(
		__( 'Salesbox forms', 'salesbox-crm-form' ),
		__( 'Salesbox forms', 'salesbox-crm-form' )
			. sbf_admin_menu_change_notice(),
		'sbf_read_contact_forms',
		'sbf',
		'sbf_admin_management_page',
		'dashicons-email',
		30
	);

	$edit = add_submenu_page( 'sbf',
		__( 'Edit Contact Form', 'salesbox-crm-form' ),
		__( 'Contact Forms', 'salesbox-crm-form' )
			. sbf_admin_menu_change_notice( 'sbf' ),
		'sbf_read_contact_forms',
		'sbf',
		'sbf_admin_management_page'
	);

	add_action( 'load-' . $edit, 'sbf_load_contact_form_admin', 10, 0 );

	$addnew = add_submenu_page( 'sbf',
		__( 'Add New Contact Form', 'salesbox-crm-form' ),
		__( 'Add New', 'salesbox-crm-form' )
			. sbf_admin_menu_change_notice( 'sbf-new' ),
		'sbf_edit_contact_forms',
		'sbf-new',
		'sbf_admin_add_new_page'
	);

	add_action( 'load-' . $addnew, 'sbf_load_contact_form_admin', 10, 0 );
	
	$integration = SBF_Integration::get_instance();

	if ( $integration->service_exists() ) {
		$integration = add_submenu_page( 'sbf',
			__( 'Connect to Salesbox', 'salesbox-crm-form' ),
			__( 'Connect to Salesbox', 'salesbox-crm-form' )
				. sbf_admin_menu_change_notice( 'sbf-integration' ),
			'sbf_manage_integration',
			'sbf-integration',
			'sbf_admin_integration_page'
		);

		add_action( 'load-' . $integration, 'sbf_load_integration_page', 10, 0 );
	}
}

function sbf_admin_menu_change_notice( $menu_slug = '' ) {
	$counts = apply_filters( 'sbf_admin_menu_change_notice',
		array(
			'sbf' => 0,
			'sbf-new' => 0,
			'sbf-integration' => 0,
		)
	);

	if ( empty( $menu_slug ) ) {
		$count = absint( array_sum( $counts ) );
	} elseif ( isset( $counts[$menu_slug] ) ) {
		$count = absint( $counts[$menu_slug] );
	} else {
		$count = 0;
	}

	return '';
}

add_action( 'admin_enqueue_scripts', 'sbf_admin_enqueue_scripts', 10, 1 );

function sbf_admin_enqueue_scripts( $hook_suffix ) {
	if ( false === strpos( $hook_suffix, 'sbf' ) ) {
		return;
	}

	wp_enqueue_style( 'salesbox-crm-form-admin',
		sbf_plugin_url( 'admin/css/styles.css' ),
		array(), SBF_VERSION, 'all'
	);

	if ( sbf_is_rtl() ) {
		wp_enqueue_style( 'salesbox-crm-form-admin-rtl',
			sbf_plugin_url( 'admin/css/styles-rtl.css' ),
			array(), SBF_VERSION, 'all'
		);
	}

	wp_enqueue_script( 'sbf-admin',
		sbf_plugin_url( 'admin/js/scripts.js' ),
		array( 'jquery', 'jquery-ui-tabs' ),
		SBF_VERSION, true
	);

	$args = array(
		'apiSettings' => array(
			'root' => esc_url_raw( rest_url( 'salesbox-crm-form/v1' ) ),
			'namespace' => 'salesbox-crm-form/v1',
			'nonce' => ( wp_installing() && ! is_multisite() )
				? '' : wp_create_nonce( 'wp_rest' ),
		),
		'pluginUrl' => sbf_plugin_url(),
		'saveAlert' => __(
			"The changes you made will be lost if you navigate away from this page.",
			'salesbox-crm-form' ),
		'activeTab' => isset( $_GET['active-tab'] )
			? (int) $_GET['active-tab'] : 0,
		'configValidator' => array(
			'errors' => array(),
			/*'howToCorrect' => __( "How to resolve?", 'salesbox-crm-form' ),
			'oneError' => __( '1 configuration error detected', 'salesbox-crm-form' ),
			'manyErrors' => __( '%d configuration errors detected', 'salesbox-crm-form' ),
			'oneErrorInTab' => __( '1 configuration error detected in this tab panel', 'salesbox-crm-form' ),
			'manyErrorsInTab' => __( '%d configuration errors detected in this tab panel', 'salesbox-crm-form' ),
			'docUrl' => SBF_ConfigValidator::get_doc_link(),
			
			'iconAlt' => __( '(configuration error)', 'salesbox-crm-form' ),*/
		),

		/* translators: screen reader text */
	);

	if ( $post = sbf_get_current_contact_form()
	and current_user_can( 'sbf_edit_contact_form', $post->id() )
	and sbf_validate_configuration() ) {
		$config_validator = new SBF_ConfigValidator( $post );
		$config_validator->restore();
		$args['configValidator']['errors'] =
			$config_validator->collect_error_messages();
	}

	wp_localize_script( 'sbf-admin', 'sbf', $args );

	add_thickbox();

	wp_enqueue_script( 'sbf-admin-taggenerator',
		sbf_plugin_url( 'admin/js/tag-generator.js' ),
		array( 'jquery', 'thickbox', 'sbf-admin' ), SBF_VERSION, true );
}

add_action( 'doing_dark_mode', 'sbf_dark_mode_support', 10, 1 );

function sbf_dark_mode_support( $user_id ) {
	wp_enqueue_style( 'salesbox-crm-form-admin-dark-mode',
		sbf_plugin_url( 'admin/css/styles-dark-mode.css' ),
		array( 'salesbox-crm-form-admin' ), SBF_VERSION, 'screen' );
}

add_filter( 'set_screen_option_sbf_contact_forms_per_page',
	'sbf_set_screen_options', 10, 3
);

function sbf_set_screen_options( $result, $option, $value ) {
	$sbf_screens = array(
		'sbf_contact_forms_per_page',
	);

	if ( in_array( $option, $sbf_screens ) ) {
		$result = $value;
	}

	return $result;
}

function sbf_load_contact_form_admin() {
	global $plugin_page;

	$action = sbf_current_action();

	do_action( 'sbf_admin_load',
		isset( $_GET['page'] ) ? trim( sanitize_text_field($_GET['page']) ) : '',
		$action
	);

	if (isset($_POST['update_custom_fields'])) {
		sbf_register_salesbox_config();
	} else if ( 'save' == $action ) {
		$id = isset( $_POST['post_ID'] ) ? sanitize_text_field($_POST['post_ID']) : '-1';
		check_admin_referer( 'sbf-save-contact-form_' . $id );

		if ( ! current_user_can( 'sbf_edit_contact_form', $id ) ) {
			wp_die( __( 'You are not allowed to edit this item.', 'salesbox-crm-form' ) );
		}

		$args = array();
		$args['id'] = $id;

		$args['title'] = isset( $_POST['post_title'] )
			? sanitize_text_field($_POST['post_title']) : null;

		$args['locale'] = isset( $_POST['sbf-locale'] )
			? sanitize_text_field($_POST['sbf-locale']) : null;

		$args['form'] = '';

		$args['mail'] = array();

		$args['mail_2'] = array();

		$args['additional_settings'] = '';

		$args['messages'] = isset( $_POST['sbf-messages'] )
			? array_map('sanitize_text_field', $_POST['sbf-messages']) : array();

		$args['responsible_user_id'] = isset( $_POST['sbf-responsible-user-id'] )
			? sanitize_text_field($_POST['sbf-responsible-user-id']) : '';
		
			// sanitizing
		if (isset( $_POST['sbf-salesbox-fields'] )) {

			$salesbox_fields = array_map(function ($item) {
				return array_map('sanitize_text_field', $item);
			}, $_POST['sbf-salesbox-fields']);

			$args['salesbox_fields'] = $salesbox_fields;

		} else {
			$args['salesbox_fields'] = '';
		}

		// error_log(print_r($args, true));

		$contact_form = sbf_save_contact_form( $args );

		if ( $contact_form and sbf_validate_configuration() ) {
			$config_validator = new SBF_ConfigValidator( $contact_form );
			$config_validator->validate();
			$config_validator->save();
		}

		$query = array(
			'post' => $contact_form ? $contact_form->id() : 0,
			'active-tab' => isset( $_POST['active-tab'] )
				? (int) $_POST['active-tab'] : 0,
		);

		if ( ! $contact_form ) {
			$query['message'] = 'failed';
		} elseif ( -1 == $id ) {
			$query['message'] = 'created';
		} else {
			$query['message'] = 'saved';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'sbf', false ) );
		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'copy' == $action ) {
		$id = empty( $_POST['post_ID'] )
			? absint( $_REQUEST['post'] )
			: absint( $_POST['post_ID'] );

		check_admin_referer( 'sbf-copy-contact-form_' . $id );

		if ( ! current_user_can( 'sbf_edit_contact_form', $id ) ) {
			wp_die( __( 'You are not allowed to edit this item.', 'salesbox-crm-form' ) );
		}

		$query = array();

		if ( $contact_form = sbf_contact_form( $id ) ) {
			$new_contact_form = $contact_form->copy();
			$new_contact_form->save();

			$query['post'] = $new_contact_form->id();
			$query['message'] = 'created';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'sbf', false ) );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'delete' == $action ) {
		if ( ! empty( $_POST['post_ID'] ) ) {
			check_admin_referer( 'sbf-delete-contact-form_' . sanitize_text_field($_POST['post_ID']) );
		} elseif ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer( 'sbf-delete-contact-form_' . sanitize_text_field($_REQUEST['post']) );
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$posts = empty( $_POST['post_ID'] )
			? (array) $_REQUEST['post']
			: (array) $_POST['post_ID'];

		$posts = array_map('sanitize_post', $posts);

		$deleted = 0;

		foreach ( $posts as $post ) {
			$post = SBF_ContactForm::get_instance( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if ( ! current_user_can( 'sbf_delete_contact_form', $post->id() ) ) {
				wp_die( __( 'You are not allowed to delete this item.', 'salesbox-crm-form' ) );
			}

			if ( ! $post->delete() ) {
				wp_die( __( 'Error in deleting.', 'salesbox-crm-form' ) );
			}

			$deleted += 1;
		}

		$query = array();

		if ( ! empty( $deleted ) ) {
			$query['message'] = 'deleted';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'sbf', false ) );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	$post = null;

	if ( 'sbf-new' == $plugin_page ) {
		$post = SBF_ContactForm::get_template( array(
			'locale' => isset( $_GET['locale'] ) ? sanitize_text_field($_GET['locale']) : null,
		) );
	} elseif ( ! empty( $_GET['post'] ) ) {
		$get_post = gettype($_GET['post']) == 'string' ? sanitize_text_field($_GET['post']) : sanitize_post($_GET['post']);
		$post = SBF_ContactForm::get_instance( $get_post );
	}

	$current_screen = get_current_screen();

	$help_tabs = new SBF_Help_Tabs( $current_screen );

	if ( $post
	and current_user_can( 'sbf_edit_contact_form', $post->id() ) ) {
		$help_tabs->set_help_tabs( 'edit' );
	} else {
		$help_tabs->set_help_tabs( 'list' );

		if ( ! class_exists( 'SBF_Contact_Form_List_Table' ) ) {
			require_once SBF_PLUGIN_DIR . '/admin/includes/class-contact-forms-list-table.php';
		}

		add_filter( 'manage_' . $current_screen->id . '_columns',
			array( 'SBF_Contact_Form_List_Table', 'define_columns' ), 10, 0 );

		add_screen_option( 'per_page', array(
			'default' => 20,
			'option' => 'sbf_contact_forms_per_page',
		) );
	}
}

function sbf_admin_management_page() {
	if ( $post = sbf_get_current_contact_form() ) {
		$post_id = $post->initial() ? -1 : $post->id();

		require_once SBF_PLUGIN_DIR . '/admin/includes/editor.php';
		require_once SBF_PLUGIN_DIR . '/admin/edit-contact-form.php';
		return;
	}

	if ( 'validate' == sbf_current_action()
	and sbf_validate_configuration()
	and current_user_can( 'sbf_edit_contact_forms' ) ) {
		sbf_admin_bulk_validate_page();
		return;
	}

	$list_table = new SBF_Contact_Form_List_Table();
	$list_table->prepare_items();

?>
<div class="wrap" id="sbf-contact-form-list-table">

<h1 class="wp-heading-inline"><?php
	echo esc_html( __( 'Contact Forms', 'salesbox-crm-form' ) );
?></h1>

<?php
	if ( current_user_can( 'sbf_edit_contact_forms' ) ) {
		echo sbf_link(
			menu_page_url( 'sbf-new', false ),
			__( 'Add New', 'salesbox-crm-form' ),
			array( 'class' => 'page-title-action' )
		);
	}

	if ( ! empty( $_REQUEST['s'] ) ) {
		echo sprintf( '<span class="subtitle">'
			/* translators: %s: search keywords */
			. __( 'Search results for &#8220;%s&#8221;', 'salesbox-crm-form' )
			. '</span>', esc_html( sanitize_text_field($_REQUEST['s']) )
		);
	}
?>

<hr class="wp-header-end">

<?php
	do_action( 'sbf_admin_warnings',
		'sbf', sbf_current_action(), null );

	sbf_welcome_panel();

	do_action( 'sbf_admin_notices',
		'sbf', sbf_current_action(), null );
?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( sanitize_text_field($_REQUEST['page']) ); ?>" />
	<?php $list_table->search_box( __( 'Search Contact Forms', 'salesbox-crm-form' ), 'sbf-contact' ); ?>
	<?php $list_table->display(); ?>
</form>

</div>
<?php
}

function sbf_admin_add_new_page() {
	$post = sbf_get_current_contact_form();

	if ( ! $post ) {
		$post = SBF_ContactForm::get_template();
	}

	$post_id = -1;

	require_once SBF_PLUGIN_DIR . '/admin/includes/editor.php';
	require_once SBF_PLUGIN_DIR . '/admin/edit-contact-form.php';
}

function sbf_load_integration_page() {
	$current_action = sbf_current_action();
	do_action( 'sbf_admin_load',
		isset( $_GET['page'] ) ? trim( sanitize_text_field($_GET['page']) ) : '',
		sbf_current_action()
	);

	$integration = SBF_Integration::get_instance();

	$request_service = 'salesbox_crm';

	$current_action = 'setup';
	if ( isset( $request_service )
	and $integration->service_exists( $request_service ) ) {
		$service = $integration->get_service( $request_service );
		$service->load( $current_action );
	}

	$help_tabs = new SBF_Help_Tabs( get_current_screen() );
	$help_tabs->set_help_tabs( 'integration' );
}

function sbf_admin_integration_page() {
	$integration = SBF_Integration::get_instance();

	$request_service = isset( $_REQUEST['service'] ) 
		? sanitize_text_field($_REQUEST['service'])
		: '';

	$service = isset( $request_service )
		? $integration->get_service( $request_service )
		: null;

?>
<div class="wrap" id="sbf-integration">

<h1><?php echo esc_html( __( 'Connect to Salesbox', 'salesbox-crm-form' ) ); ?></h1>

<?php
	do_action( 'sbf_admin_warnings',
		'sbf-integration', sbf_current_action(), $service );

	do_action( 'sbf_admin_notices',
		'sbf-integration', sbf_current_action(), $service );

	if ( $service ) {
		$message = isset( $_REQUEST['message'] ) ? sanitize_text_field($_REQUEST['message']) : '';
		$service->admin_notice( $message );
		$integration->list_services( array( 'include' => $request_service ) );
	} else {
		$integration->list_services();
	}
?>

</div>
<?php
}

/* Misc */

add_action( 'sbf_admin_notices', 'sbf_admin_updated_message', 10, 3 );

function sbf_admin_updated_message( $page, $action, $object ) {
	if ( ! in_array( $page, array( 'sbf', 'sbf-new' ) ) ) {
		return;
	}

	if ( empty( $_REQUEST['message'] ) ) {
		return;
	}

	$request_message = sanitize_text_field($_REQUEST['message']);

	if ( 'created' == $request_message ) {
		$updated_message = __( "Contact form created.", 'salesbox-crm-form' );
	} elseif ( 'saved' == $request_message ) {
		$updated_message = __( "Contact form saved.", 'salesbox-crm-form' );
	} elseif ( 'deleted' == $request_message ) {
		$updated_message = __( "Contact form deleted.", 'salesbox-crm-form' );
	}

	if ( ! empty( $updated_message ) ) {
		echo sprintf( '<div id="message" class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $updated_message ) );
		return;
	}

	if ( 'failed' == $request_message ) {
		$updated_message = __( "There was an error saving the contact form.",
			'salesbox-crm-form' );

		echo sprintf( '<div id="message" class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $updated_message ) );
		return;
	}

	if ( 'validated' == $request_message ) {
		$bulk_validate = SBF::get_option( 'bulk_validate', array() );
		$count_invalid = isset( $bulk_validate['count_invalid'] )
			? absint( $bulk_validate['count_invalid'] ) : 0;

		if ( $count_invalid ) {
			$updated_message = sprintf(
				_n(
					/* translators: %s: number of contact forms */
					"Configuration validation completed. %s invalid contact form was found.",
					"Configuration validation completed. %s invalid contact forms were found.",
					$count_invalid, 'salesbox-crm-form'
				),
				number_format_i18n( $count_invalid )
			);

			echo sprintf( '<div id="message" class="notice notice-warning is-dismissible"><p>%s</p></div>', esc_html( $updated_message ) );
		} else {
			$updated_message = __( "Configuration validation completed. No invalid contact form was found.", 'salesbox-crm-form' );

			echo sprintf( '<div id="message" class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $updated_message ) );
		}

		return;
	}
}

add_filter( 'plugin_action_links', 'sbf_plugin_action_links', 10, 2 );

function sbf_plugin_action_links( $links, $file ) {
	if ( $file != SBF_PLUGIN_BASENAME ) {
		return $links;
	}

	if ( ! current_user_can( 'sbf_read_contact_forms' ) ) {
		return $links;
	}

	$settings_link = sbf_link(
		menu_page_url( 'sbf', false ),
		__( 'Settings', 'salesbox-crm-form' )
	);

	array_unshift( $links, $settings_link );

	return $links;
}

add_action( 'sbf_admin_warnings', 'sbf_old_wp_version_error', 10, 3 );

function sbf_old_wp_version_error( $page, $action, $object ) {
	$wp_version = get_bloginfo( 'version' );

	if ( ! version_compare( $wp_version, SBF_REQUIRED_WP_VERSION, '<' ) ) {
		return;
	}

?>
<div class="notice notice-warning">
<p><?php
	echo sprintf(
		/* translators: 1: version of Salesbox forms, 2: version of WordPress, 3: URL */
		__( '<strong>Salesbox forms %1$s requires WordPress %2$s or higher.</strong> Please <a href="%3$s">update WordPress</a> first.', 'salesbox-crm-form' ),
		SBF_VERSION,
		SBF_REQUIRED_WP_VERSION,
		admin_url( 'update-core.php' )
	);
?></p>
</div>
<?php
}

add_action( 'sbf_admin_warnings', 'sbf_not_allowed_to_edit', 10, 3 );

function sbf_not_allowed_to_edit( $page, $action, $object ) {
	if ( $object instanceof SBF_ContactForm ) {
		$contact_form = $object;
	} else {
		return;
	}

	if ( current_user_can( 'sbf_edit_contact_form', $contact_form->id() ) ) {
		return;
	}

	$message = __( "You are not allowed to edit this contact form.",
		'salesbox-crm-form' );

	echo sprintf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html( $message ) );
}

// error_log(print_filters_for( 'sbf_before_send_mail' ));
