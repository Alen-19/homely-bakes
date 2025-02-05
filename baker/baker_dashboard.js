document.addEventListener('DOMContentLoaded', () => {
    // Section Navigation
    const sidebar = document.querySelector('.sidebar');
    const sections = document.querySelectorAll('.section');

    sidebar.addEventListener('click', (e) => {
        const clickedItem = e.target.closest('li');
        if (clickedItem) {
            // Remove active from all items and sections
            sidebar.querySelectorAll('li').forEach(item => item.classList.remove('active'));
            sections.forEach(section => section.classList.remove('active'));

            // Add active to clicked item and corresponding section
            clickedItem.classList.add('active');
            const sectionToShow = document.getElementById(`${clickedItem.dataset.section}-section`);
            sectionToShow.classList.add('active');
        }
    });

    // Add Product Form Submission
    const addProductForm = document.getElementById('add-product-form');
    addProductForm.addEventListener('submit', (e) => {
        e.preventDefault();
        // Here you'd typically send data to a PHP backend
        fetch('add_product.php', {
            method: 'POST',
            body: new FormData(addProductForm)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Product added successfully!');
                addProductForm.reset();
                // Refresh product list
                loadProducts();
            }
        });
    });

    // Load Products
    function loadProducts() {
        fetch('get_baker_products.php')
        .then(response => response.json())
        .then(products => {
            const productsGrid = document.getElementById('baker-products');
            productsGrid.innerHTML = ''; // Clear existing products
            
            products.forEach(product => {
                const productCard = document.createElement('div');
                productCard.classList.add('product-card');
                productCard.innerHTML = `
                    <img src="${product.image}" alt="${product.name}">
                    <h3>${product.name}</h3>
                    <p>₹${product.price}</p>
                    <div class="product-actions">
                        <button onclick="editProduct(${product.id})">Edit</button>
                        <button onclick="deleteProduct(${product.id})">Delete</button>
                    </div>
                `;
                productsGrid.appendChild(productCard);
            });
        });
    }

    // Initial product load
    loadProducts();

    // Load Orders
    function loadOrders() {
        fetch('get_baker_orders.php')
        .then(response => response.json())
        .then(orders => {
            const ordersContainer = document.getElementById('baker-orders');
            ordersContainer.innerHTML = '';
            
            orders.forEach(order => {
                const orderItem = document.createElement('div');
                orderItem.classList.add('order-item');
                orderItem.innerHTML = `
                    <span>Order #${order.id}</span>
                    <span>${order.customer_name}</span>
                    <span>₹${order.total_amount}</span>
                    <div class="order-actions">
                        <button onclick="acceptOrder(${order.id})">Accept</button>
                        <button onclick="rejectOrder(${order.id})">Reject</button>
                    </div>
                `;
                ordersContainer.appendChild(orderItem);
            });
        });
    }

    // Initial orders load
    loadOrders();
});

// Utility Functions (these would interact with PHP backend)
function editProduct(productId) {
    // Open edit modal or navigate to edit page
}

function deleteProduct(productId) {
    if(confirm('Are you sure you want to delete this product?')) {
        fetch(`delete_product.php?id=${productId}`, { method: 'DELETE' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadProducts(); // Refresh product list
            }
        });
    }
}

function acceptOrder(orderId) {
    fetch(`update_order_status.php?id=${orderId}&status=accepted`, { method: 'POST' })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadOrders(); // Refresh orders
        }
    });
}

function rejectOrder(orderId) {
    fetch(`update_order_status.php?id=${orderId}&status=rejected`, { method: 'POST' })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadOrders(); // Refresh orders
        }
    });
}