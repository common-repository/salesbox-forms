<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class SBF_Contact_Form_List_Table extends WP_List_Table {

	public static function define_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __( 'Title', 'salesbox-crm-form' ),
			'shortcode' => __( 'Shortcode', 'salesbox-crm-form' ),
			'author' => __( 'Author', 'salesbox-crm-form' ),
			'date' => __( 'Date', 'salesbox-crm-form' ),
		);

		return $columns;
	}

	public function __construct() {
		parent::__construct( array(
			'singular' => 'post',
			'plural' => 'posts',
			'ajax' => false,
		) );
	}

	public function prepare_items() {
		$current_screen = get_current_screen();
		$per_page = $this->get_items_per_page( 'sbf_contact_forms_per_page' );

		$args = array(
			'posts_per_page' => $per_page,
			'orderby' => 'title',
			'order' => 'ASC',
			'offset' => ( $this->get_pagenum() - 1 ) * $per_page,
		);

		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = sanitize_text_field($_REQUEST['s']);
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$order_by = sanitize_text_field($_REQUEST['orderby']);
			if ( 'title' ==  $order_by) {
				$args['orderby'] = 'title';
			} elseif ( 'author' == $order_by ) {
				$args['orderby'] = 'author';
			} elseif ( 'date' == $order_by ) {
				$args['orderby'] = 'date';
			}
		}

		if ( ! empty( $_REQUEST['order'] ) ) {
			$request_order = sanitize_text_field ($_REQUEST['order']);
			if ( 'asc' == strtolower( $request_order ) ) {
				$args['order'] = 'ASC';
			} elseif ( 'desc' == strtolower( $request_order ) ) {
				$args['order'] = 'DESC';
			}
		}

		$this->items = SBF_ContactForm::find( $args );

		$total_items = SBF_ContactForm::count();
		$total_pages = ceil( $total_items / $per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page,
		) );
	}

	public function get_columns() {
		return get_column_headers( get_current_screen() );
	}

	protected function get_sortable_columns() {
		$columns = array(
			'title' => array( 'title', true ),
			'author' => array( 'author', false ),
			'date' => array( 'date', false ),
		);

		return $columns;
	}

	protected function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'salesbox-crm-form' ),
		);

		return $actions;
	}

	protected function column_default( $item, $column_name ) {
		return '';
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],
			$item->id()
		);
	}

	public function column_title( $item ) {
		$edit_link = add_query_arg(
			array(
				'post' => absint( $item->id() ),
				'action' => 'edit',
			),
			menu_page_url( 'sbf', false )
		);

		$output = sprintf(
			'<a class="row-title" href="%1$s" aria-label="%2$s">%3$s</a>',
			esc_url( $edit_link ),
			esc_attr( sprintf(
				/* translators: %s: title of contact form */
				__( 'Edit &#8220;%s&#8221;', 'salesbox-crm-form' ),
				$item->title()
			) ),
			esc_html( $item->title() )
		);

		$output = sprintf( '<strong>%s</strong>', $output );

		if ( sbf_validate_configuration()
		and current_user_can( 'sbf_edit_contact_form', $item->id() ) ) {
			$config_validator = new SBF_ConfigValidator( $item );
			$config_validator->restore();

			// AUTO SKIP CONFIGURATION ERRORS
			if ( false && ($count_errors = $config_validator->count_errors() )) {
				$error_notice = sprintf(
					_n(
						/* translators: %s: number of errors detected */
						'%s configuration error detected',
						'%s configuration errors detected',
						$count_errors, 'salesbox-crm-form' ),
					number_format_i18n( $count_errors )
				);

				$output .= sprintf(
					'<div class="config-error"><span class="icon-in-circle" aria-hidden="true">!</span> %s</div>',
					$error_notice
				);
			}
		}

		return $output;
	}

	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $column_name !== $primary ) {
			return '';
		}

		$edit_link = add_query_arg(
			array(
				'post' => absint( $item->id() ),
				'action' => 'edit',
			),
			menu_page_url( 'sbf', false )
		);

		$actions = array(
			'edit' => sbf_link( $edit_link, __( 'Edit', 'salesbox-crm-form' ) ),
		);

		if ( current_user_can( 'sbf_edit_contact_form', $item->id() ) ) {
			$copy_link = add_query_arg(
				array(
					'post' => absint( $item->id() ),
					'action' => 'copy',
				),
				menu_page_url( 'sbf', false )
			);

			$copy_link = wp_nonce_url(
				$copy_link,
				'sbf-copy-contact-form_' . absint( $item->id() )
			);

			$actions = array_merge( $actions, array(
				'copy' => sbf_link( $copy_link, __( 'Duplicate', 'salesbox-crm-form' ) ),
			) );
		}

		return $this->row_actions( $actions );
	}

	public function column_author( $item ) {
		$post = get_post( $item->id() );

		if ( ! $post ) {
			return;
		}

		$author = get_userdata( $post->post_author );

		if ( false === $author ) {
			return;
		}

		return esc_html( $author->display_name );
	}

	public function column_shortcode( $item ) {
		$shortcodes = array( $item->shortcode() );

		$output = '';

		foreach ( $shortcodes as $shortcode ) {
			$output .= "\n" . '<span class="shortcode"><input type="text"'
				. ' onfocus="this.select();" readonly="readonly"'
				. ' value="' . esc_attr( $shortcode ) . '"'
				. ' class="large-text code" /></span>';
		}

		return trim( $output );
	}

	public function column_date( $item ) {
		$datetime = get_post_datetime( $item->id() );

		if ( false === $datetime ) {
			return '';
		}

		$t_time = sprintf(
			/* translators: 1: date, 2: time */
			__( '%1$s at %2$s', 'salesbox-crm-form' ),
			/* translators: date format, see https://www.php.net/date */
			$datetime->format( __( 'Y/m/d', 'salesbox-crm-form' ) ),
			/* translators: time format, see https://www.php.net/date */
			$datetime->format( __( 'g:i a', 'salesbox-crm-form' ) )
		);

		return $t_time;
	}
}
