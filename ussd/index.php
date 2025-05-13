<?php
require_once '../config/database.php';
require_once '../config/africastalking.php';

// Get the POST data
$sessionId = $_POST['sessionId'] ?? '';
$serviceCode = $_POST['serviceCode'] ?? '';
$phoneNumber = $_POST['phoneNumber'] ?? '';
$text = $_POST['text'] ?? '';

// Initialize response
$response = '';

// Get or create user
function getUser($phoneNumber) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE phone_number = ?");
    $stmt->execute([$phoneNumber]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $stmt = $db->prepare("INSERT INTO users (phone_number) VALUES (?)");
        $stmt->execute([$phoneNumber]);
        return ['id' => $db->lastInsertId(), 'phone_number' => $phoneNumber];
    }
    
    return $user;
}

// Show menu
function showMenu() {
    $db = getDBConnection();
    $stmt = $db->query("SELECT c.name as category, m.name, m.price 
                       FROM menu_items m 
                       JOIN categories c ON m.category_id = c.id 
                       WHERE m.is_available = 1 
                       ORDER BY c.name, m.name");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = "CON Today's Menu:\n\n";
    $currentCategory = '';
    
    foreach ($items as $item) {
        if ($currentCategory != $item['category']) {
            $currentCategory = $item['category'];
            $response .= "\n" . $currentCategory . ":\n";
        }
        $response .= "- " . $item['name'] . " (RWF " . $item['price'] . ")\n";
    }
    
    $response .= "\n1. Place Order\n2. Back to Main Menu";
    return $response;
}

// Handle USSD menu
function handleMenu($text, $phoneNumber) {
    $user = getUser($phoneNumber);
    $textArray = explode('*', $text);
    $level = count($textArray);
    
    switch ($level) {
        case 1:
            return "CON Welcome to Biryo Byihuse\n
1. View Menu
2. Place Order
3. Track Order
4. My Account";
            
        case 2:
            switch ($textArray[1]) {
                case '1':
                    return showMenu();
                case '2':
                    return "CON Select Category:\n" . showCategories();
                case '3':
                    return "CON Enter your order number:";
                case '4':
                    return "CON My Account\n" . showAccountInfo($user);
                default:
                    return "END Invalid option selected";
            }
            
        case 3:
            if ($textArray[1] == '2') {
                return showMenuItems($textArray[2]);
            }
            break;
            
        case 4:
            if ($textArray[1] == '2') {
                return confirmOrder($textArray[2], $textArray[3], $user);
            }
            break;
    }
    
    return "END Thank you for using Biryo Byihuse";
}

// Show menu categories
function showCategories() {
    $db = getDBConnection();
    $stmt = $db->query("SELECT id, name FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = '';
    foreach ($categories as $category) {
        $response .= $category['id'] . ". " . $category['name'] . "\n";
    }
    return $response;
}

// Show menu items
function showMenuItems($categoryId) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT id, name, price FROM menu_items WHERE category_id = ? AND is_available = 1");
    $stmt->execute([$categoryId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = "CON Select item:\n";
    foreach ($items as $item) {
        $response .= $item['id'] . ". " . $item['name'] . " - RWF " . $item['price'] . "\n";
    }
    return $response;
}

// Confirm order
function confirmOrder($categoryId, $itemId, $user) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT name, price FROM menu_items WHERE id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return "CON Confirm order:\n
Item: " . $item['name'] . "\n
Price: RWF " . $item['price'] . "\n
1. Confirm
2. Cancel";
}

// Show account info
function showAccountInfo($user) {
    return "Phone: " . $user['phone_number'] . "\n
1. Update Location
2. View Order History";
}

// Process the USSD request
$response = handleMenu($text, $phoneNumber);

// Send the response
header('Content-type: text/plain');
echo $response; 