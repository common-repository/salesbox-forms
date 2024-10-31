<?php

add_action( 'rest_api_init', 'sbf_rest_api_init', 10, 0 );

function sbf_rest_api_init() {
	$namespace = 'salesbox-crm-form/v1';

	register_rest_route( $namespace,
		'/contact-forms',
		array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'sbf_rest_get_contact_forms',
				'permission_callback' => function() {
					if ( current_user_can( 'sbf_read_contact_forms' ) ) {
						return true;
					} else {
						return new WP_Error( 'sbf_forbidden',
							__( "You are not allowed to access contact forms.", 'salesbox-crm-form' ),
							array( 'status' => 403 )
						);
					}
				},
			),
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => 'sbf_rest_create_contact_form',
				'permission_callback' => function() {
					if ( current_user_can( 'sbf_edit_contact_forms' ) ) {
						return true;
					} else {
						return new WP_Error( 'sbf_forbidden',
							__( "You are not allowed to create a contact form.", 'salesbox-crm-form' ),
							array( 'status' => 403 )
						);
					}
				},
			),
		)
	);

	register_rest_route( $namespace,
		'/contact-forms/(?P<id>\d+)',
		array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'sbf_rest_get_contact_form',
				'permission_callback' => function( WP_REST_Request $request ) {
					$id = (int) $request->get_param( 'id' );

					if ( current_user_can( 'sbf_edit_contact_form', $id ) ) {
						return true;
					} else {
						return new WP_Error( 'sbf_forbidden',
							__( "You are not allowed to access the requested contact form.", 'salesbox-crm-form' ),
							array( 'status' => 403 )
						);
					}
				},
			),
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => 'sbf_rest_update_contact_form',
				'permission_callback' => function( WP_REST_Request $request ) {
					$id = (int) $request->get_param( 'id' );

					if ( current_user_can( 'sbf_edit_contact_form', $id ) ) {
						return true;
					} else {
						return new WP_Error( 'sbf_forbidden',
							__( "You are not allowed to access the requested contact form.", 'salesbox-crm-form' ),
							array( 'status' => 403 )
						);
					}
				},
			),
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'callback' => 'sbf_rest_delete_contact_form',
				'permission_callback' => function( WP_REST_Request $request ) {
					$id = (int) $request->get_param( 'id' );

					if ( current_user_can( 'sbf_delete_contact_form', $id ) ) {
						return true;
					} else {
						return new WP_Error( 'sbf_forbidden',
							__( "You are not allowed to access the requested contact form.", 'salesbox-crm-form' ),
							array( 'status' => 403 )
						);
					}
				},
			),
		)
	);

	register_rest_route( $namespace,
		'/contact-forms/(?P<id>\d+)/feedback',
		array(
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => 'sbf_rest_create_feedback',
				'permission_callback' => '__return_true',
			),
		)
	);

	register_rest_route( $namespace,
		'/contact-forms/(?P<id>\d+)/refill',
		array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'sbf_rest_get_refill',
				'permission_callback' => '__return_true',
			),
		)
	);

	register_rest_route( $namespace,
		'/contact-forms/(?P<id>\d+)/metadata',
		array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => 'sbf_rest_get_metadata',
				'permission_callback' => '__return_true',
			),
		)
	);
}

function sbf_rest_get_contact_forms( WP_REST_Request $request ) {
	$args = array();

	$per_page = $request->get_param( 'per_page' );

	if ( null !== $per_page ) {
		$args['posts_per_page'] = (int) $per_page;
	}

	$offset = $request->get_param( 'offset' );

	if ( null !== $offset ) {
		$args['offset'] = (int) $offset;
	}

	$order = $request->get_param( 'order' );

	if ( null !== $order ) {
		$args['order'] = (string) $order;
	}

	$orderby = $request->get_param( 'orderby' );

	if ( null !== $orderby ) {
		$args['orderby'] = (string) $orderby;
	}

	$search = $request->get_param( 'search' );

	if ( null !== $search ) {
		$args['s'] = (string) $search;
	}

	$items = SBF_ContactForm::find( $args );

	$response = array();

	foreach ( $items as $item ) {
		$response[] = array(
			'id' => $item->id(),
			'slug' => $item->name(),
			'title' => $item->title(),
			'locale' => $item->locale(),
		);
	}

	return rest_ensure_response( $response );
}

