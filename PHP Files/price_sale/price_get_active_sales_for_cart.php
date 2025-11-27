<?php
include 'price_dates_check_and_delete.php';
include 'Database_Connection.php';

$query = "SELECT id, brands_in_sale, categories_in_sale, sale_price, sale_percentage FROM sales_programm_data_price WHERE status = '1'";
$result = mysqli_query($conn, $query);

$salesDataPrice = []; // Initialize an empty array

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $salesDataPrice[] = [
            'id' => $row['id'],
            'brands_in_sale' => $row['brands_in_sale'],
            'categories_in_sale' => $row['categories_in_sale'],
            'sale_price' => $row['sale_price'],
            'sale_percentage' => $row['sale_percentage']
        ];
    }
}

mysqli_close($conn);
?>
