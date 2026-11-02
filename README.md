# BlueCircuit Electronics — PHP & MySQL portfolio demo

A staged e-commerce application foundation for XAMPP. This initial release contains the database schema, shared PHP utilities, storefront shell and a working featured-product homepage. The customer catalogue, search, category filtering, sorting, pagination and product detail pages are included. Cart, checkout, authentication, product CRUD and admin dashboard are planned for subsequent stages.

## Requirements
- XAMPP with PHP 8.1+ and MySQL/MariaDB
- PHP PDO MySQL extension enabled
- A modern browser
- Internet access for the Bootstrap CDN (or download Bootstrap locally)

## Install on XAMPP
1. Copy the `electronics-store` folder into `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`.
4. Choose **Import**, select `database/schema.sql`, then run the import.
   - The SQL creates a database named `electronics_store`.
   - **Warning:** this starter schema drops and recreates its tables. Do not import it into a database containing data you need.
5. Check `config/database.php`. The default settings are for a typical local XAMPP install (`root`, empty password). If you configured a MySQL password, set `DB_PASS` in your PHP environment or edit the local configuration.
6. Open `http://localhost/electronics-store/`.

## Current scope
- Blue-and-white responsive storefront shell
- Product catalogue with keyword search, category filtering, sorting and pagination
- Product detail pages with stock, SKU, brand, price and optional JSON specifications
- Database connection using PDO, exceptions and native prepared statements
- HTML escaping helper, currency formatter and CSRF-token helpers
- Normalized starter schema for users, addresses, brands, categories, products, images, carts, orders, order items, payments and settings
- Four sample products and four categories for development

## Important limitations in this stage
- Admin login and CRUD are not implemented yet; the “Admin demo” link is a placeholder for the next stage.
- Cart, checkout and WhatsApp order generation are not implemented yet.
- M-Pesa credentials and callbacks are not configured; no real payments are taken.
- Sample products and prices are fictional demo data.
- Do not deploy publicly until authentication, authorization, validation, upload controls, CSRF protection and production secrets are fully implemented.
- The schema is designed as a starter, not a production audit of tax, privacy, consumer protection or payment compliance.

## Suggested build stages
1. Foundation and schema (this release)
2. Storefront catalogue, search, product detail and category filters
3. Secure admin login and product/category CRUD
4. Session cart and checkout with server-side price/stock validation
5. Order management, customer accounts and WhatsApp order handoff
6. Payment integration, reports, tests and deployment hardening


## Stage 3 — Admin panel
The `admin/` folder now includes login, one-time first-admin setup, dashboard, product/category management, order management, customer listing and store settings. See `admin/README.md` for setup instructions. The admin panel uses the existing schema and does not re-import or modify tables.


## Stage 3.1 additions
- Customer cart and checkout with stock validation and manual M-Pesa reference capture.
- Customer registration/login/profile and order history.
- Home-page category/latest-product, trust, CTA and FAQ sections.
- Contact page, FAQ page, editable business location/working hours.
- Admin inventory, media library, manual payment approval and SEO settings.
- XML sitemap endpoint and robots.txt.

### Important setup notes
1. Back up your current project folder before copying these files over it. Keep your existing database; do not re-import `database/schema.sql` into a database with data because that file drops and recreates tables.
2. Customer cart data is session-based in this starter. Customers should log in before checkout if they want the order visible in their account history. Guest checkout is allowed, but guest orders cannot be tied to a customer profile.
3. Manual M-Pesa approval is not automated Daraja API integration. Verify each payment against the official M-Pesa message/records before approving it.
4. Update the payment instructions, WhatsApp number, business address, working hours, and public domain under Admin → Store settings before launch.
5. Delete `admin/setup.php` after creating the first administrator. Use HTTPS and production credentials when hosting.
6. Contact form currently validates and displays a demo confirmation; it does not send email or persist messages because the existing schema has no contact-messages table. Connect a mail provider or add a table before production.


## Stage 3.2 corrections and enhancements
- Footer now loads the shared store helper before calling `setting()`, fixing the undefined-function error on the home and shop pages.
- Home-page category sections now show products beneath the category heading instead of displaying generic category tiles.
- Admin → Categories includes a “Show this category and its products on the home page” checkbox.
- Light/dark theme toggle is available in the storefront and admin panel, including admin login/setup; the preference is remembered in the browser.

### Required migration for an existing database
Run `database/migrations/20261027_category_home_sections.sql` once in phpMyAdmin while `electronics_store` is selected. This adds `categories.show_on_home` and initially enables the Accessories and Audio sections. Do not re-import `database/schema.sql` for this update. After the migration, use Admin → Categories to choose which categories appear on the home page.

## Stage 4.1 storefront and admin enhancements

This update adds the retail-style storefront header, top links, product/brand/SKU search, logo display, account/cart controls, editable homepage slides, About Us and Locations pages, social links, uniform product cards, five-across product rails, image zoom, related products, customer reviews with admin moderation, customer profile photos, and inline brand creation while editing a product.

### Existing database migration

Back up the `electronics_store` database first. Keep your existing database and `config/database.php`; do not import `database/schema.sql` into an existing installation because it recreates tables. In phpMyAdmin, select `electronics_store`, open SQL, and run `database/migrations/20261102_storefront_enhancements.sql` once. This adds `users.profile_image` and creates `product_reviews`.

The Stage 3.2 category-home migration (`20261027_category_home_sections.sql`) must already have been applied from the earlier stage. If it has not, apply it once as well.

### Configure the storefront

Open Admin → Store settings. Add the business logo, announcement text, social profile URLs, About Us content, location/map link, and up to three homepage slides with images, headings, subtitles and button destinations. Use an international-format WhatsApp number with country code and no plus sign or spaces (for example `2547XXXXXXXX`). Customers can upload their profile photo from My Account after registering. Product reviews are submitted by signed-in customers and remain pending until approved under Admin → Product reviews.

### Important notes

- Homepage slider images and logo uploads accept JPG, PNG and WEBP up to 5 MB.
- Customer profile photos accept JPG, PNG and WEBP up to 3 MB.
- Reviews are limited to one review per customer per product and require admin approval.
- Search covers product names, descriptions, SKU and brand names.
- The theme button has intentionally been left as it was in Stage 3.3, per the project decision.
