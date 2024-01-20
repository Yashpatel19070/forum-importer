(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	var ajaxurl = Obj_Public_JS.ajaxurl;
	$( document ).on('click', '.bbpress img',function( e ) {
		e.preventDefault();
		var imgsrc  = $(this).attr( 'src' );
		var classes  = $(this).attr( 'class' );
		var image_id = '';
		if ( classes  && classes.indexOf( 'wp-image-') > -1 ) {
			var classarr = classes.split( ' ' );
			image_id = classarr[classarr.length-1].split('-')[2];
		}
		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: { 'image_id': image_id, 'imgsrc': imgsrc, 'action': 'fi_enlarge_images' },
			cache: true,
			beforeSend: function() {
				$('.loader').show();
			},
			success: function ( response ) {
				if ( 'success' === response.data.code ) {
					$( '#imageEnlargeModal' ).show();
					$( '#enlargeImg' ).attr( 'src', response.data.html );
				}  
			},
			complete: function() {
				$('.loader').hide();
			}
		} );
		
	} );

	$( document ).on( 'click', '.close', function() { 
		$( '#imageEnlargeModal' ).hide();
	} );
})( jQuery );
