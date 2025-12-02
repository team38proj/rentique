<?php
session_start();
require_once 'connectdb.php';

// Rentique Contact page [Krish Backend] content type set to JSON (java script object notation)
header('Content-Type: application/json');

// Rentique Contact page [Krish Backend] only POST reqs are allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Rentique Contact page [Krish Backend] gets JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Rentique Contact page [Krish Backend] checks for success on JSON parsing
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Rentique Contact page [Krish Backend] validates required fields
if (empty($input['email']) || empty($input['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and message are required']);
    exit;
}

// Rentique Contact page [Krish Backend] sanitizes and validates input
$firstName = trim($input['firstName'] ?? '');
$lastName = trim($input['lastName'] ?? '');
$email = filter_var(trim($input['email']), FILTER_SANITIZE_EMAIL);
$message = trim($input['message']);

// Rentique Contact page [Krish Backend] email format validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Rentique Contact page [Krish Backend] message length validation
if (strlen($message) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message must be at least 10 characters long']);
    exit;
}

// Rentique Contact page [Krish Backend] validates maximum length of message
if (strlen($message) > 2000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message is too long. Maximum 2000 characters allowed.']);
    exit;
}

try {
   // Rentique Contact page [Krish Backend] prepares & executes SQL statement
    $stmt = $db->prepare("INSERT INTO contact_messages (first_name, last_name, email, message, created_at) VALUES (?, ?, ?, ?, NOW())");

    $stmt->execute([$firstName, $lastName, $email, $message]);

// Rentique Contact page [Krish Backend] sends a successful response
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We will get back to you soon.'
    ]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message. Please try again later.'
    ]);
}
?>
