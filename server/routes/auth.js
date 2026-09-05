const express = require('express');
const router = express.Router();
const jwt = require('jsonwebtoken');
const bcrypt = require('bcryptjs');
const crypto = require('crypto');
const dbStore = require('../utils/dbStore');
const { authMiddleware, adminOnly, JWT_SECRET } = require('../middleware/authMiddleware');

function boxHashPassword(password) {
  return 'sha256$' + crypto.createHash('sha256').update(password + 'box_salt_2026').digest('hex');
}

async function boxVerifyPassword(password, hash) {
  if (!hash) return false;
  
  if (hash.startsWith('sha256$')) {
    const calc = 'sha256$' + crypto.createHash('sha256').update(password + 'box_salt_2026').digest('hex');
    return hash === calc;
  }
  
  if (hash.startsWith('$2')) {
    try {
      const normalizedHash = hash.replace(/^\$2y\$/, '$2a$');
      if (await bcrypt.compare(password, normalizedHash)) return true;
    } catch (e) {}
  }
  
  return hash === String(password);
}

// POST /api/auth/login - Flexible login for employees and admin
router.post('/login', async (req, res) => {
  try {
    const { username, password } = req.body;
    if (!username || !password) {
      return res.status(400).json({ message: 'Username and password are required' });
    }

    const cleanUsername = username.trim().toLowerCase();
    
    // Case-insensitive user search
    const allUsers = dbStore.users.find({});
    const user = allUsers.find(u => u.username && u.username.trim().toLowerCase() === cleanUsername);

    if (!user) {
      return res.status(401).json({ message: 'Invalid credentials. User not found.' });
    }

    const userPasswordStr = String(user.password || '');
    const isMatch = await boxVerifyPassword(password, userPasswordStr);

    if (!isMatch) {
      return res.status(401).json({ message: 'Invalid credentials. Incorrect password.' });
    }

    const tokenSecret = JWT_SECRET || 'super_secret_box_retailer_key_2026';
    const token = jwt.sign(
      { id: user._id, username: user.username, name: user.name, role: user.role || 'employee' },
      tokenSecret,
      { expiresIn: '24h' }
    );

    return res.json({
      token,
      user: {
        id: user._id,
        username: user.username,
        name: user.name,
        role: user.role || 'employee'
      }
    });
  } catch (err) {
    console.error('🔥 Server Authentication Error:', err);
    return res.status(500).json({ message: `Authentication error: ${err.message || 'Server error'}` });
  }
});

// GET /api/auth/me
router.get('/me', authMiddleware, (req, res) => {
  res.json({ user: req.user });
});

// Admin Route: GET /api/auth/users - List all employee users
router.get('/users', authMiddleware, adminOnly, (req, res) => {
  try {
    const allUsers = dbStore.users.find({});
    const safeUsers = allUsers.map(u => ({
      _id: u._id,
      username: u.username,
      name: u.name,
      role: u.role,
      createdAt: u.createdAt
    }));
    res.json(safeUsers);
  } catch (err) {
    res.status(500).json({ message: 'Failed to fetch users' });
  }
});

// Admin Route: POST /api/auth/users - Create new internal employee account
router.post('/users', authMiddleware, adminOnly, async (req, res) => {
  try {
    const { username, password, name, role = 'employee' } = req.body;
    if (!username || !password || !name) {
      return res.status(400).json({ message: 'Name, username, and password are required' });
    }

    const cleanUsername = username.trim().toLowerCase();
    const existing = dbStore.users.find({}).find(u => u.username && u.username.trim().toLowerCase() === cleanUsername);
    if (existing) {
      return res.status(400).json({ message: 'Username is already taken' });
    }

    const hashedPassword = boxHashPassword(password);
    const newUser = dbStore.users.create({
      username: cleanUsername,
      password: hashedPassword,
      name: name.trim(),
      role: role === 'admin' ? 'admin' : 'employee'
    });

    res.status(201).json({
      _id: newUser._id,
      username: newUser.username,
      name: newUser.name,
      role: newUser.role,
      createdAt: newUser.createdAt
    });
  } catch (err) {
    console.error('Error creating user:', err);
    res.status(500).json({ message: 'Failed to create employee account' });
  }
});

// Admin Route: PUT /api/auth/users/:id/password - Reset employee password
router.put('/users/:id/password', authMiddleware, adminOnly, async (req, res) => {
  try {
    const { oldPassword, newPassword } = req.body;
    if (!oldPassword || !newPassword) {
      return res.status(400).json({ message: 'Both Old Password and New Password are required' });
    }
    if (newPassword.length < 4) {
      return res.status(400).json({ message: 'New password must be at least 4 characters long' });
    }

    const user = dbStore.users.findById(req.params.id);
    if (!user) {
      return res.status(404).json({ message: 'Employee user account not found' });
    }

    const userPasswordStr = String(user.password || '');
    const isOldMatch = await boxVerifyPassword(oldPassword, userPasswordStr);

    if (!isOldMatch) {
      return res.status(400).json({ message: 'Old password does not match current password' });
    }

    const hashedPassword = boxHashPassword(newPassword);
    dbStore.users.findByIdAndUpdate(req.params.id, { password: hashedPassword });

    res.json({ message: 'Password updated successfully' });
  } catch (err) {
    console.error('Error updating password:', err);
    res.status(500).json({ message: 'Failed to update password' });
  }
});

// Admin Route: DELETE /api/auth/users/:id - Delete employee account
router.delete('/users/:id', authMiddleware, adminOnly, (req, res) => {
  try {
    const userToDelete = dbStore.users.findById(req.params.id);
    if (!userToDelete) {
      return res.status(404).json({ message: 'User not found' });
    }
    if (userToDelete.username === 'admin' || userToDelete.role === 'admin') {
      return res.status(400).json({ message: 'Primary administrator account cannot be deleted' });
    }

    dbStore.users.findByIdAndDelete(req.params.id);
    res.json({ message: 'Employee account deleted successfully' });
  } catch (err) {
    res.status(500).json({ message: 'Failed to delete user' });
  }
});

module.exports = router;
