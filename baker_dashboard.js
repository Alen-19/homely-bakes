function showNotification(message, isError = false) {
    const notification = document.createElement('div');
    notification.className = `status-notification ${isError ? 'error' : 'success'}`;
    notification.innerHTML = `
        <i class="fas ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i>
        ${message}
    `;
    document.body.appendChild(notification);

    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function loadProducts() {
    console.log("Loading products...");
    fetch("get_baker_products.php")
        .then((response) => {
            if (!response.ok) {
                throw new Error("Network response was not ok");
            }
            return response.json();
        })
        .then((data) => {
            console.log("Products loaded:", data);
            const productsGrid = document.getElementById("baker-products");
            if (!productsGrid) {
                console.error("Products grid element not found");
                return;
            }

            productsGrid.innerHTML = "";

            if (data.error) {
                productsGrid.innerHTML = `<p class="error-message">${data.error}</p>`;
                return;
            }

            if (!Array.isArray(data) || data.length === 0) {
                productsGrid.innerHTML = `
                    <div class="no-products-container">
                        <i class="fas fa-box-open"></i>
                        <p>No products found.</p>
                        <button class="add-product-btn" data-section="add-product">
                            <i class="fas fa-plus"></i> Add your first product
                        </button>
                    </div>`;

                // Add click handler for the add product button
                const addProductBtn = productsGrid.querySelector(".add-product-btn");
                if (addProductBtn) {
                    addProductBtn.addEventListener("click", () => switchToSection('add-product'));
                }
                return;
            }

            data.forEach((product) => {
                const isActive = product.is_active === 1 || product.is_active === "1";
                const productCard = document.createElement('div');
                productCard.className = `product-card ${isActive ? 'active' : 'inactive'}`;
                productCard.dataset.productId = product.product_id;
                
                productCard.innerHTML = `
                    <div class="status-badge ${isActive ? 'active-badge' : 'inactive-badge'}">
                        ${isActive ? 'Active' : 'Inactive'}
                    </div>
                    <img src="${product.image_url}" alt="${product.product_name}">
                    <h3>${product.product_name}</h3>
                    <p class="price">₹${product.price}</p>
                    <div class="product-actions">
                        <button onclick="editProduct(${product.product_id})" class="edit-btn">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button onclick="toggleProductStatus(${product.product_id}, ${isActive})" class="toggle-btn">
                            <i class="fas ${isActive ? 'fa-eye-slash' : 'fa-eye'}"></i>
                            ${isActive ? 'Deactivate' : 'Activate'}
                        </button>
                        <button onclick="deleteProduct(${product.product_id})" class="delete-btn">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                `;
                
                productsGrid.appendChild(productCard);
            });
        })
        .catch((error) => {
            console.error("Error loading products:", error);
            const productsGrid = document.getElementById("baker-products");
            if (productsGrid) {
                productsGrid.innerHTML = '<p class="error-message">Error loading products. Please try again.</p>';
            }
        });
}

function switchToSection(sectionId) {
    // Hide all sections
    document.querySelectorAll(".section").forEach((section) => {
        section.classList.remove("active");
    });
    
    // Show target section
    const targetSection = document.getElementById(`${sectionId}-section`);
    if (targetSection) {
        targetSection.classList.add("active");
    }

    // Update navigation
    document.querySelectorAll(".main-nav li").forEach((nav) => {
        nav.classList.remove("active");
    });
    
    const targetNav = document.querySelector(`.main-nav li[data-section="${sectionId}"]`);
    if (targetNav) {
        targetNav.classList.add("active");
    }
}

function toggleBakerStatus(currentStatus) {
    const action = currentStatus ? 'close' : 'open';
    const statusBtn = document.getElementById('statusToggleBtn');
    
    if (confirm(`Are you sure you want to ${action} your store? ${!currentStatus ? 
        'This will make all your products visible.' : 
        'This will hide all your products from customers.'}`)) {
        
        // Show loading state
        statusBtn.disabled = true;
        statusBtn.innerHTML = `
            <i class="fas fa-spinner fa-spin"></i>
            <span>Updating...</span>
        `;

        fetch('toggle_baker_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                status: !currentStatus ? 1 : 0
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const newStatus = !currentStatus;
                
                // Update button appearance
                statusBtn.classList.toggle('available', newStatus);
                statusBtn.classList.toggle('unavailable', !newStatus);
                
                // Update button content
                statusBtn.innerHTML = `
                    <i class="fas ${newStatus ? 'fa-store' : 'fa-store-slash'}"></i>
                    <span>${newStatus ? 'Open' : 'Closed'}</span>
                `;
                
                // Update onclick handler
                statusBtn.onclick = () => toggleBakerStatus(newStatus);
                
                // Show success notification and refresh products
                showNotification(`Store successfully ${action}ed!`, false);
                loadProducts();
            } else {
                throw new Error(data.error || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Reset button state
            statusBtn.classList.toggle('available', currentStatus);
            statusBtn.classList.toggle('unavailable', !currentStatus);
            statusBtn.innerHTML = `
                <i class="fas ${currentStatus ? 'fa-store' : 'fa-store-slash'}"></i>
                <span>${currentStatus ? 'Open' : 'Closed'}</span>
            `;
            
            // Show error notification
            showNotification(error.message || 'Error updating store status', true);
        })
        .finally(() => {
            statusBtn.disabled = false;
        });
    }
} 