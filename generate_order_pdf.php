<?php
session_start();
include('connect.php');

// Use the Composer-installed version of FPDF
require('vendor/autoload.php');

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
$query = "SELECT o.*, p.product_name, r.first_name as baker_name, r.mobile_number as baker_phone,
          u.first_name as customer_name, u.mobile_number as customer_phone
          FROM table_orders o 
          JOIN table_product p ON o.product_id = p.product_id 
          JOIN table_registration r ON o.baker_id = r.user_id 
          JOIN table_registration u ON o.user_id = u.user_id
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

class OrderPDF extends FPDF {
    function Header() {
        // Set font instead of using image since GD extension might be missing
        $this->SetFont('Arial', 'B', 24);
        $this->Cell(0, 15, 'Homely Bakes', 0, 1, 'C');
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 5, 'Your Trusted Home Bakery Marketplace', 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function OrderInfo($order) {
        // Order Header
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Order Details', 0, 1, 'L');
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 10, 'Order ID: #' . $order['order_id'], 0, 1, 'L');
        $this->Cell(0, 10, 'Date: ' . date('F j, Y', strtotime($order['order_date'])), 0, 1, 'L');
        $this->Ln(5);

        // Customer & Baker Info
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(95, 10, 'Customer Information', 0, 0, 'L');
        $this->Cell(95, 10, 'Baker Information', 0, 1, 'L');
        
        $this->SetFont('Arial', '', 12);
        $this->Cell(95, 8, 'Name: ' . $order['customer_name'], 0, 0, 'L');
        $this->Cell(95, 8, 'Name: ' . $order['baker_name'], 0, 1, 'L');
        
        $this->Cell(95, 8, 'Phone: ' . $order['customer_phone'], 0, 0, 'L');
        $this->Cell(95, 8, 'Phone: ' . $order['baker_phone'], 0, 1, 'L');
        
        $this->Ln(5);

        // Delivery Address
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Delivery Address', 0, 1, 'L');
        $this->SetFont('Arial', '', 12);
        $this->MultiCell(0, 8, $order['delivery_address'], 0, 'L');
        $this->Ln(5);

        // Order Details
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Product Details', 0, 1, 'L');
        
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 8, 'Product: ' . $order['product_name'], 0, 1, 'L');
        $this->Cell(0, 8, 'Quantity: ' . $order['quantity'] . ' kg', 0, 1, 'L');
        
        if ($order['special_instructions']) {
            $this->Cell(0, 8, 'Special Instructions:', 0, 1, 'L');
            $this->MultiCell(0, 8, $order['special_instructions'], 0, 'L');
        }
        $this->Ln(5);

        // Price Breakdown
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Price Details', 0, 1, 'L');
        
        $this->SetFont('Arial', '', 12);
        $this->Cell(140, 8, 'Product Price:', 0, 0, 'L');
        $this->Cell(50, 8, 'Rs. ' . number_format($order['total_price'] - $order['delivery_charge'], 2), 0, 1, 'R');
        
        $this->Cell(140, 8, 'Delivery Charge:', 0, 0, 'L');
        $this->Cell(50, 8, 'Rs. ' . number_format($order['delivery_charge'], 2), 0, 1, 'R');
        
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(140, 8, 'Total Amount:', 0, 0, 'L');
        $this->Cell(50, 8, 'Rs. ' . number_format($order['total_price'], 2), 0, 1, 'R');
        
        // Payment Status
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Payment Status: ' . ucfirst($order['payment_status']), 0, 1, 'L');
        
        // Order Status
        $this->Cell(0, 10, 'Order Status: ' . ucfirst($order['status']), 0, 1, 'L');
    }
}

// Create PDF
$pdf = new OrderPDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->OrderInfo($order);

// Generate filename
$filename = 'Order_' . $order_id . '_' . date('Y-m-d') . '.pdf';

// Output PDF
$pdf->Output('D', $filename);
?>
