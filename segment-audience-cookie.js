/* global sacSettings */
( function () {
	'use strict';

	// Read a cookie by name. Segment's analytics.js stores IDs as JSON strings, so strip quotes.
	function readCookie( name ) {
		const match = document.cookie.match( new RegExp( '(?:^|; )' + name + '=([^;]*)' ) );
		return match ? decodeURIComponent( match[ 1 ] ).replace( /^"|"$/g, '' ) : '';
	}

	const userId = readCookie( 'ajs_user_id' );
	const anonymousId = readCookie( 'ajs_anonymous_id' );

	// Nothing to look up until Segment has identified the visitor.
	if ( ! userId && ! anonymousId ) {
		return;
	}

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
			document.cookie = 'segment_audiences=' + encodeURIComponent( res.data ) + '; path=/; SameSite=Lax';
		} )
		.catch( ( err ) => console.warn( 'Segment Audience Cookie:', err.message ) );
} )();
