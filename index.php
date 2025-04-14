<?php

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Process expired sessions
processExpiredSessions($db);

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="display-4">Room Booking System</h1>
            <p class="lead">Book your meeting room quickly and easily.</p>
        </div>
        <div class="col-md-4 d-flex align-items-center justify-content-end">
            <a href="admin/index.php" class="btn btn-outline-primary">
                <i class="fas fa-user-shield"></i> Admin Panel
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div id="alert-container"></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Quick Guide</h5>
                </div>
                <div class="card-body">
                    <p><strong>1.</strong> Browse available rooms</p>
                    <p><strong>2.</strong> Click "Book Now" on your preferred room</p>
                    <p><strong>3.</strong> Fill in your details</p>
                    <p><strong>4.</strong> Confirm your booking</p>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Contact Support</h5>
                </div>
                <div class="card-body">
                    <p><i class="fas fa-phone me-2"></i> +1 234 567 8900</p>
                    <p><i class="fas fa-envelope me-2"></i> support@example.com</p>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Available Rooms</h5>
                </div>
                <div class="card-body">
                    <div class="row" id="rooms-container">
                        <div class="col-12 text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading rooms...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Book a Room: <span id="booking-room-name"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Step 1: Queue Status -->
                <div id="booking-step-1">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h4>Checking booking availability...</h4>
                        <p id="queue-message">You are currently in queue position: <span id="queue-position">--</span></p>
                        <p class="small text-muted">Please wait while we process your request.</p>
                    </div>
                </div>

                <!-- Step 2: Booking Form -->
                <div id="booking-step-2" style="display: none;">
                    <div class="alert alert-warning">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock me-2"></i>
                            <div>
                                <strong>Time limited booking session</strong>
                                <p class="mb-0">You have <span id="timer-text">--</span> to complete your booking.</p>
                            </div>
                        </div>
                    </div>

                    <form id="booking-form">
                        <input type="hidden" id="booking-room-id" name="room_id">
                        <input type="hidden" id="booking-session-id" name="session_id">

                        <div class="mb-3">
                            <label for="booking-name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="booking-name" name="user_name" required>
                        </div>

                        <!-- <div class="mb-3">
                            <label for="booking-mobile" class="form-label">Mobile Number</label>
                            <input type="tel" class="form-control" id="booking-mobile" name="mobile" required>
                        </div> -->

                        <div class="mb-3">
                            <label for="booking-email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="booking-email" name="email" required>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="booking-form-submit">Complete Booking</button>
                        </div>
                    </form>
                </div>

                <!-- Step 3: Confirmation -->
                <div id="booking-step-3" style="display: none;">
                    <div class="text-center py-4">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success fa-5x"></i>
                        </div>
                        <h3>Booking Confirmed!</h3>
                        <p>Your booking has been successfully processed.</p>
                        <p class="mb-4">Booking ID: <strong id="booking-confirmation-id"></strong></p>
                        <p>An email confirmation has been sent to your email address.</p>
                        <div class="mt-4">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>