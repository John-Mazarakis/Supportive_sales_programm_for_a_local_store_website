<?php
require_once '../wp-load.php';

function apply_discount_to_products($conn, $product_ids, $discount) {
    foreach ($product_ids as $product_id) {
        $stmt = $conn->prepare("SELECT meta_value FROM wp_postmeta WHERE post_id = ? AND meta_key = '_regular_price'");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $regular_price = floatval($row['meta_value']);

            if ($regular_price > 0) {
                $sale_price = $regular_price - ($regular_price * $discount / 100);

                $conn->query("DELETE FROM wp_postmeta WHERE post_id = $product_id AND meta_key = '_sale_price'");
                $conn->query("INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ($product_id, '_sale_price', '$sale_price')");
                $conn->query("UPDATE wp_postmeta SET meta_value = '$sale_price' WHERE post_id = $product_id AND meta_key = '_price'");

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
