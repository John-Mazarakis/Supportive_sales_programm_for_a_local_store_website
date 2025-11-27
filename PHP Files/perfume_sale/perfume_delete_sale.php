<?php
include 'Database_Connection.php';
include 'perfume_remove_discounts.php';

if (isset($_POST['sale_id'])) {
    $sale_id = intval($_POST['sale_id']);

    // Fetch the sale data first
    $stmt = $conn->prepare("SELECT * FROM sales_programm_data_perfumes WHERE id = ?");
    $stmt->bind_param("i", $sale_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sale = $result->fetch_assoc();
    $stmt->close();
    
    if($sale['status'] == 1){
        $product_ids = explode(",",$sale['ids_in_sale']);
        //error_log('numberof ids = ' . print_r(sizeof($product_ids), true));
        remove_discount_from_products($conn, $product_ids);
    }

    $stmt = $conn->prepare("DELETE FROM sales_programm_data_perfumes WHERE id = ?");
    $stmt->bind_param("i", $sale_id);
    if ($stmt->execute()) {
        echo "Sale deleted successfully.";
    } else {
        echo "Error deleting sale.";
    }
    $stmt->close();
}

mysqli_close($conn);
?>
