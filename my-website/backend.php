    <?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// XSS Protection Functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateOrderData($orderData) {
    $errors = [];
    
    // Validate customer info
    if (!isset($orderData['customer'])) {
        $errors[] = 'Customer information is required';
    } else {
        $customer = $orderData['customer'];
        if (empty($customer['name'])) $errors[] = 'Customer name is required';
        if (empty($customer['email'])) $errors[] = 'Customer email is required';
        if (empty($customer['address'])) $errors[] = 'Shipping address is required';
        if (!filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    }
    
    // Validate items
    if (!isset($orderData['items']) || !is_array($orderData['items']) || count($orderData['items']) == 0) {
        $errors[] = 'Order must contain at least one item';
    }
    
    if (!isset($orderData['total']) || !is_numeric($orderData['total']) || $orderData['total'] <= 0) {
        $errors[] = 'Invalid total amount';
    }
    
    foreach ($orderData['items'] as $item) {
        if (!isset($item['id']) || !isset($item['name']) || !isset($item['price']) || !isset($item['quantity'])) {
            $errors[] = 'Invalid item structure';
        }
        if (!is_numeric($item['price']) || $item['price'] <= 0) {
            $errors[] = 'Invalid price for item: ' . sanitizeInput($item['name']);
        }
        if (!is_numeric($item['quantity']) || $item['quantity'] <= 0) {
            $errors[] = 'Invalid quantity for item: ' . sanitizeInput($item['name']);
        }
    }
    
    return $errors;
}

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Get raw POST data
    $rawData = file_get_contents('php://input');
    $orderData = json_decode($rawData, true);
    
    if (!$orderData) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }
    
    // Validate order data
    $validationErrors = validateOrderData($orderData);
    
    if (count($validationErrors) > 0) {
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $validationErrors]);
        exit;
    }
    
    // Sanitize customer info (XSS Protection)
    $customer = $orderData['customer'];
    $sanitizedCustomer = [
        'name' => sanitizeInput($customer['name']),
        'email' => sanitizeInput($customer['email']),
        'phone' => sanitizeInput($customer['phone'] ?? ''),
        'address' => sanitizeInput($customer['address']),
        'payment_method' => sanitizeInput($customer['payment_method'] ?? 'credit_card')
    ];
    
    // Sanitize items
    $sanitizedItems = [];
    foreach ($orderData['items'] as $item) {
        $sanitizedItems[] = [
            'id' => (int)$item['id'],
            'name' => sanitizeInput($item['name']),
            'price' => (float)$item['price'],
            'quantity' => (int)$item['quantity'],
            'subtotal' => (float)($item['price'] * $item['quantity'])
        ];
    }
    
    $sanitizedTotal = (float)$orderData['total'];
    
    // Generate order ID
    $orderId = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    $timestamp = date('Y-m-d H:i:s');
    
    // Create complete order record
    $orderRecord = [
        'order_id' => $orderId,
        'order_date' => $timestamp,
        'customer' => $sanitizedCustomer,
        'items' => $sanitizedItems,
        'total_amount' => $sanitizedTotal,
        'status' => 'confirmed',
        'payment_status' => 'pending'
    ];
    
    // Save order to JSON file (database simulation)
    $ordersFile = 'orders_log.json';
    $existingOrders = [];
    
    if (file_exists($ordersFile)) {
        $existingOrders = json_decode(file_get_contents($ordersFile), true);
        if (!is_array($existingOrders)) $existingOrders = [];
    }
    
    $existingOrders[] = $orderRecord;
    file_put_contents($ordersFile, json_encode($existingOrders, JSON_PRETTY_PRINT));
    
    // Also save to a readable CSV for easy viewing
    saveOrderToCSV($orderRecord);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_id' => $orderId,
        'customer_name' => $sanitizedCustomer['name'],
        'timestamp' => $timestamp
    ]);
    
} elseif ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_orders') {
    // Return all orders for admin view
    $ordersFile = 'orders_log.json';
    if (file_exists($ordersFile)) {
        $orders = json_decode(file_get_contents($ordersFile), true);
        echo json_encode(['success' => true, 'orders' => $orders ?: []]);
    } else {
        echo json_encode(['success' => true, 'orders' => []]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Send POST request with order data']);
}

// Helper function to save order as CSV for easy viewing in Excel
function saveOrderToCSV($order) {
    $csvFile = 'orders_export.csv';
    $fileExists = file_exists($csvFile);
    
    $fp = fopen($csvFile, 'a');
    
    if (!$fileExists) {
        // Add CSV headers
        fputcsv($fp, [
            'Order ID', 'Order Date', 'Customer Name', 'Customer Email', 
            'Customer Phone', 'Shipping Address', 'Payment Method',
            'Items', 'Total Amount', 'Status'
        ]);
    }
    
    // Format items as string
    $itemsString = '';
    foreach ($order['items'] as $item) {
        $itemsString .= $item['quantity'] . 'x ' . $item['name'] . ' ($' . $item['price'] . ') | ';
    }
    
    // Write order data
    fputcsv($fp, [
        $order['order_id'],
        $order['order_date'],
        $order['customer']['name'],
        $order['customer']['email'],
        $order['customer']['phone'],
        $order['customer']['address'],
        $order['customer']['payment_method'],
        $itemsString,
        '$' . number_format($order['total_amount'], 2),
        $order['status']
    ]);
    
    fclose($fp);
}
?>