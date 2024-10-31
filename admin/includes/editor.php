<?php

class SBF_Editor {

	private $contact_form;
	private $panels = array();

	public function __construct( SBF_ContactForm $contact_form ) {
		$this->contact_form = $contact_form;
	}

	public function add_panel( $id, $title, $callback ) {
		if ( sbf_is_name( $id ) ) {
			$this->panels[$id] = array(
				'title' => $title,
				'callback' => $callback,
			);
		}
	}

	public function display() {
		if ( empty( $this->panels ) ) {
			return;
		}

		echo '<ul id="contact-form-editor-tabs">';

		foreach ( $this->panels as $id => $panel ) {
			echo sprintf( '<li id="%1$s-tab"><a href="#%1$s">%2$s</a></li>',
				esc_attr( $id ), esc_html( $panel['title'] ) );
		}

		echo '</ul>';

		foreach ( $this->panels as $id => $panel ) {
			echo sprintf( '<div class="contact-form-editor-panel" id="%1$s">',
				esc_attr( $id ) );

			if ( is_callable( $panel['callback'] ) ) {
				$this->notice( $id, $panel );
				call_user_func( $panel['callback'], $this->contact_form );
			}

			echo '</div>';
		}
	}

	public function notice( $id, $panel ) {
		echo '<div class="config-error"></div>';
	}
}

function sbf_editor_panel_form( $post ) {
	$desc_link = sbf_link(
		__( 'https://salesbox.com', 'salesbox-crm-form' ),
		__( 'Editing form template', 'salesbox-crm-form' ) );
	$description = __( "You can edit the form template here.", 'salesbox-crm-form' );
	$description = sprintf( esc_html( $description ), $desc_link );
?>

<h2><?php echo esc_html( __( 'Form', 'salesbox-crm-form' ) ); ?></h2>

<fieldset>
<legend><?php echo $description; ?></legend>

<?php
	$tag_generator = SBF_TagGenerator::get_instance();
	$tag_generator->print_buttons();
?>

<textarea id="sbf-form" name="sbf-form" cols="100" rows="24" class="large-text code" data-config-field="form.body"><?php echo esc_textarea( $post->prop( 'form' ) ); ?></textarea>
</fieldset>
<?php
}

function sbf_editor_panel_mail( $post ) {
	sbf_editor_box_mail( $post );

	echo '<br class="clear" />';

	sbf_editor_box_mail( $post, array(
		'id' => 'sbf-mail-2',
		'name' => 'mail_2',
		'title' => __( 'Mail (2)', 'salesbox-crm-form' ),
		'use' => __( 'Use Mail (2)', 'salesbox-crm-form' ),
	) );
}

