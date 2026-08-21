// Products Data
const products = [
    {id: 1, name: "Nike Air Max", category: "shoes", price: 129.99, icon: "👟"},
    {id: 2, name: "Adidas Ultraboost", category: "shoes", price: 159.99, icon: "👟"},
    {id: 3, name: "Puma Suede Classic", category: "shoes", price: 79.99, icon: "👞"},
    {id: 4, name: "Premium Cotton T-Shirt", category: "clothes", price: 29.99, icon: "👕"},
    {id: 5, name: "Slim Fit Jeans", category: "clothes", price: 59.99, icon: "👖"},
    {id: 6, name: "Hoodie Sweatshirt", category: "clothes", price: 49.99, icon: "🧥"},
    {id: 7, name: "Running Sneakers", category: "shoes", price: 99.99, icon: "👟"},
    {id: 8, name: "Leather Jacket", category: "clothes", price: 199.99, icon: "🧥"},
    {id: 9, name: "Canvas Shoes", category: "shoes", price: 49.99, icon: "👟"},
    {id: 10, name: "Summer Dress", category: "clothes", price: 79.99, icon: "👗"}
];

let cart = [];
let currentFilter = 'all';

// DOM Elements
const productsContainer = document.getElementById('productsContainer');
const cartSidebar = document.getElementById('cartSidebar');
const cartOverlay = document.getElementById('cartOverlay');
const cartItemsList = document.getElementById('cartItemsList');
const cartTotalSpan = document.getElementById('cartTotal');
const cartCounter = document.getElementById('cartCounter');
const checkoutFormModal = document.getElementById('checkoutFormModal');

// XSS Prevention: Escape HTML function
function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Render Products
function renderProducts() {
    const filteredProducts = currentFilter === 'all' 
        ? products 
        : products.filter(p => p.category === currentFilter);
    
    if (filteredProducts.length === 0) {
        productsContainer.innerHTML = '<div class="empty-cart">No products found</div>';
        return;
    }
    
    productsContainer.innerHTML = filteredProducts.map(product => `
        <div class="product-card">
            <div class="product-image">${escapeHTML(product.icon)}</div>
            <div class="product-info">
                <div class="product-title">${escapeHTML(product.name)}</div>
                <div class="product-category">${escapeHTML(product.category)}</div>
                <div class="product-price">$${product.price.toFixed(2)}</div>
                <button class="add-to-cart" onclick="addToCart(${product.id})">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>
            </div>
        </div>
    `).join('');
}

// Add to Cart
window.addToCart = function(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    
    const existingItem = cart.find(item => item.id === productId);
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: product.price,
            quantity: 1,
            icon: product.icon
        });
    }
    
    updateCartUI();
    showNotification(`${product.name} added to cart!`);
};

// Update Cart UI
function updateCartUI() {
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    cartCounter.textContent = totalItems;
    cartTotalSpan.textContent = `$${totalPrice.toFixed(2)}`;
    
    if (cart.length === 0) {
        cartItemsList.innerHTML = '<div class="empty-cart">Your cart is empty</div>';
        return;
    }
    
    cartItemsList.innerHTML = cart.map(item => `
        <div class="cart-item">
            <div class="cart-item-image">${escapeHTML(item.icon)}</div>
            <div class="cart-item-details">
                <div class="cart-item-title">${escapeHTML(item.name)}</div>
                <div class="cart-item-price">$${(item.price * item.quantity).toFixed(2)}</div>
                <div class="cart-item-actions">
                    <button class="quantity-btn" onclick="updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                    <span>${item.quantity}</span>
                    <button class="quantity-btn" onclick="updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                    <span class="remove-item" onclick="removeFromCart(${item.id})">Remove</span>
                </div>
            </div>
        </div>
    `).join('');
}

// Update Quantity
window.updateQuantity = function(productId, newQuantity) {
    if (newQuantity <= 0) {
        removeFromCart(productId);
    } else {
        const item = cart.find(i => i.id === productId);
        if (item) {
            item.quantity = newQuantity;
            updateCartUI();
        }
    }
};

