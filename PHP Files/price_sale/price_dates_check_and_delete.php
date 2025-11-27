<?php
// Include database connection
include 'Database_Connection.php';

// Get today's date in YYYY-MM-DD format
$today = date("Y-m-d");

// Fetch all sales data
$sql = "SELECT id, start_date, end_date FROM sales_programm_data_price";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sale_id = $row["id"];
        $start_date = $row["start_date"];
        $end_date = $row["end_date"];

        if ($today >= $start_date && $today <= $end_date) {
            // If today is within the sale period, update status to 1
            $update_sql = "UPDATE sales_programm_data_price SET status = 1 WHERE id = $sale_id";
            if (!$conn->query($update_sql)) {
                error_log("Error updating sale ID $sale_id: " . $conn->error);
            }
        } elseif ($today > $end_date) {
            // If today is past the sale period, delete the sale
            $delete_sql = "DELETE FROM sales_programm_data_price WHERE id = $sale_id";
            if (!$conn->query($delete_sql)) {
                error_log("Error deleting sale ID $sale_id: " . $conn->error);
            }
        }
    }
}

// Close database connection
$conn->close();
?>
