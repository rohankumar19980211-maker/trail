const fs = require('fs');
const path = require('path');

const DB_DIR = path.join(__dirname, '../data');
if (!fs.existsSync(DB_DIR)) {
  fs.mkdirSync(DB_DIR, { recursive: true });
}

class JsonCollection {
  constructor(name) {
    this.name = name;
    this.filePath = path.join(DB_DIR, `${name}.json`);
    this._load();
  }

  _load() {
    try {
      if (fs.existsSync(this.filePath)) {
        const raw = fs.readFileSync(this.filePath, 'utf8');
        this.data = JSON.parse(raw);
      } else {
        this.data = [];
        this._save();
      }
    } catch (e) {
      this.data = [];
    }
  }

  _save() {
    try {
      fs.writeFileSync(this.filePath, JSON.stringify(this.data, null, 2));
    } catch (e) {
      console.error(`Error writing ${this.name} JSON file:`, e);
    }
  }

  find(filter = {}) {
    let results = this.data;
    if (filter.category) {
      results = results.filter(item => item.category === filter.category);
    }
    if (filter.search) {
      const q = filter.search.toLowerCase();
      results = results.filter(item =>
        (item.title && item.title.toLowerCase().includes(q)) ||
        (item.boxSize && item.boxSize.toLowerCase().includes(q)) ||
        (item.category && item.category.toLowerCase().includes(q)) ||
        (item.description && item.description.toLowerCase().includes(q))
      );
    }
    if (filter.sizeCategory) {
      results = results.filter(item => item.sizeCategory === filter.sizeCategory);
    }
    if (filter.role) {
      results = results.filter(item => item.role === filter.role);
    }
    if (filter.username) {
      results = results.filter(item => item.username === filter.username);
    }
    return results;
  }

  findOne(query) {
    return this.data.find(item => {
      for (let key in query) {
        if (item[key] !== query[key]) return false;
      }
      return true;
    }) || null;
  }

  findById(id) {
    return this.data.find(item => item._id === id || item.id === id) || null;
  }

  create(doc) {
    const newItem = {
      _id: doc._id || 'id_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
      ...doc
    };
    this.data.push(newItem);
    this._save();
    return newItem;
  }

  insertMany(docs) {
    const created = docs.map(doc => ({
      _id: doc._id || 'id_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
      ...doc
    }));
    this.data.push(...created);
    this._save();
    return created;
  }

  findByIdAndUpdate(id, updates, options = { new: true }) {
    const idx = this.data.findIndex(item => item._id === id || item.id === id);
    if (idx === -1) return null;
    this.data[idx] = {
      ...this.data[idx],
      ...updates,
      updatedAt: new Date().toISOString()
    };
    this._save();
    return this.data[idx];
  }

  findByIdAndDelete(id) {
    const idx = this.data.findIndex(item => item._id === id || item.id === id);
    if (idx === -1) return null;
    const removed = this.data.splice(idx, 1)[0];
    this._save();
    return removed;
  }

  clear() {
    this.data = [];
    this._save();
  }

  countDocuments(filter = {}) {
    return this.find(filter).length;
  }
}

const collections = {
  users: new JsonCollection('users'),
  products: new JsonCollection('products'),
  orders: new JsonCollection('orders')
};

module.exports = collections;
