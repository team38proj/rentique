<?php
// contact page [Krish Backend]
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data']);
    exit;
}

$firstName = trim($data['firstName'] ?? '');
$lastName = trim($data['lastName'] ?? '');
$email = trim($data['email'] ?? '');
$message = trim($data['message'] ?? '');

// saves to file
$log = "=== NEW MESSAGE ===\n";
$log .= "Date: " . date('Y-m-d H:i:s') . "\n";
$log .= "From: $firstName $lastName\n";
$log .= "Email: $email\n";
$log .= "Message: $message\n";
$log .= "==================\n\n";


file_put_contents('contact_submissions.txt', $log, FILE_APPEND);

$to = "dtblations@gmail.com";
$subject = "Contact from $firstName $lastName";
$headers = "From: $email\r\n";
$headers .= "Reply-To: $email\r\n";

$emailMessage = "Name: $firstName $lastName\n";
$emailMessage .= "Email: $email\n\n";
$emailMessage .= "Message:\n$message\n";

@mail($to, $subject, $emailMessage, $headers);

// success message displayed to user
echo json_encode([
    'success' => true,
    'message' => 'Thank you! Your message has been sent.'
]);
?>