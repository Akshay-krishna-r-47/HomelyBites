<?php
require('fpdf.php');
include 'role_check.php';
// We allow customer, seller, admin to download, but here checks for customer primarily or just login
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

if (!isset($_GET['id'])) {
    die("Order ID missing");
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Fetch Order (ensure user owns it OR is admin/seller - strict check for customer)
if ($_SESSION['role'] == 'Customer') {
    $sql = "SELECT o.*, u.name as seller_name, u.phone as seller_phone 
            FROM orders o 
            LEFT JOIN users u ON o.seller_id = u.user_id 
            WHERE o.order_id = ? AND o.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $order_id, $user_id);
} else {
     // Allow generic access for other roles (simplified for now)
    $sql = "SELECT o.*, u.name as seller_name, u.phone as seller_phone 
            FROM orders o 
            LEFT JOIN users u ON o.seller_id = u.user_id 
            WHERE o.order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Order not found or access denied.");
}
$order = $result->fetch_assoc();
$stmt->close();

// Fetch Items
$items_sql = "SELECT oi.quantity, oi.price, f.name FROM order_items oi JOIN foods f ON oi.food_id = f.id WHERE oi.order_id = ?";
$stmt_items = $conn->prepare($items_sql);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$res_items = $stmt_items->get_result();
$order_items = [];
while ($row = $res_items->fetch_assoc()) {
    $order_items[] = $row;
}
$stmt_items->close();

// Fallback legacy
if (empty($order_items) && !empty($order['items'])) {
    $order_items[] = ['name' => $order['items'], 'quantity' => 1, 'price' => $order['total_amount']];
}

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial','B',20);
        $this->SetTextColor(252, 128, 25); // Primary Orange
        $this->Cell(0,10,'Homely Bites',0,1,'C');
        $this->SetFont('Arial','',10);
        $this->SetTextColor(50,50,50);
        $this->Cell(0,10,'Order Receipt',0,1,'C');
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(128);
        $this->Cell(0,10,'Thank you for ordering with Homely Bites!',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

// Order Info
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0, 10, 'Order #HB-' . (1000 + $order['order_id']), 0, 1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(0, 6, 'Date: ' . date("d M Y, h:i A", strtotime($order['created_at'])), 0, 1);
$status_display = !empty($order['status']) ? $order['status'] : 'Pending';
$pdf->Cell(0, 6, 'Status: ' . $status_display, 0, 1);
$pdf->Ln(10);

// Addresses
$pdf->SetFillColor(245, 245, 245);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(95, 8, 'Delivery Address', 0, 0, 'L', true);
$pdf->Cell(95, 8, 'Seller Details', 0, 1, 'L', true);

$pdf->SetFont('Arial','',10);
$x = $pdf->GetX();
$y = $pdf->GetY();
$pdf->MultiCell(90, 6, $order['address'] ?: 'N/A', 0, 'L');
$pdf->SetXY($x + 95, $y);
$pdf->MultiCell(90, 6, $order['seller_name'] . "\n" . ($order['seller_phone'] ? 'Ph: ' . $order['seller_phone'] : ''), 0, 'L');
$pdf->Ln(10);

// Items Table
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(10, 143, 8); // Brand Green
$pdf->SetTextColor(255);
$pdf->Cell(110, 8, 'Item', 0, 0, 'L', true);
$pdf->Cell(30, 8, 'Price', 0, 0, 'R', true);
$pdf->Cell(20, 8, 'Qty', 0, 0, 'C', true);
$pdf->Cell(30, 8, 'Total', 0, 1, 'R', true);

$pdf->SetTextColor(0);
$pdf->SetFont('Arial','',10);

$grand_total = 0;

foreach($order_items as $item) {
    if(!is_numeric($item['quantity'])) $item['quantity'] = 1; 
    $total = $item['price'] * $item['quantity'];
    
    $pdf->Cell(110, 8, $item['name'], 1, 0, 'L');
    $pdf->Cell(30, 8, iconv('UTF-8', 'windows-1252', 'Rs. ') . number_format($item['price']), 1, 0, 'R');
    $pdf->Cell(20, 8, $item['quantity'], 1, 0, 'C');
    $pdf->Cell(30, 8, iconv('UTF-8', 'windows-1252', 'Rs. ') . number_format($total), 1, 1, 'R');
}

$pdf->Ln(5);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(160, 10, 'Total Amount', 0, 0, 'R');
$pdf->SetTextColor(10, 143, 8);
$pdf->Cell(30, 10, iconv('UTF-8', 'windows-1252', 'Rs. ') . number_format($order['total_amount']), 0, 1, 'R');

$pdf->Output('D', 'Order_HB-' . (1000 + $order['order_id']) . '.pdf');
?>
