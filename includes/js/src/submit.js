import apiFetch from '@wordpress/api-fetch';

import { setStatus } from './status';
import { triggerEvent } from './event';

export default function submit( form, options ) {
	const formData = new FormData( form );

	if ( options.submitter && options.submitter.name ) {
		formData.append( options.submitter.name, options.submitter.value );
	}

	const detail = {
		contactFormId: form.sbf.id,
		pluginVersion: form.sbf.pluginVersion,
		contactFormLocale: form.sbf.locale,
		unitTag: form.sbf.unitTag,
		containerPostId: form.sbf.containerPost,
		status: form.sbf.status,
		inputs: Array.from(
			formData,
			val => {
				const name = val[0], value = val[1];
				return name.match( /^_/ ) ? false : { name, value };
			}
		).filter( val => false !== val ),
		formData,
	};

	const setScreenReaderValidationError = error => {
		const li = document.createElement( 'li' );

		li.setAttribute( 'id', error.error_id );

		if ( error.idref ) {
			li.insertAdjacentHTML(
				'beforeend',
				`<a href="#${ error.idref }">${ error.message }</a>`
			);
		} else {
			li.insertAdjacentText(
				'beforeend',
				error.message
			);
		}

		form.sbf.parent.querySelector(
			'.screen-reader-response ul'
		).appendChild( li );
	};

	const setVisualValidationError = error => {
		const wrap = form.querySelector( error.into );

		const control = wrap.querySelector( '.sbf-form-control' );
		control.classList.add( 'sbf-not-valid' );
		control.setAttribute( 'aria-invalid', 'true' );
		control.setAttribute( 'aria-describedby', error.error_id );

		const tip = document.createElement( 'span' );
		tip.setAttribute( 'class', 'sbf-not-valid-tip' );
		tip.setAttribute( 'aria-hidden', 'true' );
		tip.insertAdjacentText( 'beforeend', error.message );
		wrap.appendChild( tip );

		if ( control.closest( '.use-floating-validation-tip' ) ) {
			control.addEventListener( 'focus', event => {
				tip.setAttribute( 'style', 'display: none' );
			} );

			tip.addEventListener( 'mouseover', event => {
				tip.setAttribute( 'style', 'display: none' );
			} );
		}
	};

	apiFetch( {
		path: `salesbox-crm-form/v1/contact-forms/${ form.sbf.id }/feedback`,
		method: 'POST',
		body: formData,
		sbf: {
			endpoint: 'feedback',
			form,
			detail,
		},
	} ).then( response => {

		const status = setStatus( form, response.status );

		detail.status = response.status;
		detail.apiResponse = response;

		if ( [ 'invalid', 'unaccepted', 'spam', 'aborted' ].includes( status ) ) {
			triggerEvent( form.sbf.parent, status, detail );
		} else if ( [ 'sent', 'failed' ].includes( status ) ) {
			triggerEvent( form.sbf.parent, `mail${ status }`, detail );
		}

		triggerEvent( form.sbf.parent, 'submit', detail );

		return response;

	} ).then( response => {

		if ( response.posted_data_hash ) {
			form.querySelector(
				'input[name="_sbf_posted_data_hash"]'
			).value = response.posted_data_hash;
		}

		if ( 'mail_sent' === response.status ) {
			form.reset();
		}

		if ( response.invalid_fields ) {
			response.invalid_fields.forEach( setScreenReaderValidationError );
			response.invalid_fields.forEach( setVisualValidationError );
		}

		form.sbf.parent.querySelector(
			'.screen-reader-response [role="status"]'
		).insertAdjacentText( 'beforeend', response.message );

		form.querySelectorAll( '.sbf-response-output' ).forEach( div => {
			div.innerText = response.message;
		} );

	} ).catch( error => console.error( error ) );
}


export const getMetadata = (form) => {
	return new Promise((resolve, reject) => {
		apiFetch( {
			path: `salesbox-crm-form/v1/contact-forms/${ form.sbf.id }/metadata`,
			method: 'GET'
		} ).then( response => {
			resolve(response);
			return response;
	
		} ).catch( error => {
			console.error( error );
			reject(error);
		});
	});
}


apiFetch.use( ( options, next ) => {
	if ( options.sbf && 'feedback' === options.sbf.endpoint ) {
		const { form, detail } = options.sbf;

		clearResponse( form );
		triggerEvent( form.sbf.parent, 'beforesubmit', detail );
		setStatus( form, 'submitting' );
	}

	return next( options );
} );

export const clearResponse = form => {
	form.sbf.parent.querySelector(
		'.screen-reader-response [role="status"]'
	).innerText = '';

	form.sbf.parent.querySelector(
		'.screen-reader-response ul'
	).innerText = '';

	form.querySelectorAll( '.sbf-not-valid-tip' ).forEach( span => {
		span.remove();
	} );

	form.querySelectorAll( '.sbf-form-control' ).forEach( control => {
		control.setAttribute( 'aria-invalid', 'false' );
		control.removeAttribute( 'aria-describedby' );
		control.classList.remove( 'sbf-not-valid' );
	} );

	form.querySelectorAll( '.sbf-response-output' ).forEach( div => {
		div.innerText = '';
	} );
};
