const dbStore = require('./dbStore');

const SAMPLE_IMAGES = {
  'Corrugated Cartons': [
    'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1549465220-1a8b9238cd48?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1578575437130-527eed3abbec?auto=format&fit=crop&w=600&q=80'
  ],
  'Heavy-Duty Moving': [
    'https://images.unsplash.com/photo-1607344645866-009c320c5ab8?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1566576721346-d4a3b4eaeb55?auto=format&fit=crop&w=600&q=80'
  ],
  'Corrugated Mailers': [
    'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?auto=format&fit=crop&w=600&q=80'
  ],
  'Die-Cut / Gift Boxes': [
    'https://images.unsplash.com/photo-1549465220-1a8b9238cd48?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1512909006721-3d6018887383?auto=format&fit=crop&w=600&q=80'
  ],
  'Telescopic & Special': [
    'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1607344645866-009c320c5ab8?auto=format&fit=crop&w=600&q=80'
  ]
};

const CATEGORIES = [
  'Corrugated Cartons',
  'Heavy-Duty Moving',
  'Corrugated Mailers',
  'Die-Cut / Gift Boxes',
  'Telescopic & Special'
];

function generateSeedProducts() {
  const products = [];
  let idCounter = 1;

  const lengthOptions = [4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 28, 30, 36];
  const widthOptions = [4, 6, 8, 10, 12, 14, 16, 18, 20, 24];
  const heightOptions = [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 24, 30];

  let count = 0;
  for (let l of lengthOptions) {
    for (let w of widthOptions) {
      if (w > l) continue;
      for (let h of heightOptions) {
        if (count >= 360) break;

        const category = CATEGORIES[count % CATEGORIES.length];
        const volume = l * w * h;
        
        let sizeCategory = 'Medium';
        if (volume <= 300) sizeCategory = 'Small';
        else if (volume <= 1500) sizeCategory = 'Medium';
        else if (volume <= 4000) sizeCategory = 'Large';
        else sizeCategory = 'Extra Large';

        // Base price calculation in INR (₹)
        let baseCostINR = 25.00 + (volume * 0.045);
        if (category === 'Heavy-Duty Moving') baseCostINR *= 1.45;
        if (category === 'Die-Cut / Gift Boxes') baseCostINR *= 1.30;
        const unitPrice = parseFloat(baseCostINR.toFixed(2));

        // Warehouse quantity
        const stockQuantities = [150, 250, 400, 600, 850, 1200, 1500, 2200, 3500];
        const availableQuantity = stockQuantities[(l + w + h) % stockQuantities.length];

        const images = SAMPLE_IMAGES[category];
        const image = images[count % images.length];

        const sku = `BOX-${category.substr(0, 3).toUpperCase()}-${l}X${w}X${h}-${String(idCounter).padStart(3, '0')}`;

        // Default tier discounts requested by user:
        // 100 boxes -> 5%, 300 boxes -> 10%, 500 boxes -> 18%, 600 boxes -> 20%
        const discountTiers = [
          { minQuantity: 100, discountPercent: 5 },
          { minQuantity: 300, discountPercent: 10 },
          { minQuantity: 500, discountPercent: 18 },
          { minQuantity: 600, discountPercent: 20 }
        ];

        if (count % 7 === 0) {
          discountTiers.push({ minQuantity: 1000, discountPercent: 25 });
        }

        products.push({
          sku,
          title: `${l}" x ${w}" x ${h}" ${category.replace('/', '&')} Box`,
          boxSize: `${l}" x ${w}" x ${h}"`,
          length: l,
          width: w,
          height: h,
          category,
          sizeCategory,
          description: `Industrial grade ${category.toLowerCase()} constructed from durable ECT-32 single/double wall kraft paper. Ideal for bulk shipping, storage, and retail distribution in India.`,
          unitPrice,
          availableQuantity,
          image,
          discountTiers
        });

        idCounter++;
        count++;
      }
    }
  }

  return products;
}

function seedDatabase() {
  console.log('Seeding Database with INR pricing...');
  
  // 1. Seed Users (Admin & Initial Employees)
  dbStore.users.clear();
  const defaultUsers = [
    { username: 'admin', password: 'admin123', name: 'Master Admin', role: 'admin' },
    { username: 'emp_john', password: 'boxemp123', name: 'John Miller (Sales)', role: 'employee' },
    { username: 'emp_sarah', password: 'boxemp123', name: 'Sarah Jenkins (Logistics)', role: 'employee' },
    { username: 'emp_alex', password: 'boxemp123', name: 'Alex Rivera (Warehouse)', role: 'employee' },
    { username: 'emp_david', password: 'boxemp123', name: 'David Vance (Procurement)', role: 'employee' }
  ];
  dbStore.users.insertMany(defaultUsers);

  // 2. Seed 350+ Box Products in INR
  dbStore.products.clear();
  const seedProducts = generateSeedProducts();
  dbStore.products.insertMany(seedProducts);

  console.log(`Database successfully seeded with ${defaultUsers.length} users and ${seedProducts.length} box items in INR!`);
  return { usersCount: defaultUsers.length, productsCount: seedProducts.length };
}

module.exports = { seedDatabase, generateSeedProducts };
