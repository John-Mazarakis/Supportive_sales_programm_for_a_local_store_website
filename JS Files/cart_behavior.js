jQuery(document).ready(function($){
    $('#ship-to-different-address-checkbox').prop('checked', false);
});



jQuery(document).ready(function($) {
    // Function to check the total price and change the shipping method
    function updateShippingMethod() {
        // Get the subtotal value (assumes the subtotal is already formatted correctly)
        var subtotalText = $('.cart-subtotal td bdi').text().replace('€', '').replace(',', '.').trim();
        var subtotal = parseFloat(subtotalText);

        // Check if subtotal is a valid number
        if (isNaN(subtotal)) {
            console.warn('Subtotal is not a valid number:', subtotalText);
            return; // Exit function if subtotal is invalid
        }

        // If subtotal is greater than or equal to 49 euros, select appropriate shipping method
        if ($('#payment_method_cheque').is(':checked')) {
            $('#shipping_method_0_local_pickup1').prop('checked', true);
        }
        else if (subtotal >= 49 && ($('#payment_method_eurobank_gateway').is(':checked') ||
                                    $('#payment_method_bacs').is(':checked') ||
                                    $('#payment_method_cod').is(':checked') ||
                                    $('#payment_method_ppcp-gateway').is(':checked'))) {//paypal
            $('#shipping_method_0_free_shipping2').prop('checked', true);
        }
        else {
            // If subtotal is less than 49 euros, select the default shipping method (Speedex)
            $('#shipping_method_0_flat_rate3').prop('checked', true);
        }
    }

    // Run the function on page load
    updateShippingMethod();

    // Run the function every time the checkout is updated (e.g., when items or quantities change)
    $('body').on('updated_checkout', function() {
        updateShippingMethod();
    });
});


//Code for display timologio fields
document.addEventListener("DOMContentLoaded", function () {
    // Get the radio buttons
    const timologio1 = document.getElementById("timologio_1");
    const timologio2 = document.getElementById("timologio_2");
    
    // Get the fields to show/hide
    const optionalFields = [
        document.getElementById("billing_company_field"),
        document.getElementById("drastiriotita_field"),
        document.getElementById("afm_field"),
        document.getElementById("doy_field")
    ];

    // Function to toggle visibility
    function toggleFields() {
        if (timologio2.checked) {
            optionalFields.forEach(field => field.style.display = "block");
        } else {
            optionalFields.forEach(field => field.style.display = "none");
        }
    }

    // Attach event listeners to radio buttons
    timologio1.addEventListener("change", toggleFields);
    timologio2.addEventListener("change", toggleFields);

    // Initial state
    toggleFields();
});



