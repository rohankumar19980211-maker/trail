const mongoose = require('mongoose');

const discountTierSchema = new mongoose.Schema({
  minQuantity: { type: Number, required: true },
  discountPercent: { type: Number, required: true }
}, { _id: false });

const productSchema = new mongoose.Schema({
  title: { type: String, required: true },
  boxSize: { type: String, required: true }, // e.g. "12\" x 12\" x 12\""
  length: { type: Number, required: true },  // inches
  width: { type: Number, required: true },   // inches
  height: { type: Number, required: true },  // inches
  category: { 
    type: String, 
    enum: ['Corrugated Cartons', 'Heavy-Duty Moving', 'Corrugated Mailers', 'Die-Cut / Gift Boxes', 'Telescopic & Special'],
    default: 'Corrugated Cartons' 
  },
  sizeCategory: {
    type: String,
    enum: ['Small', 'Medium', 'Large', 'Extra Large'],
    default: 'Medium'
  },
  description: { type: String, default: '' },
  unitPrice: { type: Number, required: true }, // Base price per unit ($)
  availableQuantity: { type: Number, required: true, default: 0 }, // Warehouse inventory stock
  image: { type: String, required: true }, // Image URL or upload path
  sku: { type: String, required: true },
  discountTiers: {
    type: [discountTierSchema],
    default: [
      { minQuantity: 100, discountPercent: 5 },
      { minQuantity: 300, discountPercent: 10 },
      { minQuantity: 500, discountPercent: 18 },
      { minQuantity: 600, discountPercent: 20 }
    ]
  }
}, { timestamps: true });

let ProductModel;
try {
  ProductModel = mongoose.model('Product', productSchema);
} catch (e) {
  ProductModel = mongoose.model('Product');
}

module.exports = ProductModel;
