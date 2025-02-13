<?php
session_start();
require_once 'connect.php';

// Get product ID and quantity from URL parameters
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;

// Fetch product and baker information
$stmt = $conn->prepare("
    SELECT p.*, b.name as baker_name, b.location as baker_location 
    FROM products p 
    JOIN bakers b ON p.baker_id = b.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// Check if product exists and has enough stock
if (!$product || $product['stock'] < $quantity) {
    header("Location: products.php?error=invalid_product");
    exit;
}

// Calculate totals
$subtotal = $product['price'] * $quantity;
$tax_rate = 0.08; // 8% tax
$tax = $subtotal * $tax_rate;
$shipping = 5.99; // Fixed shipping rate
$total = $subtotal + $tax + $shipping;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Local Bakery Marketplace</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="checkout-container">
        <h1>Checkout</h1>
        
        <!-- Order Summary -->
        <div class="order-summary">
            <h2>Order Summary</h2>
            <div class="product-summary">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumbnail">
                <div class="product-details">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p>Baker: <?php echo htmlspecialchars($product['baker_name']); ?></p>
                    <p>Location: <?php echo htmlspecialchars($product['baker_location']); ?></p>
                    <p>Quantity: <?php echo $quantity; ?></p>
                    <p>Price per item: $<?php echo number_format($product['price'], 2); ?></p>
                </div>
            </div>
            
            <div class="price-breakdown">
                <div class="price-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="price-row">
                    <span>Tax (8%):</span>
                    <span>$<?php echo number_format($tax, 2); ?></span>
                </div>
                <div class="price-row">
                    <span>Shipping:</span>
                    <span>$<?php echo number_format($shipping, 2); ?></span>
                </div>
                <div class="price-row total">
                    <span>Total:</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Checkout Form -->
        <form id="checkout-form" action="process_order.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            <input type="hidden" name="quantity" value="<?php echo $quantity; ?>">
            <input type="hidden" name="total" value="<?php echo $total; ?>">
            
            <div class="form-section">
                <h2>Shipping Information</h2>
                <div class="form-group">
                    <label for="full_name">Full Name:</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="address">Street Address:</label>
                    <input type="text" id="address" name="address" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City:</label>
                        <input type="text" id="city" name="city" required>
                    </div>
                    <div class="form-group">
                        <label for="state">State:</label>
                        <input type="text" id="state" name="state" required>
                    </div>
                    <div class="form-group">
                        <label for="zip">ZIP Code:</label>
                        <input type="text" id="zip" name="zip" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2>Payment Information</h2>
                <div class="form-group">
                    <label for="card_number">Card Number:</label>
                    <input type="text" id="card_number" name="card_number" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry">Expiry Date:</label>
                        <input type="text" id="expiry" name="expiry" placeholder="MM/YY" required>
                    </div>
                    <div class="form-group">
                        <label for="cvv">CVV:</label>
                        <input type="text" id="cvv" name="cvv" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2>Delivery Instructions</h2>
                <div class="form-group">
                    <label for="delivery_notes">Special Instructions (Optional):</label>
                    <textarea id="delivery_notes" name="delivery_notes"></textarea>
                </div>
            </div>

            <button type="submit" class="checkout-button">Place Order</button>
        </form>
    </div>

    <script>
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic form validation
            const required = ['full_name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'card_number', 'expiry', 'cvv'];
            let isValid = true;
            
            required.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    input.classList.add('error');
                    isValid = false;
                } else {
                    input.classList.remove('error');
                }
            });
            
            // Validate email format
            const email = document.getElementById('email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                email.classList.add('error');
                isValid = false;
            }
            
            // Validate card number (basic check)
            const cardNumber = document.getElementById('card_number');
            if (!/^\d{16}$/.test(cardNumber.value.replace(/\s/g, ''))) {
                cardNumber.classList.add('error');
                isValid = false;
            }
            
            if (isValid) {
                // In a real application, you would typically handle payment processing here
                // For demo purposes, we'll just submit the form
                this.submit();
            } else {
                alert('Please fill in all required fields correctly.');
            }
        });
    </script>
</body>
</html>