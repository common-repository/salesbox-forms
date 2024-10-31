import init from './init';
import submit from './submit';
import reset from './reset';
import { getMetadata } from './submit';

document.addEventListener( 'DOMContentLoaded', event => {
	sbf = {
		init,
		submit,
		reset,
		getMetadata,
		...( sbf ?? {} ),
	};

	const forms = document.querySelectorAll( '.sbf > form' );

	forms.forEach( form => sbf.init( form ) );
} );
