<?php

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Process expired sessions
processExpiredSessions($db);

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="display-4">Admin Dashboard Statistics</h1>
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <div class="row mb-5">
        <?php
        // Get total rooms
        $query = "SELECT COUNT(*) as total FROM rooms";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $totalRooms = $stmt->fetchColumn();
        
        // Get available rooms
        $query = "SELECT COUNT(*) as available FROM rooms WHERE status = 'available'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $availableRooms = $stmt->fetchColumn();
        
        // Get total bookings
        $query = "SELECT COUNT(*) as total FROM bookings";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $totalBookings = $stmt->fetchColumn();
        
        // Get completed bookings
        $query = "SELECT COUNT(*) as completed FROM bookings WHERE status = 'booked'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $completedBookings = $stmt->fetchColumn();
        ?>
        
        <div class="col-md-3">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div class="stats-number"><?php echo $totalRooms; ?></div>
                    <div class="stats-text text-white-50">Total Rooms</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stats-card bg-success text-white">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-number"><?php echo $availableRooms; ?></div>
                    <div class="stats-text text-white-50">Available Rooms</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stats-card bg-info text-white">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stats-number"><?php echo $totalBookings; ?></div>
                    <div class="stats-text text-white-50">Total Bookings</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stats-card bg-warning text-white">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="stats-number"><?php echo $completedBookings; ?></div>
                    <div class="stats-text text-white-50">Completed Bookings</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Monthly Booking Statistics</h5>
                </div>
                <div class="card-body">
                    <canvas id="bookingsChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Room Usage Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="roomUsageChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Booking Status Overview</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get bookings by status
                    $query = "SELECT status, COUNT(*) as count FROM bookings GROUP BY status";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $initiated = 0;
                    $booked = 0;
                    $timeout = 0;
                    
                    foreach ($statusStats as $stat) {
                        switch ($stat['status']) {
                            case 1:
                                $initiated = $stat['count'];
                                break;
                            case 'booked':
                                $booked = $stat['count'];
                                break;
                            case 'timeout':
                                $timeout = $stat['count'];
                                break;
                        }
                    }
                    
                    $total = $initiated + $booked + $timeout;
                    $initiatedPercent = $total > 0 ? round(($initiated / $total) * 100) : 0;
                    $bookedPercent = $total > 0 ? round(($booked / $total) * 100) : 0;
                    $timeoutPercent = $total > 0 ? round(($timeout / $total) * 100) : 0;
                    ?>
                    
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $initiatedPercent; ?>%">
                                    Initiated (<?php echo $initiatedPercent; ?>%)
                                </div>
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $bookedPercent; ?>%">
                                    Booked (<?php echo $bookedPercent; ?>%)
                                </div>
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $timeoutPercent; ?>%">
                                    Timeout (<?php echo $timeoutPercent; ?>%)
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-around">
                                <div class="text-center">
                                    <h4 class="text-warning"><?php echo $initiated; ?></h4>
                                    <p class="mb-0">Initiated</p>
                                </div>
                                <div class="text-center">
                                    <h4 class="text-success"><?php echo $booked; ?></h4>
                                    <p class="mb-0">Booked</p>
                                </div>
                                <div class="text-center">
                                    <h4 class="text-danger"><?php echo $timeout; ?></h4>
                                    <p class="mb-0">Timeout</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    // Get monthly booking data
    $.ajax({
        url: 'get_chart_data.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Monthly Bookings Chart
                const monthlyCtx = document.getElementById('bookingsChart').getContext('2d');
                const monthlyChart = new Chart(monthlyCtx, {
                    type: 'line',
                    data: {
                        labels: response.monthly.labels,
                        datasets: [{
                            label: 'Bookings',
                            data: response.monthly.data,
                            backgroundColor: 'rgba(63, 81, 181, 0.2)',
                            borderColor: 'rgba(63, 81, 181, 1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
                
                // Room Usage Chart
                const roomCtx = document.getElementById('roomUsageChart').getContext('2d');
                const roomChart = new Chart(roomCtx, {
                    type: 'doughnut',
                    data: {
                        labels: response.rooms.labels,
                        datasets: [{
                            data: response.rooms.data,
                            backgroundColor: [
                                'rgba(63, 81, 181, 0.7)',
                                'rgba(33, 150, 243, 0.7)',
                                'rgba(0, 188, 212, 0.7)',
                                'rgba(139, 195, 74, 0.7)',
                                'rgba(255, 193, 7, 0.7)',
                                'rgba(255, 87, 34, 0.7)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });
            } else {
                console.error('Error loading chart data:', response.message);
            }
        },
        error: function() {
            console.error('Error connecting to server');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>