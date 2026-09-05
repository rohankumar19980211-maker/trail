# 🗄️ BOXRETAIL - cPanel MySQL Database Setup Guide

Follow these quick 4 steps to set up your MySQL Database on cPanel / Shared Hosting:

---

## Step 1: Create a MySQL Database in cPanel
1. Log into your **cPanel**.
2. Click **MySQL Database Wizard** (or **MySQL Databases**).
3. Create a new database name: e.g., `boxretail_db`.
4. Create a database user: e.g., `boxretail_user` with a strong password.
5. Grant **ALL PRIVILEGES** to the user for this database.

---

## Step 2: Import `schema.sql` into phpMyAdmin
1. In cPanel, click **phpMyAdmin**.
2. Select your newly created database on the left sidebar.
3. Click the **Import** tab at the top.
4. Click **Choose File** and select `schema.sql` from your project folder.
5. Click **Go** at the bottom.
6. You will see 4 tables created: `users`, `products`, `orders`, `order_items`.

---

## Step 3: Update Credentials in `api/db.php`
Open `api/db.php` on your host (or in your local project before uploading) and update:

```php
$host = 'localhost';
$db   = 'YOUR_CPANEL_USERNAME_boxretail_db'; // Your cPanel Database Name
$user = 'YOUR_CPANEL_USERNAME_boxretail_user'; // Your cPanel Database User
$pass = 'YOUR_DB_PASSWORD';                  // Your cPanel Database Password
```

---

## 🔑 Default Master Admin & Employee Accounts

- **Master Admin:** `admin` | **Password:** `admin123`
- **Employee:** `emp_john` | **Password:** `boxemp123`
- **Employee:** `emp_sarah` | **Password:** `boxemp123`
