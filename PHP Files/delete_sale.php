<?php
include 'Database_Connection.php';

if (isset($_POST['sale_id'])) {
    $sale_id = intval($_POST['sale_id']);

    $stmt = $conn->prepare("DELETE FROM sales_programm_data WHERE id = ?");
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
