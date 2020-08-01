<?php
/**
 * Plugin Name: WooCommerce to Zapier
 * Description: Sends WooCommerce orders to Zapier
 * Version: 0.0.2
 * Author: René Sauvé
 * Author URI: https://github.com/ReneSauve
 */


// define the after_woocommerce_pay callback 
function send_order_to_zapier( $order_id ) {

    if (function_exists("wc_get_order")) {
        $order = wc_get_order($order_id);

        $date_time = $order->get_date_created()->format('m/d/Y, g:i a');

        $firstname = $order->get_billing_first_name();
        $lastname = $order->get_billing_last_name();
        $customer_name = "$firstname $lastname";

        
        $customer_email = $order->get_billing_email();
        $customer_phone_number = $order->get_billing_phone();
        $billing_street_address = $order->get_billing_address_1();
        $billing_city = $order->get_billing_city();
        $billing_province = $order->get_billing_state();
        $billing_country = $order->get_billing_country();
        $billing_zip_code = $order->get_billing_postcode();

        $payment_status = $order->get_status();


        $data = [
            "id" => $order_id,
            "date" => $date_time,
            "customer" => [
                "name" => $customer_name,
                "email" => $customer_email,
                "phone" => $customer_phone_number,
                "address" => [
                    "street_address" => $billing_street_address,
                    "city" => $billing_city,
                    "state" => $billing_province,
                    "country" => $billing_country,
                    "zip_code" => $billing_zip_code,
                ],
               
            ],
        ];

        
        $cart_items = [];
        foreach ( $order->get_items() as $item ) {
            
            $product = $item->get_product();
            $name = $item->get_name();
            $quantity = $item->get_quantity();
            $type = $item->get_type();

            $cart_item = "$name x$quantity";
            array_push($cart_items, $cart_item);
        }

        $data['cart'] = implode("\n", $cart_items);

        if (defined("ZAPIER_URL")) {
            $response = wp_remote_post( ZAPIER_URL, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode($data),
                ]);
                
        } else {
            error_log('ZAPIER_URL not defined in wp-config.php');
        }

        // error_log(json_encode($response));       
    }

}; 

// /**
//  * Fire on the initialization of the admin screen or scripts.
//  */
// function pooper() {
//     $order = wc_get_order(75);

//     var_dump($order->get_items());
    
//     die();
// }
// add_action( 'admin_init', 'pooper' );


function action_wp_insert_post( $post_id, $post ) { 
  // action triggers on every post creation
    if ($post->post_status === 'wc-processing') {
        // check post status for wc-processing
        send_order_to_zapier($post_id);
    }
}; 
         
// add the action 
add_action( 'wp_insert_post', 'action_wp_insert_post', 10, 2 );