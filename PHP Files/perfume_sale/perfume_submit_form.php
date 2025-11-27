<?php
// Allow AJAX requests from WordPress
header("Content-Type: text/plain");
header("Access-Control-Allow-Origin: *");

// Include the database connection
include 'Database_Connection.php';

// Check for required POST values
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['percentage'], $_POST['startdate'], $_POST['enddate'], $_POST['textField'])
) {
    // Get and sanitize inputs
    $percentage = intval($_POST['percentage']);
    $startdate = $_POST['startdate'];
    $enddate = $_POST['enddate'];
    $textField = $_POST['textField'];
    $sizeFrom = intval(isset($_POST['size_from'])) ? intval($_POST['size_from']) : 0;
    $sizeTo = intval(isset($_POST['size_to'])) ? intval($_POST['size_to']) : 300;
    

    // Categories to search in
    $categories = ['Eau de Parfum', 'Eau de Toilette']; // Add more if needed

    // Disable ONLY_FULL_GROUP_BY mode
    $conn->query("SET SESSION sql_mode = ''");

    // SQL template
    $sql = "
        SELECT 
            p.ID AS product_id,
            p.post_title AS product_name,
            pm_sku.meta_value AS sku
        FROM wp_posts p
        JOIN wp_postmeta pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
        LEFT JOIN wp_posts parent_product ON p.post_parent = parent_product.ID AND parent_product.post_type = 'product'
        JOIN wp_term_relationships tr ON tr.object_id IN (p.ID, parent_product.ID)
        JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'product_cat'
        JOIN wp_terms t ON tt.term_id = t.term_id
        WHERE (p.post_type = 'product' OR p.post_type = 'product_variation')
        AND t.name = ?
        AND pm_sku.meta_value IS NOT NULL
        AND pm_sku.meta_value != ''
        GROUP BY p.ID
        ORDER BY p.post_title ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('MySQL prepare error: ' . $conn->error);
    }

    $all_perfumes = [];

    foreach ($categories as $category_name) {
        $stmt->bind_param("s", $category_name);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $size = getPerfumeSize($row['product_name']);
            $all_perfumes[] = [
                "id" => $row['product_id'],
                "size" => $size
            ];
        }
    }
    error_log(sizeof($all_perfumes));
    $stmt->close();

    $idsInSale = [];
    foreach ($all_perfumes as $perfume) {
        if ($perfume['size'] >= $sizeFrom && $perfume['size'] <= $sizeTo) {
            $idsInSale[] = $perfume['id'];
        }
    }
    error_log(sizeof($idsInSale));
    $ids_in_sale = implode(",",$idsInSale);


    $status = 0;
    $status_checked = 0;

    // Prepare insert query
    $query = "INSERT INTO sales_programm_data_perfumes 
              (sale_name, start_date, end_date, ids_in_sale, sale_percentage, status, status_checked) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo "SQL preparation error: " . $conn->error;
        exit;
    }

    $stmt->bind_param("ssssiii", $textField, $startdate, $enddate, $ids_in_sale, $percentage, $status, $status_checked);

    if ($stmt->execute()) {
        echo "Η προσφορά καταχωρήθηκε με επιτυχία!";
    } else {
        echo "Σφάλμα κατά την καταχώρηση: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

} else {
    echo "Λείπουν απαιτούμενα πεδία.";
}

function getPerfumeSize($str) {
    $str = strtolower($str);

    // If it's a "Set", prioritize ml after 'edp' or 'edt'
    if (strpos($str, 'set') !== false) {
        if (preg_match('/(edp|edt)\s*(\d+)\s*ml/', $str, $match)) {
            return intval($match[2]);
        }
    }

    // For regular cases, find the LAST number followed by 'ml'
    if (preg_match_all('/(\d+)\s*ml/', $str, $matches)) {
        // Return the LAST match
        $ml_values = $matches[1];
        return intval(end($ml_values));
    }
    return 0;
}

require_once 'perfume_dates_check_and_delete.php';

?>
