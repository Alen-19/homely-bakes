// product.js
document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    
    // Add to cart functionality
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            addToCart(productId);
        });
    });

    const searchButton = document.getElementById('search-button');
    const searchInput = document.getElementById('search-input');
    const filterOptions = document.getElementById('filter-options');
    
    if (searchButton) {
        searchButton.addEventListener('click', function() {
            const searchTerm = searchInput?.value || '';
            const category = filterOptions?.value || '';
            searchProducts(searchTerm, category);
        });
    }
});

function addToCart(productId) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Product added to cart!');
            // You can update cart count here if needed
        } else {
            alert(data.message || 'Error adding product to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding product to cart. Please try again.');
    });
}

function searchProducts(searchTerm, category) {
    const params = new URLSearchParams({
        search: searchTerm,
        category: category
    });
    
    window.location.href = `product.php?${params.toString()}`;
}

function displayProducts(products) {
    const productsGrid = document.getElementById('products-grid');
    if (!productsGrid) return;
    
    productsGrid.innerHTML = '';
    
    if (!products.length) {
        productsGrid.innerHTML = '<p class="no-results">No products found.</p>';
        return;
    }

    products.forEach(product => {
        const productCard = createProductCard(product);
        productsGrid.appendChild(productCard);
    });
}

function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';
    
    // Safely handle missing image URLs
    const imageUrl = product.image_url || 'placeholder.jpg';
    const safePrice = parseFloat(product.price).toFixed(2);
    
    card.innerHTML = `
        <img src="products/${imageUrl}" alt="${product.name}" class="product-image" onerror="this.src=''">
        <h3>${product.name}</h3>
        <p class="description">${product.description || 'No description available'}</p>
        <p class="baker">By ${product.baker_name || 'Unknown Baker'}</p>
        <p class="price">₹${safePrice}</p>
        <p class="stock">In Stock: ${product.stock || 0}</p>
        <div class="quantity-controls">
            <button class="quantity-btn minus" ${product.stock <= 0 ? 'disabled' : ''}>-</button>
            <span class="quantity">1</span>
            <button class="quantity-btn plus" ${product.stock <= 0 ? 'disabled' : ''}>+</button>
        </div>
        <div class="action-buttons">
            <button class="cart-btn add-to-cart" data-product-id="${product.id}" ${product.stock <= 0 ? 'disabled' : ''}>
                Add to Cart
            </button>
            <button class="buy-btn" onclick="buyNow(${product.id})" ${product.stock <= 0 ? 'disabled' : ''}>
                Buy Now
            </button>
        </div>
    `;

    // Add quantity control event listeners
    const quantitySpan = card.querySelector('.quantity');
    const priceElement = card.querySelector('.price');
    const basePrice = parseFloat(product.price);

    card.querySelector('.minus').addEventListener('click', () => {
        let qty = parseInt(quantitySpan.textContent);
        if (qty > 1) {
            qty -= 1;
            quantitySpan.textContent = qty;
            updatePrice(priceElement, basePrice, qty);
        }
    });

    card.querySelector('.plus').addEventListener('click', () => {
        let qty = parseInt(quantitySpan.textContent);
        if (qty < product.stock) {
            qty += 1;
            quantitySpan.textContent = qty;
            updatePrice(priceElement, basePrice, qty);
        }
    });

    return card;
}

function updatePrice(priceElement, basePrice, quantity) {
    const totalPrice = (basePrice * quantity).toFixed(2);
    priceElement.textContent = `₹${totalPrice}`;
}

function buyNow(productId) {
    const card = event.target.closest('.product-card');
    if (!card) return;
    
    const quantity = parseInt(card.querySelector('.quantity').textContent);
    const stockElement = card.querySelector('.stock');
    const currentStock = parseInt(stockElement.textContent.match(/\d+/)[0]);

    if (quantity > currentStock) {
        alert('Not enough stock available');
        return;
    }

    const params = new URLSearchParams({
        product_id: productId,
        quantity: quantity
    });
    
    window.location.href = `checkout.php?${params.toString()}`;
}