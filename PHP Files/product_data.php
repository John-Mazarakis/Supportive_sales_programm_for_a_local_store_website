<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'get_active_sales_for_cart.php';
include 'price_get_active_sales_for_cart.php';
include 'Database_Connection.php';
// Get JSON data from the request
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    die(json_encode(["error" => "No data received."]));
}

$products = []; // Store results
$leftover_products = []; 
$products_already_in_sale = [];

foreach ($data as $item) {
    $sku = $item['sku'];
    $quantity = $item['quantity'];

    // Query to fetch product data based on SKU
    $sql = "
        SELECT
            pm_regular_price.meta_value AS regular_price,
            pm_sale_price.meta_value AS sale_price,
            (SELECT GROUP_CONCAT(DISTINCT cat.name SEPARATOR ', ') 
             FROM wp_term_relationships tr_cat
             JOIN wp_term_taxonomy tt_cat ON tr_cat.term_taxonomy_id = tt_cat.term_taxonomy_id AND tt_cat.taxonomy = 'product_cat'
             JOIN wp_terms cat ON tt_cat.term_id = cat.term_id
             WHERE tr_cat.object_id = p.ID OR tr_cat.object_id = parent_product.ID
            ) AS categories,
            (SELECT GROUP_CONCAT(DISTINCT tag.name SEPARATOR ', ') 
             FROM wp_term_relationships tr_tag
             JOIN wp_term_taxonomy tt_tag ON tr_tag.term_taxonomy_id = tt_tag.term_taxonomy_id AND tt_tag.taxonomy = 'product_tag'
             JOIN wp_terms tag ON tt_tag.term_id = tag.term_id
             WHERE tr_tag.object_id = p.ID OR tr_tag.object_id = parent_product.ID
            ) AS tags,
            (SELECT GROUP_CONCAT(DISTINCT brand.name SEPARATOR ', ') 
             FROM wp_term_relationships tr_brand
             JOIN wp_term_taxonomy tt_brand ON tr_brand.term_taxonomy_id = tt_brand.term_taxonomy_id AND tt_brand.taxonomy = 'yith_product_brand'
             JOIN wp_terms brand ON tt_brand.term_id = brand.term_id
             WHERE tr_brand.object_id = p.ID OR tr_brand.object_id = parent_product.ID
            ) AS brands
        FROM wp_posts p
        LEFT JOIN wp_postmeta pm_regular_price ON p.ID = pm_regular_price.post_id AND pm_regular_price.meta_key = '_regular_price'
        LEFT JOIN wp_postmeta pm_sale_price ON p.ID = pm_sale_price.post_id AND pm_sale_price.meta_key = '_sale_price'
        LEFT JOIN wp_posts parent_product ON p.post_parent = parent_product.ID AND parent_product.post_type = 'product'
        WHERE (p.post_type = 'product' OR p.post_type = 'product_variation') 
        AND EXISTS (SELECT 1 FROM wp_postmeta pm WHERE pm.post_id = p.ID AND pm.meta_key = '_sku' AND pm.meta_value = ?)
        LIMIT 1;
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $sku);
    $stmt->execute();
    $result = $stmt->get_result();
    

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if ($row['sale_price'] == '') { // Keep condition for sale price
            $products[] = [
                "sku" => $sku,
                "quantity" => $quantity,
                "regular_price" => $row['regular_price'],
                "categories" => $row['categories'],
                "tags" => $row['tags'],
                "brands" => $row['brands'],
            ];
        }
        else{
            $products_already_in_sale[] = [
                "sku" => $sku,
                "quantity" => $quantity,
                "regular_price" => $row['sale_price'],
                "categories" => $row['categories'],
                "tags" => $row['tags'],
                "brands" => $row['brands'],
            ];
        }
    } else {
        $products[] = ["sku" => $sku, "error" => "Product not found"];
    }

    $stmt->close();
}

$conn->close();

