# PHP E-Commerce Website

A complete, professional, and production-ready E-commerce website built with **Core PHP**, **MySQL**, and **Bootstrap 5**.

## ✨ Features

### Frontend (User Side)
- 🏠 **Home Page** with banner slider, featured products, and categories
- 📦 **Product Listing** with search, filter by category, and pagination
- 🔍 **Product Details** with image gallery and add to cart
- 🛒 **Shopping Cart** with update quantity and remove items
- 💳 **Checkout** with delivery information form and COD payment
- 👤 **User Authentication** (Login/Signup with password hashing)
- 📊 **User Dashboard** with order history and profile management
- 📱 **Fully Responsive** - Mobile, Tablet, and Desktop optimized

### Admin Panel
- 📈 **Dashboard** with statistics (products, users, orders, revenue)
- 📂 **Category Management** (Add, Edit, Delete)
- 📦 **Product Management** (Add, Edit, Delete with multiple images)
- 📋 **Order Management** (View, Update Status: Pending → Processing → Completed)
- 👥 **User Management** (View all users with order statistics)
- 🔐 **Secure Admin Authentication**

## 🛠 Technology Stack

- **Backend:** Core PHP (No Framework)
- **Database:** MySQL with PDO
- **Frontend:** Bootstrap 5
- **Icons:** Bootstrap Icons
- **Security:** PDO Prepared Statements, Password Hashing, Session Management

## 📁 Project Structure

```
phpecommerce/
├── admin/                  # Admin panel files
│   ├── index.php          # Admin dashboard
│   ├── login.php          # Admin login
│   ├── categories.php     # Category management
│   ├── products.php       # Product listing
│   ├── product-add.php    # Add product
│   ├── product-edit.php   # Edit product
│   ├── orders.php         # Order management
│   ├── order-details.php  # Order details
│   ├── users.php          # User management
│   └── logout.php         # Admin logout
│
├── assets/                # Static assets
│   ├── css/
│   │   ├── style.css      # Main stylesheet
│   │   └── admin.css      # Admin panel stylesheet
│   ├── js/
│   │   ├── main.js        # Main JavaScript
│   │   └── admin.js       # Admin JavaScript
│   └── images/            # Static images
│
├── config/                # Configuration files
│   ├── config.php         # Site configuration
│   └── database.php       # Database connection
│
├── includes/              # Reusable components
│   ├── header.php         # User header
│   ├── footer.php         # User footer
│   ├── admin_header.php   # Admin header
│   ├── admin_footer.php   # Admin footer
│   └── functions.php      # Helper functions
│
├── user/                  # User dashboard files
│   ├── dashboard.php      # User dashboard
│   ├── orders.php         # User orders
│   ├── order-details.php  # Order details
│   └── profile.php        # User profile
│
├── uploads/               # Uploaded files
│   ├── products/          # Product images
│   └── categories/        # Category images
│
├── index.php              # Home page
├── products.php           # Product listing
├── product-details.php    # Product details
├── cart.php               # Shopping cart
├── cart-action.php        # Cart actions (add/update/delete)
├── checkout.php           # Checkout page
├── login.php              # User login
├── signup.php             # User registration
├── logout.php             # User logout
├── database.sql           # Database schema
└── README.md              # This file
```

## 🚀 Installation Guide

### Prerequisites
- **XAMPP** (Apache, MySQL, PHP 7.4+) or similar stack
- Web browser

### Step-by-Step Installation

1. **Download and Install XAMPP**
   - Download from: https://www.apachefriends.org/
   - Install and start Apache and MySQL services

2. **Place Project Files**
   ```
   Copy the 'phpecommerce' folder to:
   C:\xampp\htdocs\phpecommerce
   ```

3. **Create Database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Click "New" to create a database
   - Name it: `phpecommerce`
   - Go to "Import" tab
   - Choose file: `C:\xampp\htdocs\phpecommerce\database.sql`
   - Click "Go" to import

