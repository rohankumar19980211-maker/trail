const express = require('express');
const cors = require('cors');
const path = require('path');
const dotenv = require('dotenv');
const dbStore = require('./utils/dbStore');
const { seedDatabase } = require('./utils/seedGenerator');

dotenv.config();

const app = express();
const PORT = process.env.PORT || 5050;

// Middleware
app.use(cors());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Static uploads directory (for box images)
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));

// Mount API routes
app.use('/api/auth', require('./routes/auth'));
app.use('/api/products', require('./routes/products'));
app.use('/api/orders', require('./routes/orders'));

// Health check endpoint
app.get('/api/health', (req, res) => {
  res.json({
    status: 'OK',
    app: 'Box Bulk Retailer API',
    time: new Date().toISOString(),
    totalProducts: dbStore.products.countDocuments(),
    totalUsers: dbStore.users.countDocuments()
  });
});

// Auto-seed database if empty
if (dbStore.products.countDocuments() === 0) {
  console.log('Database empty on startup. Auto-seeding 350+ box catalog items and default accounts...');
  seedDatabase();
}

app.listen(PORT, '0.0.0.0', () => {
  console.log(`=================================================`);
  console.log(`🚀 Box Retailer Backend running on http://localhost:${PORT}`);
  console.log(`📦 Loaded ${dbStore.products.countDocuments()} products in catalog`);
  console.log(`👥 Loaded ${dbStore.users.countDocuments()} employee & admin accounts`);
  console.log(`=================================================`);
});
