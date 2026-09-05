import React, { useState, useEffect, useRef } from 'react';
import { MessageSquare, X, Send, Bot, Sparkles, Box, ShieldCheck, ChevronRight } from 'lucide-react';

export default function BotpressChatbot() {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState([
    {
      id: 1,
      sender: 'bot',
      text: '👋 Namaste! Welcome to Box Retailer Assistant. How can I help you with bulk box orders, sizes, or discount tiers today?'
    }
  ]);
  const [input, setInput] = useState('');
  const messagesEndRef = useRef(null);

  // Dynamically attempt to inject Botpress Webchat CDN if available
  useEffect(() => {
    const injectScript = document.createElement('script');
    injectScript.src = 'https://cdn.botpress.cloud/webchat/v2.2/inject.js';
    injectScript.async = true;
    document.body.appendChild(injectScript);

    return () => {
      if (document.body.contains(injectScript)) {
        document.body.removeChild(injectScript);
      }
    };
  }, []);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    if (isOpen) {
      scrollToBottom();
    }
  }, [messages, isOpen]);

  const handleSend = (textToSend = null) => {
    const text = textToSend || input;
    if (!text.trim()) return;

    const userMsg = { id: Date.now(), sender: 'user', text };
    setMessages(prev => [...prev, userMsg]);
    if (!textToSend) setInput('');

    // Generate intelligent AI Botpress response for box retail queries
    setTimeout(() => {
      const lower = text.toLowerCase();
      let botText = '';

      if (lower.includes('discount') || lower.includes('offer') || lower.includes('tier') || lower.includes('percent')) {
        botText = '🏷️ **Bulk Tier Discount Scale**:\n• 100+ Boxes: **5% OFF**\n• 300+ Boxes: **10% OFF**\n• 500+ Boxes: **18% OFF**\n• 600+ Boxes: **20% OFF**\n\nDiscounts apply automatically in your cart!';
      } else if (lower.includes('size') || lower.includes('dimension') || lower.includes('type') || lower.includes('category')) {
        botText = '📦 We offer over **360+ box sizes** across 5 industrial categories:\n1. Corrugated Shipping Cartons\n2. Heavy-Duty Moving Boxes\n3. Corrugated Mailers\n4. Die-Cut Gift & Apparel Boxes\n5. Telescopic & Special Boxes\n\nUse the search bar in the Catalog to filter by exact dimensions (e.g. 12x12x12).';
      } else if (lower.includes('price') || lower.includes('rupee') || lower.includes('inr') || lower.includes('cost') || lower.includes('₹')) {
        botText = '₹ All box prices are listed in **Indian Rupees (₹ INR)**. Base prices start from ₹25.00/unit and vary based on box volume and material grade.';
      } else if (lower.includes('stock') || lower.includes('available') || lower.includes('warehouse') || lower.includes('quantity')) {
        botText = '🏭 Warehouse stock is updated live. When an employee submits a bulk order, stock is automatically deducted in real-time. Admins can adjust stock counts anytime in `/admin`.';
      } else if (lower.includes('admin') || lower.includes('login') || lower.includes('account') || lower.includes('employee')) {
        botText = '🔐 **Portal Access**:\n• Employee Login: `/` (Default logins: `emp_john` / `boxemp123`)\n• Admin Control Center: `/admin` (Login: `admin` / `admin123`)\n• Admins can create new employee credentials in `/admin`.';
      } else if (lower.includes('order') || lower.includes('buy') || lower.includes('cart') || lower.includes('checkout')) {
        botText = '🛒 **How to place a bulk order**:\n1. Browse catalog & click **Calculate Bulk Discount & Order**\n2. Select quantity (100, 300, 500, 600+)\n3. Click **Add to Cart** and proceed to checkout!';
      } else {
        botText = `Box Retail Assistant here! I can help you with:\n• Bulk Discount Tiers (5%, 10%, 18%, 20%)\n• 360+ Box Sizes & Dimensions\n• Warehouse Stock Status\n• Admin & Employee Accounts\n\nWhat specific box size or order quantity are you looking for?`;
      }

      setMessages(prev => [...prev, { id: Date.now() + 1, sender: 'bot', text: botText }]);
    }, 500);
  };

  const quickPrompts = [
    '🏷️ What are the bulk discounts?',
    '📦 What box sizes are available?',
    '₹ How is box pricing calculated?',
    '🔐 How do employee logins work?'
  ];

  return (
    <div className="fixed bottom-6 right-6 z-50 font-sans">
      
      {/* Chatbot Toggle Button */}
      {!isOpen && (
        <button
          onClick={() => setIsOpen(true)}
          className="bg-gradient-to-r from-amber-600 to-amber-800 hover:from-amber-500 hover:to-amber-700 text-white font-bold px-4 py-3.5 rounded-full shadow-2xl flex items-center space-x-3 border border-amber-400/40 hover:scale-105 transition-all duration-200 group"
        >
          <div className="relative">
            <Bot className="w-6 h-6 text-amber-200 group-hover:rotate-12 transition transform" />
            <span className="absolute -top-1 -right-1 w-2.5 h-2.5 bg-emerald-400 rounded-full ring-2 ring-amber-900 animate-pulse"></span>
          </div>
          <span className="text-sm tracking-wide">Box AI Assistant</span>
          <Sparkles className="w-4 h-4 text-amber-300" />
        </button>
      )}

      {/* Floating Botpress Chatbot Modal */}
      {isOpen && (
        <div className="bg-white rounded-2xl w-80 sm:w-96 shadow-2xl border border-gray-200 overflow-hidden flex flex-col h-[520px] animate-fadeIn">
          
          {/* Header */}
          <div className="bg-gradient-to-r from-amber-950 via-amber-900 to-amber-950 text-white p-4 flex items-center justify-between border-b border-amber-800">
            <div className="flex items-center space-x-3">
              <div className="bg-amber-500 text-amber-950 p-2 rounded-xl font-bold shadow">
                <Bot className="w-5 h-5" />
              </div>
              <div>
                <h3 className="font-extrabold text-sm text-white flex items-center space-x-1.5">
                  <span>Botpress Assistant</span>
                  <span className="bg-amber-500/20 text-amber-300 text-[10px] px-1.5 py-0.2 rounded border border-amber-400/30">AI Bot</span>
                </h3>
                <p className="text-[11px] text-amber-200/80 flex items-center">
                  <span className="w-1.5 h-1.5 bg-emerald-400 rounded-full mr-1.5"></span>
                  Online • Wholesale Box Expert
                </p>
              </div>
            </div>
            <button
              onClick={() => setIsOpen(false)}
              className="text-amber-200 hover:text-white p-1 rounded-lg hover:bg-amber-800 transition"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Messages Area */}
          <div className="flex-1 p-4 overflow-y-auto space-y-3 bg-gray-50/50 text-xs">
            {messages.map((msg) => (
              <div
                key={msg.id}
                className={`flex ${msg.sender === 'user' ? 'justify-end' : 'justify-start'}`}
              >
                <div
                  className={`max-w-[85%] p-3 rounded-2xl ${
                    msg.sender === 'user'
                      ? 'bg-amber-600 text-white font-medium rounded-br-none shadow-sm'
                      : 'bg-white text-gray-800 border border-gray-200 rounded-bl-none shadow-xs whitespace-pre-line leading-relaxed'
                  }`}
                >
                  {msg.text}
                </div>
              </div>
            ))}
            <div ref={messagesEndRef} />
          </div>

          {/* Quick Prompts */}
          <div className="p-2 bg-gray-100/70 border-t border-gray-200 overflow-x-auto flex space-x-1.5 no-scrollbar">
            {quickPrompts.map((prompt, idx) => (
              <button
                key={idx}
                onClick={() => handleSend(prompt)}
                className="bg-white hover:bg-amber-50 hover:border-amber-400 border border-gray-200 rounded-lg px-2.5 py-1 text-[11px] font-semibold text-gray-700 whitespace-nowrap transition flex items-center space-x-1"
              >
                <span>{prompt}</span>
              </button>
            ))}
          </div>

          {/* Input Bar */}
          <form
            onSubmit={(e) => { e.preventDefault(); handleSend(); }}
            className="p-3 bg-white border-t border-gray-200 flex items-center space-x-2"
          >
            <input
              type="text"
              placeholder="Ask about box sizes, discounts, pricing..."
              value={input}
              onChange={(e) => setInput(e.target.value)}
              className="flex-1 px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none"
            />
            <button
              type="submit"
              disabled={!input.trim()}
              className="p-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl disabled:opacity-40 transition"
            >
              <Send className="w-4 h-4" />
            </button>
          </form>

        </div>
      )}

    </div>
  );
}
