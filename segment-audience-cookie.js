/* global sacSettings */
( function () {
	'use strict';

	// Read a cookie by name. Segment's analytics.js stores IDs as JSON strings, so strip quotes.
	function readCookie( name ) {
		const match = document.cookie.match( new RegExp( '(?:^|; )' + name + '=([^;]*)' ) );
		return match ? decodeURIComponent( match[ 1 ] ).replace( /^"|"$/g, '' ) : null;
	}

	// Already looked up this session: nothing to do.
	if ( readCookie( sacSettings.cookieName ) !== null ) {
		return;
	}

	const userId = readCookie( 'ajs_user_id' );
	const anonymousId = readCookie( 'ajs_anonymous_id' );

	// Nothing to look up until Segment has identified the visitor.
	if ( ! userId && ! anonymousId ) {
		return;
	}

	// `action` tells admin-ajax.php which wp_ajax_* hook to run.
	// Only send IDs we have, so the server never receives the string "null".
	const body = new URLSearchParams( { action: 'sac_get_audiences' } );
	if ( userId ) {
		body.append( 'user_id', userId );
	}
	if ( anonymousId ) {
		body.append( 'anonymous_id', anonymousId );
	}

	fetch( sacSettings.ajaxUrl, { method: 'POST', body } )
		.then( ( res ) => res.json() )
		.then( ( res ) => {
			if ( ! res.success ) {
				throw new Error( res.data );
			}
			// Session cookie: cleared when the browser closes.
			document.cookie = sacSettings.cookieName + '=' + encodeURIComponent( res.data ) + '; path=/; SameSite=Lax';
		} )
		// On failure no cookie is set, so the lookup is retried on the next page.
		.catch( ( err ) => console.warn( 'Segment Audience Cookie:', err.message ) );
} )();
