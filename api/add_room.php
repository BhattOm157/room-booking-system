<?php

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/functions.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $capacity = isset($_POST['capacity']) ? intval($_POST['capacity']) : 0;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $image_url = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';
    
    if (empty($name) || $capacity <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Room name and capacity are required'
        ]);
        exit;
    }
    
    // Insert room
    $query = "INSERT INTO rooms (name, capacity, description, image_url) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    try {
        $stmt->execute([$name, $capacity, $description, $image_url]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Room added successfully',
            'room_id' => $db->lastInsertId()
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error adding room: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
