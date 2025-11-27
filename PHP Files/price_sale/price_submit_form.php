<?php
header("Content-Type: text/plain");
header("Access-Control-Allow-Origin: *");

// Include the database connection
include 'Database_Connection.php';

// Check for required POST data
if (
    $_SERVER["REQUEST_METHOD"] == "POST" &&
    isset($_POST['brands']) &&
    isset($_POST['categories']) &&
    isset($_POST['percentage']) &&
    isset($_POST['pricelimit']) &&
    isset($_POST['startdate']) &&
    isset($_POST['enddate']) &&
    isset($_POST['textField'])
) {
    // Collect and sanitize inputs
    $selected_brands = $_POST['brands'];
    $selected_categories = $_POST['categories'];
    $percentage = floatval($_POST['percentage']);
    $pricelimit = floatval($_POST['pricelimit']);
    $startdate = $_POST['startdate'];
    $enddate = $_POST['enddate'];
    $textField = $_POST['textField'];
    $status = 0;

    // Helper to get names from term IDs
    function get_term_names($conn, $ids) {
        if (empty($ids)) return '';
        $safe_ids = implode(",", array_map('intval', $ids));
        $names = [];

        $sql = "SELECT name FROM wp_terms WHERE term_id IN ($safe_ids)";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $names[] = $row['name'];
        }

        return implode(",", $names);
    }

    $brand_names = get_term_names($conn, $selected_brands);
    $category_names = get_term_names($conn, $selected_categories);

    // Prepare insert statement
    $query = "INSERT INTO sales_programm_data_price 
                (sale_name, start_date, end_date, brands_in_sale, categories_in_sale, sale_price, sale_percentage, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo "Prepare failed: " . $conn->error;
        exit;
    }

    $stmt->bind_param(
        "sssssiii",
        $textField,
        $startdate,
        $enddate,
        $brand_names,
        $category_names,
        $pricelimit,
        $percentage,
        $status
    );

    if ($stmt->execute()) {
        echo "Data inserted successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    include 'dates_check_and_delete.php';

} else {
    echo "Missing required form data.";
}
?>
