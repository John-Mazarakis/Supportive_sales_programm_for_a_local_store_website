<?php
// Include the database connection
include 'Database_Connection.php';

// Error handling for the database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch all brands
$sql = "
    SELECT DISTINCT t.term_id, t.name 
    FROM wp_terms t
    INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
    WHERE tt.taxonomy = 'yith_product_brand'
    ORDER BY t.name ASC;
";

// Execute the query
$result = $conn->query($sql);

// Output the <select> field for the brands
echo '<select id="brand-select" name="brands[]" multiple="multiple" class="brand-dropdown">';
if ($result->num_rows > 0) {
    // Loop through the results and create options for the dropdown
    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . htmlspecialchars($row['term_id']) . '">' . htmlspecialchars($row['name']) . '</option>';
    }
}
echo '</select>';

// Close the database connection
$conn->close();
?>
