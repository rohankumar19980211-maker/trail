const express = require('express');
const router = express.Router();
const dbStore = require('../utils/dbStore');
const { authMiddleware } = require('../middleware/authMiddleware');

// POST /api/orders - Submit a bulk order & update warehouse inventory stock
router.post('/', authMiddleware, (req, res) => {
  try {
    const { items, deliveryNotes } = req.body;
    if (!items || !Array.isArray(items) || items.length === 0) {
      return res.status(400).json({ message: 'Order must contain at least one item' });
    }

    let processedItems = [];
    let totalQuantity = 0;
    let subtotalAmount = 0;
    let finalAmount = 0;

    for (let item of items) {
      const product = dbStore.products.findById(item.productId);
      if (!product) {
        return res.status(400).json({ message: `Product ${item.productId} not found` });
      }

      const orderQty = Number(item.quantity);
      if (isNaN(orderQty) || orderQty <= 0) {
        return res.status(400).json({ message: `Invalid quantity for item ${product.title}` });
      }

      if (product.availableQuantity < orderQty) {
        return res.status(400).json({
          message: `Insufficient warehouse stock for ${product.title}. Requested: ${orderQty}, Available: ${product.availableQuantity}`
        });
      }

      // Calculate applicable tier discount based on requested quantity
      let applicableDiscountPercent = 0;
      if (product.discountTiers && Array.isArray(product.discountTiers)) {
        // Sort tiers by minQuantity descending to find highest applicable threshold
        const sortedTiers = [...product.discountTiers].sort((a, b) => b.minQuantity - a.minQuantity);
        for (let tier of sortedTiers) {
          if (orderQty >= tier.minQuantity) {
            applicableDiscountPercent = tier.discountPercent;
            break;
          }
        }
      }

      const unitPrice = product.unitPrice;
      const itemSubtotal = unitPrice * orderQty;
      const discountedUnitPrice = unitPrice * (1 - applicableDiscountPercent / 100);
      const itemTotal = discountedUnitPrice * orderQty;

      processedItems.push({
        productId: product._id,
        title: product.title,
        boxSize: product.boxSize,
        unitPrice,
        quantity: orderQty,
        discountPercent: applicableDiscountPercent,
        discountedUnitPrice: parseFloat(discountedUnitPrice.toFixed(2)),
        totalItemPrice: parseFloat(itemTotal.toFixed(2))
      });

      totalQuantity += orderQty;
      subtotalAmount += itemSubtotal;
      finalAmount += itemTotal;

      // Deduct available warehouse stock
      dbStore.products.findByIdAndUpdate(product._id, {
        availableQuantity: product.availableQuantity - orderQty
      });
    }

    const totalSavings = subtotalAmount - finalAmount;
    const orderNumber = `ORD-${Date.now().toString().substr(-6)}`;

    const newOrder = dbStore.orders.create({
      orderNumber,
      userUsername: req.user.username,
      userName: req.user.name,
      items: processedItems,
      totalQuantity,
      subtotalAmount: parseFloat(subtotalAmount.toFixed(2)),
      totalSavings: parseFloat(totalSavings.toFixed(2)),
      finalAmount: parseFloat(finalAmount.toFixed(2)),
      status: 'Processing',
      deliveryNotes: deliveryNotes || ''
    });

    res.status(201).json(newOrder);
  } catch (err) {
    console.error('Error submitting order:', err);
    res.status(500).json({ message: 'Failed to place order' });
  }
});

// GET /api/orders - Get user order history or all orders for Admin
router.get('/', authMiddleware, (req, res) => {
  try {
    let orders;
    if (req.user.role === 'admin') {
      orders = dbStore.orders.find({});
    } else {
      orders = dbStore.orders.find({ username: req.user.username });
    }
    // Sort orders newest first
    orders.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
    res.json(orders);
  } catch (err) {
    res.status(500).json({ message: 'Error retrieving order history' });
  }
});

module.exports = router;
