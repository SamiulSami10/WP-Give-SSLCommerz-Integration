<?php
/*
Plugin Name: SSLCommerz Integration for WP Give
Description: Custom SSLCommerz integration with WP Give plugin for donations.
Version: 1.0
Author: Samiul H Pranto
*/

add_filter('give_payment_gateways', 'custom_sslcommerz_gateway');
function custom_sslcommerz_gateway($gateways) {
    $gateways['sslcommerz'] = array(
        'admin_label'    => 'SSLCommerz',
        'checkout_label' => 'SSLCommerz',
    );
    return $gateways;
}


add_filter('give_get_sections_gateways', 'custom_sslcommerz_gateway_section');
function custom_sslcommerz_gateway_section($sections) {
    $sections['sslcommerz'] = __('SSLCommerz', 'give');
    return $sections;
}

add_filter('give_get_settings_gateways', 'custom_sslcommerz_gateway_settings');
function custom_sslcommerz_gateway_settings($settings) {
    $sslcommerz_settings = array(
        array(
            'id'   => 'give_title_sslcommerz',
            'type' => 'title',
        ),
        array(
            'name' => __('SSLCommerz Store ID', 'give'),
            'desc' => __('Enter your SSLCommerz Store ID', 'give'),
            'id'   => 'give_sslcommerz_store_id',
            'type' => 'text',
        ),
        array(
            'name' => __('SSLCommerz Store Password', 'give'),
            'desc' => __('Enter your SSLCommerz Store Password', 'give'),
            'id'   => 'give_sslcommerz_store_password',
            'type' => 'password',
        ),
        array(
            'id'   => 'give_title_sslcommerz',
            'type' => 'sectionend',
        ),
    );

    return array_merge($settings, $sslcommerz_settings);
}

add_action('give_gateway_sslcommerz', 'custom_process_sslcommerz_payment');
function custom_process_sslcommerz_payment($purchase_data) {
    $store_id = give_get_option('give_sslcommerz_store_id');
    $store_password = give_get_option('give_sslcommerz_store_password');

    // Prepare the data for the SSLCommerz payment request
    $sslcommerz_args = array(
        'store_id' => $store_id,
        'store_passwd' => $store_password,
        'total_amount' => $purchase_data['price'],
        'currency' => 'USD',
        'tran_id' => $purchase_data['purchase_key'],
        'success_url' => add_query_arg('give-listener', 'sslcommerz', home_url('/')),
        'fail_url' => add_query_arg('give-listener', 'sslcommerz', home_url('/')),
        'cancel_url' => add_query_arg('give-listener', 'sslcommerz', home_url('/')),
        'cus_name' => $purchase_data['user_info']['first_name'] . ' ' . $purchase_data['user_info']['last_name'],
        'cus_email' => $purchase_data['user_info']['email'],
    );

    // Redirect to SSLCommerz payment page
    $payment_url = 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'; // Use sandbox URL for testing
    $response = wp_remote_post($payment_url, array(
        'body' => $sslcommerz_args,
    ));

    if (is_wp_error($response)) {
        // Handle error
        wp_die('Payment failed. Please try again.');
    } else {
        // Parse the response and redirect to SSLCommerz
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($response_data['status'] === 'SUCCESS') {
            wp_redirect($response_data['GatewayPageURL']);
            exit;
        } else {
            wp_die('Payment failed. Please try again.');
        }
    }
}


add_action('init', 'custom_handle_sslcommerz_response');
function custom_handle_sslcommerz_response() {
    if (isset($_GET['give-listener']) && $_GET['give-listener'] == 'sslcommerz') {
        // Handle the payment response from SSLCommerz
        // Verify the payment, update the donation status, and redirect accordingly

        // Example: Redirect to a custom thank you page or show an error message
        wp_redirect(home_url('/thank-you'));
        exit;
    }
}
