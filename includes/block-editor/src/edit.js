import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { useInstanceId } from '@wordpress/compose';
import { SelectControl } from '@wordpress/components';

const contactForms = new Map();

apiFetch( {
	path: 'salesbox-crm-form/v1/contact-forms?per_page=20',
} ).then( response => {
	Object.entries( response ).forEach( ( [ key, value ] ) => {
		contactForms.set( value.id, value );
	} );
} );

export default function ContactFormSelectorEdit( { attributes, setAttributes } ) {
	if ( ! contactForms.size && ! attributes.id ) {
		return(
			<div className="components-placeholder">
				<p>
					{ __( "There are no Salesbox CRM forms added yet.", 'salesbox-crm-form' ) }
				</p>
			</div>
		);
	}

	const options = Array.from( contactForms.values(), ( val ) => {
		return { value: val.id, label: val.title };
	} );

	if ( ! attributes.id ) {
		const firstOption = options[0];

		setAttributes( {
			id: parseInt( firstOption.value ),
			title: firstOption.label,
		} );
	} else if ( ! options.length ) {
		options.push( {
			value: attributes.id,
			label: attributes.title,
		} );
	}

	const instanceId = useInstanceId( ContactFormSelectorEdit );
	const id = `salesbox-crm-form-contact-form-selector-${ instanceId }`;

	return(
		<div className="components-placeholder">
			<label
				htmlFor={ id }
				className="components-placeholder__label"
			>
				{ __( "Select a Salesbox CRM form:", 'salesbox-crm-form' ) }
			</label>
			<SelectControl
				id={ id }
				options={ options }
				value={ attributes.id }
				onChange={
					( value ) => setAttributes( {
						id: parseInt( value ),
						title: contactForms.get( parseInt( value ) ).title
					} )
				}
			/>
		</div>
	);
}
