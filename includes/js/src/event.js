export const triggerEvent = ( target, name, detail ) => {
	const event = new CustomEvent( `sbf${ name }`, {
		bubbles: true,
		detail,
	} );

	if ( typeof target === 'string' ) {
		target = document.querySelector( target );
	}

	target.dispatchEvent( event );
};
