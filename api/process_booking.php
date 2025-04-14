<?php

header("Content-Type: application/json");
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if all required fields are present
    if (
        isset($_POST['room_id']) && 
        isset($_POST['user_name']) && 
        isset($_POST['email'])
    ) {
        $room_id = intval($_POST['room_id']);
        $name = htmlspecialchars(trim($_POST['user_name']));
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $start_time = date("Y-m-d H:i:s"); // Current datetime
        $end_time = date("Y-m-d H:i:s", strtotime("+1 minute")); // +1 minute
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email address'
            ]);
            exit;
        }
        
        // Check if the room exists and is available
        $query = "SELECT * FROM rooms WHERE id = ? ";
        $stmt = $db->prepare($query);
        $stmt->execute([$room_id]);
        
        if ($stmt->rowCount() > 0) {
            // Generate a unique booking reference
            $booking_ref = generateBookingReference();
            
            // Insert booking record with 1 status
            $query = "INSERT INTO bookings (room_id, user_name, email, 
                       booking_time) 
                     VALUES (?, ?, ?, NOW())";
            
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $room_id,
                $name,
                $email,
            ]);
            
            if ($result) {
                $booking_id = $db->lastInsertId();
                
                // Update room status to 'pending'
                // $query = "UPDATE rooms SET status = 'pending' WHERE id = ?";
                // $stmt = $db->prepare($query);
                // $stmt->execute([$room_id]);
                
                // Set session data for the booking
                $_SESSION['booking_id'] = $booking_id;
                $_SESSION['booking_reference'] = $booking_ref;
                $_SESSION['booking_start_time'] = time();
                $_SESSION['booking_timeout'] = time() + (5 * 60); // 5 minutes timeout
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Booking initiated successfully',
                    'booking_id' => $booking_id,
                    'booking_reference' => $booking_ref,
                    'timeout' => $_SESSION['booking_timeout'],
                    'redirect' => 'confirm_booking.php?ref=' . $booking_ref
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error processing booking'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Room is not available for booking'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required booking information'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
// booking reference generation
function generateBookingReference() {
    return 'BK' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}
?>