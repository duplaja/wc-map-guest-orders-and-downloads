<?php
/*
Plugin Name: WC Map Guest Orders and Downloads
Plugin URI: https://www.wptechguides.com/
Description: Maps WooCommerce guest orders and downloads to an account with the same e-mail on account creation or login
Version: 1.0
Author: duplaja
Author URI: https://convexcode.com
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

//Kills script if accessed directly
defined('ABSPATH') or die("Cannot access pages directly.");


//Checks to make sure WooCommerce is activated before allowing plugin activation
register_activation_hook(__FILE__, 'wc_map_guest_activate');

function wc_map_guest_activate() {
  
    if( !class_exists( 'WooCommerce' ) ) {
	
		 deactivate_plugins( plugin_basename( __FILE__ ) );
         wp_die( __( 'Please activate WooCommerce before activating this plugin.', 'wc-map-guest-orders-and-downloads' ), 'Plugin dependency check', array( 'back_link' => true ) );
        
    }
}


//Runs on User Registration
add_action( 'user_register', 'wc_map_guest_initial_match_past_orders', 10, 1 );

function wc_map_guestinitial_match_past_orders( $user_id ) {

	//Get current users's e-mail from ID
	$current_user = get_user_by( 'ID', $user_id );
	$email = $current_user->user_email;

	//Pulls all orders made with the user e-mail as the billing e-mail
	$customer_orders = get_posts( array(
                    'meta_key'    => '_billing_email',
                    'meta_value'  => "$email",
                    'post_type'   => 'shop_order',
		    'post_status' => 'wc-completed',
                    'numberposts'=>-1
         ) );

	//If matching orders are found..
	if (!empty($customer_orders)) {
		
		global $wpdb;
		$prefix = $wpdb->prefix;
		$table=$prefix.'woocommerce_downloadable_product_permissions';
		$data = array('user_id'=>"$user_id");
		$where = array('user_email'=>"$email",'user_id'=>'0');

		//Updates all downloads with the same e-mail but user_id=0 (guest) to the correct user ID
		$wpdb->update( $table, $data, $where, $format = null, $where_format = null );
         
		
		//Updates all WC Orders with the e-mail to map to the correct user ID
                foreach($customer_orders as $k => $v){

		    $order_id = $customer_orders[ $k ]->ID;
                    update_post_meta( $order_id, '_customer_user', $user_id, 0);			
                }
         }
}


//Runs on login (to catch those who accidently make an order while not logged in
add_action('wp_login', 'wc_map_guest_returning_match_past_orders', 10, 2);

function wc_map_guest_returning_match_past_orders($user_login, $current_user) {
    
	//Gets current user ID and e-mail
	$user_id = $current_user->ID;

	$email = $current_user->user_email;
	

	//Pulls all orders made with the user e-mail as the billing e-mail
	$customer_orders = get_posts( array(
                    'meta_key'    => '_billing_email',
                    'meta_value'  => "$email",
                    'post_type'   => 'shop_order',
		    'post_status' => 'wc-completed',
                    'numberposts'=>-1
        ) );

	//If matching orders are found..

	if (!empty($customer_orders)) {
		
		global $wpdb;
		$prefix = $wpdb->prefix;
		$table=$prefix.'woocommerce_downloadable_product_permissions';
		$data = array('user_id'=>"$user_id");
		$where = array('user_email'=>"$email",'user_id'=>'0');


		//Updates all downloads with the same e-mail but user_id=0 (guest) to the correct user ID
		$wpdb->update( $table, $data, $where, $format = null, $where_format = null );
       

		//Updates all WC Orders with the e-mail to map to the correct user ID
                foreach($customer_orders as $k => $v){

		   $order_id = $customer_orders[ $k ]->ID;
		   update_post_meta( $order_id, '_customer_user', $user_id, 0);
		   
		}
	}
	
}
