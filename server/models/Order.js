const mongoose = require('mongoose');

const orderItemSchema = new mongoose.Schema({
  productId: { type: String, required: true },
  title: { type: String, required: true },
  boxSize: { type: String, required: true },
  unitPrice: { type: Number, required: true },
  quantity: { type: Number, required: true },
  discountPercent: { type: Number, default: 0 },
  discountedUnitPrice: { type: Number, required: true },
  totalItemPrice: { type: Number, required: true }
}, { _id: false });

const orderSchema = new mongoose.Schema({
  orderNumber: { type: String, required: true },
  userUsername: { type: String, required: true },
  userName: { type: String, required: true },
  items: [orderItemSchema],
  totalQuantity: { type: Number, required: true },
  subtotalAmount: { type: Number, required: true },
  totalSavings: { type: Number, required: true },
  finalAmount: { type: Number, required: true },
  status: { type: String, enum: ['Pending', 'Processing', 'Dispatched', 'Delivered'], default: 'Processing' },
  deliveryNotes: { type: String, default: '' }
}, { timestamps: true });

let OrderModel;
try {
  OrderModel = mongoose.model('Order', orderSchema);
} catch (e) {
  OrderModel = mongoose.model('Order');
}

module.exports = OrderModel;
