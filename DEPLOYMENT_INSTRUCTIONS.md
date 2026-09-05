# 📦 BOXRETAIL - Shared Hosting Deployment Guide (Zero-Config)

This guide provides simple, step-by-step instructions to deploy the **BOXRETAIL** Box Bulk Retailer web application to any standard PHP Shared Hosting provider (such as cPanel, Hostinger, Namecheap, GoDaddy, Bluehost, etc.).

---

## ⚡ Method 1: Zero-Config Direct Zip Upload (Recommended & Fastest)

Because we have bundled a native PHP API fallback layer into `box_bulk_retailer_public_html.zip`, **you DO NOT need Node.js terminal commands or cPanel "Setup Node.js App"!**

### Step 1: Log in to your Web Hosting Control Panel
- Open **cPanel** (or Hostinger hPanel, DirectAdmin, etc.).

### Step 2: Open File Manager
- Navigate to **File Manager** -> `public_html` (or your domain's root directory).
- *Optional:* If there are default files like `index.html` or `default.php` from your web host, delete them.

### Step 3: Upload & Extract the Zip
1. Click **Upload** and select `box_bulk_retailer_public_html.zip` from your computer.
2. Once uploaded, right-click `box_bulk_retailer_public_html.zip` inside `public_html` and select **Extract** (or Extract Here).
3. Confirm all files extract directly into `public_html`. You will see:
   - `.htaccess`
   - `index.html`
   - `assets/`
   - `api/`
   - `data/`

### Step 4: Test Your Live Website!
- Open your domain URL in any web browser (e.g. `https://yourdomain.com`).
- **Default Master Admin Credentials:**
  - **Username:** `admin`
  - **Password:** `admin123`
- **Default Employee Credentials:**
  - **Username:** `emp_john`
  - **Password:** `boxemp123`

---

## 🔧 Method 2: cPanel "Setup Node.js App" (Optional - Only if Node.js is enabled)

If your shared hosting explicitly supports Node.js via cPanel's *Setup Node.js App* (Phusion Passenger), you can also run the original Express Node server:

1. Upload the entire project folder to `/home/username/server_backend`.
2. In cPanel, open **Setup Node.js App**.
3. Create App:
   - **Node.js Version:** `18.x` or `20.x`
   - **Application Mode:** `Production`
   - **Application Root:** `server_backend`
   - **Application Startup File:** `app.js`
   - **Application URL:** Select your domain
4. Click **Run NPM Install** and click **Restart Application**.

---

## 📁 File Package Summary

| File / Archive | Destination Path | Description |
| :--- | :--- | :--- |
| `box_bulk_retailer_public_html.zip` | `public_html/` | Single zip containing complete React UI + PHP API + Pre-seeded 360+ products database |

---

## 🔒 Default Accounts & Features Included

- **360+ Box Catalog items pre-loaded** with dynamic bulk tier pricing in Indian Rupees (`₹ INR`).
- **Interactive Tier Discount Calculator** (`100+ = 5%`, `300+ = 30%`, `500+ = 18%`, `600+ = 20%`).
- **Botpress Chatbot widget** integrated.
- **Admin Portal (`/admin`)** with warehouse stock editing, custom box item creation, employee user creation, and order tracking.