$calculation_quantity_list = [];
$calculation_price_list = [];

foreach ($products as &$product) {
    $sum1 = 0;
    foreach ($salesData as &$sale) {
        if(strpos($sale['brands_in_sale'], $product['brands']) !== false){
            if(sizeof($calculation_quantity_list) > 0){
                $sum2 = 0;
                foreach($calculation_quantity_list as &$calc){
                    if ($sale['id'] == $calc['sale_id']) {
                        $calc['quantity'] = $calc['quantity'] + $product['quantity'];
                        $calc['total_price'] = $calc['total_price'] + ($product['regular_price'] * $product['quantity']);
                        break;
                    }
                    else{
                        $sum2 = $sum2 + 1; 
                    }
                }
                if($sum2 == sizeof($calculation_quantity_list)){
                    add_new_calc_to_calculation_quantity_list($sale['id'],$product['quantity'],$product['regular_price'],$sale['sale_percentage']);
                }
            }
            else{
                add_new_calc_to_calculation_quantity_list($sale['id'],$product['quantity'],$product['regular_price'],$sale['sale_percentage']);
            }
        }
        else{
            $sum1 = $sum1 + 1;
        }
    }
    if ($sum1 == sizeof($salesData)){
        $leftover_products[] = $product;
    }
}

foreach($leftover_products as &$product_left){
    #error_log('products_left = ' . print_r($product_left['sku'], true));
    $leftover_sum = 0;
    foreach($salesDataPrice as &$sale_by_price){
        $brands_in_sale = !empty($sale_by_price['brands_in_sale']);
        $categories_in_sale = !empty($sale_by_price['categories_in_sale']);
        $Product_categories = explode(',',$product_left['categories']);
        $product_in_sale = false;
        $temp_sum = 0;
        $bitCode = ($brands_in_sale << 1) | $categories_in_sale;
        switch ($bitCode) {
            case 1: // 01 -> Brands false, Categories true
                error_log('Case 1');
                foreach($Product_categories as &$product_category){
                    if(strpos($sale_by_price['categories_in_sale'], $product_category) !== false){
                        processFunction($sale_by_price, $product_left);
                    }
                    else{
                        $temp_sum ++;
                    }
                }
                if(sizeof($Product_categories) == $temp_sum){
                    $leftover_sum ++;
                }
                break;
            case 2: // 10 -> Brands true, Categories false
                error_log('Case 2');
                if(strpos($sale_by_price['brands_in_sale'], $product_left['brands']) !== false){
                    processFunction($sale_by_price, $product_left);
                }
                else{
                    $leftover_sum ++;
                }
                break;
            case 3: // 11 -> Brands true, Categories true
                error_log('Case 3');
                if(strpos($sale_by_price['brands_in_sale'], $product_left['brands']) !== false){
                    error_log('product = ' . print_r($product_left['sku'], true));
                    error_log('size_of_product_categories = ' . print_r(sizeof($Product_categories), true));
                    foreach($Product_categories as &$product_category){
                        if(strpos($sale_by_price['categories_in_sale'], $product_category) !== false){
                            processFunction($sale_by_price, $product_left);
                        }
                        else{
                            $temp_sum ++;
                        }
                    }
                    if(sizeof($Product_categories) == $temp_sum){
                        $leftover_sum ++;
                    }
                }
                else{
                    $leftover_sum ++;
                }
                break;
        }
    }
    if(sizeof($salesDataPrice) == $leftover_sum){
        $sum3 = 0;
        if(sizeof($calculation_quantity_list) > 0){
            foreach($calculation_quantity_list as &$calc){
                if ($calc['sale_id'] == 0) {
                    $calc['quantity'] = $calc['quantity'] + $product_left['quantity'];
                    $calc['total_price'] = $calc['total_price'] + ($product_left['regular_price'] * $product_left['quantity']);
                    break;
                }
                $sum3 = $sum3 + 1; 
            }
            if ($sum3 == sizeof($calculation_quantity_list)){
                add_new_calc_to_calculation_quantity_list(0,$product_left['quantity'],$product_left['regular_price'],0);
            }
        }
        else{
            add_new_calc_to_calculation_quantity_list(0,$product_left['quantity'],$product_left['regular_price'],0);
        }
    }
}

