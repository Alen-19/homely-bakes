// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM Content Loaded');
    
    // Profile dropdown functionality
    const profileContainer = document.querySelector('.profile-container');
    const profileButton = document.getElementById('profile-button');

    console.log('Profile Container:', profileContainer);
    console.log('Profile Button:', profileButton);

    if (profileButton && profileContainer) {
        console.log('Adding click event listener to profile button');
        
        profileButton.addEventListener('click', function(e) {
            console.log('Profile button clicked');
            e.stopPropagation();
            profileContainer.classList.toggle('active');
            console.log('Active class toggled:', profileContainer.classList.contains('active'));
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!profileContainer.contains(e.target)) {
                profileContainer.classList.remove('active');
            }
        });

        // Close dropdown when pressing escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                profileContainer.classList.remove('active');
            }
        });
    }

    // Section Navigation
    const sidebar = document.querySelector('.sidebar');
    const sections = document.querySelectorAll('.section');

    if (sidebar) {
        sidebar.addEventListener('click', (e) => {
            const clickedItem = e.target.closest('li');
            if (clickedItem) {
                // Remove active from all items and sections
                sidebar.querySelectorAll('li').forEach(item => item.classList.remove('active'));
                sections.forEach(section => section.classList.remove('active'));

                // Add active to clicked item and corresponding section
                clickedItem.classList.add('active');
                const sectionToShow = document.getElementById(`${clickedItem.dataset.section}-section`);
                if (sectionToShow) {
                    sectionToShow.classList.add('active');
                }
            }
        });
    }

    // Image Preview Functionality
    const productImageInput = document.getElementById('product-image');
    const imagePreview = document.getElementById('image-preview');
    const imagePreviewContainer = document.querySelector('.image-preview-container');
    const removeImageBtn = document.getElementById('remove-image');

    if (productImageInput && imagePreview && removeImageBtn) {
        productImageInput.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                // Check if file is an image
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file');
                    this.value = '';
                    return;
                }

                // Check file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image size should be less than 5MB');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        removeImageBtn.addEventListener('click', function() {
            productImageInput.value = '';
            imagePreviewContainer.style.display = 'none';
            imagePreview.src = '#';
        });
    }

    // Add Product Form Submission
    const addProductForm = document.getElementById('add-product-form');
    if (addProductForm) {
        addProductForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Disable submit button to prevent double submission
            const submitButton = addProductForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Adding Product...';
            
            fetch('add_product.php', {
                method: 'POST',
                body: new FormData(addProductForm)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added successfully!');
                    addProductForm.reset();
                    loadProducts(); // Refresh product list
                } else {
                    alert(data.error || 'Failed to add product');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding product. Please try again.');
            })
            .finally(() => {
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.textContent = 'Add Product';
            });
        });
    }

    // Load Products
    function loadProducts() {
        console.log('Loading products...');
        fetch('get_baker_products.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Products loaded:', data);
            const productsGrid = document.getElementById('baker-products');
            if (!productsGrid) {
                console.error('Products grid element not found');
                return;
            }
            
            productsGrid.innerHTML = '';
            
            if (data.error) {
                productsGrid.innerHTML = `<p class="error-message">${data.error}</p>`;
                return;
            }
            
            if (!Array.isArray(data) || data.length === 0) {
                productsGrid.innerHTML = '<p class="no-products">No products found. Add your first product!</p>';
                return;
            }

            data.forEach(product => {
                const productCard = document.createElement('div');
                productCard.classList.add('product-card');
                productCard.innerHTML = `
                    <img src="../${product.image_url}" alt="${product.product_name}" onerror="this.src='../assets/images/placeholder.jpg'">
                    <h3>${product.product_name}</h3>
                    <p class="category">${product.category_name || 'Uncategorized'}</p>
                    <p class="price">₹${parseFloat(product.price).toFixed(2)}</p>
                    <p class="stock">Stock: ${product.stock}</p>
                    <div class="product-actions">
                        <button onclick="editProduct(${product.product_id})" class="edit-btn">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button onclick="deleteProduct(${product.product_id})" class="delete-btn">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                `;
                productsGrid.appendChild(productCard);
            });
        })
        .catch(error => {
            console.error('Error loading products:', error);
            const productsGrid = document.getElementById('baker-products');
            if (productsGrid) {
                productsGrid.innerHTML = '<p class="error-message">Error loading products. Please try again.</p>';
            }
        });
    }

    // Load Categories
    function loadCategories() {
        console.log('Loading categories...');
        fetch('get_categories.php')
        .then(response => {
            console.log('Response received:', response);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Categories data:', data);
            const categorySelect = document.querySelector('select[name="category"]');
            if (!categorySelect) {
                console.error('Category select element not found!');
                return;
            }
            
            // Reset select element
            categorySelect.innerHTML = '<option value="">Select Category</option>';
            
            // Check if we got an error response
            if (data.error) {
                console.warn('Server returned error:', data.error);
                const option = document.createElement('option');
                option.value = "";
                option.textContent = "No categories available";
                option.disabled = true;
                categorySelect.appendChild(option);
                return;
            }
            
            // Add categories if we have them
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.category_id;
                    option.textContent = category.category_name;
                    categorySelect.appendChild(option);
                });
                console.log('Categories populated in select element');
            } else {
                const option = document.createElement('option');
                option.value = "";
                option.textContent = "No categories available";
                option.disabled = true;
                categorySelect.appendChild(option);
                console.log('No categories available');
            }
        })
        .catch(error => {
            console.error('Error loading categories:', error);
            const categorySelect = document.querySelector('select[name="category"]');
            if (categorySelect) {
                categorySelect.innerHTML = '<option value="">Error loading categories</option>';
            }
        });
    }

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

    // Initial loads
    loadProducts();
    loadCategories();
    loadOrders();
    console.log('Initial loads completed');
});

// Utility Functions
function editProduct(productId) {
    // Open edit modal or navigate to edit page
}

function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        const formData = new FormData();
        formData.append('product_id', productId);

        fetch('delete_product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the product card from the UI
                const productCard = document.querySelector(`.product-card button[onclick="deleteProduct(${productId})"]`).closest('.product-card');
                productCard.remove();
                alert('Product deleted successfully');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the product');
        });
    }
}

function submitCategoryForm(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('add_category.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Category added successfully!');
            form.reset();
            loadCategories(); // Reload categories after adding new one
        } else {
            alert(data.error || 'Error adding category');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding category');
    });
    
    return false;
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

document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('profile_image');
    const previewImage = document.getElementById('profile-preview');

    fileInput.addEventListener('change', function () {
        const file = fileInput.files[0];

        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            previewImage.style.display = 'none';
        }
    });
});
