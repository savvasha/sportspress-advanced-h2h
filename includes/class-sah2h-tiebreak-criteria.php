<?php
/**
 * SAH2H Tiebreak Criteria Class
 *
 * An Advanced Head to Head League Table Tiebreak Criteria Class.
 *
 * @class       SAH2H_Tiebreak_Criteria
 * @version     1.5.0
 * @package     SAH2H/Classes
 * @category    Class
 * @author      Savvas
 */

/**
 * SAH2H Tiebreak Criteria Class
 *
 * @class SAH2H_Tiebreak_Criteria
 */
class SAH2H_Tiebreak_Criteria {

	/**
	 * Output the Regular Order metabox
	 */
	public static function regular_order_output( $post ) {
		$args = array(
				  'numberposts' => -1,
				  'post_type'   => 'sp_column',
				  'post_status' => 'publish'
				);
		$columns = get_posts( $args );
		//var_dump($columns); ?>
		<div class="sah2h-sortable-list-container">
			<p class="description"><?php esc_html_e( 'Drag each item into the order you prefer.', 'sportspress' ); ?></p>
			<ul class="sah2h-layout sah2h-sortable-list sah2h-connected-list ui-sortable">
			<?php
			foreach( $columns as $column ) { ?>
				<li>
					<div class="sah2h-item-bar sah2h-layout-item-bar">
						<div class="sah2h-item-handle sah2h-layout-item-handle ui-sortable-handle">
							<span class="sah2h-item-title item-title"><?php echo esc_html( $column->post_title ); ?></span>
							<input type="hidden" name="sah2h_column_order[]" value="">
						</div>
						
						<input type="hidden" name="sportspress_<?php echo esc_attr( $column->post_name ); ?>_visibility[]" value="0">
						<input class="sah2h-toggle-switch" type="checkbox" name="sportspress_<?php echo esc_attr( $column->post_name ); ?>_visibility[]" id="" value="1">
						<label for="sah2h_column_show_<?php echo esc_attr( $column->post_name ); ?>"></label>
					</div>
				</li>				
			<?php
			}
			?>
			</ul>
		</div>
		<?php
		//$calendar   = new SP_Calendar( $post );
		//$data       = $calendar->data();
		//$usecolumns = $calendar->columns;
		//self::table( $data, $usecolumns );
	}
	
	/**
	 * Output the Tiebreak Order metabox
	 */
	public static function tiebreak_order_output( $post ) {
		//$calendar   = new SP_Calendar( $post );
		//$data       = $calendar->data();
		//$usecolumns = $calendar->columns;
		//self::table( $data, $usecolumns );
	}


}
