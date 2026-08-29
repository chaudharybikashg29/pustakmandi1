# PustakMandi — Setup Guide (Phase 1: Core Foundation)

This zip contains the **foundation** of the site: database, config, shared
includes, styling, and full authentication (register/login/logout). The
admin, seller, and customer panels are placeholders for now — I'll build
those next in the same order shown in your folder tree.

## 1. Install into XAMPP
Extract this zip so the contents land at:
```
C:\xampp\htdocs\pustakmandi\
```

## 2. Create the database
1. Start Apache and MySQL in the XAMPP Control Panel.
2. Open http://localhost/phpmyadmin
3. Click **Import** → choose `database/pustakmandi.sql` → Go.
   (This creates the `pustakmandi` database, all tables, sample categories,
   and a placeholder admin user.)

## 3. Fix the admin password
The password hash in the SQL file is a placeholder. Set the real one by
visiting, once:
```
http://localhost/pustakmandi/reset-admin-password.php
```
This sets:
- **Email:** admin@pustakmandi.com
- **Password:** Admin@123

**Delete `reset-admin-password.php` after running it once.**

## 4. Check your DB config
Open `config/db.php` and confirm `DB_USER` / `DB_PASS` match your MySQL
setup (defaults `root` / empty password work for a stock XAMPP install).

## 5. Try it out
- http://localhost/pustakmandi/index.php — homepage
- http://localhost/pustakmandi/register.php — create a customer or seller account
- http://localhost/pustakmandi/login.php — log in
- http://localhost/pustakmandi/contact.php — sends messages into `contact_messages` table

Logging in currently redirects to `admin/dashboard.php`, `seller/dashboard.php`,
or `customer/dashboard.php` depending on role — those files don't exist yet,
so you'll get a 404 until the next phase. That's expected.

## What's included
- `database/pustakmandi.sql` — full schema (users, categories, products, cart,
  wishlist, orders, order_items, contact_messages) + seed categories + admin user
- `config/db.php` — PDO connection + BASE_URL constant
- `includes/functions.php` — sanitizing, flash messages, price/date formatting,
  slug helper, image upload helper, pagination helper
- `includes/auth.php` — session handling, login/logout, `require_login()`,
  `require_role()`, role-based dashboard redirect
- `includes/header.php`, `navbar.php`, `footer.php` — shared layout, role-aware nav
- `assets/css/style.css` — full custom design system (no framework)
- `assets/js/main.js` — flash auto-hide + confirm-before-delete helper
- `index.php`, `about.php`, `contact.php`, `login.php`, `register.php`, `logout.php`

## Next phases (say "continue" when ready)
1. **Customer panel** — browse/search books, product details, cart, wishlist, orders, profile
2. **Seller panel** — dashboard, add/edit/list products, view orders, profile/settings
3. **Admin panel** — dashboard, manage users/sellers/customers, products, categories, orders, reports, settings