function sbf_editor_box_mail( $post, $args = '' ) {
	$args = wp_parse_args( $args, array(
		'id' => 'sbf-mail',
		'name' => 'mail',
		'title' => __( 'Mail', 'salesbox-crm-form' ),
		'use' => null,
	) );

	$id = esc_attr( $args['id'] );

	$mail = wp_parse_args( $post->prop( $args['name'] ), array(
		'active' => false,
		'recipient' => '',
		'sender' => '',
		'subject' => '',
		'body' => '',
		'additional_headers' => '',
		'attachments' => '',
		'use_html' => false,
		'exclude_blank' => false,
	) );

?>
<div class="contact-form-editor-box-mail" id="<?php echo $id; ?>">
<h2><?php echo esc_html( $args['title'] ); ?></h2>

<?php
	if ( ! empty( $args['use'] ) ) :
?>
<label for="<?php echo $id; ?>-active"><input type="checkbox" id="<?php echo $id; ?>-active" name="<?php echo $id; ?>[active]" class="toggle-form-table" value="1"<?php echo ( $mail['active'] ) ? ' checked="checked"' : ''; ?> /> <?php echo esc_html( $args['use'] ); ?></label>
<p class="description"><?php echo esc_html( __( "Mail (2) is an additional mail template often used as an autoresponder.", 'salesbox-crm-form' ) ); ?></p>
<?php
	endif;
?>

<fieldset>
<legend>
<?php
	$desc_link = sbf_link(
		__( 'https://salesbox.com/setting-up-mail/', 'salesbox-crm-form' ),
		__( 'Setting up mail', 'salesbox-crm-form' ) );
	$description = __( "You can edit the mail template here.", 'salesbox-crm-form' );
	$description = sprintf( esc_html( $description ), $desc_link );
	echo $description;
	echo '<br />';

	echo esc_html( __( "In the following fields, you can use these mail-tags:",
		'salesbox-crm-form' ) );
	echo '<br />';
	$post->suggest_mail_tags( $args['name'] );
?>
</legend>
<table class="form-table">
<tbody>
	<tr>
	<th scope="row">
		<label for="<?php echo $id; ?>-recipient"><?php echo esc_html( __( 'To', 'salesbox-crm-form' ) ); ?></label>
	</th>
	<td>
		<input type="text" id="<?php echo $id; ?>-recipient" name="<?php echo $id; ?>[recipient]" class="large-text code" size="70" value="<?php echo esc_attr( $mail['recipient'] ); ?>" data-config-field="<?php echo sprintf( '%s.recipient', esc_attr( $args['name'] ) ); ?>" />
	</td>
	</tr>

	<tr>
	<th scope="row">
		<label for="<?php echo $id; ?>-sender"><?php echo esc_html( __( 'From', 'salesbox-crm-form' ) ); ?></label>
	</th>
	<td>
		<input type="text" id="<?php echo $id; ?>-sender" name="<?php echo $id; ?>[sender]" class="large-text code" size="70" value="<?php echo esc_attr( $mail['sender'] ); ?>" data-config-field="<?php echo sprintf( '%s.sender', esc_attr( $args['name'] ) ); ?>" />
	</td>
	</tr>

	<tr>
	<th scope="row">
		<label for="<?php echo $id; ?>-subject"><?php echo esc_html( __( 'Subject', 'salesbox-crm-form' ) ); ?></label>
	</th>
	<td>
		<input type="text" id="<?php echo $id; ?>-subject" name="<?php echo $id; ?>[subject]" class="large-text code" size="70" value="<?php echo esc_attr( $mail['subject'] ); ?>" data-config-field="<?php echo sprintf( '%s.subject', esc_attr( $args['name'] ) ); ?>" />
	</td>
	</tr>

	<tr>
	<th scope="row">
		<label for="<?php echo $id; ?>-additional-headers"><?php echo esc_html( __( 'Additional headers', 'salesbox-crm-form' ) ); ?></label>
	</th>
	<td>
		<textarea id="<?php echo $id; ?>-additional-headers" name="<?php echo $id; ?>[additional_headers]" cols="100" rows="4" class="large-text code" data-config-field="<?php echo sprintf( '%s.additional_headers', esc_attr( $args['name'] ) ); ?>"><?php echo esc_textarea( $mail['additional_headers'] ); ?></textarea>
	</td>
	</tr>

	<tr>
	<th scope="row">
		<label for="<?php echo $id; ?>-body"><?php echo esc_html( __( 'Message body', 'salesbox-crm-form' ) ); ?></label>
	</th>
	<td>
		<textarea id="<?php echo $id; ?>-body" name="<?php echo $id; ?>[body]" cols="100" rows="18" class="large-text code" data-config-field="<?php echo sprintf( '%s.body', esc_attr( $args['name'] ) ); ?>"><?php echo esc_textarea( $mail['body'] ); ?></textarea>

		<p>
			<label for="<?php echo $id; ?>-exclude-blank">
				<input 
					type="checkbox" 
					id="<?php echo $id; ?>-exclude-blank" 
					name="<?php echo $id; ?>[exclude_blank]" 
					value="1"
					<?php echo ( ! empty( $mail['exclude_blank'] ) ) ? ' checked="checked"' : ''; ?> 
				/> 
				<?php echo esc_html( __( 'Exclude lines with blank mail-tags from output', 'salesbox-crm-form' ) ); ?>
			</label>
		</p>

		<p><label for="<?php echo $id; ?>-use-html"><input type="checkbox" id="<?php echo $id; ?>-use-html" name="<?php echo $id; ?>[use_html]" value="1"<?php echo ( $mail['use_html'] ) ? ' checked="checked"' : ''; ?> /> <?php echo esc_html( __( 'Use HTML content type', 'salesbox-crm-form' ) ); ?></label></p>
	</td>
	</tr>

	<tr>
	<th scope="row">
		<label for="<?php echo $id; ?>-attachments"><?php echo esc_html( __( 'File attachments', 'salesbox-crm-form' ) ); ?></label>
	</th>
	<td>
		<textarea id="<?php echo $id; ?>-attachments" name="<?php echo $id; ?>[attachments]" cols="100" rows="4" class="large-text code" data-config-field="<?php echo sprintf( '%s.attachments', esc_attr( $args['name'] ) ); ?>"><?php echo esc_textarea( $mail['attachments'] ); ?></textarea>
	</td>
	</tr>
</tbody>
</table>
</fieldset>
</div>
<?php
}

