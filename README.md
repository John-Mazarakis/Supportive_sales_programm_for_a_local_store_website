# Sales Program – WooCommerce Promotion Manager

This repository contains a **custom WooCommerce sales engine** built for a local retail store (cosmetics/perfumes).

It provides:

- Admin **HTML panels** to create and manage promotions:
  - Brand-based promotions
  - Price-/category-based promotions
  - Perfume-specific promotions based on **ml size** and category (EDP/EDT, sets, etc.)
- **PHP** backend to:
  - Store promotions in custom tables
  - Fetch brands, categories, tags and product data from the WordPress/WooCommerce DB
  - Compute discounts based on active campaigns
  - Apply discounts by updating `_sale_price` or overriding the cart total
- **JavaScript** to:
  - Hook into the **cart/checkout** page
  - Send current cart (SKU/quantity) to the backend
  - Receive calculated prices and update:
    - Cart total
    - Shipping options
    - “Savings” row in the order summary
