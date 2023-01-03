/**
 * Back-end functionality for SportsPress Advanced Head to Head.
 *
 * @package sah2h-admin
 */

jQuery( document ).ready(
	function($) {

		// Sortable lists.
		$( ".sah2h-sortable-list" ).sortable(
			{
				handle: ".sah2h-item-handle",
				placeholder: "sah2h-item-placeholder",
				connectWith: ".sah2h-connected-list"
			}
		);

		// Tiebreak Criteria order selector.
		$( ".sah2h-toggle-switch" ).change(
			function() {
				if ($( this ).is( ':checked' )) {
					$( this ).siblings().prop( "disabled", false );
				} else {
					$( this ).siblings().prop( "disabled", true );
				}
			}
		);

	}
);
