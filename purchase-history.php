<?php
require_once( WPSC_FILE_PATH . '/wpsc-includes/purchaselogs.class.php' );


function my_wpsc_purchases() {

	global $wpdb;

	$wp_user = wp_get_current_user();
	$current_user_email = $wp_user->user_email;
	$current_user_id = $wp_user->ID;

	//$form_sql = "SELECT * FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `active` = '1' AND `display_log` = '1';";
	$sql = "SELECT id FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `user_ID` = {$current_user_id} AND `processed` IN (3,4,5) ORDER BY `date` DESC";

	//$sql = "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE  `processed` IN (3,4,5) ORDER BY `date` DESC";
	$purchase_ids_from_user_id = $wpdb->get_col( $sql );

	$sql = "SELECT log_id FROM `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` WHERE  `value` = \"{$current_user_email}\" ORDER BY log_id DESC";
	$purchase_ids_from_email = $wpdb->get_col( $sql );

	$purchase_ids = array_unique ( array_merge( $purchase_ids_from_email, $purchase_ids_from_user_id ) );

	rsort( $purchase_ids, SORT_NUMERIC );

	return $purchase_ids;
}



$purchases = my_wpsc_purchases();

if ( ! empty( $purchases ) ) { ?>

	<table class="logdisplay">
			<?php cph_wpsc_user_purchases( $purchases ); ?>
	</table>

<?php } else { ?>

	<table>
		<tr>
			<td><?php _e( 'There have not been any purchases yet.', 'wp-e-commerce' ); ?></td>
		</tr>
	</table>

<?php } ?>