document.addEventListener("DOMContentLoaded", function () {
    const initialQuantities = {}; // Store initial product quantities
    const cartItemsData = []; // Array to store SKU and quantity pairs

    function extractProductData() {
        let quantitiesUnchanged = true; // Flag to check if quantities changed

        const cartItems = document.querySelectorAll(".cart_item");
        cartItemsData.length = 0; // Clear previous data

        cartItems.forEach((item) => {
            const skuElement = item.querySelector(".wd-product-sku span:last-child");
            const sku = skuElement ? skuElement.textContent.trim() : "N/A";

            const quantityElement = item.querySelector(".input-text.qty.text");
            const currentQuantity = quantityElement ? quantityElement.value : "N/A";

            if (initialQuantities[sku] === undefined) {
                initialQuantities[sku] = currentQuantity;
            }

            if (initialQuantities[sku] !== currentQuantity) {
                quantitiesUnchanged = false;
            }

            cartItemsData.push({ sku, quantity: currentQuantity });

            initialQuantities[sku] = currentQuantity;
        });

        if (quantitiesUnchanged) {
            console.log("Quantities are unchanged.");
        }

        // Send SKU and quantity data to PHP
        sendCartData(cartItemsData);
    }

    function sendCartData(cartItems) {
        fetch("https://beautyisland.gr/Sales_Programm/product_data.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(cartItems),
        })
        .then((response) => response.text()) // Get raw response text
        .then((data) => {
            console.log(data); // Log raw response
            try {
                const jsonData = JSON.parse(data); // Attempt to parse JSON
                if (jsonData.final_price !== undefined) {
                    let finalPrice = parseFloat(jsonData.final_price); // Ensure it's a number
                    let subtotalText = document.querySelector('.cart-subtotal td bdi').textContent.replace('€', '').replace(',', '.').trim();
                    let subtotal = parseFloat(subtotalText);
                    let ofelos = 0;
                    ofelos = finalPrice - subtotal;
                    
                    console.log("ofelos", ofelos);

                    if (!isNaN(finalPrice)) {
                        if (finalPrice < 49 && 
                            (document.querySelector('#payment_method_eurobank_gateway')?.checked ||
                             document.querySelector('#payment_method_bacs')?.checked ||
                             document.querySelector('#payment_method_ppcp-gateway')?.checked)) {//paypal
                            finalPrice += 2.8;
                            //jQuery('#shipping_method_0_flat_rate3').prop('checked', true);
                            setTimeout(() => jQuery('#shipping_method_0_flat_rate3').prop('checked', true), 5000);
                        } else if (finalPrice < 49 && document.querySelector('#payment_method_cod')?.checked) {
                            finalPrice += 4.7;
                            //jQuery('#shipping_method_0_flat_rate3').prop('checked', true);
                            setTimeout(() => jQuery('#shipping_method_0_flat_rate3').prop('checked', true), 5000);
                        } else if (finalPrice > 49 && document.querySelector('#payment_method_cod')?.checked) {
                            finalPrice += 1.9;
                        }

                        console.log("Final Price:", finalPrice);
                        updateCartTotal(finalPrice);
                        
                        if (ofelos < 0) {
                            setTimeout(insertDiscountRow, 5000);
                            setTimeout(() => updateDiscountValue(ofelos), 5000);
                            showDiscountRow();
                        } else {
                            hideDiscountRow();
                        }

                    } else {
                        console.error("Invalid final_price received:", jsonData.final_price);
                    }
                }        
            } catch (e) {
                console.error("Error parsing JSON:", e);
            }
        })
        .catch((error) => console.error("Error sending data:", error));
        ofelos = 0;
    }

    extractProductData(); // Run on page load

    document.body.addEventListener("click", function (e) {
        if (e.target.closest(".checkout-order-review")) {
            setTimeout(extractProductData, 3000);
        }
    });
    
    document.body.addEventListener("change", function (e) {
        if (e.target.closest(".checkout-order-review")) {
            setTimeout(extractProductData, 3000);
        }
    });    

    document.body.addEventListener("change", function (e) {
        if (e.target && e.target.classList.contains("input-text.qty.text")) {
            const quantityElement = e.target;
            const skuElement = quantityElement
                .closest(".cart_item")
                .querySelector(".wd-product-sku span:last-child");
            const sku = skuElement ? skuElement.textContent.trim() : null;

            if (sku) {
                initialQuantities[sku] = quantityElement.value;
                console.log(`Updated quantity for SKU ${sku} to ${quantityElement.value}`);
            }
        }
    });
});

function updateCartTotal(newPrice) {
    console.log("Sending new cart total:", newPrice);

    fetch("https://beautyisland.gr/Sales_Programm/cart_price_change.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ price: newPrice }),
    })
    .then((response) => response.json())
    .then((data) => {
        console.log("Server Response:", data);

        if (data.success) {
            console.log("Cart total updated:", data.final_price);
            
            // Force WooCommerce to recalculate totals
            jQuery(document.body).trigger("update_checkout");
            jQuery(document.body).trigger("wc_fragment_refresh");
        } else {
            console.error("Error updating cart total:", data.error);
        }
    })
    .catch(error => console.error("Request failed:", error));
}