function sbf_editor_panel_messages( $post ) {
	$desc_link = sbf_link(
		__( 'https://salesbox.com', 'salesbox-crm-form' ),
		__( 'Editing messages', 'salesbox-crm-form' ) );
	$description = __( "You can edit messages used in various situations here.", 'salesbox-crm-form' );
	$description = sprintf( esc_html( $description ), $desc_link );

	$messages = sbf_messages();

	if ( isset( $messages['captcha_not_match'] )
	and ! sbf_use_really_simple_captcha() ) {
		unset( $messages['captcha_not_match'] );
	}

?>
<h2><?php echo esc_html( __( 'Messages', 'salesbox-crm-form' ) ); ?></h2>
<fieldset>
<legend><?php echo $description; ?></legend>
<?php

	foreach ( $messages as $key => $arr ) {
		$field_id = sprintf( 'sbf-message-%s', strtr( $key, '_', '-' ) );
		$field_name = sprintf( 'sbf-messages[%s]', $key );

?>
		<p class="description">
			<label for="<?php echo $field_id; ?>"><?php echo esc_html( $arr['description'] ); ?><br />
				<input type="text" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" class="large-text" size="70" value="<?php echo esc_attr( $post->message( $key, false ) ); ?>" data-config-field="<?php echo sprintf( 'messages.%s', esc_attr( $key ) ); ?>" />
			</label>
		</p>
<?php
	}
?>
</fieldset>
<?php
}

function sbf_editor_panel_additional_settings( $post ) {
	$desc_link = sbf_link(
		__( 'https://salesbox.com/additional-settings/', 'salesbox-crm-form' ),
		__( 'Additional settings', 'salesbox-crm-form' ) );
	$description = __( "You can add customization code snippets here.", 'salesbox-crm-form' );
	$description = sprintf( esc_html( $description ), $desc_link );

?>
<h2><?php echo esc_html( __( 'Additional Settings', 'salesbox-crm-form' ) ); ?></h2>
<fieldset>
<legend><?php echo $description; ?></legend>
<textarea id="sbf-additional-settings" name="sbf-additional-settings" cols="100" rows="8" class="large-text" data-config-field="additional_settings.body"><?php echo esc_textarea( $post->prop( 'additional_settings' ) ); ?></textarea>
</fieldset>
<?php
}

