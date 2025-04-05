<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

session_start();
require_once 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get product ID and quantity from URL
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : null;
$quantity = isset($_GET['quantity']) ? $_GET['quantity'] : 1;

// Fetch product details
$stmt = $conn->prepare("
    SELECT 
        p.product_id,
        p.product_name,
        p.price,
        p.description,
        p.image_url,
        r.first_name as baker_name,
        r.city as baker_city,
        b.bakery_name,
        b.baker_id
    FROM table_product p 
    JOIN table_baker b ON p.baker_id = b.baker_id
    JOIN table_registration r ON b.user_id = r.user_id 
    WHERE p.product_id = ? AND p.is_active = 1
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: cart.php?error=invalid_product");
    exit();
}

$delivery_charge = 0; // Initialize delivery charge

// Calculate total price
$total_price = $product['price'] * $quantity + $delivery_charge; // Include delivery charge in total price

// After order is placed, clear the cart for this item
$delete_from_cart = $conn->prepare("DELETE FROM table_cart WHERE user_id = ? AND product_id = ?");
$delete_from_cart->bind_param("ii", $user_id, $product_id);
$delete_from_cart->execute();

// Get user address
$address_query = "SELECT street_address, city, district, state, country, pincode 
                 FROM table_registration 
                 WHERE user_id = ?";
