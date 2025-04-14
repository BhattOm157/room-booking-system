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

// Get all rooms
$query = "SELECT * FROM rooms ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate HTML
$html = '';

if (count($rooms) > 0) {
    foreach ($rooms as $room) {
        $html .= '<div class="col-md-4 mb-4">';
        $html .= '<div class="card room-card h-100">';

        if (!empty($room['image_url'])) {
            $html .= '<img src="https://diyversify.com/cdn/shop/articles/Modern_Working_Area_in_Corner_of_Room_With_Green_Walls_and_Plants_1000x.jpg?v=1663348923" class="card-img-top" alt="' . htmlspecialchars($room['name']) . '">';
        } else {
            $html .= '<div class="card-img-top bg-light text-center py-5">';
            $html .= '<i class="fas fa-door-open fa-5x text-muted"></i>';
            $html .= '</div>';
        }

        $html .= '<div class="card-body">';
        $html .= '<h5 class="card-title">' . htmlspecialchars($room['name']) . '</h5>';
        $html .= '<p class="card-text">' . htmlspecialchars($room['description']) . '</p>';
        $html .= '<p class="card-text"><small class="text-muted">Capacity: ' . htmlspecialchars($room['capacity']) . ' people</small></p>';
        $html .= '</div>';

        $html .= '<div class="card-footer d-flex justify-content-between align-items-center">';
        $html .= '<span class="badge ' . ($room['status'] == 'available' ? 'bg-success' : 'bg-danger') . '">';
        $html .= ucfirst($room['status']) . '</span>';

        if ($room['status'] == 'available') {
            $html .= '<button class="btn btn-primary book-now-btn" data-room-id="' . $room['id'] . '" data-room-name="' . htmlspecialchars($room['name']) . '">';
            $html .= 'Book Now</button>';
        } else {
            $html .= '<button class="btn btn-secondary" disabled>Booked</button>';
        }

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
} else {
    $html .= '<div class="col-12">';
    $html .= '<div class="alert alert-info">No rooms available at the moment.</div>';
    $html .= '</div>';
}

echo json_encode([
    'success' => true,
    'html' => $html
]);
?>

