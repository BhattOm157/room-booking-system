<?php

// Get the current active booking sessions count
function getActiveSessionsCount($db) {
    $query = "SELECT COUNT(*) as count FROM active_sessions WHERE expires_at > NOW()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

// Check if a user can start a booking session
function canStartBookingSession($db, $user_ip) {
    // Check if user already has an active session
    $query = "SELECT COUNT(*) as count FROM active_sessions WHERE user_ip = ? AND expires_at > NOW()";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_ip]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        return [
            'can_book' => true,
            'message' => 'You already have an active booking session'
        ];
    }
    
    // Check active sessions count
    $active_sessions = getActiveSessionsCount($db);
    if ($active_sessions < 2) {
        return [
            'can_book' => true,
            'message' => 'You can proceed with booking'
        ];
    } else {
        // Add user to queue
        $queue_position = addToQueue($db, $user_ip, $_POST['room_id']);
        return [
            'can_book' => false,
            'message' => 'Maximum booking sessions reached. You have been added to the queue at position ' . $queue_position,
            'queue_position' => $queue_position
        ];
    }
}

// Add user to queue
function addToQueue($db, $user_ip, $room_id) {
    // Check if user is already in queue
    $query = "SELECT id FROM queue WHERE user_ip = ? AND room_id = ? AND status = 'waiting'";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_ip, $room_id]);
    
    if ($stmt->rowCount() > 0) {
        // User already in queue, get position
        $query = "SELECT COUNT(*) as position FROM queue WHERE status = 'waiting' AND queued_at <= (SELECT queued_at FROM queue WHERE user_ip = ? AND room_id = ? AND status = 'waiting' LIMIT 1)";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_ip, $room_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['position'];
    } else {
        // Add user to queue
        $query = "INSERT INTO queue (user_ip, room_id) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_ip, $room_id]);
        
        // Get queue position
        $query = "SELECT COUNT(*) as position FROM queue WHERE status = 'waiting'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['position'];
    }
}

// Start a booking session
function startBookingSession($db, $user_ip, $room_id) {
    $session_id = session_id();
    $expires_at = date('Y-m-d H:i:s', time() + 60); // 1 minute from now
    
    // Check if there's an existing session for this room and user
    $query = "SELECT id FROM active_sessions WHERE room_id = ? AND user_ip = ? AND expires_at > NOW()";
    $stmt = $db->prepare($query);
    $stmt->execute([$room_id, $user_ip]);
    
    if ($stmt->rowCount() > 0) {
        // Update existing session
        $query = "UPDATE active_sessions SET expires_at = ? WHERE room_id = ? AND user_ip = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$expires_at, $room_id, $user_ip]);
    } else {
        // Create new session
        $query = "INSERT INTO active_sessions (room_id, session_id, user_ip, expires_at) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$room_id, $session_id, $user_ip, $expires_at]);
    }
    
    // Update room status to indicate booking in progress
    $query = "UPDATE rooms SET status = 'booked' WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$room_id]);
    
    return [
        'session_id' => $session_id,
        'expires_at' => $expires_at
    ];
}

// Complete a booking
function completeBooking($db, $session_id, $room_id, $user_name, $mobile, $email) {
    // Check if session is valid
    $query = "SELECT * FROM active_sessions WHERE session_id = ? AND room_id = ? AND expires_at > NOW()";
    $stmt = $db->prepare($query);
    $stmt->execute([$session_id, $room_id]);
    
    if ($stmt->rowCount() > 0) {
        // Create booking record
        $query = "INSERT INTO bookings (room_id, user_name, mobile, email, booking_time, status, completed_at) 
                  VALUES (?, ?, ?, ?, NOW(), 'booked', NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([$room_id, $user_name, $mobile, $email]);
        
        // Remove active session
        $query = "DELETE FROM active_sessions WHERE session_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$session_id]);
        
        return [
            'success' => true,
            'message' => 'Booking completed successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Booking session expired or invalid'
        ];
    }
}

// Get next user in queue
function getNextUserInQueue($db, $room_id) {
    $query = "SELECT * FROM queue WHERE room_id = ? AND status = 'waiting' ORDER BY queued_at ASC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$room_id]);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update queue status to notified
        $query = "UPDATE queue SET status = 'notified' WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user['id']]);
        
        return $user;
    }
    
    return null;
}

// Check for expired sessions and process queue
function processExpiredSessions($db) {
    // Get expired sessions
    $query = "SELECT * FROM active_sessions WHERE expires_at <= NOW()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $expired_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($expired_sessions as $session) {
        // Update any initiated bookings to timeout
        $query = "UPDATE bookings SET status = 'timeout' WHERE room_id = ? AND status = 'initiated'";
        $stmt = $db->prepare($query);
        $stmt->execute([$session['room_id']]);
        
        // Delete the expired session
        $query = "DELETE FROM active_sessions WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$session['id']]);
        
        // Reset room status
        $query = "UPDATE rooms SET status = 'available' WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$session['room_id']]);
        
        // Get next user in queue
        $next_user = getNextUserInQueue($db, $session['room_id']);
        
        if ($next_user) {
            // Here would be code to notify the next user
            // In a real implementation, this might use WebSockets or server-sent events
        }
    }
    
    return count($expired_sessions);
}

// Get user's IP address
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// Format date/time
function formatDateTime($datetime) {
    return date('M j, Y g:i A', strtotime($datetime));
}
?>