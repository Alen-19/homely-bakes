// Load Products
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
          addProductBtn.addEventListener("click", function () {
            // Switch to add product section
            document
              .querySelectorAll(".main-nav li")
              .forEach((nav) => nav.classList.remove("active"));
            document
              .querySelector('.main-nav li[data-section="add-product"]')
              .classList.add("active");

            document
              .querySelectorAll(".section")
              .forEach((section) => section.classList.remove("active"));
            document
              .getElementById("add-product-section")
              .classList.add("active");
          });
        }
        return;
      }

      data.forEach((product) => {
        // Convert the is_active value to boolean properly
        const isActive = product.is_active === 1 || product.is_active === "1";
        const productCard = document.createElement("div");
        productCard.classList.add(
          "product-card",
          isActive ? "active" : "inactive"
        );
        productCard.innerHTML = `
                  <div class="status-badge ${
                    isActive ? "active-badge" : "inactive-badge"
                  }">
                      ${isActive ? "Active" : "Inactive"}
                  </div>
                  <img src="../${product.image_url}" alt="${
          product.product_name
        }" onerror="this.src='../assets/images/placeholder.jpg'">
                  <h3>${product.product_name}</h3>
                  <p class="category">${
                    product.category_name || "Uncategorized"
                  }</p>
                  <p class="price">₹${parseFloat(product.price).toFixed(
                    2
                  )}</p>
                  <div class="product-actions">
                      <button onclick="editProduct(${product.product_id})" class="edit-btn">
                          <i class="fas fa-edit"></i> Edit
                      </button>
                      <button onclick="toggleProductStatus(${product.product_id}, ${isActive})" class="toggle-btn">
                          <i class="fas ${isActive ? "fa-eye-slash" : "fa-eye"}"></i>
                          ${isActive ? "Deactivate" : "Activate"}
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
        productsGrid.innerHTML =
          '<p class="error-message">Error loading products. Please try again.</p>';
      }
    });
}

// Wait for the DOM to be fully loaded
document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM Content Loaded");

  // Profile dropdown functionality
  const profileContainer = document.querySelector(".profile-container");
  const profileButton = document.getElementById("profile-button");

  console.log("Profile Container:", profileContainer);
  console.log("Profile Button:", profileButton);

  if (profileButton && profileContainer) {
    console.log("Adding click event listener to profile button");

    profileButton.addEventListener("click", function (e) {
      console.log("Profile button clicked");
      e.stopPropagation();
      profileContainer.classList.toggle("active");
      console.log(
        "Active class toggled:",
        profileContainer.classList.contains("active")
      );
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function (e) {
      if (!profileContainer.contains(e.target)) {
        profileContainer.classList.remove("active");
      }
    });

    // Close dropdown when pressing escape key
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        profileContainer.classList.remove("active");
      }
    });
  }

  // Section Navigation
  const sections = document.querySelectorAll(".section");
  const navItems = document.querySelectorAll(".main-nav li");

  // Handle navigation clicks
  if (navItems) {
    navItems.forEach((item) => {
      item.addEventListener("click", function () {
        // Remove active class from all nav items
        navItems.forEach((nav) => nav.classList.remove("active"));

        // Add active class to clicked item
        this.classList.add("active");

        // Get the section id and show corresponding section
        const sectionId = this.getAttribute("data-section");
        sections.forEach((section) => section.classList.remove("active"));
        const targetSection = document.getElementById(sectionId + "-section");
        if (targetSection) {
          targetSection.classList.add("active");
        }
      });
    });
  }

  // Image preview functionality
  const productImage = document.getElementById("product-image");
  const imagePreview = document.getElementById("image-preview");
  const imagePreviewContainer = document.querySelector(
    ".image-preview-container"
  );
  const removeImageBtn = document.getElementById("remove-image");

  if (productImage) {
    productImage.addEventListener("change", function (e) {
      if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();

        reader.onload = function (e) {
          imagePreview.src = e.target.result;
          imagePreviewContainer.style.display = "block";
        };

        reader.readAsDataURL(e.target.files[0]);
      }
    });
  }

  if (removeImageBtn) {
    removeImageBtn.addEventListener("click", function () {
      productImage.value = "";
      imagePreviewContainer.style.display = "none";
      imagePreview.src = "#";
    });
  }

  // Add Product Form Submission
  const addProductForm = document.getElementById("add-product-form");
  if (addProductForm) {
    addProductForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      try {
        const isEditMode = this.getAttribute("data-edit-mode") === "true";
        const productId = this.getAttribute("data-product-id");
        const formData = new FormData(this);

        // Add product ID to form data if in edit mode
        if (isEditMode && productId) {
          formData.append("product_id", productId);
        }

        // Handle radio buttons explicitly
        const productType = document.querySelector('input[name="productType"]:checked')?.value;
        const cakeType = document.querySelector('input[name="cakeType"]:checked')?.value;
        formData.append("productType", productType || 'egg');
        formData.append("cakeType", cakeType || 'regular');

        const url = isEditMode ? "update_product.php" : "add_product.php";

        const response = await fetch(url, {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          alert(data.message || "Product updated successfully!");

          // Completely reset the form
          resetProductForm();
          
          // Switch to products section
          switchToSection('products');

          // Refresh products list
          loadProducts();
        } else {
          alert(data.error || "Error saving product");
          console.error("Server error:", data.error);
        }
      } catch (error) {
        console.error("Error:", error);
        alert("Error processing request: " + error.message);
      }
    });
  }

  // Load Categories
  function loadCategories() {
    console.log("Loading categories...");
    fetch("get_categories.php")
      .then((response) => {
        console.log("Response received:", response);
        if (!response.ok) {
          throw new Error("Network response was not ok");
        }
        return response.json();
      })
      .then((data) => {
        console.log("Categories data:", data);
        const categorySelect = document.querySelector(
          'select[name="category"]'
        );
        if (!categorySelect) {
          console.error("Category select element not found!");
          return;
        }

        // Reset select element
        categorySelect.innerHTML = '<option value="">Select Category</option>';

        // Check if we got an error response
        if (data.error) {
          console.warn("Server returned error:", data.error);
          const option = document.createElement("option");
          option.value = "";
          option.textContent = "No categories available";
          option.disabled = true;
          categorySelect.appendChild(option);
          return;
        }

        // Add categories if we have them
        if (Array.isArray(data) && data.length > 0) {
          data.forEach((category) => {
            const option = document.createElement("option");
            option.value = category.category_id;
            option.textContent = category.category_name;
            categorySelect.appendChild(option);
          });
          console.log("Categories populated in select element");
        } else {
          const option = document.createElement("option");
          option.value = "";
          option.textContent = "No categories available";
          option.disabled = true;
          categorySelect.appendChild(option);
          console.log("No categories available");
        }
      })
      .catch((error) => {
        console.error("Error loading categories:", error);
        const categorySelect = document.querySelector(
          'select[name="category"]'
        );
        if (categorySelect) {
          categorySelect.innerHTML =
            '<option value="">Error loading categories</option>';
        }
      });
  }

  // Load Orders
  function loadOrders() {
    fetch("get_baker_orders.php")
      .then((response) => response.json())
      .then((orders) => {
        const ordersContainer = document.getElementById("baker-orders");
        ordersContainer.innerHTML = "";

        orders.forEach((order) => {
          const orderItem = document.createElement("div");
          orderItem.classList.add("order-item");
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
  console.log("Initial loads completed");

  // Handle "Add your first product" button click
  const addProductBtn = document.querySelector(".add-product-btn");
  if (addProductBtn) {
    addProductBtn.addEventListener("click", function (e) {
      e.preventDefault();

      // Remove active class from all nav items
      document.querySelectorAll(".main-nav li").forEach((item) => {
        item.classList.remove("active");
      });

      // Add active class to "Add Product" nav item
      document
        .querySelector('.main-nav li[data-section="add-product"]')
        .classList.add("active");

      // Hide all sections and show add-product section
      document.querySelectorAll(".section").forEach((section) => {
        section.classList.remove("active");
      });
      document.getElementById("add-product-section").classList.add("active");
    });
  }
});

// Utility Functions


function deleteProduct(productId) {
  if (confirm("Are you sure you want to delete this product?")) {
    const formData = new FormData();
    formData.append("product_id", productId);

    fetch("delete_product.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Remove the product card from the UI
          const productCard = document
            .querySelector(
              `.product-card button[onclick="deleteProduct(${productId})"]`
            )
            .closest(".product-card");
          productCard.remove();
          alert("Product deleted successfully");
        } else {
          alert("Error: " + data.message);
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred while deleting the product");
      });
  }
}

function submitCategoryForm(event) {
  event.preventDefault();
  const form = event.target;
  const formData = new FormData(form);

  fetch("add_category.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Category added successfully!");
        form.reset();
        loadCategories(); // Reload categories after adding new one
      } else {
        alert(data.error || "Error adding category");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Error adding category");
    });

  return false;
}

function acceptOrder(orderId) {
  fetch(`update_order_status.php?id=${orderId}&status=accepted`, {
    method: "POST",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        loadOrders(); // Refresh orders
      }
    });
}

function rejectOrder(orderId) {
  fetch(`update_order_status.php?id=${orderId}&status=rejected`, {
    method: "POST",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        loadOrders(); // Refresh orders
      }
    });
}

document.addEventListener("DOMContentLoaded", function () {
  const fileInput = document.getElementById("profile_image");
  const previewImage = document.getElementById("profile-preview");

  fileInput.addEventListener("change", function () {
    const file = fileInput.files[0];

    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        previewImage.src = e.target.result;
        previewImage.style.display = "block";
      };
      reader.readAsDataURL(file);
    } else {
      previewImage.style.display = "none";
    }
  });
});

// Add this function to handle product status toggle
function toggleProductStatus(productId, currentStatus) {
  const action = currentStatus ? "deactivate" : "activate";
  if (confirm(`Are you sure you want to ${action} this product?`)) {
    fetch("toggle_product_status.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        product_id: productId,
        status: !currentStatus, // Invert the current status
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Find and update the specific product card
          const productCard = document
            .querySelector(
              `.product-card button[onclick*="toggleProductStatus(${productId}"]`
            )
            .closest(".product-card");
          const statusBadge = productCard.querySelector(".status-badge");
          const toggleButton = productCard.querySelector(".toggle-btn");

          // Update the card's status
          const newStatus = !currentStatus;
          productCard.classList.toggle("active", newStatus);
          productCard.classList.toggle("inactive", !newStatus);

          // Update the status badge
          statusBadge.textContent = newStatus ? "Active" : "Inactive";
          statusBadge.className = `status-badge ${
            newStatus ? "active-badge" : "inactive-badge"
          }`;

          // Update the toggle button
          toggleButton.innerHTML = `
          <i class="fas ${newStatus ? "fa-eye-slash" : "fa-eye"}"></i>
          ${newStatus ? "Deactivate" : "Activate"}
        `;
          toggleButton.onclick = () =>
            toggleProductStatus(productId, newStatus);

          // Optional: Show a success message
          alert(`Product successfully ${action}d`);
        } else {
          alert(
            "Error updating product status: " + (data.error || "Unknown error")
          );
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Error updating product status");
      });
  }
}

function toggleBakerStatus(currentStatus) {
  const action = currentStatus ? "close" : "open";
  if (
    confirm(
      `Are you sure you want to ${action} your store? ${
        !currentStatus
          ? "This will make all your products visible."
          : "This will hide all your products from customers."
      }`
    )
  ) {
    fetch("toggle_baker_status.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        status: !currentStatus,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Update button appearance
          const btn = document.getElementById("statusToggleBtn");
          const newStatus = !currentStatus;

          // Update button classes
          btn.classList.toggle("available", newStatus);
          btn.classList.toggle("unavailable", !newStatus);

          // Update button content
          btn.innerHTML = `
          <i class="fas ${newStatus ? "fa-store" : "fa-store-slash"}"></i>
          <span>${newStatus ? "Open" : "Closed"}</span>
        `;

          // Update onclick handler
          btn.onclick = () => toggleBakerStatus(newStatus);

          // Show success message
          showNotification(`Store successfully ${action}ed!`, 'success');
          loadProducts(); // Call loadProducts separately after showing the notification
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Error updating store status");
      });
  }
}

// Add event listener when document loads
document.addEventListener("DOMContentLoaded", function () {
  const statusBtn = document.getElementById("statusToggleBtn");
  if (statusBtn) {
    // Make sure the button has the correct initial onclick handler
    const currentStatus = statusBtn.classList.contains("available");
    statusBtn.onclick = () => toggleBakerStatus(currentStatus);
  }
});

// Add this new function to properly reset the form
function resetProductForm() {
  const form = document.getElementById('add-product-form');
  if (!form) return;

  // Reset the actual form
  form.reset();

  // Clear edit mode attributes
  form.removeAttribute("data-edit-mode");
  form.removeAttribute("data-product-id");

  // Reset the form title
  const titleElement = document.querySelector("#add-product-section h2");
  if (titleElement) {
    titleElement.textContent = "Add New Product";
  }

  // Reset the submit button
  const submitButton = document.querySelector("#add-product-section .submit-btn");
  if (submitButton) {
    submitButton.textContent = "Add Product";
  }

  // Clear image preview
  const imagePreview = document.getElementById('image-preview');
  const imagePreviewContainer = document.querySelector('.image-preview-container');
  if (imagePreview && imagePreviewContainer) {
    imagePreview.src = "#";
    imagePreviewContainer.style.display = "none";
  }

  // Reset file input
  const fileInput = document.getElementById('product-image');
  if (fileInput) {
    fileInput.value = "";
  }

  // Reset radio buttons to defaults
  const withEggRadio = document.querySelector('input[name="productType"][value="egg"]');
  const regularRadio = document.querySelector('input[name="cakeType"][value="regular"]');
  if (withEggRadio) withEggRadio.checked = true;
  if (regularRadio) regularRadio.checked = true;
}

// Add this function to handle section switching
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

// Update the editProduct function to properly set up the form
function editProduct(productId) {
  fetch(`get_product_details.php?product_id=${productId}`)
    .then(async response => {
      const text = await response.text();
      try {
        const data = JSON.parse(text);
        return data;
      } catch (e) {
        console.error('Raw response:', text);
        throw new Error('Server returned invalid JSON. Check console for details.');
      }
    })
    .then(product => {
      if (product.error) {
        throw new Error(product.error);
      }

      // Switch to add product section
      switchToSection('add-product');

      // Get form and check if it exists
      const form = document.getElementById('add-product-form');
      if (!form) {
        throw new Error('Form not found');
      }

      // Reset form before setting new values
      resetProductForm();

      // Set edit mode
      form.setAttribute('data-edit-mode', 'true');
      form.setAttribute('data-product-id', productId);

      // Update form title and button
      const titleElement = document.querySelector('#add-product-section h2');
      const submitButton = document.querySelector('#add-product-section .submit-btn');
      
      if (titleElement) titleElement.textContent = 'Edit Product: ' + (product.product_name || '');
      if (submitButton) submitButton.textContent = 'Update Product';

      // Update form fields
      const fields = {
        'name': product.product_name || '',
        'category': product.category_id || '',
        'price': product.price || '',
        'weight': product.weight || '',
        'description': product.description || ''
      };

      // Safely update each field
      Object.entries(fields).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
          element.value = value;
        }
      });

      // Update radio buttons
      if (product.product_type) {
        const productTypeRadio = form.querySelector(`input[name="productType"][value="${product.product_type}"]`);
        if (productTypeRadio) {
          productTypeRadio.checked = true;
        }
      }

      if (product.cake_type) {
        const cakeTypeRadio = form.querySelector(`input[name="cakeType"][value="${product.cake_type}"]`);
        if (cakeTypeRadio) {
          cakeTypeRadio.checked = true;
        }
      }

      // Update image preview
      const imagePreviewContainer = form.querySelector('.image-preview-container');
      const imagePreview = form.querySelector('#image-preview');
      if (imagePreviewContainer && imagePreview && product.image_url) {
        imagePreview.src = product.image_url;
        imagePreviewContainer.style.display = 'block';
      }

      // Make image upload optional
      const imageInput = form.querySelector('#product-image');
      if (imageInput) {
        imageInput.removeAttribute('required');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert(`Failed to load product details: ${error.message}`);
    });
}

// Add this function to handle section switching
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

// Add this function at the top of your baker_dashboard.js file
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
