(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
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

	var ajax_url = FiAdminJsObj.ajax_url;
	
	$( window ).load( function() {
		var is_forum_import_page = fi_get_query_string_parameter_value( 'page' );
		if ( 'import-forums-from-drupal-database' === is_forum_import_page ) {
			var new_forums_added     = 0;
			var old_forums_updated   = 0;
			var forums_import_failed = 0;
			kickoff_forum_import( 1, new_forums_added, old_forums_updated, forums_import_failed );
		}
	} );
	
	/**
	 * Kickoff forums import.
	 *
	 * @param {*} page
	 * @param {*} new_forums_added
	 * @param {*} old_forums_updated
	 * @param {*} forums_import_failed
	 */
	function kickoff_forum_import( page, new_forums_added, old_forums_updated, forums_import_failed ) {
		var forum_id = $('#wp_forum_id').val();
		if ( ! forum_id ) {
			return false;
		}
		$.ajax( {
			dataType: 'JSON',
			url: ajax_url,
			type: 'POST',
			data: {
				action: 'kickoff_forums_import',
				wp_forum_id: forum_id,
				page: page,
				new_forums_added: new_forums_added,
				old_forums_updated: old_forums_updated,
				forums_import_failed: forums_import_failed,
			},
			cache: true,
			success: function ( response ) {
				console.log(response);
				// Exit, if there is an invalid response.
				if ( 0 === response ) {
					console.log( 'invalid ajax call for forums import' );
					return false;
				}

				// If all the forums are imported.
				var code = response.data.code;
				if ( 'forums-imported' === code ) {
					$( '.forum-importer-wrapper .finish-card' ).show(); // Show the card details for imported products.
					$( '.forum-importer-wrapper .importing-card' ).hide(); // Hide the the progress bar.

					// Set the numeric logs here.
					$( '.new-forums-count' ).text( response.data.new_forums_added );
					$( '.old-forums-updated-count' ).text( response.data.old_forums_updated );
					$( '.failed-forums-count' ).text( response.data.forums_import_failed );

					// Hide the log button if there are no failed forums.
					if ( 0 === response.data.forums_import_failed ) {
						$( '.openCollapse_log' ).hide();
					}
					return false;
				}

				// If the import is in process.
				if( 'forums-import-in-progress' === code ) {
					// Set the progress bar.
					make_the_bar_progress( response.data.percent );

					// Update the import notice.
					var imported_forums = parseInt( response.data.imported );
					var total_forums    = parseInt( response.data.total );
					imported_forums     = ( imported_forums >= total_forums ) ? total_forums : imported_forums;
					$( '.importing-notice span.imported-count' ).text( imported_forums );
					$( '.importing-notice span.total-products-count' ).text( total_forums );

					/**
					 * Call self to import next set of products.
					 * This wait of 500ms is just to allow the script to set the progress bar.
					 */
					setTimeout( function() {
						page++;
						kickoff_forum_import( page, response.data.new_forums_added, response.data.old_forums_updated, response.data.forums_import_failed );
					}, 500 );
				}
			},
		} );
	}

	/**
	 * Make progress to the progress bar.
	 *
	 * @param {*} percent
	 */
	function make_the_bar_progress( percent ) {
		percent = percent.toFixed( 2 ); // Upto 2 decimal places.
		percent = parseFloat( percent ); // Convert the percent to float.
		percent = ( 100 <= percent ) ? 100 : percent;

		// Set the progress bar.
		$( '.importer-progress' ).val( percent );
		$( '.importer-progress' ).next( '.value' ).html( percent + '%' );
		$( '.importer-progress' ).next( '.value' ).css( 'width', percent + '%' );
	}

	// Call the closeModal function on the clicks/keyboard
	$( document ).on("click", ".close, .mask", function(){
		closeModal();
	});

	$(document).keyup(function(e) {
		if (e.keyCode == 27) {
			closeModal();
		}
	});

	// Function for close the Modal
	function closeModal(){
		$(".mask").removeClass("active");
		location.reload();
	}

	/**
	 * Get query string parameter value.
	 *
	 * @param {string} string
	 * @return {string} string
	 */
	function fi_get_query_string_parameter_value( param_name ) {
		var url_string = window.location.href;
		var url        = new URL( url_string );
		var val        = url.searchParams.get( param_name );

		return val;
	}

})( jQuery );
