<?php

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Clear session variables related to booking
unset($_SESSION['booking_id']);
unset($_SESSION['booking_reference']);
unset($_SESSION['booking_start_time']);
unset($_SESSION['booking_timeout']);

$page_title = 'Booking Timeout';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i> Booking Timeout</h4>
                </div>
                <div class="card-body text-center">
                    <div class="display-1 text-danger mb-4">
                        <i class="fas fa-clock"></i>
                    </div>
                    
                    <h2>Your Booking Session Has Expired</h2>
                    <p class="lead mb-4">
                        We're sorry, but your booking session has timed out due to inactivity.
                        The room has been released for other users to book.
                    </p>
                    
                    <div class="d-grid gap-2 col-md-6 mx-auto">
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i> Return to Home
                        </a>
                        <a href="index.php#available-rooms" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-search me-2"></i> Find Another Room
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>