function sbf_editor_panel_salesbox_fields( $post ) {
	$description = __( "Choose what Salesbox fields you want to include in the form." );
	$id = 'sbf-salesbox-fields';
?>
	<?php
		submit_button(
			_x( 'Update custom fields', 'API keys', 'salesbox-crm-form' ),
			'medium', 'update_custom_fields'
		);
	?>

	<h2>
		<?php echo esc_html( __( 'Select what data fields from Salesbox CRM that you want to include in your lead generation form.', 'salesbox-crm-form' ) ); ?>
	</h2>
	
	<fieldset>
	

	<?php 
		// $current_fields = $post->prop( 'salesbox_fields' );
		$current_config = $post->prop( 'salesbox_config' );
		// error_log(print_r($current_config, true));
		$fields = SBF_ContactFormTemplate::salesbox_fields(true);
		$salesbox_config = SBF_ContactFormTemplate::salesbox_config();
		$responsible_users = isset($salesbox_config['responsible_users']) ? $salesbox_config['responsible_users'] : [];
		$current_responsible_id = isset($current_config['responsible_user']) ? $current_config['responsible_user']['uuid'] : null;
		$current_fields = isset($current_config['fields']) ? $current_config['fields'] : [];
		$submit_button_field = $fields[array_search('SUBMIT_BUTTON', array_column($fields, 'name'))];
		$submit_button_field_name = 'SUBMIT_BUTTON';
		$submit_button_field_column_index = array_search('SUBMIT_BUTTON', array_column($current_fields, 'name'));
	?>
		
		<table style="width:100%; padding-bottom: 20px;">
			<tr>
				<th>Field</th>
				<th>Display name</th>
				<th>Included</th>
				<th>Required</th>
			</tr>
		
			<?php 	
				foreach ( $fields as $field) {
					$field_name = $field['name'];
					if ($field_name == 'SUBMIT_BUTTON') {
						continue;
					}
					$field_current_index = array_search($field_name, array_column($current_fields, 'name'));
			?>
				<tr>
					<th style="text-align: left;">
						<?php echo esc_html( __( $field['label'], 'salesbox-crm-form' ) ); ?>
					</th>
					<th>
						<label for="<?php echo "salesbox-field-".$field['name']."-displayName"; ?>">
							<input 
								id="<?php echo "salesbox-field-".$field['name']."-displayName"; ?>" 
								name="<?php echo "sbf-salesbox-fields[$field_name][displayName]"; ?>" 
								value="<?php echo ( ($field_current_index > -1 
													&& isset($current_fields[$field_current_index])
													&& isset($current_fields[$field_current_index]['displayName'])
													&& $current_fields[$field_current_index]['displayName'] != '') 
													? $current_fields[$field_current_index]['displayName'] 
													: $field['label'] )?>"
							/> 
						</label>
					</th>
					<th>
						<label for="<?php echo "salesbox-field-".$field['name']."-selected"; ?>">
							<input 
								type="checkbox" 
								id="<?php echo "salesbox-field-".$field['name']."-selected"; ?>" 
								name="<?php echo "sbf-salesbox-fields[$field_name][selected]"; ?>" 
								value="1"
								<?php echo ( $field['alwaysRequired'] || 
											($field_current_index > -1 
											&& isset($current_fields[$field_current_index]['selected']) 
											&& $current_fields[$field_current_index]['selected']) )
											? ' checked="checked"' : ''; 
								?> 
								<?php
									echo ( $field['alwaysRequired'] ? 'readonly onclick="return false;" onkeydown="return false;"' : '' )
								?>
							/> 
						</label>
					</th>
					<th>
						<label for="<?php echo "salesbox-field-".$field['name']."-required"; ?>">
							<input 
								type="checkbox" 
								id="<?php echo "salesbox-field-".$field['name']."-required"; ?>" 
								name="<?php echo "sbf-salesbox-fields[$field_name][required]"; ?>" 
								value="1"
								<?php echo ( 
											($field_current_index > -1 
											&& isset($current_fields[$field_current_index]['required']) 
											&& $current_fields[$field_current_index]['required']) ) 
											? ' checked="checked"' : ''; 
								?> 
							/> 
						</label>
					</th>
				</tr>
			<?php
				}
			?>
			<tr class="spacer" style="height: 20px;"></tr>
			<tr style="margin-top: 15px;">
				<th style="text-align: left;">
					Submit button text
				</th>
				<th>
					<label for="<?php echo "salesbox-field-".$submit_button_field['name']."-displayName"; ?>">
						<input 
							id="<?php echo "salesbox-field-".$submit_button_field['name']."-displayName"; ?>" 
							name="<?php echo "sbf-salesbox-fields[$submit_button_field_name][displayName]"; ?>" 
							value="<?php echo ( ($submit_button_field_column_index > -1 
												&& isset($current_fields[$submit_button_field_column_index])
												&& isset($current_fields[$submit_button_field_column_index]['displayName'])
												&& $current_fields[$submit_button_field_column_index]['displayName'] != '') 
												? $current_fields[$submit_button_field_column_index]['displayName'] 
												: $submit_button_field['label'] )?>"
						/> 
					</label>
				</th>
			</tr>
		</table>

		<label for="<?php echo "salesbox-field-responsible-user"; ?>">
			Responsible user:
			<select 
				name="sbf-responsible-user-id" 
				id="sbf-responsible-user-id"
			>
				<option value="none">None</option>
				<?php 
					foreach ( $responsible_users as $responsible_user) {
				?>
						<option 
							value="<?php echo $responsible_user['uuid'] ?>"
							<?php echo ($responsible_user['uuid'] == $current_responsible_id ? 'selected' : '') ?>
						>
							<?php echo $responsible_user['name'] ?>
						</option>
				<?php
					}
				?>
			</select>
		</label>
	</fieldset>
<?php
}


//<textarea id="sbf-additional-settings" name="sbf-additional-settings" cols="100" rows="8" class="large-text" data-config-field="additional_settings.body"><?php echo esc_textarea( $post->prop( 'additional_settings' ) ); ?></textarea>