foreach($products_already_in_sale as &$product) { //It can stay like this but do we want it?
    $sum3 = 0;
    if(sizeof($calculation_quantity_list) > 0){
        foreach($calculation_quantity_list as &$calc){
            if ($calc['sale_id'] == 0) {
                $calc['quantity'] = $calc['quantity'] + $product['quantity'];
                $calc['total_price'] = $calc['total_price'] + ($product['regular_price'] * $product['quantity']);
                break;
            }
            $sum3 = $sum3 + 1; 
        }
        if ($sum3 == sizeof($calculation_quantity_list)){
            add_new_calc_to_calculation_quantity_list(0,$product['quantity'],$product['regular_price'],0);
        }
    }
    else{
        add_new_calc_to_calculation_quantity_list(0,$product['quantity'],$product['regular_price'],0);
    }
}

error_log('size_of_quantity = ' . print_r(sizeof($calculation_quantity_list), true));
error_log('size_of_price = ' . print_r(sizeof($calculation_price_list), true));

$final_price = 0;
foreach($calculation_quantity_list as &$calc){
    if ($calc['sale_id'] != 0 && $calc['quantity'] >= 2) {
        $final_price = $final_price + ($calc['total_price'] - ($calc['total_price'] * $calc['sale_percentage'] / 100));
    }
    else{
        $final_price = $final_price + $calc['total_price'];
    }
}

foreach($calculation_price_list as &$calc){
    if($calc['total_price'] >= $calc['sale_price']){
        $final_price = $final_price + ($calc['total_price'] - ($calc['total_price'] * $calc['sale_percentage'] / 100));
    }
    else{
        $final_price = $final_price + $calc['total_price'];
    }
}
// Send final price back to JavaScript
header("Content-Type: application/json");
echo json_encode(["final_price" => $final_price]);

function add_new_calc_to_calculation_quantity_list($id,$quantity,$price,$percentage){
    global $calculation_quantity_list;
    $price = $price * $quantity;
    $calculation_quantity_list[] = [
        'sale_id' => $id,
        'quantity' => $quantity,
        'total_price' => $price,
        'sale_percentage' => $percentage
    ];
}

function add_new_calc_to_calculation_price_list($id,$quantity,$price,$sale_price,$percentage){
    global $calculation_price_list;
    $price = $price * $quantity;
    $calculation_price_list[] = [
        'sale_id' => $id,
        'quantity' => $quantity,
        'total_price' => $price,
        'sale_price' => $sale_price,
        'sale_percentage' => $percentage
    ];
}

function processFunction($sale_by_price, $product_left){
    global $calculation_price_list;
    if(sizeof($calculation_price_list) > 0){
        $sum = 0;
        foreach($calculation_price_list as &$calc){
            if ($sale_by_price['id'] == $calc['sale_id']) {
                $calc['quantity'] = $calc['quantity'] + $product_left['quantity'];
                $calc['total_price'] = $calc['total_price'] + ($product_left['regular_price'] * $product_left['quantity']);
                break;
            }
            else{
                $sum = $sum + 1; 
            }
        }
        if($sum == sizeof($calculation_price_list)){
            add_new_calc_to_calculation_price_list($sale_by_price['id'],$product_left['quantity'],$product_left['regular_price'],$sale_by_price['sale_price'],$sale_by_price['sale_percentage']);
        }
    }
    else{
        add_new_calc_to_calculation_price_list($sale_by_price['id'],$product_left['quantity'],$product_left['regular_price'],$sale_by_price['sale_price'],$sale_by_price['sale_percentage']);
    }
}

?>
