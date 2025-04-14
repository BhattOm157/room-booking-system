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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'init_booking') {
        $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;

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
    } elseif ($action === 'complete_booking') {
        // Complete the booking
        $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
        $session_id = isset($_POST['session_id']) ? $_POST['session_id'] : '';
        $user_name = isset($_POST['user_name']) ? $_POST['user_name'] : '';
        $mobile = isset($_POST['mobile']) ? $_POST['mobile'] : '';
        $email = isset($_POST['email']) ? $_POST['email'] : '';

        if (empty($room_id) || empty($session_id) || empty($user_name) || empty($mobile) || empty($email)) {
            echo json_encode([
                'success' => false,
                'message' => 'All fields are required'
            ]);
            exit;
        }

        // Complete booking
        $result = completeBooking($db, $session_id, $room_id, $user_name, $mobile, $email);

        echo json_encode($result);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>

