<?php
// Include database connection and discount application logic
include 'Database_Connection.php';
require_once 'perfume_insert_discounts.php';
require_once 'perfume_remove_discounts.php';

// Get today's date in YYYY-MM-DD format
$today = date("Y-m-d");

// Fetch all sales data including necessary fields
$sql = "SELECT id, start_date, end_date, status, status_checked, ids_in_sale, sale_percentage FROM sales_programm_data_perfumes";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sale_id = $row["id"];
        $start_date = $row["start_date"];
        $end_date = $row["end_date"];
        $ids_in_sale = explode(",", $row["ids_in_sale"]);
        if ($today >= $start_date && $today <= $end_date) {
            // Within sale period
            if ($row["status_checked"] == 1) {
                // Only update status if already checked
                $update_sql = "UPDATE sales_programm_data_perfumes SET status = 1 WHERE id = $sale_id";
                if (!$conn->query($update_sql)) {
                    error_log("Error updating status for sale ID $sale_id: " . $conn->error);
                }
            } else {
                // First time activation of sale
                $update_sql = "UPDATE sales_programm_data_perfumes SET status = 1, status_checked = 1 WHERE id = $sale_id";
                if (!$conn->query($update_sql)) {
                    error_log("Error activating sale ID $sale_id: " . $conn->error);
                }

                // Apply discount
                apply_discount_to_products($conn, $ids_in_sale, (int)$row["sale_percentage"]);
            }
        } elseif ($today > $end_date) {
            if($row["status"] == 1){
                remove_discount_from_products($conn, $ids_in_sale);
            }
            // After the end date — delete the sale
            $delete_sql = "DELETE FROM sales_programm_data_perfumes WHERE id = $sale_id";
            if (!$conn->query($delete_sql)) {
                error_log("Error deleting expired sale ID $sale_id: " . $conn->error);
            }
        }
    }
}

$conn->close();
?>