// Remove from Cart
window.removeFromCart = function(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCartUI();
    showNotification('Item removed from cart');
};

// Show Notification
function showNotification(message) {
    const notification = document.createElement('div');
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #27ae60;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        z-index: 9999;
        animation: slideInRight 0.3s ease;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    `;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 2000);
}

// Show Checkout Form
function showCheckoutForm() {
    if (cart.length === 0) {
        showNotification('Your cart is empty!');
        return;
    }
    closeCart();
    checkoutFormModal.style.display = 'flex';
}

// Process Order with Customer Info
async function processOrder(event) {
    event.preventDefault();
    
    // Get customer information
    const customerName = document.getElementById('customerName').value;
    const customerEmail = document.getElementById('customerEmail').value;
    const customerPhone = document.getElementById('customerPhone').value;
    const customerAddress = document.getElementById('customerAddress').value;
    const paymentMethod = document.getElementById('paymentMethod').value;
    
    // Validate
    if (!customerName || !customerEmail || !customerPhone || !customerAddress) {
        showNotification('Please fill in all required fields');
        return;
    }
    
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const orderData = {
        customer: {
            name: customerName,
            email: customerEmail,
            phone: customerPhone,
            address: customerAddress,
            payment_method: paymentMethod
        },
        items: cart.map(item => ({
            id: item.id,
            name: item.name,
            price: item.price,
            quantity: item.quantity,
            subtotal: item.price * item.quantity
        })),
        total: total,
        order_date: new Date().toISOString()
    };
    
    showLoading(true);
    
    try {
        const response = await fetch('backend.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Clear cart and close form
            cart = [];
            updateCartUI();
            checkoutFormModal.style.display = 'none';
            
            // Show success message with customer name
            document.getElementById('orderSuccessMessage').innerHTML = 
                `Thank you ${escapeHTML(customerName)}! Your order #${result.order_id} has been confirmed.<br>
                We'll send shipping updates to ${escapeHTML(customerEmail)}`;
            document.getElementById('successModal').style.display = 'flex';
            
            // Clear form
            document.getElementById('checkoutForm').reset();
            showNotification(`Order placed successfully! Order ID: ${result.order_id}`);
        } else {
            showNotification('Error: ' + (result.message || 'Order failed'));
        }
    } catch (error) {
        console.error('Checkout error:', error);
        // Demo fallback
        const fakeOrderId = 'DEMO-' + Math.random().toString(36).substr(2, 8).toUpperCase();
        cart = [];
        updateCartUI();
        checkoutFormModal.style.display = 'none';
        document.getElementById('orderSuccessMessage').innerHTML = 
            `Thank you ${escapeHTML(customerName)}! Your demo order #${fakeOrderId} has been confirmed.`;
        document.getElementById('successModal').style.display = 'flex';
        document.getElementById('checkoutForm').reset();
        showNotification('Demo: Order placed successfully!');
    } finally {
        showLoading(false);
    }
}

function showLoading(show) {
    const spinner = document.getElementById('loadingSpinner');
    spinner.style.display = show ? 'flex' : 'none';
}

function openCart() {
    cartSidebar.classList.add('open');
    cartOverlay.style.display = 'block';
}

function closeCart() {
    cartSidebar.classList.remove('open');
    cartOverlay.style.display = 'none';
}

// Event Listeners
document.getElementById('cartIcon').addEventListener('click', openCart);
document.getElementById('closeCartBtn').addEventListener('click', closeCart);
document.getElementById('cartOverlay').addEventListener('click', closeCart);
document.getElementById('checkoutBtn').addEventListener('click', showCheckoutForm);
document.getElementById('cancelCheckoutBtn').addEventListener('click', () => {
    checkoutFormModal.style.display = 'none';
});
document.getElementById('modalCloseBtn').addEventListener('click', () => {
    document.getElementById('successModal').style.display = 'none';
});
document.getElementById('checkoutForm').addEventListener('submit', processOrder);

// Filter buttons
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentFilter = btn.dataset.category;
        renderProducts();
    });
});

// Initialize
renderProducts();
console.log('E-commerce site loaded successfully!');