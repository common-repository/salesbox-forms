import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

import icon from './icon';
import edit from './edit';
import transforms from './transforms';

registerBlockType( 'salesbox-crm-form/contact-form-selector', {

	title: __( 'Salesbox Form', 'salesbox-crm-form' ),

	description: __( "Catch leads from your Wordpress website and get them added automatically in realtime to Salesbox CRM. Get contact, company and lead data added automatically to the Unassigned prospect area in Salesbox CRM. Let Salesbox notify you in realtime every time a lead is added to Salesbox CRM by the form. Include your preferred data fields (standard or custom fields) from the Contact, Company and Prospect objects in Salesbox CRM into your lead generation forms in Wordpress.", 'salesbox-crm-form' ),

	category: 'widgets',

	attributes: {
		id: {
			type: 'integer',
		},
		title: {
			type: 'string',
		},
	},

	icon,

	transforms,

	edit,

	save: ( { attributes } ) => {
		return(
			<div>
				[salesbox-crm-form id="{ attributes.id }" title="{ attributes.title }"]
			</div>
		);
	},
} );
