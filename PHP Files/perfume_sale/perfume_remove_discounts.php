<?php
require_once '../wp-load.php';

function remove_discount_from_products($conn, $product_ids) {
    foreach ($product_ids as $product_id) {
        $stmt = $conn->prepare("SELECT meta_value FROM wp_postmeta WHERE post_id = ? AND meta_key = '_regular_price'");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $regular_price = floatval($row['meta_value']);

            if ($regular_price > 0) {
                $conn->query("DELETE FROM wp_postmeta WHERE post_id = $product_id AND meta_key = '_sale_price'");
                $conn->query("UPDATE wp_postmeta SET meta_value = '$regular_price' WHERE post_id = $product_id AND meta_key = '_price'");

                wc_delete_product_transients($product_id);

            } else {
                echo "Product ID $product_id: Invalid regular price\n";
            }
        } else {
            echo "Product ID $product_id: No regular price found\n";
        }

        $stmt->close();
    }
}
?>
