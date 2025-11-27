<?php
include 'Database_Connection.php';

$query = "SELECT id, sale_name FROM sales_programm_data_price";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<div>{$row['sale_name']} 
                <button onclick='deleteSale({$row['id']})'>Διαγραφή</button>
              </div>";
    }
} else {
    echo "No active sales found.";
}
mysqli_close($conn);
?>