function sbf_rest_create_contact_form( WP_REST_Request $request ) {
	$id = (int) $request->get_param( 'id' );

	if ( $id ) {
		return new WP_Error( 'sbf_post_exists',
			__( "Cannot create existing contact form.", 'salesbox-crm-form' ),
			array( 'status' => 400 )
		);
	}

	$args = $request->get_params();
	$args['id'] = -1; // Create
	$context = $request->get_param( 'context' );
	$item = sbf_save_contact_form( $args, $context );

	if ( ! $item ) {
		return new WP_Error( 'sbf_cannot_save',
			__( "There was an error saving the contact form.", 'salesbox-crm-form' ),
			array( 'status' => 500 )
		);
	}

	$response = array(
		'id' => $item->id(),
		'slug' => $item->name(),
		'title' => $item->title(),
		'locale' => $item->locale(),
		'properties' => sbf_get_properties_for_api( $item ),
		'config_errors' => array(),
	);

	if ( sbf_validate_configuration() ) {
		$config_validator = new SBF_ConfigValidator( $item );
		$config_validator->validate();

		$response['config_errors'] = $config_validator->collect_error_messages();

		if ( 'save' == $context ) {
			$config_validator->save();
		}
	}

	return rest_ensure_response( $response );
}

function sbf_rest_get_contact_form( WP_REST_Request $request ) {
	$id = (int) $request->get_param( 'id' );
	$item = sbf_contact_form( $id );

	if ( ! $item ) {
		return new WP_Error( 'sbf_not_found',
			__( "The requested contact form was not found.", 'salesbox-crm-form' ),
			array( 'status' => 404 )
		);
	}

	$response = array(
		'id' => $item->id(),
		'slug' => $item->name(),
		'title' => $item->title(),
		'locale' => $item->locale(),
		'properties' => sbf_get_properties_for_api( $item ),
	);

	return rest_ensure_response( $response );
}

function sbf_rest_update_contact_form( WP_REST_Request $request ) {
	$id = (int) $request->get_param( 'id' );
	$item = sbf_contact_form( $id );

	if ( ! $item ) {
		return new WP_Error( 'sbf_not_found',
			__( "The requested contact form was not found.", 'salesbox-crm-form' ),
			array( 'status' => 404 )
		);
	}

	$args = $request->get_params();
	$context = $request->get_param( 'context' );
	$item = sbf_save_contact_form( $args, $context );

	if ( ! $item ) {
		return new WP_Error( 'sbf_cannot_save',
			__( "There was an error saving the contact form.", 'salesbox-crm-form' ),
			array( 'status' => 500 )
		);
	}

	$response = array(
		'id' => $item->id(),
		'slug' => $item->name(),
		'title' => $item->title(),
		'locale' => $item->locale(),
		'properties' => sbf_get_properties_for_api( $item ),
		'config_errors' => array(),
	);

	if ( sbf_validate_configuration() ) {
		$config_validator = new SBF_ConfigValidator( $item );
		$config_validator->validate();

		$response['config_errors'] = $config_validator->collect_error_messages();

		if ( 'save' == $context ) {
			$config_validator->save();
		}
	}

	return rest_ensure_response( $response );
}

function sbf_rest_delete_contact_form( WP_REST_Request $request ) {
	$id = (int) $request->get_param( 'id' );
	$item = sbf_contact_form( $id );

	if ( ! $item ) {
		return new WP_Error( 'sbf_not_found',
			__( "The requested contact form was not found.", 'salesbox-crm-form' ),
			array( 'status' => 404 )
		);
	}

	$result = $item->delete();

	if ( ! $result ) {
		return new WP_Error( 'sbf_cannot_delete',
			__( "There was an error deleting the contact form.", 'salesbox-crm-form' ),
			array( 'status' => 500 )
		);
	}

	$response = array( 'deleted' => true );

	return rest_ensure_response( $response );
}

