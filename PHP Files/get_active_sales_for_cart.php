<?php
include 'dates_check_and_delete.php';
include 'Database_Connection.php';

$query = "SELECT id, brands_in_sale, sale_percentage FROM sales_programm_data WHERE status = '1'";
$result = mysqli_query($conn, $query);

$salesData = []; // Initialize an empty array

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $salesData[] = [
            'id' => $row['id'],
            'brands_in_sale' => $row['brands_in_sale'],
            'sale_percentage' => $row['sale_percentage']
        ];
    }
}

mysqli_close($conn);
?>
