import React, { createContext, useContext, useState } from 'react';

const CartContext = createContext();

export const calculateProductTierDiscount = (product, quantity) => {
  if (!product || !product.discountTiers || !Array.isArray(product.discountTiers)) {
    return 0;
  }
  // Sort tiers descending
  const sorted = [...product.discountTiers].sort((a, b) => b.minQuantity - a.minQuantity);
  for (let tier of sorted) {
    if (quantity >= tier.minQuantity) {
      return tier.discountPercent;
    }
  }
  return 0;
};

export const CartProvider = ({ children }) => {
  const [cartItems, setCartItems] = useState([]);

  const addToCart = (product, quantity) => {
    const qty = parseInt(quantity, 10);
    if (isNaN(qty) || qty <= 0) return;

    setCartItems(prev => {
      const existingIdx = prev.findIndex(item => item.product._id === product._id);
      if (existingIdx > -1) {
        const updated = [...prev];
        const newQty = updated[existingIdx].quantity + qty;
        const discountPercent = calculateProductTierDiscount(product, newQty);
        updated[existingIdx] = {
          product,
          quantity: newQty,
          discountPercent
        };
        return updated;
      } else {
        const discountPercent = calculateProductTierDiscount(product, qty);
        return [...prev, { product, quantity: qty, discountPercent }];
      }
    });
  };

  const updateQuantity = (productId, newQuantity) => {
    const qty = parseInt(newQuantity, 10);
    if (isNaN(qty) || qty <= 0) {
      removeFromCart(productId);
      return;
    }

    setCartItems(prev => prev.map(item => {
      if (item.product._id === productId) {
        const discountPercent = calculateProductTierDiscount(item.product, qty);
        return { ...item, quantity: qty, discountPercent };
      }
      return item;
    }));
  };

  const removeFromCart = (productId) => {
    setCartItems(prev => prev.filter(item => item.product._id !== productId));
  };

  const clearCart = () => {
    setCartItems([]);
  };

  const getSubtotal = () => {
    return cartItems.reduce((acc, item) => acc + (item.product.unitPrice * item.quantity), 0);
  };

  const getTotalSavings = () => {
    return cartItems.reduce((acc, item) => {
      const regularPrice = item.product.unitPrice * item.quantity;
      const discountedPrice = regularPrice * (1 - item.discountPercent / 100);
      return acc + (regularPrice - discountedPrice);
    }, 0);
  };

  const getFinalTotal = () => {
    return getSubtotal() - getTotalSavings();
  };

  return (
    <CartContext.Provider value={{
      cartItems,
      addToCart,
      updateQuantity,
      removeFromCart,
      clearCart,
      getSubtotal,
      getTotalSavings,
      getFinalTotal
    }}>
      {children}
    </CartContext.Provider>
  );
};

export const useCart = () => useContext(CartContext);
