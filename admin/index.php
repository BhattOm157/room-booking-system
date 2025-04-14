<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Process expired sessions
processExpiredSessions($db);

// Get all bookings
$query = "SELECT b.*, r.name as room_name FROM bookings b 
          JOIN rooms r ON b.room_id = r.id 
          ORDER BY b.initiated_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get active sessions
$query = "SELECT s.*, r.name as room_name FROM active_sessions s 
          JOIN rooms r ON s.room_id = r.id 
          WHERE s.expires_at > NOW() 
          ORDER BY s.started_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$active_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get queue
$query = "SELECT q.*, r.name as room_name FROM queue q 
          JOIN rooms r ON q.room_id = r.id 
          WHERE q.status = 'waiting' 
          ORDER BY q.queued_at ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$queue = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="card">
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-4">Admin Dashboard</h1>
                <p class="lead">Manage rooms and view bookings.</p>
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

        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Monthly Booking Statistics</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="bookingsChart"></canvas>
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
        <div class="row mb-4 mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">All Rooms</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get all rooms
                        $query = "SELECT * FROM rooms ORDER BY name";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php if (count($rooms) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Capacity</th>
                                            <th>Status</th>
                                            <th>Created At</th>
                                            <!-- <th>Actions</th> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rooms as $room): ?>
                                            <tr>
                                                <td><?php echo $room['id']; ?></td>
                                                <td><?php echo $room['name']; ?></td>
                                                <td><?php echo $room['capacity']; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $room['status'] == 'available' ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo ucfirst($room['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatDateTime($room['created_at']); ?></td>
                                                <!-- <td>
                                                    <button class="btn btn-sm btn-primary edit-room-btn"
                                                        data-room-id="<?php echo $room['id']; ?>"
                                                        data-room-name="<?php echo $room['name']; ?>"
                                                        data-room-capacity="<?php echo $room['capacity']; ?>"
                                                        data-room-description="<?php echo $room['description']; ?>"
                                                        data-room-image="<?php echo $room['image_url']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-room-btn" data-room-id="<?php echo $room['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td> -->
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No rooms available. Add one to get started.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Active Booking Sessions</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($active_sessions) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Room</th>
                                            <th>User IP</th>
                                            <th>Started At</th>
                                            <th>Expires At</th>
                                            <th>Time Left</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($active_sessions as $session): ?>
                                            <tr>
                                                <td><?php echo $session['room_name']; ?></td>
                                                <td><?php echo $session['user_ip']; ?></td>
                                                <td><?php echo formatDateTime($session['started_at']); ?></td>
                                                <td><?php echo formatDateTime($session['expires_at']); ?></td>
                                                <td class="session-timer" data-expires="<?php echo $session['expires_at']; ?>">
                                                    Calculating...
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No active booking sessions.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Booking Queue</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($queue) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Position</th>
                                            <th>Room</th>
                                            <th>User IP</th>
                                            <th>Queued At</th>
                                            <th>Wait Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($queue as $index => $item): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo $item['room_name']; ?></td>
                                                <td><?php echo $item['user_ip']; ?></td>
                                                <td><?php echo formatDateTime($item['queued_at']); ?></td>
                                                <td class="queue-timer" data-queued="<?php echo $item['queued_at']; ?>">
                                                    Calculating...
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No users in queue.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Bookings</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($bookings) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Room</th>
                                            <th>User Name</th>
                                            <th>Contact</th>
                                            <th>Booking Time</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td><?php echo $booking['id']; ?></td>
                                                <td><?php echo $booking['room_name']; ?></td>
                                                <td><?php echo $booking['user_name']; ?></td>
                                                <td>
                                                    <?php echo $booking['mobile']; ?><br>
                                                    <small><?php echo $booking['email']; ?></small>
                                                </td>
                                                <td><?php echo formatDateTime($booking['booking_time']); ?></td>
                                                <td>
                                                    <?php
                                                    $statusClass = '';
                                                    switch ($booking['status']) {
                                                        case 1:
                                                            $statusClass = 'bg-warning';
                                                            break;
                                                        case 'booked':
                                                            $statusClass = 'bg-success';
                                                            break;
                                                        case 'timeout':
                                                            $statusClass = 'bg-danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No bookings yet.</div>
                        <?php endif; ?>
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
<script>
    $(document).ready(function() {
        // Update active session timers
        function updateSessionTimers() {
            $('.session-timer').each(function() {
                const expiresAt = new Date($(this).data('expires'));
                const now = new Date();

                const diffMs = expiresAt - now;

                if (diffMs <= 0) {
                    $(this).text('Expired');
                    $(this).removeClass('text-success').addClass('text-danger');
                } else {
                    const diffSecs = Math.floor(diffMs / 1000);
                    $(this).text(diffSecs + ' seconds');
                    $(this).removeClass('text-danger').addClass('text-success');
                }
            });
        }

        // Update queue wait times
        function updateQueueTimers() {
            $('.queue-timer').each(function() {
                const queuedAt = new Date($(this).data('queued'));
                const now = new Date();

                const diffMs = now - queuedAt;
                const diffMins = Math.floor(diffMs / 60000);
                const diffSecs = Math.floor((diffMs % 60000) / 1000);

                $(this).text(diffMins + ' min ' + diffSecs + ' sec');
            });
        }

        // Initial updates
        updateSessionTimers();
        updateQueueTimers();

        // Update timers every second
        setInterval(function() {
            updateSessionTimers();
            updateQueueTimers();
        }, 1000);

        // Refresh page every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);

        // Add Room
        $('#addRoomBtn').click(function() {
            const formData = {
                name: $('#add_room_name').val(),
                capacity: $('#add_room_capacity').val(),
                description: $('#add_room_description').val(),
                image_url: $('#add_room_image').val()
            };

            $.ajax({
                url: 'add_room.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Room added successfully!');
                        $('#addRoomModal').modal('hide');
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Error adding room. Please try again.');
                }
            });
        });

        // Edit Room
        $('.edit-room-btn').click(function() {
            const roomId = $(this).data('room-id');
            const roomName = $(this).data('room-name');
            const roomCapacity = $(this).data('room-capacity');
            const roomDescription = $(this).data('room-description');
            const roomImage = $(this).data('room-image');

            $('#edit_room_id').val(roomId);
            $('#edit_room_name').val(roomName);
            $('#edit_room_capacity').val(roomCapacity);
            $('#edit_room_description').val(roomDescription);
            $('#edit_room_image').val(roomImage);

            $('#editRoomModal').modal('show');
        });

        $('#updateRoomBtn').click(function() {
            const formData = {
                id: $('#edit_room_id').val(),
                name: $('#edit_room_name').val(),
                capacity: $('#edit_room_capacity').val(),
                description: $('#edit_room_description').val(),
                image_url: $('#edit_room_image').val()
            };

            $.ajax({
                url: 'edit_room.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Room updated successfully!');
                        $('#editRoomModal').modal('hide');
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Error updating room. Please try again.');
                }
            });
        });

        // Delete Room
        $('.delete-room-btn').click(function() {
            const roomId = $(this).data('room-id');
            $('#delete_room_id').val(roomId);
            $('#deleteRoomModal').modal('show');
        });

        $('#confirmDeleteBtn').click(function() {
            const roomId = $('#delete_room_id').val();

            $.ajax({
                url: 'delete_room.php',
                type: 'POST',
                data: {
                    id: roomId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Room deleted successfully!');
                        $('#deleteRoomModal').modal('hide');
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Error deleting room. Please try again.');
                }
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>