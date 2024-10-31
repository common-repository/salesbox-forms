import { absInt } from './utils';

export const exclusiveCheckboxHelper = form => {
	form.querySelectorAll( '.sbf-exclusive-checkbox' ).forEach( element => {
		element.addEventListener( 'change', event => {
			const nameAttr = event.target.getAttribute( 'name' );

			const siblings = form.querySelectorAll(
				`input[type="checkbox"][name="${ nameAttr }"]`
			);

			siblings.forEach( sibling => {
				if ( sibling !== event.target ) {
					sibling.checked = false;
				}
			} );
		} );
	} );
};

export const freeTextHelper = form => {
	form.querySelectorAll( '.has-free-text' ).forEach( element => {
		const freetext = element.querySelector( 'input.sbf-free-text' );

		const checkbox = element.querySelector(
			'input[type="checkbox"], input[type="radio"]'
		);

		freetext.disabled = ! checkbox.checked;

		form.addEventListener( 'change', event => {
			freetext.disabled = ! checkbox.checked;

			if ( event.target === checkbox && checkbox.checked ) {
				freetext.focus();
			}
		} );
	} );
};

export const urlInputHelper = form => {
	form.querySelectorAll( '.sbf-validates-as-url' ).forEach( element => {
		element.addEventListener( 'change', event => {
			let val = element.value.trim();

			if ( val
			&& ! val.match( /^[a-z][a-z0-9.+-]*:/i )
			&& -1 !== val.indexOf( '.' ) ) {
				val = val.replace( /^\/+/, '' );
				val = 'http://' + val;
			}

			element.value = val;
		} );
	} );
};

export const initSubmitButton = form => {
	if ( ! form.querySelector( '.sbf-acceptance' )
	|| form.classList.contains( 'sbf-acceptance-as-validation' ) ) {
		return;
	}

	const checkAcceptance = () => {
		let accepted = true;

		form.querySelectorAll( '.sbf-acceptance' ).forEach( parent => {
			if ( ! accepted || parent.classList.contains( 'optional' ) ) {
				return;
			}

			const checkbox = parent.querySelector( 'input[type="checkbox"]' );

			if ( parent.classList.contains( 'invert' ) && checkbox.checked
			|| ! parent.classList.contains( 'invert' ) && ! checkbox.checked ) {
				accepted = false;
			}
		} );

		form.querySelectorAll( '.sbf-submit' ).forEach( button => {
			button.disabled = ! accepted;
		} );
	};

	checkAcceptance();

	if ( 'init' === form.sbf.status ) {
		form.addEventListener(
			'change',
			event => checkAcceptance()
		);
	}
};

export const initCharacterCount = form => {
	const updateCount = ( counter, target ) => {
		const starting = absInt( counter.getAttribute( 'data-starting-value' ) );
		const maximum = absInt( counter.getAttribute( 'data-maximum-value' ) );
		const minimum = absInt( counter.getAttribute( 'data-minimum-value' ) );

		const count = counter.classList.contains( 'down' )
			? starting - target.value.length
			: target.value.length;

		counter.setAttribute( 'data-current-value', count );
		counter.innerText = count;

		if ( maximum && maximum < target.value.length ) {
			counter.classList.add( 'too-long' );
		} else {
			counter.classList.remove( 'too-long' );
		}

		if ( minimum && target.value.length < minimum ) {
			counter.classList.add( 'too-short' );
		} else {
			counter.classList.remove( 'too-short' );
		}
	};

	const counters = form.querySelectorAll( '.sbf-character-count' );

	counters.forEach( counter => {
		const targetName = counter.getAttribute( 'data-target-name' );
		const target = form.querySelector( `[name="${ targetName }"]` );

		if ( target ) {
			target.value = target.defaultValue;

			updateCount( counter, target );

			if ( 'init' === form.sbf.status ) {
				target.addEventListener( 'keyup', event => {
					updateCount( counter, target );
				} );
			}
		}
	} );
};
