<?php
require_once 'header.php';
?>

<div class="container" style="text-align: center; margin-top: 50px;">
    <div class="order-status">
        <h2>Order Request Status</h2>
        <div class="status-message">
            <p>Request Pending</p>
            <p>We will process your order shortly.</p>
        </div>
        <a href="index.php" class="back-btn">Back to Home</a>
    </div>
</div>

<style>
.order-status {
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    max-width: 500px;
    margin: 0 auto;
}

.status-message {
    margin: 20px 0;
    padding: 20px;
    background: #fff9e6;
    border-radius: 5px;
}

.status-message p {
    margin: 10px 0;
    color: #333;
}

.status-message p:first-child {
    font-size: 24px;
    color: #ff6b6b;
    font-weight: bold;
}

.back-btn {
    display: inline-block;
    padding: 10px 20px;
    background: #ff6b6b;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 20px;
    transition: background 0.3s ease;
}

.back-btn:hover {
    background: #ff5252;
}
</style>


