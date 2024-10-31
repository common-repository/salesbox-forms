import { absInt } from './utils';
import { resetCaptcha, resetQuiz } from './reset';

import {
	exclusiveCheckboxHelper,
	freeTextHelper,
	urlInputHelper,
	initSubmitButton,
	initCharacterCount,
} from './helpers';

export default function init( form ) {
	const formData = new FormData( form );

	form.sbf = {
		id: absInt( formData.get( '_sbf' ) ),
		status: form.getAttribute( 'data-status' ),
		pluginVersion: formData.get( '_sbf_version' ),
		locale: formData.get( '_sbf_locale' ),
		unitTag: formData.get( '_sbf_unit_tag' ),
		containerPost: absInt( formData.get( '_sbf_container_post' ) ),
		parent: form.closest( '.sbf' ),
	};

	form.querySelectorAll( '.sbf-submit' ).forEach( element => {
		element.insertAdjacentHTML(
			'afterend',
			'<span class="ajax-loader"></span>'
		);
	} );

	let formMetadata = {};
	const selectProductGroupElement = form.querySelector( 'select[name=NAME_PRODUCT_GROUP]' );
	const selectProductElement = form.querySelector( 'select[name=NAME_PRODUCT]' );

	if (selectProductGroupElement && selectProductElement) {
		sbf.getMetadata(form).then(data => {
			formMetadata = data;
		});

		selectProductGroupElement.onchange = function (event){
			const productGroupText = event.target.value;
			
			if (!formMetadata.productGroupList || !formMetadata.productList) return;
			const productGroup = formMetadata.productGroupList.find(i => i.text == productGroupText);

			let productList = formMetadata.productList;
			if (productGroup) {
				productList = productList.filter(p => p.productGroupId == productGroup.uuid);
			}
			
			let optionString = '<option value="">---</option>';
			productList.forEach(p => {
				optionString += `
					<option value="${p.text}">${p.text}</option>
				`
			});

			selectProductElement.innerHTML = optionString;
		};
	}

	exclusiveCheckboxHelper( form );
	freeTextHelper( form );
	urlInputHelper( form );

	initSubmitButton( form );
	initCharacterCount( form );

	window.addEventListener( 'load', event => {
		if ( sbf.cached ) {
			form.reset();
		}
	} );

	form.addEventListener( 'reset', event => {
		initSubmitButton( form );
		initCharacterCount( form );

		sbf.reset( form );
	} );

	form.addEventListener( 'submit', event => {
		const submitter = event.submitter;
		sbf.submit( form, { submitter } );

		event.preventDefault();
	} );

	form.sbf.parent.addEventListener( 'sbfsubmit', event => {
		if ( event.detail.apiResponse.captcha ) {
			resetCaptcha( form, event.detail.apiResponse.captcha );
		}

		if ( event.detail.apiResponse.quiz ) {
			resetQuiz( form, event.detail.apiResponse.quiz );
		}
	} );

	form.sbf.parent.addEventListener( 'sbfreset', event => {
		if ( event.detail.apiResponse.captcha ) {
			resetCaptcha( form, event.detail.apiResponse.captcha );
		}

		if ( event.detail.apiResponse.quiz ) {
			resetQuiz( form, event.detail.apiResponse.quiz );
		}
	} );
}
