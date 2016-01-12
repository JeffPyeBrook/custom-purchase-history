<?php
/*
** Copyright 2010-2016, Pye Brook Company, Inc.
**
**
** This software is provided under the GNU General Public License, version
** 2 (GPLv2), that covers its  copying, distribution and modification. The 
** GPLv2 license specifically states that it only covers only copying,
** distribution and modification activities. The GPLv2 further states that 
** all other activities are outside of the scope of the GPLv2.
**
** All activities outside the scope of the GPLv2 are covered by the Pye Brook
** Company, Inc. License. Any right not explicitly granted by the GPLv2, and 
** not explicitly granted by the Pye Brook Company, Inc. License are reserved
** by the Pye Brook Company, Inc.
**
** This software is copyrighted and the property of Pye Brook Company, Inc.
**
** Contact Pye Brook Company, Inc. at info@pyebrook.com for more information.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY 
** WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR 
** A PARTICULAR PURPOSE. 
**
*/

add_filter( 'wpsc_path_wpsc-account-purchase-history.php', 'my_custom_purchase_history', 10, 1 );

function my_custom_purchase_history( $path ) {
	return plugin_dir_path( __FILE__ ) . 'purchase-history.php';
}

function my_purchase_log_cart_items() {
	$total_weight = 0;
	$item_count   = 0;
	while ( wpsc_have_purchaselog_details() ) : wpsc_the_purchaselog_item(); ?>
		<tr>
			<td><?php echo wpsc_purchaselog_details_quantity(); ?></td>
			<!-- QUANTITY! -->
			<td class="itemdetails"><b><?php echo wpsc_purchaselog_details_name(); ?></b>
				<?php// my_additional_sales_item_info(); ?>
			</td>
			<!-- NAME! -->
			<td><?php echo my_wpsc_purchaselog_details_SKU(); ?></td>
			<!-- SKU! -->
			<?php
			$item_weight = my_purchaselog_details_weight();
			$total_weight += $item_weight;
			$item_count ++;
			?>
			<td class="weight"><?php echo number_format( $item_weight, 2 ); ?></td>
		</tr>
		<?php
	endwhile;
}


function my_purchaselog_details_weight() {
	global $purchlogitem;
	$product_meta = get_product_meta( $purchlogitem->purchitem->prodid, 'product_metadata', true );
	$weight       = 0.0;

	if ( ! empty( $product_meta ['weight'] ) ) {

		$converted_weight = floatval( $product_meta ['weight'] );
		$weight           = $converted_weight * floatval( $purchlogitem->purchitem->quantity );
	}

	return $weight;
}


/**
 * Weight of current or specified purchase
 *
 * @since 3.8.14
 *
 *
 * @param string $id
 *
 * @return float $weight in '$out_unit' of shipment
 */
function my_get_item_weight( $id = '', $out_unit = 'pound' ) {
	global $purchlogitem;
	$weight      = 0.0;
	$items_count = 0;

	if ( empty( $id ) || ( ! empty( $purchlogitem ) && ( $id == $purchlogitem->purchlogid ) ) ) {
		$thepurchlogitem = $purchlogitem;
	} else {
		$thepurchlogitem = new wpsc_purchaselogs_items( $id );
	}

	/**
	 * Filter wpsc_purchlogs_before_get_weight
	 *
	 * Allow the weight to be overridden, can be used to persistantly save weight and recall it rather than recalculate
	 *
	 * @since 3.8.14
	 *
	 * @param  float $weight , purchase calculation will not continue if value is returned
	 * @param  string weight unit to use for return value
	 * @param  object wpsc_purchaselogs_items purchase log item being used
	 * @param  int    purchase log item id
	 *
	 * @return float  $weight
	 */
	$weight_override = apply_filters( 'wpsc_purchlogs_before_get_weight', false, $out_unit, $thepurchlogitem, $thepurchlogitem->purchlogid );
	if ( $weight_override !== false ) {
		return $weight_override;
	}

	// if there isn't a purchase log item we are done
	if ( empty( $thepurchlogitem ) ) {
		return false;
	}

	foreach ( ( array ) $thepurchlogitem->allcartcontent as $cartitem ) {
		$product_meta = get_product_meta( $cartitem->prodid, 'product_metadata', true );
		if ( ! empty( $product_meta ['weight'] ) ) {

			$converted_weight = wpsc_convert_weight( $product_meta ['weight'], $product_meta['weight_unit'], $out_unit, true );

			$weight += $converted_weight * $cartitem->quantity;
			$items_count += $cartitem->quantity;
		}
	}

	/**
	 * Filter wpsc_purchlogs_get_weight
	 *
	 * Allow the weight to be overridden
	 *
	 * @since 3.8.14
	 *
	 * @param  float $weight calculated cart weight
	 * @param  object wpsc_purchaselogs_items purchase log item being used
	 * @param  int    purchase log item id
	 * @param  int $items_count how many items are in the cart, useful for
	 *                                        cases where packaging weight changes as more items are
	 *                                        added
	 */
	$weight = apply_filters( 'wpsc_purchlogs_get_weight', $weight, $thepurchlogitem, $thepurchlogitem->purchlogid, $items_count );

	return $weight;
}


