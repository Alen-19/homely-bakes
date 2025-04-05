<?php
session_start();
if (!isset($_SESSION['baker_id'])) {
    header('Location: ../login.php');
    exit;
}
?>

<div class="orders-container">
    <h2>Order Requests</h2>
    <div class="order-tabs">
        <button class="tab-btn active" data-status="pending">Pending</button>
        <button class="tab-btn" data-status="accepted">Accepted</button>
        <button class="tab-btn" data-status="rejected">Rejected</button>
        <button class="tab-btn" data-status="completed">Completed</button>
    </div>
    <div id="orders-list"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadOrders('pending');
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            loadOrders(this.dataset.status);
        });
    });
});
</script> 