<?php
/**
 * Enqueue script and styles for child theme
 */
function woodmart_child_enqueue_styles() {
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'woodmart-style' ), woodmart_get_theme_info( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'woodmart_child_enqueue_styles', 10010 );

add_filter('woocommerce_calculated_total', function ($total) {
    if (WC()->session && WC()->session->get('custom_cart_total')) {
        $new_total = WC()->session->get('custom_cart_total');

        // Debugging: Log applied total
        error_log("Applying custom cart total: " . $new_total);

        return floatval($new_total);
    }
    return $total;
}, 20, 1);