$stmt = $conn->prepare($address_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_address = $result->fetch_assoc();

// Format baker location and customer location for distance calculation
$baker_location = $product['baker_city'];
$customer_location = $user_address['city'] . ", " . $user_address['state'] . ", " . $user_address['country'];

// Update the order processing section (after the session_start and before the HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $conn->begin_transaction();

        // Get the order details
        $user_id = $_SESSION['user_id'];
        $product_id = $_GET['product_id'];
        $quantity = $_POST['quantity'];
        $delivery_charge = $_POST['delivery_charge'];
        $distance = $_POST['distance'];
        $total_price = ($product['price'] * $quantity) + floatval($delivery_charge);
        $status = 'pending';
        $payment_status = 'pending';

        // Get address based on selection
        if ($_POST['address_choice'] === 'default') {
            $delivery_address = $user_address['street_address'] . ', ' . 
                              $user_address['city'] . ', ' . 
                              $user_address['district'] . ', ' . 
                              $user_address['state'] . ', ' . 
                              $user_address['country'] . ' - ' . 
                              $user_address['pincode'];
        } else {
            $delivery_address = $_POST['street_address'] . ', ' . 
                              $_POST['city'] . ', ' . 
                              $_POST['district'] . ', ' . 
                              $_POST['state'] . ', ' . 
                              $_POST['country'] . ' - ' . 
                              $_POST['pincode'];
        }

        // Insert into orders table
        $order_query = "INSERT INTO table_orders (
            user_id,
            baker_id,
            product_id,
            order_date,
            status,
            quantity,
            total_price,
            delivery_address,
            special_instructions,
            delivery_charge,
            distance,
            payment_status
        ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($order_query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $special_instructions = isset($_POST['special_instructions']) ? $_POST['special_instructions'] : '';
        
        // Update bind_param with correct number of parameters and types
        $stmt->bind_param(
            "iiisidssdds", // 11 parameters: i=integer, d=double, s=string
            $user_id,
            $product['baker_id'],
            $product_id,
            $status,
            $quantity,
            $total_price,
            $delivery_address,
            $special_instructions,
            $delivery_charge,
            $distance,
            $payment_status
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Get the order ID
        $order_id = $conn->insert_id;
        
        // Remove from cart and commit transaction
        $delete_cart = $conn->prepare("DELETE FROM table_cart WHERE user_id = ? AND product_id = ?");
        $delete_cart->bind_param("ii", $user_id, $product_id);
        $delete_cart->execute();
        
        $conn->commit();
        
        // Redirect to order confirmation
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit();

    } catch (Exception $e) {
        // Log the error
        error_log("Order placement error: " . $e->getMessage());
        
        // Rollback transaction
        $conn->rollback();
        
        // Set error message for display
        $error_message = "Failed to place order. Please try again. Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Place Order - Homely Bakes</title>
    <style>
        /* Modern Reset and Base Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            line-height: 1.6;
            background-color: #f7f9fc;
            color: #2d3748;
        }

        /* Container Styles */
        .order-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
            background: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            transition: transform 0.2s ease;
        }

        .order-container:hover {
            transform: translateY(-2px);
        }

        /* Title Styles */
        .order-title {
            font-size: 28px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 35px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
            position: relative;
        }

        .order-title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background: #4CAF50;
        }

        /* Section Styles */
        .section-title {
            font-size: 22px;
            font-weight: 600;
            color: #2d3748;
            margin: 25px 0 20px;
            padding-left: 12px;
            border-left: 4px solid #4CAF50;
        }

        /* Product Details Styles */
        .product-details, .delivery-details {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 500;
            color: #4a5568;
        }

        /* Form Controls */
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
            outline: none;
        }

        /* Quantity Input */
        input[type="number"] {
            width: 150px;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input[type="number"]:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        /* Address Selection */
        .address-selection {
            margin: 25px 0;
        }

        .address-option {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .address-option:hover {
            border-color: #4CAF50;
        }

        .address-box {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #4CAF50;
        }

        /* Price Details */
        .price-details {
            background: #f8fafc;
            padding: 25px;
            border-radius: 12px;
            margin: 30px 0;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 16px;
        }

        .price-row.total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            font-size: 20px;
            font-weight: 600;
            color: #1a202c;
        }

        /* Submit Button */
        .submit-btn {
            background: #4CAF50;
            color: white;
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 30px;
        }

        .submit-btn:hover {
            background: #43a047;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* Error Messages */
        .error-message {
            color: #e53e3e;
            font-size: 14px;
            margin-top: 8px;
            padding-left: 12px;
            border-left: 3px solid #e53e3e;
            background: #fff5f5;
            padding: 8px 12px;
            border-radius: 4px;
        }

        /* Delivery Info */
        .delivery-info {
            background: linear-gradient(to right, #f8fafc, #ffffff);
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            border-left: 4px solid #4CAF50;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        /* Special Instructions */
        textarea {
            width: 100%;
            min-height: 120px;
            padding: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            resize: vertical;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        textarea:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .order-container {
                margin: 20px;
                padding: 20px;
            }

            .price-row {
                flex-direction: column;
                gap: 8px;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }

        /* Add these button styles to the existing CSS */
        .submit-btn:disabled {
            background: #a5d6a7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .submit-btn:disabled:hover {
            background: #a5d6a7;
            transform: none;
            box-shadow: none;
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <div class="order-container">
        <h2 class="order-title">Place Order</h2>
           
        <?php if (isset($error_message)): ?>
            <div class="error-alert" style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="product-details">
            <h3 class="section-title">Product Details</h3>
            <div class="detail-row">
                <span class="detail-label">Product Name:</span> 
                <?php echo htmlspecialchars($product['product_name']); ?>
            </div>
            <div class="detail-row">
                <span class="detail-label">Baker:</span> 
                <?php echo htmlspecialchars($product['baker_name']); ?>
            </div>
            <div class="detail-row">
                <span class="detail-label">Baker Location:</span> 
                <?php echo htmlspecialchars($baker_location); ?>
            </div>
            <div class="detail-row">
                <span class="detail-label">Base Price:</span> 
                ₹<?php echo number_format($product['price'], 2); ?> /kg
            </div>
        </div>

        <form id="orderForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?product_id=' . $product_id; ?>">
            <input type="hidden" name="delivery_charge" id="delivery-charge-input" value="0">
            <input type="hidden" name="distance" id="distance-input" value="0">
            <input type="hidden" name="total_price" id="total-price-input" value="<?php echo $total_price; ?>">
            
            <div class="quantity-section">
                <label for="quantity">Quantity (kg):</label>
                <input type="number" id="quantity" name="quantity" min="1" max="10" value="1" oninput="validateQuantity(this); updateTotalPrice();" required>
            </div>

            <div class="delivery-details">
                <h3 class="section-title">Delivery Address</h3>
                
                <div class="address-selection">
                    <div class="address-option">
                        <input type="radio" id="default-address" name="address_choice" value="default" checked 
                               onclick="toggleAddressForm('default')">
                        <label for="default-address">Use Default Address</label>
                        
                        <div class="default-address-box address-box" id="default-address-box">
                            <p><?php echo htmlspecialchars($user_address['street_address'] ?? ''); ?></p>
                            <p><?php echo htmlspecialchars($user_address['city'] ?? ''); ?></p>
                            <p><?php echo htmlspecialchars($user_address['district'] ?? ''); ?></p>
                            <p><?php echo htmlspecialchars($user_address['state'] ?? ''); ?></p>
                            <p><?php echo htmlspecialchars($user_address['country'] ?? ''); ?></p>
                            <p>PIN: <?php echo htmlspecialchars($user_address['pincode'] ?? ''); ?></p>
                        </div>
                    </div>

                    <div class="address-option">
                        <input type="radio" id="new-address" name="address_choice" value="new" 
                               onclick="toggleAddressForm('new')">
                        <label for="new-address">Use Different Address</label>
                        
                        <div class="new-address-form" id="new-address-form" style="display: none;">
                            <div class="form-group">
                                <label for="country">Country:</label>
                                <input type="text" id="country" name="country" class="form-control" value="India" readonly>
                                <small class="error-message" id="country-error"></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="state">State:</label>
                                <select id="state" name="state" class="form-control">
                                    <option value="">Select State</option>
                                </select>
                                <small class="error-message" id="state-error"></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="district">District:</label>
                                <select id="district" name="district" class="form-control">
                                    <option value="">Select District</option>
                                </select>
                                <small class="error-message" id="district-error"></small>
                            </div>

                            <div class="form-group">
                                <label for="street_address">Street Address/House Name:</label>
                                <input type="text" id="street_address" name="street_address" class="form-control" oninput="validateStreetAddress(this);">
                                <small class="error-message" id="street-error"></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="city">City:</label>
                                <input type="text" id="city" name="city" class="form-control">
                                <small class="error-message" id="city-error"></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="pincode">PIN Code:</label>
                                <input type="text" id="pincode" name="pincode" class="form-control">
                                <small class="error-message" id="pincode-error"></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="delivery-info">
                <div id="distance-info">Calculating delivery distance...</div>
                <div id="delivery-charge-info">Calculating delivery charge...</div>
            </div>

            <div class="special-instructions">
                <label for="special_instructions">Special Instructions (optional):</label>
                <textarea name="special_instructions" id="special_instructions" rows="4"></textarea>
            </div>
            
            <div class="price-details">
                <div class="price-row">
                    <span>Base Price:</span>
                    <span>₹<span id="base-price"><?php echo number_format($product['price'], 2); ?></span>/kg</span>
                </div>
                <div class="price-row">
                    <span>Product Price (₹<span id="base-price-display"><?php echo number_format($product['price'], 2); ?></span> × <span id="quantity-display">1</span>kg):</span>
                    <span>₹<span id="calculated-product-price"><?php echo number_format($product['price'], 2); ?></span></span>
                </div>
                <div class="price-row">
                    <span>Delivery Charge:</span>
                    <span>₹<span id="delivery-charge"><?php echo number_format($delivery_charge, 2); ?></span></span>
                </div>
                <div class="price-row total">
                    <strong>Total Price:</strong>
                    <strong>₹<span id="total-price">0.00</span></strong>
                </div>
            </div>

            <button type="submit" class="submit-btn">Place Order</button>
        </form>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize when page loads
    calculateDistance();
    updateTotalPrice();

    // Initially disable the button
    const submitButton = document.querySelector('.submit-btn');
    submitButton.disabled = true;
    submitButton.textContent = 'Calculating Delivery Charge...';

    // Handle form submission
    document.getElementById('orderForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        let isValid = true;
        const addressChoice = document.querySelector('input[name="address_choice"]:checked').value;
        
        if (addressChoice === 'new') {
            const fields = ['street_address', 'city', 'district', 'state', 'country', 'pincode'];
            for (const fieldId of fields) {
                const input = document.getElementById(fieldId);
                if (!validateField(input)) {
                    isValid = false;
                }
            }
        }

        // Update hidden fields before submission
        document.getElementById('total-amount-input').value = 
            parseFloat(document.getElementById('total-price').textContent);

        if (isValid) {
            this.submit();
        }
    });
});

// Initial values
const basePrice = <?php echo $product['price']; ?>;
let deliveryCharge = 0;
let distance = 0;
let quantity = <?php echo $quantity; ?>;

// Default customer location
let customerLocation = "<?php echo htmlspecialchars($customer_location); ?>";
const bakerLocation = "<?php echo htmlspecialchars($baker_location); ?>";

function updateTotalPrice() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const basePrice = parseFloat(document.getElementById('base-price').textContent.replace(/,/g, '')) || 0;
    const deliveryCharge = parseFloat(document.getElementById('delivery-charge').textContent) || 0;
    
    const productTotal = quantity * basePrice;
    const totalPrice = productTotal + deliveryCharge;
    
    // Update displays with proper formatting
    document.getElementById('quantity-display').textContent = quantity;
    document.getElementById('calculated-product-price').textContent = productTotal.toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    document.getElementById('total-price').textContent = totalPrice.toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    document.getElementById('total-amount-input').value = totalPrice.toFixed(2);
}

function updateDeliveryCharge(dist) {
    distance = dist;
    deliveryCharge = Math.max(20, Math.round(dist * 5));
    
    document.getElementById('distance-info').textContent = 
        `Estimated distance: ${parseFloat(dist).toFixed(2)} km`;
    document.getElementById('delivery-charge-info').textContent = 
        `Delivery charge: ₹${deliveryCharge.toFixed(2)}`;
    document.getElementById('delivery-charge').textContent = deliveryCharge.toFixed(2);
    document.getElementById('delivery-charge-input').value = deliveryCharge.toFixed(2);
    document.getElementById('distance-input').value = parseFloat(dist).toFixed(2);
    
    // Enable the submit button once delivery charge is calculated
    const submitButton = document.querySelector('.submit-btn');
    submitButton.disabled = false;
    submitButton.textContent = 'Place Order';
    
    // Update total price
    updateTotalPrice();
}

function toggleAddressForm(type) {
    const defaultAddressBox = document.getElementById('default-address-box');
    const newAddressForm = document.getElementById('new-address-form');
    
    if (type === 'default') {
        defaultAddressBox.style.display = 'block';
        newAddressForm.style.display = 'none';
        // Reset to default customer location
        customerLocation = "<?php echo htmlspecialchars($customer_location); ?>";
        calculateDistance();
    } else {
        defaultAddressBox.style.display = 'none';
        newAddressForm.style.display = 'block';
    }
}

function recalculateDistance() {
    // Get values from the new address form
    const city = document.getElementById('city').value;
    const state = document.getElementById('state').value;
    const country = document.getElementById('country').value;
    
    if (city && state && country) {
        customerLocation = `${city}, ${state}, ${country}`;
        calculateDistance();
    }
}

// Add rate limiting for Nominatim API
const NOMINATIM_DELAY = 1000; // 1 second delay between requests
let lastNominatimRequest = 0;

async function getCoordinates(location) {
    try {
        // Implement rate limiting
        const now = Date.now();
        const timeSinceLastRequest = now - lastNominatimRequest;
        if (timeSinceLastRequest < NOMINATIM_DELAY) {
            await new Promise(resolve => setTimeout(resolve, NOMINATIM_DELAY - timeSinceLastRequest));
        }
        
        // Add user agent as required by Nominatim usage policy
        const response = await fetch(
            `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(location)}`,
            {
                headers: {
                    'User-Agent': 'HomelyBakes_Website/1.0'
                }
            }
        );
        
        lastNominatimRequest = Date.now();

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.length === 0) {
            console.warn(`No coordinates found for location: ${location}`);
            return null;
        }
        
        return { 
            lat: parseFloat(data[0].lat), 
            lon: parseFloat(data[0].lon) 
        };
    } catch (error) {
        console.error(`Error getting coordinates for ${location}:`, error);
        return null;
    }
}

async function getDrivingDistance(location1, location2) {
    try {
        console.log('Calculating distance between:', location1, 'and', location2);
        
        const loc1 = await getCoordinates(location1);
        const loc2 = await getCoordinates(location2);
        
        if (!loc1 || !loc2) {
            console.warn('Could not get coordinates for one or both locations, falling back to default distance');
            return 5; // Default fallback distance
        }

        console.log('Coordinates obtained:', 
            'Location 1:', loc1, 
            'Location 2:', loc2
        );

        const response = await fetch(
            `https://router.project-osrm.org/route/v1/driving/${loc1.lon},${loc1.lat};${loc2.lon},${loc2.lat}?overview=false`
        );

        if (!response.ok) {
            throw new Error(`OSRM API HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.code !== 'Ok' || !data.routes || data.routes.length === 0) {
            throw new Error('No route found in OSRM response');
        }

        const distance_km = data.routes[0].distance / 1000;
        console.log('Calculated distance:', distance_km, 'km');
        
        return Math.max(1, distance_km); // Ensure minimum 1km distance
    } catch (error) {
        console.error('Error calculating driving distance:', error);
        console.log('Falling back to Haversine distance calculation');
        return calculateHaversineDistance(location1, location2);
    }
}

// Fallback to Haversine distance calculation if driving distance fails
async function calculateHaversineDistance(location1, location2) {
    try {
        const loc1 = await getCoordinates(location1);
        const loc2 = await getCoordinates(location2);
        
        if (!loc1 || !loc2) {
            throw new Error("Could not get coordinates for Haversine calculation");
        }
        
        // Calculate distance using Haversine formula
        const dist = haversineDistance(
            parseFloat(loc1.lat), 
            parseFloat(loc1.lon), 
            parseFloat(loc2.lat), 
            parseFloat(loc2.lon)
        );
        
        return dist;
    } catch (error) {
        console.error("Error calculating Haversine distance:", error);
        return 5; // Default to 5km if all calculations fail
    }
}

// Haversine formula to calculate straight-line distance between two coordinates
function haversineDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth radius in kilometers
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
        
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    const distance = R * c; // Distance in km
    
    return Math.max(1, distance); // Ensure minimum 1km distance
}

// Main function to calculate distance with proper error handling
async function calculateDistance() {
    const submitButton = document.querySelector('.submit-btn');
    submitButton.disabled = true;
    submitButton.textContent = 'Calculating Delivery Charge...';
    
    const distanceInfo = document.getElementById('distance-info');
    const deliveryChargeInfo = document.getElementById('delivery-charge-info');
    
    distanceInfo.textContent = "Calculating distance...";
    deliveryChargeInfo.textContent = "Calculating delivery charge...";
    
    console.log('Starting distance calculation between:', bakerLocation, 'and', customerLocation);
    
    try {
        const dist = await getDrivingDistance(bakerLocation, customerLocation);
        console.log('Distance calculation result:', dist);
        
        if (isNaN(dist) || dist <= 0) {
            throw new Error("Invalid distance calculated");
        }
        
        updateDeliveryCharge(dist);
    } catch (error) {
        console.error("Error in distance calculation:", error);
        distanceInfo.textContent = "Could not calculate exact distance. Using estimated distance.";
        updateDeliveryCharge(5); // Use default 5km distance
    }
}

function validateQuantity(input) {
    if (input.value > 10) {
        input.value = 10;
        alert('Maximum order quantity is 10kg');
    }
    if (input.value < 1) {
        input.value = 1;
    }
    document.getElementById('quantity-display').textContent = input.value;
}

// Load state-district data
fetch('india-states-districts.json')
    .then(response => response.json())
    .then(statesAndDistricts => {
        const stateSelect = document.getElementById('state');
        Object.keys(statesAndDistricts).forEach(state => {
            const option = document.createElement('option');
            option.value = state;
            option.textContent = state;
            stateSelect.appendChild(option);
        });

        // Handle state change
        stateSelect.addEventListener('change', function() {
            const districtSelect = document.getElementById('district');
            districtSelect.innerHTML = '<option value="">Select District</option>';
            
            if (statesAndDistricts[this.value]) {
                statesAndDistricts[this.value].forEach(district => {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    districtSelect.appendChild(option);
                });
            }
            validateField(this);
        });
    });

// Load pincodes
let pincodeList = [];
fetch("pincode.json")
    .then(response => response.json())
    .then(data => {
        pincodeList = data.pincodes.map(String);
    });

function showError(element, errorElement, message) {
    if (!element || !errorElement) return;
    
    element.classList.add('error');
    errorElement.textContent = message;
    errorElement.classList.add('visible');
}

function hideError(element, errorElement) {
    if (!element || !errorElement) return;
    
    element.classList.remove('error');
    errorElement.textContent = '';
    errorElement.classList.remove('visible');
}

function validateStreetAddress(input) {
    if (!input) return false;
    
    const errorElement = document.getElementById('street-error');
    if (!errorElement) return false;
    
    const value = input.value.trim();
    
    // First hide any existing error

    if (!value) {
        showError(input, errorElement, 'Street address is required');
        return false;
    } else if (value.length < 5 || value.length > 100) {
        showError(input, errorElement, 'Address should be 5-100 characters long');
        return false;
    } else if (/^\d+$/.test(value)) {
        showError(input, errorElement, 'Address cannot contain only numbers');
        return false;
    } else if (!/^[a-zA-Z0-9\s,.-/#]+$/.test(value)) {
        showError(input, errorElement, 'Address can only contain letters, numbers, spaces, and basic punctuation (,.-/#)');
        return false;
    }
    
    return true;
}

function validateField(input) {
    if (!input) return false;
    
    const errorElement = document.getElementById(`${input.id}-error`);
    if (!errorElement) return false;
    
    let isValid = true;

    // Remove previous validation classes
    hideError(input, errorElement);

    const value = input.value.trim();

    switch(input.id) {
        case 'street_address':
            return validateStreetAddress(input);
        case 'city':
            if (!value) {
                showError(input, errorElement, 'City is required');
                isValid = false;
            } else if (!/^[a-zA-Z0-9\s]{2,50}$/.test(value)) {
                showError(input, errorElement, 'City name should be 2-50 characters long and contain only letters, numbers and spaces');
                isValid = false;
            } else if (/^\d+$/.test(value)) {
                showError(input, errorElement, 'City name cannot contain only numbers');
                isValid = false;
            }
            break;
        case 'district':
            if (!value) {
                showError(input, errorElement, 'Please select a district');
                isValid = false;
            }
            break;
        case 'state':
            if (!value) {
                showError(input, errorElement, 'Please select a state');
                isValid = false;
            }
            break;
        case 'pincode':
            if (!value) {
                showError(input, errorElement, 'PIN code is required');
                isValid = false;
            } else if (!/^\d{6}$/.test(value)) {
                showError(input, errorElement, 'Please enter a valid 6-digit PIN code');
                isValid = false;
            } else if (!pincodeList.includes(value)) {
                showError(input, errorElement, 'This PIN code is not in our database');
                isValid = false;
            }
            break;
    }

    return isValid;
}

// Add live validation listeners
document.querySelectorAll('#new-address-form .form-control').forEach(input => {
    let inputTimeout;

    // Real-time validation with delay
    input.addEventListener('input', () => {
        clearTimeout(inputTimeout);
        if (input.value.trim()) {
            inputTimeout = setTimeout(() => {
                validateField(input);
            }, 500);
        } else {
            hideError(input, document.getElementById(`${input.id}-error`));
        }
    });

    // Validate on blur
    input.addEventListener('blur', () => {
        validateField(input);
    });

    // Clear error on focus
    input.addEventListener('focus', () => {
        hideError(input, document.getElementById(`${input.id}-error`));
    });
});
    </script>

</body>
</html>
<?php
// Flush the output buffer
ob_end_flush();
?>
