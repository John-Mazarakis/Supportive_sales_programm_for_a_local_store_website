<?php
// Load WordPress Core
include('/home/beautyisland/public_html/wp-load.php');

// Ensure WooCommerce is active
if (!class_exists('WooCommerce')) {
    die(json_encode(["error" => "WooCommerce is not active."]));
}

// Get raw JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Debugging: Log received data
error_log("Received data: " . print_r($data, true));

// Validate input
if (!isset($data['price'])) {
    die(json_encode(["error" => "Price is missing.", "received_data" => $data]));
}
if (!is_numeric($data['price'])) {
    die(json_encode(["error" => "Invalid price format.", "received_price" => $data['price']]));
}

// Store new total in WooCommerce session
$new_total = floatval($data['price']);
WC()->session->set('custom_cart_total', $new_total);

echo json_encode(["success" => true, "final_price" => $new_total]);
?>