4. **Configure Database Connection**
   - Open: `config/config.php`
   - Update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'phpecommerce');
   ```

5. **Set Folder Permissions**
   - Ensure the `uploads/` folder has write permissions

6. **Access the Website**
   - **Frontend:** http://localhost/phpecommerce/
   - **Admin Panel:** http://localhost/phpecommerce/admin/

## 🔐 Default Credentials

### Admin Login
- **URL:** http://localhost/phpecommerce/admin/login.php
- **Email:** admin@ecommerce.com
- **Password:** admin123

### Test User (You can create new users via signup)
- Create your own account through the signup page

## 📋 Database Schema

The system includes 8 main tables:

1. **users** - Customer accounts
2. **admins** - Admin accounts
3. **categories** - Product categories
4. **products** - Product information
5. **product_images** - Product image gallery
6. **cart** - Shopping cart items
7. **orders** - Customer orders
8. **order_items** - Order line items

## 🎯 Key Features Explained

### Security Features
- ✅ PDO Prepared Statements (SQL Injection Prevention)
- ✅ Password Hashing (bcrypt)
- ✅ Session Management
- ✅ Input Sanitization
- ✅ CSRF Protection Ready

### User Features
- ✅ User Registration & Login
- ✅ Profile Management
- ✅ Shopping Cart with Quantity Update
- ✅ Order Placement with COD
- ✅ Order History & Tracking
- ✅ Product Search & Filter

### Admin Features
- ✅ Complete Dashboard with Statistics
- ✅ Category CRUD Operations
- ✅ Product CRUD with Multiple Image Upload
- ✅ Order Status Management
- ✅ User Overview

### UI/UX Features
- ✅ Bootstrap 5 Responsive Design
- ✅ Mobile-First Approach
- ✅ Smooth Animations
- ✅ Flash Messages
- ✅ Form Validation
- ✅ Product Image Gallery

## 🌐 Sample Data

The database.sql file includes:
- 1 Admin account
- 5 Sample categories
- 9 Sample products

## 📱 Responsive Design

The website is fully responsive and tested on:
- 📱 Mobile devices (320px and up)
- 📱 Tablets (768px and up)
- 💻 Desktops (1024px and up)
- 🖥️ Large screens (1200px and up)

## 🔧 Configuration

### Site URL Configuration
Edit `config/config.php` to change the site URL:
```php
define('SITE_URL', 'http://localhost/phpecommerce');
```

### Upload Limits
To increase file upload size, edit `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

## 📝 Usage Guide

### For Customers:
1. Browse products on home page
2. Click on products to view details
3. Add products to cart
4. Update quantities or remove items in cart
5. Proceed to checkout
6. Enter shipping information
7. Place order (COD)
8. Track orders in user dashboard

### For Administrators:
1. Login to admin panel
2. View dashboard statistics
3. Manage categories, products, orders, and users
4. Update order status
5. Add/Edit/Delete products with images
6. Monitor user activities

## 🛡️ Security Best Practices

- Change default admin password immediately
- Use HTTPS in production
- Enable CSRF tokens for forms
- Regular database backups
- Keep PHP and MySQL updated
- Use strong passwords

## 🐛 Troubleshooting

### Common Issues:

**Issue:** Cannot connect to database
- **Solution:** Check database credentials in `config/config.php`

**Issue:** Images not displaying
- **Solution:** Check folder permissions for `uploads/` directory

**Issue:** Session errors
- **Solution:** Ensure `session_start()` is not called multiple times

**Issue:** File upload fails
- **Solution:** Check PHP upload limits in `php.ini`

## 📧 Support

For issues or questions:
- Check the troubleshooting section
- Review the code comments
- Verify database schema is properly imported

## 📄 License

This project is created for educational purposes. Feel free to use and modify.

## 🎉 Features Coming Soon

- Email notifications
- Payment gateway integration
- Product reviews and ratings
- Wishlist functionality
- Advanced search filters
- Multi-language support

---

**Built with ❤️ using Core PHP, MySQL, and Bootstrap 5**

**Version:** 1.0.0  
**Last Updated:** February 9, 2026
