<?php
// Include the database connection
include 'Database_Connection.php';

// Error handling
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch all product categories
$sql = "
    SELECT DISTINCT t.term_id, t.name 
    FROM wp_terms t
    INNER JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
    WHERE tt.taxonomy = 'product_cat'
    ORDER BY t.name ASC;
";

$result = $conn->query($sql);

// Output the <select> field for the categories
echo '<select id="category-select" name="categories[]" multiple="multiple" class="category-dropdown">';
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . htmlspecialchars($row['term_id']) . '">' . htmlspecialchars($row['name']) . '</option>';
    }
}
echo '</select>';

$conn->close();
?>