function my_logo_url() {
	return plugins_url( 'sparkle-gear-logo-vector-100.png', __FILE__ );
}

function my_purchase_log_cart_items_count() {

	$count = 0;

	while ( wpsc_have_purchaselog_details() ) :
		wpsc_the_purchaselog_item();
		$count += wpsc_purchaselog_details_quantity();
	endwhile;

	return $count;
}

function my_additional_sales_item_info() {
	global $purchlogitem;
	$itemid = $purchlogitem->purchitem->id;
	do_action( 'optn8r_product_checkout_details', $itemid );
	do_action( 'wpsc_additional_packing_item_info', $itemid );
}

function my_wpsc_purchaselog_details_SKU() {
	global $purchlogitem;
	$meta_value = wpsc_get_cart_item_meta( $purchlogitem->purchitem->id, 'sku', true );
	if ( $meta_value != null ) {
		$sku = esc_attr( $meta_value );
	} else {
		$meta_value = get_product_meta( $purchlogitem->purchitem->prodid, 'sku', true );
		if ( $meta_value != null ) {
			$sku = esc_attr( $meta_value );
		} else {
			$sku = $purchlogitem->purchitem->prodid;
		}
	}

	$sku = '<a href="' . get_permalink( $purchlogitem->purchitem->prodid ) . '">' . $sku . '</a>';

	return $sku;
}


function my_wpsc_user_purchases( $purchase_ids ) {
	global $wpdb;
	global $purchlogitem;

	$i = 0;
	$col_count = 4;

	//do_action( 'wpsc_pre_purchase_logs' );

	foreach ( $purchase_ids as $purchase_id ) {

		$alternate = "";
		$alternate_style = "";
		$i++;
		$purchlogitem = new wpsc_purchaselogs_items( $purchase_id );

		//my_purchase_log_cart_items();

		if ( ($i % 2) != 0 ) {
			$alternate = 'class="header-row alt"';
		}

		$alternate_style = 'style = "background: lightgray; font-weight: bold;"';

		$purchase_log = new WPSC_Purchase_Log( $purchase_id );

		echo "<tr {$alternate} {$alternate_style} >\n\r";

		echo " <td style=\"width:25%;\" class='status processed'>";
		echo '<label>Purchase ID:</label>&nbsp;' . $purchase_id;
		echo " </td>\n\r";

		echo " <td style=\"width:25%;\" class='date'>";
		echo '<label>Date:</label>&nbsp;' . date( "jS M Y", $purchase_log->get( 'date' ) );
		echo " </td>\n\r";

		echo " <td  style=\"width:25%;\" class='price'>";
		echo '<label>Total:</label>&nbsp;' .  wpsc_currency_display( $purchase_log->get( 'totalprice' ), array('display_as_html' => false) );
		echo " </td>\n\r";

		echo " <td style=\"width:25%;\" class='tracking'>";
		echo $purchase_log->get( 'track_id' );
		echo " </td>\n\r";

		echo "</tr>\n\r";

		echo "<tr>\n\r";
		echo " <td colspan='$col_count' class='details'>\n\r";

		echo "  <div>\n\r";

		//cart contents display starts here;
		$cartsql = $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid`= %d", $purchase_id );
		$cart_log = $wpdb->get_results( $cartsql, ARRAY_A );
		$j = 0;

		if ( $cart_log != null ) {
			echo "<table class='logdisplay'>";
			echo "<tr class='toprow2'>";

			echo " <th class='details_name'>";
			_e( 'Item Name', 'wp-e-commerce' );
			echo " </th>";

			echo " <th class='details_quantity'>";
			_e( 'Quantity', 'wp-e-commerce' );
			echo " </th>";

			echo " <th class='details_price'>";
			_e( 'Price', 'wp-e-commerce' );
			echo " </th>";

			echo " <th class='details_total'>";
			_e( 'Total', 'wp-e-commerce' );
			echo " </th>";

			echo "</tr>";

			while ( wpsc_have_purchaselog_details() ) {
				wpsc_the_purchaselog_item();
				$alternate = "";
				$j++;

				if ( ($j % 2) != 0 ) {
					$alternate = "alt";
				}

				echo "<tr class='$alternate'>";

				echo " <td class='details_name'>";
				echo wpsc_purchaselog_details_href();
				echo " </td>";

				echo " <td class='details_quantity'>";
				echo wpsc_purchaselog_details_quantity();
				echo " </td>";

				echo " <td class='details_price'>";
				echo wpsc_currency_display( wpsc_purchaselog_details_price() );
				echo " </td>";

				echo " <td class='details_total'>";
				echo wpsc_currency_display( wpsc_purchaselog_details_total() );
				echo " </td>";

				echo '</tr>';

				echo '<tr>';
				do_action( 'wpsc_additional_sales_item_info',  $purchase_id );
				echo '</tr>';

			}

			echo "</table>";
			echo "<br />";
		}

		echo "  </div>\n\r";
		echo " </td>\n\r";
		echo "</tr>\n\r";
	}
}
