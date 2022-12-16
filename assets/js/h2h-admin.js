/**
 * Back-end functionality for SportsPress Advanced Head to Head.
 *
 * @package h2h-admin
 */

jQuery( document ).ready(
	function($) {

		// H2H Order selector.
		$( ".h2h-order-selector select:first" ).change(
			function() {
				if ($( this ).val() == "0") {
					$( this ).siblings().prop( "disabled", true );
				} else {
					$( this ).siblings().prop( "disabled", false );
				}
			}
		);

		// Trigger order selector.
		$( ".h2h-order-selector select:first" ).change();

	}
);
