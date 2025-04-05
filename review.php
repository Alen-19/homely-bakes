<?php
session_start();
include('connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Get order details
$query = "SELECT o.*, p.product_name, r.first_name as baker_name 
          FROM table_orders o 
          JOIN table_product p ON o.product_id = p.product_id 
          JOIN table_registration r ON o.baker_id = r.user_id 
          WHERE o.order_id = ? AND o.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

// If order not found or doesn't belong to user
if (!$order) {
    header("Location: orders.php");
    exit();
}

// Check if review already submitted
$query = "SELECT * FROM table_reviews WHERE order_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$review_result = $stmt->get_result();

if ($review_result->num_rows > 0) {
    header("Location: orders.php?already_reviewed=true");
    exit();
}

$pageTitle = "Review Order - Homely Bakes";
include('header.php');
?>

<style>
.review-container {
    max-width: 600px;
    margin: 100px auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.product-info {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.product-details h3 {
    margin: 0 0 10px;
    color: #333;
}

.baker-name {
    color: #666;
    font-size: 0.9em;
}

.rating-container {
    margin-bottom: 20px;
}

.stars {
    display: flex;
    gap: 10px;
    margin: 10px 0;
}

.star {
    font-size: 24px;
    cursor: pointer;
    color: #ddd;
}

.star.active {
    color: #ffd700;
}

textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 20px;
    min-height: 100px;
    resize: vertical;
}

.submit-btn {
    background: #4CAF50;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

.submit-btn:hover {
    background: #45a049;
}

.error-message {
    color: #dc3545;
    margin-bottom: 10px;
    display: none;
}
</style>

<div class="review-container">
    <div class="product-info">
        <div class="product-details">
            <h3><?php echo htmlspecialchars($order['product_name']); ?></h3>
            <span class="baker-name">Baker: <?php echo htmlspecialchars($order['baker_name']); ?></span>
        </div>
    </div>

    <form id="reviewForm" action="submit_review.php" method="POST">
        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
        <input type="hidden" name="rating" id="ratingInput" value="">
        
        <div class="rating-container">
            <h4>Rate your experience</h4>
            <div class="stars">
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <i class="star fas fa-star" data-rating="<?php echo $i; ?>"></i>
                <?php endfor; ?>
            </div>
            <div class="error-message" id="ratingError">Please select a rating</div>
        </div>

        <div class="review-container">
            <h4>Write your review</h4>
            <textarea name="review_text" placeholder="Tell us about your experience..."></textarea>
        </div>

        <button type="submit" class="submit-btn">Submit Review</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star');
    const ratingInput = document.getElementById('ratingInput');
    const form = document.getElementById('reviewForm');
    const ratingError = document.getElementById('ratingError');

    // Handle star rating
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.dataset.rating;
            ratingInput.value = rating;
            ratingError.style.display = 'none';
            
            // Update stars visual
            stars.forEach(s => {
                if (s.dataset.rating <= rating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });

        star.addEventListener('mouseover', function() {
            const rating = this.dataset.rating;
            stars.forEach(s => {
                if (s.dataset.rating <= rating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });
    });

    // Handle form submission
    form.addEventListener('submit', function(e) {
        if (!ratingInput.value) {
            e.preventDefault();
            ratingError.style.display = 'block';
            return false;
        }
    });

    // Reset stars on mouseout if no rating selected
    document.querySelector('.stars').addEventListener('mouseout', function() {
        const rating = ratingInput.value;
        stars.forEach(s => {
            if (rating && s.dataset.rating <= rating) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });
    });
});
</script>

<?php include('footer.php'); ?> 