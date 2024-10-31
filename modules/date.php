<?php
/**
** A base module for the following types of tags:
** 	[date] and [date*]		# Date
**/

/* form_tag handler */

add_action( 'sbf_init', 'sbf_add_form_tag_date', 10, 0 );

function sbf_add_form_tag_date() {
	sbf_add_form_tag( array( 'date', 'date*' ),
		'sbf_date_form_tag_handler',
		array(
			'name-attr' => true,
		)
	);
}

function sbf_date_form_tag_handler( $tag ) {
	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = sbf_get_validation_error( $tag->name );

	$class = sbf_form_controls_class( $tag->type );

	$class .= ' sbf-validates-as-date';

	if ( $validation_error ) {
		$class .= ' sbf-not-valid';
	}

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );
	$atts['min'] = $tag->get_date_option( 'min' );
	$atts['max'] = $tag->get_date_option( 'max' );
	$atts['step'] = $tag->get_option( 'step', 'int', true );

	if ( $tag->has_option( 'readonly' ) ) {
		$atts['readonly'] = 'readonly';
	}

	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}

	if ( $validation_error ) {
		$atts['aria-invalid'] = 'true';
		$atts['aria-describedby'] = sbf_get_validation_error_reference(
			$tag->name
		);
	} else {
		$atts['aria-invalid'] = 'false';
	}

	$value = (string) reset( $tag->values );

	if ( $tag->has_option( 'placeholder' )
	or $tag->has_option( 'watermark' ) ) {
		$atts['placeholder'] = $value;
		$value = '';
	}

	$value = $tag->get_default_option( $value );

	if ( $value ) {
		$datetime_obj = date_create_immutable(
			preg_replace( '/[_]+/', ' ', $value ),
			wp_timezone()
		);

		if ( $datetime_obj ) {
			$value = $datetime_obj->format( 'Y-m-d' );
		}
	}

	$value = sbf_get_hangover( $tag->name, $value );

	$atts['value'] = $value;

	if ( sbf_support_html5() ) {
		$atts['type'] = $tag->basetype;
	} else {
		$atts['type'] = 'text';
	}

	$atts['name'] = $tag->name;

	$atts = sbf_format_atts( $atts );

	$html = sprintf(
		'<span class="sbf-form-control-wrap %1$s"><input %2$s />%3$s</span>',
		sanitize_html_class( $tag->name ), $atts, $validation_error
	);

	return $html;
}


/* Validation filter */

add_filter( 'sbf_validate_date', 'sbf_date_validation_filter', 10, 2 );
add_filter( 'sbf_validate_date*', 'sbf_date_validation_filter', 10, 2 );

function sbf_date_validation_filter( $result, $tag ) {
	$name = $tag->name;

	$min = $tag->get_date_option( 'min' );
	$max = $tag->get_date_option( 'max' );

	$value = isset( $_POST[$name] )
		? trim( strtr( (string) sanitize_text_field($_POST[$name]), "\n", " " ) )
		: '';

	if ( $tag->is_required() and '' === $value ) {
		$result->invalidate( $tag, sbf_get_message( 'invalid_required' ) );
	} elseif ( '' !== $value and ! sbf_is_date( $value ) ) {
		$result->invalidate( $tag, sbf_get_message( 'invalid_date' ) );
	} elseif ( '' !== $value and ! empty( $min ) and $value < $min ) {
		$result->invalidate( $tag, sbf_get_message( 'date_too_early' ) );
	} elseif ( '' !== $value and ! empty( $max ) and $max < $value ) {
		$result->invalidate( $tag, sbf_get_message( 'date_too_late' ) );
	}

	return $result;
}


/* Messages */

add_filter( 'sbf_messages', 'sbf_date_messages', 10, 1 );

function sbf_date_messages( $messages ) {
	return array_merge( $messages, array(
		'invalid_date' => array(
			'description' => __( "Date format that the sender entered is invalid", 'salesbox-crm-form' ),
			'default' => __( "The date format is incorrect.", 'salesbox-crm-form' ),
		),

		'date_too_early' => array(
			'description' => __( "Date is earlier than minimum limit", 'salesbox-crm-form' ),
			'default' => __( "The date is before the earliest one allowed.", 'salesbox-crm-form' ),
		),

		'date_too_late' => array(
			'description' => __( "Date is later than maximum limit", 'salesbox-crm-form' ),
			'default' => __( "The date is after the latest one allowed.", 'salesbox-crm-form' ),
		),
	) );
}


/* Tag generator */

add_action( 'sbf_admin_init', 'sbf_add_tag_generator_date', 19, 0 );

function sbf_add_tag_generator_date() {
	$tag_generator = SBF_TagGenerator::get_instance();
	$tag_generator->add( 'date', __( 'date', 'salesbox-crm-form' ),
		'sbf_tag_generator_date' );
}

function sbf_tag_generator_date( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'date';

	$description = __( "Generate a form-tag for a date input field. For more details, see %s.", 'salesbox-crm-form' );

	$desc_link = sbf_link( __( 'https://salesbox.com/date-field/', 'salesbox-crm-form' ), __( 'Date field', 'salesbox-crm-form' ) );

?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><?php echo esc_html( __( 'Field type', 'salesbox-crm-form' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'salesbox-crm-form' ) ); ?></legend>
		<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'salesbox-crm-form' ) ); ?></label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'salesbox-crm-form' ) ); ?></label></th>
	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html( __( 'Default value', 'salesbox-crm-form' ) ); ?></label></th>
	<td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" /><br />
	<label><input type="checkbox" name="placeholder" class="option" /> <?php echo esc_html( __( 'Use this text as the placeholder of the field', 'salesbox-crm-form' ) ); ?></label></td>
	</tr>

	<tr>
	<th scope="row"><?php echo esc_html( __( 'Range', 'salesbox-crm-form' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Range', 'salesbox-crm-form' ) ); ?></legend>
		<label>
		<?php echo esc_html( __( 'Min', 'salesbox-crm-form' ) ); ?>
		<input type="date" name="min" class="date option" />
		</label>
		&ndash;
		<label>
		<?php echo esc_html( __( 'Max', 'salesbox-crm-form' ) ); ?>
		<input type="date" name="max" class="date option" />
		</label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'salesbox-crm-form' ) ); ?></label></th>
	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'salesbox-crm-form' ) ); ?></label></th>
	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
	</tr>
</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'salesbox-crm-form' ) ); ?>" />
	</div>

	<br class="clear" />

	<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'salesbox-crm-form' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
</div>
<?php
}
