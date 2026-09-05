const mongoose = require('mongoose');

const userSchema = new mongoose.Schema({
  username: { type: String, required: true, unique: true },
  password: { type: String, required: true },
  name: { type: String, required: true },
  role: { type: String, enum: ['admin', 'employee'], default: 'employee' }
}, { timestamps: true });

let UserModel;
try {
  UserModel = mongoose.model('User', userSchema);
} catch (e) {
  UserModel = mongoose.model('User');
}

module.exports = UserModel;
