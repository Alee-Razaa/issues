<?php
/**
 * AJAX functions for the theme
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Add to cart functionality
 */
function homewellness_ajax_add_to_cart() {
    ob_start();

    $product_id = apply_filters('woocommerce_add_to_cart_product_id', absint($_POST['product_id']));
    $quantity = empty($_POST['quantity']) ? 1 : wc_stock_amount($_POST['quantity']);
    $variation_id = empty($_POST['variation_id']) ? 0 : absint($_POST['variation_id']);
    $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
    $product_status = get_post_status($product_id);

    if ($passed_validation && false !== WC()->cart->add_to_cart($product_id, $quantity, $variation_id) && 'publish' === $product_status) {
        do_action('woocommerce_ajax_added_to_cart', $product_id);

        if ('yes' === get_option('woocommerce_cart_redirect_after_add')) {
            wc_add_to_cart_message(array($product_id => $quantity), true);
        }

        WC_AJAX::get_refreshed_fragments();
    } else {
        $data = array(
            'error' => true,
            'product_url' => apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id)
        );
        wp_send_json($data);
    }

    wp_die();
}

add_action('wp_ajax_homewellness_ajax_add_to_cart', 'homewellness_ajax_add_to_cart');
add_action('wp_ajax_nopriv_homewellness_ajax_add_to_cart', 'homewellness_ajax_add_to_cart');

/**
 * Add WooCommerce parameters
 */
function homewellness_add_wc_params() {
    if (!is_product()) {
        return;
    }

    wp_localize_script('ajax-add-to-cart', 'woocommerce_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'cart_url' => wc_get_cart_url(),
        'cart_redirect_after_add' => get_option('woocommerce_cart_redirect_after_add')
    ));
}
add_action('wp_enqueue_scripts', 'homewellness_add_wc_params', 99);
