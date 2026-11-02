# Admin panel setup — BlueCircuit Electronics

## Included pages
- `login.php` — administrator login using `users.password_hash`, `users.role`, and `users.status`.
- `setup.php` — one-time setup of the first admin, only while there are no users with `role='admin'`.
- `index.php` — dashboard metrics and recent orders.
- `products.php` — product CRUD, category/brand selection, optional JSON specifications and image upload.
- `categories.php` — category CRUD.
- `orders.php` — view orders and items; manually update order/payment statuses.
- `customers.php` — registered customer list and order totals.
- `settings.php` — edit the four existing store settings.

## Run locally
1. Copy this whole `electronics-store` folder to `C:\xampp\htdocs\electronics-store`.
2. Ensure the existing database is `electronics_store` and `config/database.php` can connect.
3. Visit `http://localhost/electronics-store/admin/setup.php`.
4. Create the first admin with a strong password (minimum 12 characters).
5. **Delete `admin/setup.php` immediately after successful setup.** The page also refuses setup once an admin exists, but deleting it is recommended.
6. Visit `http://localhost/electronics-store/admin/login.php`.

## Important notes
- No database tables are created or dropped by this admin panel. It uses your existing schema.
- Product image uploads are restricted by MIME type and a 4 MB size limit. The upload folder should not allow PHP execution on a production server.
- The dashboard's revenue figure counts orders marked `paid`, excluding cancelled orders. Manually changing an order to paid is not payment verification.
- This is a local development stage. Before public deployment, use HTTPS, keep secrets outside the web root, configure upload execution restrictions, add login throttling/audit logs and review hosting security.
- Store settings' currency field is saved but does not automatically change the existing `money()` formatter, which currently displays KSh.
- The sample admin has not been created in the database. Use the one-time setup page to create it securely.
