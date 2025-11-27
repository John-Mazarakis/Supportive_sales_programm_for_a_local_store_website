<?php
// Allow AJAX requests from WordPress
header("Content-Type: text/plain");
header("Access-Control-Allow-Origin: *");

// Include the database connection
include 'Database_Connection.php';

// Check if form is submitted via POST and contains 'brands' data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['brands']) && isset($_POST['percentage']) && isset($_POST['startdate']) && isset($_POST['enddate']) && isset($_POST['textField'])) {
    $selected_brands = $_POST['brands']; // Array of selected brand IDs
    $percentage = intval($_POST['percentage']); // The percentage value
    $startdate = $_POST['startdate']; // The start date
    $enddate = $_POST['enddate']; // The end date
    $textField = $_POST['textField']; // The new text field value

    // Convert array of IDs to a string for SQL query
    $ids = implode(",", array_map('intval', $selected_brands));

    // Query to fetch brand names based on selected IDs
    $sql = "SELECT name FROM wp_terms WHERE term_id IN ($ids)";

    $result = $conn->query($sql);
    $brand_names = [];
    while ($row = $result->fetch_assoc()) {
        $brand_names[] = $row['name']; // Store brand names in an array
    }
    
    $brand_list = implode(",", $brand_names);
    
    // Prepare the SQL statement
    $query1 = "INSERT INTO sales_programm_data 
                (sale_name, start_date, end_date, brands_in_sale, sale_percentage, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query1);
    $status = 0; // Status value
    
    // Bind parameters to prevent SQL injection
    $stmt->bind_param("ssssii", $textField, $startdate, $enddate, $brand_list, $percentage, $status);
    
    if ($stmt->execute()) {
        echo "Data inserted successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
    
    // Close statement
    $stmt->close();

    // Close the database connection
    $conn->close();
    include 'dates_check_and_delete.php';
} else {
    echo "No brands, percentage, or dates selected."; // If no brands, percentage, or dates were submitted
}
?>
