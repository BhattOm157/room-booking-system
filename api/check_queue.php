<?php

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/functions.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Process expired sessions
processExpiredSessions($db);

// Get user IP address
$user_ip = getUserIP();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

    if ($room_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid room ID'
        ]);
        exit;
    }

    // Check if user can book
    $booking_check = canStartBookingSession($db, $user_ip);

    if ($booking_check['can_book']) {
        // Start booking session
        $session = startBookingSession($db, $user_ip, $room_id);

        // Update user's queue entry
        $query = "UPDATE queue SET status = 'expired' WHERE user_ip = ? AND room_id = ? AND status = 'waiting'";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_ip, $room_id]);

        echo json_encode([
            'success' => true,
            'can_book' => true,
            'message' => $booking_check['message'],
            'session_id' => $session['session_id'],
            'expires_at' => $session['expires_at']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'can_book' => false,
            'message' => $booking_check['message'],
            'queue_position' => $booking_check['queue_position']
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>