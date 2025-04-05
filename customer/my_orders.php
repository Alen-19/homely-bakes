<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
?>

<div class="my-orders-container">
    <h2>My Orders</h2>
    <div class="orders-list" id="my-orders-list"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadMyOrders();
});

function loadMyOrders() {
    fetch('get_my_orders.php')
        .then(response => response.json())
        .then(orders => {
            const ordersList = document.getElementById('my-orders-list');
            ordersList.innerHTML = '';

            orders.forEach(order => {
                const orderCard = document.createElement('div');
                orderCard.className = `order-card ${order.status}`;
                orderCard.innerHTML = `
                    <div class="order-details">
                        <h3>Order #${order.order_id}</h3>
                        <p>Product: ${order.product_name}</p>
                        <p>Baker: ${order.baker_name}</p>
                        <p>Quantity: ${order.quantity}</p>
                        <p>Total: ₹${order.total_price}</p>
                        <p>Status: <span class="status-badge ${order.status}">${order.status}</span></p>
                        <p>Order Date: ${new Date(order.order_date).toLocaleString()}</p>
                    </div>
                `;
                ordersList.appendChild(orderCard);
            });
        })
        .catch(error => console.error('Error loading orders:', error));
}
</script> 