function sbf_rest_create_feedback( WP_REST_Request $request ) {
	$url_params = $request->get_url_params();

	$item = null;

	if ( ! empty( $url_params['id'] ) ) {
		$item = sbf_contact_form( $url_params['id'] );
	}

	if ( ! $item ) {
		return new WP_Error( 'sbf_not_found',
			__( "The requested contact form was not found.", 'salesbox-crm-form' ),
			array( 'status' => 404 )
		);
	}

	$result = $item->submit();

	$unit_tag = $request->get_param( '_sbf_unit_tag' );

	$response = array(
		'into' => '#' . sbf_sanitize_unit_tag( $unit_tag ),
		'status' => $result['status'],
		'message' => $result['message'],
		'posted_data_hash' => $result['posted_data_hash'],
	);

	if ( 'validation_failed' == $result['status'] ) {
		$invalid_fields = array();

		foreach ( (array) $result['invalid_fields'] as $name => $field ) {
			$invalid_fields[] = array(
				'into' => 'span.sbf-form-control-wrap.'
					. sanitize_html_class( $name ),
				'message' => $field['reason'],
				'idref' => $field['idref'],
				'error_id' => sprintf(
					'%1$s-ve-%2$s',
					$unit_tag,
					$name
				),
			);
		}

		$response['invalid_fields'] = $invalid_fields;
	}

	$response = sbf_apply_filters_deprecated(
		'sbf_ajax_json_echo',
		array( $response, $result ),
		'5.2',
		'sbf_feedback_response'
	);

	$response = apply_filters( 'sbf_feedback_response', $response, $result );

	return rest_ensure_response( $response );
}

function sbf_rest_get_refill( WP_REST_Request $request ) {
	$id = (int) $request->get_param( 'id' );
	$item = sbf_contact_form( $id );

	if ( ! $item ) {
		return new WP_Error( 'sbf_not_found',
			__( "The requested contact form was not found.", 'salesbox-crm-form' ),
			array( 'status' => 404 )
		);
	}

	$response = sbf_apply_filters_deprecated(
		'sbf_ajax_onload',
		array( array() ),
		'5.2',
		'sbf_refill_response'
	);

	$response = apply_filters( 'sbf_refill_response', array() );

	return rest_ensure_response( $response );
}

function sbf_rest_get_metadata( WP_REST_Request $request ) {
	$id = (int) $request->get_param( 'id' );
	$contact_form = sbf_contact_form( $id );

	if ( ! $contact_form ) {
		return new WP_Error( 'sbf_not_found',
			__( "The requested contact form was not found.", 'salesbox-crm-form' ),
			array( 'status' => 404 )
		);
	}

	$form_properties = $contact_form -> get_properties();

	$salesbox_config = $form_properties['salesbox_config'];
	
	$fields = $salesbox_config['fields'];

	$return_data = array();

	$product_field_key = array_search('PRODUCT', array_column($fields, 'name'));

	if ($product_field_key) {
		$return_data['productList'] = $fields[$product_field_key]['productList'];
	}

	$product_group_field_key = array_search('PRODUCT_GROUP', array_column($fields, 'name'));

	if ($product_group_field_key) {
		$return_data['productGroupList'] = $fields[$product_group_field_key]['options'];
	}

	return rest_ensure_response( $return_data );
}


function sbf_get_properties_for_api( SBF_ContactForm $contact_form ) {
	$properties = $contact_form->get_properties();

	$properties['form'] = array(
		'content' => (string) $properties['form'],
		'fields' => array_map(
			function( SBF_FormTag $form_tag ) {
				return array(
					'type' => $form_tag->type,
					'basetype' => $form_tag->basetype,
					'name' => $form_tag->name,
					'options' => $form_tag->options,
					'raw_values' => $form_tag->raw_values,
					'labels' => $form_tag->labels,
					'values' => $form_tag->values,
					'pipes' => $form_tag->pipes instanceof SBF_Pipes
						? $form_tag->pipes->to_array()
						: $form_tag->pipes,
					'content' => $form_tag->content,
				);
			},
			$contact_form->scan_form_tags()
		),
	);

	$properties['additional_settings'] = array(
		'content' => (string) $properties['additional_settings'],
		'settings' => array_filter( array_map(
			function( $setting ) {
				$pattern = '/^([a-zA-Z0-9_]+)[\t ]*:(.*)$/';

				if ( preg_match( $pattern, $setting, $matches ) ) {
					$name = trim( $matches[1] );
					$value = trim( $matches[2] );

					if ( in_array( $value, array( 'on', 'true' ), true ) ) {
						$value = true;
					} elseif ( in_array( $value, array( 'off', 'false' ), true ) ) {
						$value = false;
					}

					return array( $name, $value );
				}

				return false;
			},
			explode( "\n", $properties['additional_settings'] )
		) ),
	);

	return $properties;
}
