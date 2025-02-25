<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['product_id'])) {
    header("Location: product.php");
    exit();
}

$product_id = mysqli_real_escape_string($conn, $_GET['product_id']);
$query = "SELECT * FROM table_product WHERE product_id = '$product_id' AND stock > 0";
$result = mysqli_query($conn, $query);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    header("Location: product.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - Homely Bakes</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .order-form {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .product-summary {
            background: #f9f9f9;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        .total-price {
            font-size: 1.2rem;
            font-weight: bold;
            margin-top: 1rem;
        }
        .submit-btn {
            background: #4CAF50;
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .submit-btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>

    <div class="container">
        <div class="order-form">
            <h2>Place Order</h2>
            
            <div class="product-summary">
                <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                <p>Base Price: ₹<?php echo number_format($product['price'], 2); ?> /kg</p>
            </div>

            <form id="orderForm" action="orderrequest.php" method="POST">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                
                <div class="form-group">
                    <label for="size">Size (kg):</label>
                    <input type="number" id="size" name="size" min="1" max="10" value="1" required>
                </div>

                <div class="form-group">
                    <label for="delivery_address">Delivery Address:</label>
                    <textarea id="delivery_address" name="delivery_address" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="city">City:</label>
                    <input type="text" id="city" name="city" required>
                </div>

                <div class="form-group">
                    <label for="state">State:</label>
                    <input type="text" id="state" name="state" required>
                </div>

                <div class="form-group">
                    <label for="country">Country:</label>
                    <input type="text" id="country" name="country" required>
                </div>

                <div class="total-price">
                    Total Price: ₹<span id="totalPrice"><?php echo number_format($product['price'], 2); ?></span>
                </div>

                <button type="submit" class="submit-btn">Place Order</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('size').addEventListener('change', function() {
            const basePrice = <?php echo $product['price']; ?>;
            const size = this.value;
            const totalPrice = basePrice * size;
            document.getElementById('totalPrice').textContent = totalPrice.toFixed(2);
        });
    </script>
</body>
</html>
