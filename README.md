# Bulk Box Retailer MERN Stack Application

A full-stack MERN portal built for bulk box retail operations, featuring employee authentication, automated tier discount calculation, warehouse stock tracking, and administrative catalog control.

## 🚀 Key Features

1. **Simple Login Homepage**:
   - Clean employee & admin authentication portal at `/`.
   - Pre-configured demo accounts for 4 employees and 1 Admin with 1-click login buttons.

2. **Employee Accounts**:
   - `admin` / `admin123` (Administrator)
   - `emp_john` / `boxemp123` (Sales)
   - `emp_sarah` / `boxemp123` (Logistics)
   - `emp_alex` / `boxemp123` (Warehouse)
   - `emp_david` / `boxemp123` (Procurement)

3. **Catalog & Bulk Discount Calculator**:
   - Displays listed box products with images, dimensions, sizes (e.g. 12"x12"x12"), base unit prices, and warehouse availability.
   - Interactive live bulk discount calculator showing discount tiers:
     - 100 boxes -> **5% OFF**
     - 300 boxes -> **10% OFF**
     - 500 boxes -> **18% OFF**
     - 600 boxes -> **20% OFF**

4. **Admin Inventory Control Panel**:
   - Add new box products with image URL, size dimensions, unit price, stock, and description.
   - Warehouse stock editor: adjust available warehouse quantities in real-time.
   - Tiered Discount Manager: easily add, remove, or modify discount percentage thresholds per product.
   - **350+ Box Catalog Seeder**: 1-click button to seed 350+ industrial box listings.

---

## 💻 How to Run

### 1. Start Express Backend API (Port 5000)
```bash
cd server
npm start
```

### 2. Start React Frontend (Port 3000)
```bash
cd client
npm run dev
```

Open your browser at `http://localhost:3000`.
