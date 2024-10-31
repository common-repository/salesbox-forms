import apiFetch from '@wordpress/api-fetch';

import { setStatus } from './status';
import { triggerEvent } from './event';
import { clearResponse } from './submit';

export default function reset( form ) {
	const formData = new FormData( form );

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

	apiFetch( {
		path: `salesbox-crm-form/v1/contact-forms/${ form.sbf.id }/refill`,
		method: 'GET',
		sbf: {
			endpoint: 'refill',
			form,
			detail,
		},
	} ).then( response => {

		if ( 'sent' === detail.status ) {
			setStatus( form, 'mail_sent' );
		} else {
			setStatus( form, 'init' );
		}

		detail.apiResponse = response;

		triggerEvent( form.sbf.parent, 'reset', detail );

	} ).catch( error => console.error( error ) );
}

apiFetch.use( ( options, next ) => {
	if ( options.sbf && 'refill' === options.sbf.endpoint ) {
		const { form, detail } = options.sbf;

		clearResponse( form );
		setStatus( form, 'resetting' );
	}

	return next( options );
} );

// Refill for Really Simple CAPTCHA
export const resetCaptcha = ( form, refill ) => {
	for ( const name in refill ) {
		const url = refill[ name ];

		form.querySelectorAll( `input[name="${ name }"]` ).forEach( input => {
			input.value = '';
		} );

		form.querySelectorAll( `img.sbf-captcha-${ name }` ).forEach( img => {
			img.setAttribute( 'src', url );
		} );

		const match = /([0-9]+)\.(png|gif|jpeg)$/.exec( url );

		if ( match ) {
			form.querySelectorAll(
				`input[name="_sbf_captcha_challenge_${ name }"]`
			).forEach( input => {
				input.value = match[ 1 ];
			} );
		}
	}
};

// Refill for quiz fields
export const resetQuiz = ( form, refill ) => {
	for ( const name in refill ) {
		const question = refill[ name ][ 0 ];
		const hashedAnswer = refill[ name ][ 1 ];

		form.querySelectorAll(
			`.sbf-form-control-wrap.${ name }`
		).forEach( wrap => {
			wrap.querySelector( `input[name="${ name }"]` ).value = '';
			wrap.querySelector( '.sbf-quiz-label' ).textContent = question;

			wrap.querySelector(
				`input[name="_sbf_quiz_answer_${ name }"]`
			).value = hashedAnswer;
		} );
	}
};
