const express = require('express');
const router = express.Router();
const dbStore = require('../utils/dbStore');
const { authMiddleware, adminOnly } = require('../middleware/authMiddleware');
const { seedDatabase } = require('../utils/seedGenerator');

// GET /api/products - Get product list with search and filters
router.get('/', (req, res) => {
  try {
    let { search, category, sizeCategory, page = 1, limit = 50 } = req.query;
    page = parseInt(page);
    limit = parseInt(limit);

    let items = dbStore.products.find({ search, category, sizeCategory });

    const total = items.length;
    const startIndex = (page - 1) * limit;
    const paginatedItems = items.slice(startIndex, startIndex + limit);

    res.json({
      total,
      page,
      totalPages: Math.ceil(total / limit),
      count: paginatedItems.length,
      products: paginatedItems
    });
  } catch (err) {
    console.error('Error fetching products:', err);
    res.status(500).json({ message: 'Error retrieving products' });
  }
});

// GET /api/products/:id - Get single product
router.get('/:id', (req, res) => {
  try {
    const product = dbStore.products.findById(req.params.id);
    if (!product) {
      return res.status(404).json({ message: 'Box product not found' });
    }
    res.json(product);
  } catch (err) {
    res.status(500).json({ message: 'Error fetching product' });
  }
});

// POST /api/products/seed - Trigger database re-seeding
router.post('/seed', (req, res) => {
  try {
    const result = seedDatabase();
    res.json({ message: 'Database successfully seeded!', ...result });
  } catch (err) {
    console.error('Seeding error:', err);
    res.status(500).json({ message: 'Failed to seed database' });
  }
});

// POST /api/products - Admin add box product
router.post('/', authMiddleware, adminOnly, (req, res) => {
  try {
    const {
      title,
      boxSize,
      length,
      width,
      height,
      category,
      sizeCategory,
      description,
      unitPrice,
      availableQuantity,
      image,
      discountTiers
    } = req.body;

    if (!title || !boxSize || unitPrice === undefined || availableQuantity === undefined) {
      return res.status(400).json({ message: 'Title, box size, unit price, and available quantity are required' });
    }

    const sku = `BOX-CUSTOM-${Date.now().toString().substr(-6)}`;

    // Default tiers if not provided
    const defaultTiers = [
      { minQuantity: 100, discountPercent: 5 },
      { minQuantity: 300, discountPercent: 10 },
      { minQuantity: 500, discountPercent: 18 },
      { minQuantity: 600, discountPercent: 20 }
    ];

    const newProduct = dbStore.products.create({
      sku,
      title,
      boxSize,
      length: Number(length) || 12,
      width: Number(width) || 12,
      height: Number(height) || 12,
      category: category || 'Corrugated Cartons',
      sizeCategory: sizeCategory || 'Medium',
      description: description || 'Industrial corrugated box for bulk storage.',
      unitPrice: Number(unitPrice),
      availableQuantity: Number(availableQuantity),
      image: image || 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80',
      discountTiers: (discountTiers && discountTiers.length > 0) ? discountTiers : defaultTiers
    });

    res.status(201).json(newProduct);
  } catch (err) {
    console.error('Error creating box product:', err);
    res.status(500).json({ message: 'Failed to create product' });
  }
});

// PUT /api/products/:id - Admin update box details & discount tiers
router.put('/:id', authMiddleware, adminOnly, (req, res) => {
  try {
    const product = dbStore.products.findById(req.params.id);
    if (!product) {
      return res.status(404).json({ message: 'Box product not found' });
    }

    const updated = dbStore.products.findByIdAndUpdate(req.params.id, req.body);
    res.json(updated);
  } catch (err) {
    console.error('Error updating product:', err);
    res.status(500).json({ message: 'Failed to update product' });
  }
});

// PATCH /api/products/:id/stock - Admin change warehouse stock quantity
router.patch('/:id/stock', authMiddleware, adminOnly, (req, res) => {
  try {
    const { availableQuantity } = req.body;
    if (availableQuantity === undefined || isNaN(availableQuantity)) {
      return res.status(400).json({ message: 'Valid availableQuantity is required' });
    }

    const updated = dbStore.products.findByIdAndUpdate(req.params.id, {
      availableQuantity: Number(availableQuantity)
    });

    if (!updated) {
      return res.status(404).json({ message: 'Box product not found' });
    }

    res.json({ message: 'Warehouse stock updated successfully', product: updated });
  } catch (err) {
    res.status(500).json({ message: 'Failed to update stock quantity' });
  }
});

// DELETE /api/products/:id - Admin delete product
router.delete('/:id', authMiddleware, adminOnly, (req, res) => {
  try {
    const deleted = dbStore.products.findByIdAndDelete(req.params.id);
    if (!deleted) {
      return res.status(404).json({ message: 'Box product not found' });
    }
    res.json({ message: 'Box product deleted successfully' });
  } catch (err) {
    res.status(500).json({ message: 'Failed to delete product' });
  }
});

module.exports = router;
