<!-- cart.php -->
<?php
session_start();
include('connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch cart items from the database
$query = "SELECT c.*, p.product_name, p.price FROM table_cart c JOIN table_product p ON c.product_id = p.product_id WHERE c.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total_price = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart - Homely Bakes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .cart-container {
            max-width: 1000px;
            margin: 100px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .cart-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .cart-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            color: #333;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        .cart-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .product-name {
            color: #333;
            font-weight: 500;
        }

        .price {
            color: #2e7d32;
            font-weight: 500;
        }

        .quantity-input {
            width: 80px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            -moz-appearance: textfield; /* Firefox */
        }

        .quantity-input::-webkit-inner-spin-button,
        .quantity-input::-webkit-outer-spin-button {
            opacity: 1;
            height: 24px;
        }

        .update-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            transition: background-color 0.3s;
        }

        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .update-btn:hover {
            background: #45a049;
        }

        .remove-btn:hover {
            background: #c82333;
        }

        .total-section {
            text-align: right;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-top: 20px;
        }

        .total-price {
            font-size: 20px;
            color: #2e7d32;
            font-weight: bold;
        }

        .empty-cart {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .continue-shopping {
            display: inline-block;
            padding: 12px 24px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .continue-shopping:hover {
            background: #5a6268;
        }

        .checkout-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .checkout-btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <div class="cart-container">
        <h1 class="cart-title">Shopping Cart</h1>
        
        <?php if ($result->num_rows === 0): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart fa-3x" style="color: #ddd; margin-bottom: 20px;"></i>
                <p>Your cart is empty</p>
                <a href="product.php" class="continue-shopping">Continue Shopping</a>
            </div>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="product-name"><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td class="price">₹<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <input type="number" 
                                       class="quantity-input" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" 
                                       max="10" 
                                       data-product-id="<?php echo $item['product_id']; ?>"
                                       onchange="updateCart(<?php echo $item['product_id']; ?>, this)"
                                       oninput="if(this.value > 10) this.value = 10; if(this.value < 1) this.value = 1;">
                            </td>
                            <td class="price item-total">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="remove-btn" onclick="removeFromCart(<?php echo $item['product_id']; ?>)">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php $total_price += $item['price'] * $item['quantity']; ?>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="total-section">
                <div class="total-price">Total: ₹<?php echo number_format($total_price, 2); ?></div>
                <div style="margin-top: 20px;">
                    <a href="product.php" class="continue-shopping">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                    <a href="place_order.php?<?php 
                        // Reset result pointer to beginning
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $items = [];
                        while($item = $result->fetch_assoc()) {
                            $items[] = "product_id=" . $item['product_id'] . "&quantity=" . $item['quantity'];
                        }
                        echo implode("&", $items);
                    ?>" class="checkout-btn">
                        Proceed to Order <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateCart(productId, inputElement) {
            // Limit quantity to maximum 10
            if (inputElement.value > 10) {
                inputElement.value = 10;
            }
            if (inputElement.value < 1) {
                inputElement.value = 1;
            }

            const quantity = inputElement.value;
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            // Find the price from the row
            const row = inputElement.closest('tr');
            const priceText = row.querySelector('.price').innerText;
            const unitPrice = parseFloat(priceText.replace('₹', '').replace(',', '')); // Handle comma in price

            // Update the total for this item
            const itemTotal = row.querySelector('.item-total');
            const totalAmount = (unitPrice * quantity).toFixed(2);
            itemTotal.innerText = '₹' + totalAmount.replace(/\B(?=(\d{3})+(?!\d))/g, ","); // Add comma formatting

            // Update the grand total
            updateTotalPrice();

            fetch('update_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message);
                    location.reload(); // Reload if there's an error to restore correct state
                }
            });
        }

        function updateTotalPrice() {
            const itemTotals = document.querySelectorAll('.item-total');
            let grandTotal = 0;
            
            itemTotals.forEach(item => {
                const amount = parseFloat(item.innerText.replace('₹', '').replace(/,/g, '')); // Handle comma in price
                grandTotal += amount;
            });

            document.querySelector('.total-price').innerText = 'Total: ₹' + grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        function removeFromCart(productId) {
            if (confirm('Are you sure you want to remove this item?')) {
                const formData = new FormData();
                formData.append('product_id', productId);

                fetch('remove_from_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>