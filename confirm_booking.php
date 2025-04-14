<?php

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if booking reference is provided
if (!isset($_GET['ref']) || empty($_GET['ref'])) {
    header('Location: index.php');
    exit;
}

$booking_ref = $_GET['ref'];

// Get booking details
$query = "SELECT b.*, r.name as room_name, r.capacity, r.location 
          FROM bookings b 
          JOIN rooms r ON b.room_id = r.id 
          WHERE b.booking_reference = ?";
$stmt = $db->prepare($query);
$stmt->execute([$booking_ref]);

if ($stmt->rowCount() === 0) {
    header('Location: index.php');
    exit;
}

$booking = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if booking has already timed out
if ($booking['status'] === 'timeout') {
    header('Location: booking_timeout.php');
    exit;
}

// Check if booking is already completed
if ($booking['status'] === 'booked') {
    // Show booking successful page
    $page_title = 'Booking Confirmed';
    include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-check-circle me-2"></i> Booking Confirmed</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="display-1 text-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2 class="mt-3">Thank You!</h2>
                        <p class="lead">Your booking has been confirmed successfully.</p>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Booking Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Booking Reference:</div>
                                <div class="col-md-8"><?php echo $booking['booking_reference']; ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Room:</div>
                                <div class="col-md-8"><?php echo $booking['room_name']; ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Location:</div>
                                <div class="col-md-8"><?php echo $booking['location']; ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">Start Time:</div>
                                <div class="col-md-8"><?php echo formatDateTime($booking['start_time']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 fw-bold">End Time:</div>
                                <div class="col-md-8"><?php echo formatDateTime($booking['end_time']); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i> Return to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
    include 'includes/footer.php';
    exit;
}

// Check if session is still valid
if (!isset($_SESSION['booking_timeout']) || time() > $_SESSION['booking_timeout']) {
    // Mark booking as timed out
    $query = "UPDATE bookings SET status = 'timeout' WHERE booking_reference = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$booking_ref]);
    
    // Release the room
    $query = "UPDATE rooms SET status = 'available' WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$booking['room_id']]);
    
    // Redirect to timeout page
    header('Location: booking_timeout.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    // Update booking status to confirmed
    $query = "UPDATE bookings SET status = 'booked', confirmation_time = NOW() WHERE booking_reference = ?";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$booking_ref]);
    
    if ($result) {
        // Update room status
        $query = "UPDATE rooms SET status = 'available' WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$booking['room_id']]);
        
        // Clear session
        unset($_SESSION['booking_id']);
        unset($_SESSION['booking_reference']);
        unset($_SESSION['booking_start_time']);
        unset($_SESSION['booking_timeout']);
        
        // Redirect to confirmation page
        header('Location: confirm_booking.php?ref=' . $booking_ref);
        exit;
    }
}

// Calculate time remaining
$time_remaining = $_SESSION['booking_timeout'] - time();
$page_title = 'Confirm Booking';

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Alert container for messages -->
            <div id="alert-container"></div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Complete Booking</h4>
                        <div id="booking-timer" data-end-time="<?php echo $_SESSION['booking_timeout']; ?>" class="badge bg-warning text-dark">
                            Time remaining: <span id="timer-text">calculating...</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5>Booking Details</h5>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-md-4 fw-bold">Room:</div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($booking['room_name']); ?></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-4 fw-bold">Location:</div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($booking['location']); ?></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-4 fw-bold">Capacity:</div>
                                    <div class="col-md-8"><?php echo $booking['capacity']; ?> people</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-4 fw-bold">Start Time:</div>
                                    <div class="col-md-8"><?php echo formatDateTime($booking['start_time']); ?></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-4 fw-bold">End Time:</div>
                                    <div class="col-md-8"><?php echo formatDateTime($booking['end_time']); ?></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-4 fw-bold">Reference:</div>
                                    <div class="col-md-8"><?php echo $booking['booking_reference']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Customer Information</h5>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-md-4 fw-bold">Name:</div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-4 fw-bold">Email:</div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($booking['email']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <form id="confirmForm" method="POST">
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the terms and conditions of the room booking
                            </label>
                            <div class="invalid-feedback">
                                You must agree before confirming.
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i> Cancel
                            </a>
                            <button type="submit" name="confirm_booking" class="btn btn-success">
                                <i class="fas fa-check-circle me-2"></i> Confirm Booking
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>