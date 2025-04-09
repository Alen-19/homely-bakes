<?php
session_start();
include('connect.php');
include('header.php');

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

// Get order details and check if it belongs to the user and is delivered
$query = "SELECT o.*, p.product_name, p.image_url, r.first_name as baker_name, p.baker_id 
          FROM table_orders o 
          JOIN table_product p ON o.product_id = p.product_id 
          JOIN table_registration r ON p.baker_id = r.user_id 
          WHERE o.order_id = ? AND o.user_id = ? AND o.status = 'delivered'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

// If order not found or not delivered, redirect
if (!$order) {
    $_SESSION['error_message'] = "Order not found or not eligible for review.";
    header("Location: orders.php");
    exit();
}

// Check if review already exists
$check_review = "SELECT * FROM table_reviews WHERE order_id = ?";
$stmt = $conn->prepare($check_review);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$existing_review = $stmt->get_result()->fetch_assoc();

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing_review) {
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    
    if (!$rating || !$comment) {
        $error = "Please provide both rating and comment.";
    } else {
        // Insert review
        $insert_query = "INSERT INTO table_reviews (order_id, user_id, baker_id, rating, comment, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iiiis", $order_id, $user_id, $order['baker_id'], $rating, $comment);
        
        if ($stmt->execute()) {
            // Update baker's average rating
            $update_baker = "UPDATE table_registration 
                            SET avg_rating = (
                                SELECT AVG(rating) 
                                FROM table_reviews 
                                WHERE baker_id = ?
                            )
                            WHERE user_id = ?";
            $stmt = $conn->prepare($update_baker);
            $stmt->bind_param("ii", $order['baker_id'], $order['baker_id']);
            $stmt->execute();
            
            // Update order review status
            $update_order = "UPDATE table_orders SET review_status = 'reviewed' WHERE order_id = ?";
            $stmt = $conn->prepare($update_order);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            $_SESSION['success_message'] = "Thank you! Your review has been submitted successfully.";
            header("Location: orders.php");
            exit();
        } else {
            $error = "Error submitting review. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Leave a Review - Homely Bakes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .review-container {
            max-width: 800px;
            margin: 150px auto 40px;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .order-details {
            display: flex;
            align-items: center;
            gap: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 30px;
        }

        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-info h3 {
            margin: 0 0 10px;
            color: #333;
        }

        .baker-info {
            color: #666;
            font-size: 0.9em;
        }

        .rating-section {
            margin: 20px 0;
            text-align: center;
        }

        .star-rating {
            font-size: 2em;
            color: #ddd;
            cursor: pointer;
        }

        .star-rating .fas {
            color: #ffd700;
        }

        .comment-section {
            margin: 20px 0;
        }

        textarea {
            width: 100%;
            min-height: 150px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            margin: 10px 0;
        }

        .submit-btn {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: #45a049;
        }

        .existing-review {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .existing-review .rating {
            color: #ffd700;
            font-size: 1.2em;
            margin-bottom: 10px;
        }

        .existing-review .comment {
            color: #666;
            font-style: italic;
        }

        .review-date {
            color: #999;
            font-size: 0.9em;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="review-container">
        <div class="order-details">
            <img src="<?php echo htmlspecialchars($order['image_url']); ?>" alt="<?php echo htmlspecialchars($order['product_name']); ?>" class="product-image">
            <div class="product-info">
                <h3><?php echo htmlspecialchars($order['product_name']); ?></h3>
                <div class="baker-info">
                    <i class="fas fa-user"></i> Baker: <?php echo htmlspecialchars($order['baker_name']); ?>
                </div>
                <div class="order-info">
                    <i class="fas fa-shopping-cart"></i> Order #<?php echo $order_id; ?>
                </div>
            </div>
        </div>

        <?php if ($existing_review): ?>
            <div class="existing-review">
                <h3>Your Review</h3>
                <div class="rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?php echo $i <= $existing_review['rating'] ? 'active' : ''; ?>"></i>
                    <?php endfor; ?>
                </div>
                <div class="comment">
                    "<?php echo htmlspecialchars($existing_review['comment']); ?>"
                </div>
                <div class="review-date">
                    Posted on <?php echo date('F j, Y', strtotime($existing_review['created_at'])); ?>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="rating-section">
                    <h3>Rate your experience</h3>
                    <div class="star-rating" id="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="far fa-star" data-rating="<?php echo $i; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="rating-value" required>
                </div>

                <div class="comment-section">
                    <h3>Share your thoughts</h3>
                    <textarea name="comment" placeholder="Tell us about your experience with this order..." required></textarea>
                </div>

                <button type="submit" class="submit-btn">Submit Review</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Star rating functionality
        const starContainer = document.getElementById('star-rating');
        const stars = starContainer.getElementsByTagName('i');
        const ratingInput = document.getElementById('rating-value');

        function setRating(rating) {
            ratingInput.value = rating;
            for (let i = 0; i < stars.length; i++) {
                if (i < rating) {
                    stars[i].className = 'fas fa-star';
                } else {
                    stars[i].className = 'far fa-star';
                }
            }
        }

        for (let i = 0; i < stars.length; i++) {
            stars[i].addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                setRating(rating);
            });

            stars[i].addEventListener('mouseover', function() {
                const rating = this.getAttribute('data-rating');
                for (let j = 0; j < stars.length; j++) {
                    if (j < rating) {
                        stars[j].className = 'fas fa-star';
                    } else {
                        stars[j].className = 'far fa-star';
                    }
                }
            });

            starContainer.addEventListener('mouseout', function() {
                const currentRating = ratingInput.value || 0;
                for (let j = 0; j < stars.length; j++) {
                    if (j < currentRating) {
                        stars[j].className = 'fas fa-star';
                    } else {
                        stars[j].className = 'far fa-star';
                    }
                }
            });
        }
    </script>
</body>
</html> 