# PHP E-Commerce Website

A feature-rich e-commerce platform built with **Core PHP 8**, **MySQL**, and **Bootstrap 5**.

## Features

### Frontend (User Side)

- **Home Page** — Hero slider (3 slides), featured products, category grid with smart category-specific icons
- **Product Listing** — Search, filter by category/price/rating, sort, pagination (12/page)
- **Product Details** — Image, description, features, quantity selector, related products, reviews & ratings
- **Shopping Cart** — AJAX add/update/remove/clear, stock validation, CSRF-protected
- **Checkout** — Address form (prefilled), COD & Razorpay online payment, flat ₹50 shipping (free over ₹500)
- **User Auth** — Login/Signup with bcrypt hashing, forgot/reset password via 6-digit email code (10 min expiry)
- **User Dashboard** — Order stats, recent orders, profile management, wishlist, returns
- **Wishlist** — AJAX toggle, dedicated wishlist page with add-to-cart
- **Order Tracking** — Visual timeline (Pending → Processing → Shipped → Delivered → Completed)
- **Product Reviews** — Star rating (1-5), per-purchase validation, one review per product
- **Return Management** — 7-day return window, reason selection, drag-drop image upload, status tracking
- **Dark Mode** — Full theme toggle (frontend + admin), persists via localStorage with `prefers-color-scheme` fallback
- **Multilingual** — English + Hindi, language switcher, file-based translations (`lang/`)

### Admin Panel

- **Dashboard** — Stats cards: products, users, orders, revenue, pending orders; recent 10 orders table
- **Category Management** — Full CRUD via modal forms, active/inactive toggle
- **Product Management** — Add/Edit/Delete, image upload, category assignment, status toggle
- **Order Management** — List with status filters, search by order/customer/email/ID, inline status update
- **Order Details** — Shipping address, payment info (Razorpay transaction ID), items table, status update
- **Return Management** — Filter by status, search, approve/reject, schedule pickup, process refund
- **User Management** — User list with total orders & total spent
- **Dark Mode** — Toggle synced with user preference

## Technology Stack

| Layer    | Technology                     |
| -------- | ------------------------------ |
| Backend  | Core PHP 8 (No Framework)      |
| Database | MySQL / MariaDB with PDO       |
| Frontend | Bootstrap 5.3, Bootstrap Icons |
| Payments | Razorpay (Test Mode)           |
| Server   | Apache via XAMPP               |

## Project Structure

