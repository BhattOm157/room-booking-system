<?php

session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/functions.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get monthly booking statistics for the last 6 months
    $monthlyLabels = [];
    $monthlyData = [];
    
    $query = "SELECT 
                DATE_FORMAT(booking_time, '%b %Y') as month,
                COUNT(*) as count
              FROM bookings 
              WHERE booking_time IS NOT NULL AND booking_time >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
              GROUP BY DATE_FORMAT(booking_time, '%Y-%m')
              ORDER BY MIN(booking_time)";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($monthlyStats as $stat) {
        $monthlyLabels[] = $stat['month'];
        $monthlyData[] = $stat['count'];
    }
    
    // If no data, add current month
    if (empty($monthlyLabels)) {
        $monthlyLabels[] = date('M Y');
        $monthlyData[] = 0;
    }
    
    // Get room usage statistics
    $roomLabels = [];
    $roomData = [];
    
    $query = "SELECT 
                r.name as room_name,
                COUNT(b.id) as booking_count
              FROM rooms r
              LEFT JOIN bookings b ON r.id = b.room_id AND b.status = 'booked'
              GROUP BY r.id
              ORDER BY booking_count DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $roomStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($roomStats as $stat) {
        $roomLabels[] = $stat['room_name'];
        $roomData[] = intval($stat['booking_count']);
    }
    
    echo json_encode([
        'success' => true,
        'monthly' => [
            'labels' => $monthlyLabels,
            'data' => $monthlyData
        ],
        'rooms' => [
            'labels' => $roomLabels,
            'data' => $roomData
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>