```
phpecommerce/
├── admin/                  # Admin panel (11 files)
│   ├── index.php          # Dashboard
│   ├── login.php          # Admin login
│   ├── logout.php         # Admin logout
│   ├── products.php       # Product listing + delete
│   ├── product-add.php    # Add product
│   ├── product-edit.php   # Edit product
│   ├── categories.php     # Category CRUD
│   ├── orders.php         # Order management
│   ├── order-details.php  # Order details
│   ├── returns.php        # Returns management
│   ├── return-details.php # Return details
│   └── users.php          # User overview
│
├── api/                    # API endpoints (4 files)
│   ├── reviews.php        # Submit/get reviews
│   ├── wishlist.php       # Toggle/get wishlist
│   ├── search.php         # Product search/filter
│   └── language.php       # Language switcher
│
├── assets/                 # Static assets
│   ├── css/
│   │   ├── style.css      # Frontend styles + dark mode
│   │   └── admin.css      # Admin styles + dark mode
│   └── js/
│       ├── main.js        # Frontend JavaScript
│       └── admin.js       # Admin JavaScript
│
├── config/                 # Configuration
│   ├── config.php         # Site config, DB constants, Razorpay keys
│   └── database.php       # PDO singleton
│
├── includes/               # Shared components
│   ├── header.php         # Frontend header + navbar
│   ├── footer.php         # Frontend footer
│   ├── admin_header.php   # Admin header + navbar
│   ├── admin_footer.php   # Admin footer
│   └── functions.php      # Helper functions (~4000 lines)
│
├── lang/                   # Language files
│   ├── en.php             # English translations
│   └── hi.php             # Hindi translations
│
├── user/                   # User dashboard (8 files)
│   ├── dashboard.php      # User dashboard
│   ├── orders.php         # User orders
│   ├── order-details.php  # Order details + timeline
│   ├── profile.php        # Edit profile
│   ├── wishlist.php       # Wishlist page
│   ├── returns.php        # My returns
│   ├── return-request.php # Return request form
│   └── return-details.php # Return status + images
│
├── sql/                    # Database schemas
│   ├── phpecommerce.sql   # Core schema + sample data
│   ├── advanced_features.sql # Brands, subcategories, reviews, wishlist
│   ├── return_tables.sql  # Returns, return_images, status_history
│   ├── migrate_razorpay.sql # Razorpay columns
│   └── insert_translations.php # Import translations to DB
│
├── uploads/                # Uploaded files
│   ├── products/          # Product images
│   ├── categories/        # Category images
│   └── returns/           # Return request images
│
├── index.php              # Home page
├── products.php           # Product listing
├── product-details.php    # Product details
├── cart.php               # Shopping cart
├── cart-action.php        # Cart AJAX actions
├── checkout.php           # Checkout page
├── login.php              # User login
├── signup.php             # User registration
├── logout.php             # User logout
├── forgot-password.php    # Forgot password
├── verify-code.php        # Verify reset code
├── reset-password.php     # Reset password
├── payment-success.php    # Payment success page
├── payment-failure.php    # Payment failure page
├── razorpay-create-order.php # Create Razorpay order
└── razorpay-verify.php    # Verify Razorpay payment
```

## Screenshots - PHP-Ecommerce Website

![alt text](<Screenshot 2026-07-29 112301.png>)

![alt text](<Screenshot 2026-07-29 112900.png>)

## Screenshots - Admin Panel

![alt text](<Screenshot 2026-07-29 113414.png>)

![alt text](<Screenshot 2026-07-29 113710.png>)

## Database Schema (15 tables)

- `users` — Customer accounts
- `admins` — Admin accounts
- `categories` — Product categories
- `products` — Product information (FK to categories, brands, subcategories)
- `brands` — Product brands
- `subcategories` — Product subcategories
- `cart` — Shopping cart items
- `orders` — Customer orders (COD + Razorpay)
- `order_items` — Order line items
- `password_resets` — Password reset codes (10 min TTL)
- `wishlist` — User wishlist items
- `reviews` — Product reviews & ratings (1-5 stars)
- `returns` — Return requests with status workflow
- `return_images` — Return request images
- `return_status_history` — Return status audit log
- `site_translations` — DB-backed translations (alternative to file-based)

## Installation

### Prerequisites

- XAMPP (Apache, MySQL, PHP 7.4+)

### Steps

1. Copy `phpecommerce` folder to `C:\xampp\htdocs\`
2. Start Apache & MySQL via XAMPP Control Panel
3. Open phpMyAdmin, create database `phpecommerce`, import `sql/phpecommerce.sql`
4. (Optional) Import `sql/advanced_features.sql` and `sql/return_tables.sql` for full feature set
5. Access at `http://localhost/phpecommerce/`
6. Admin panel: `http://localhost/phpecommerce/admin/`

### Default Credentials

- **Admin:** admin@ecommerce.com / admin123
- **Razorpay Test:** Use 4111 1111 1111 1111 card with any future expiry and any CVV

## Security

- PDO prepared statements (SQL injection prevention)
- bcrypt password hashing
- Session-based auth (user + admin separate)
- CSRF tokens on all AJAX mutations
- File upload validation (type, size, dimension)
- Input sanitization

## Configuration

Edit `config/config.php`:

- Database credentials
- Site URL (`SITE_URL`)
- Razorpay test/live keys (`RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`)
- Timezone

## Version

**1.0.0** — Last Updated: July 2026
Name: Md Raushan Jilani 
Email: mdraushanji22@gmail.com
