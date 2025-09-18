<?php
// Enhanced Notification System for Tiksuma Ticketing System
// This file provides the backend API for enhanced notifications

session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(["error" => "Not authorized"]);
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'tiksumadb');
if ($conn->connect_error) {
    die(json_encode(["error" => "DB connection failed"]));
}

// Enhanced notification types
$notification_types = [
    'new_ticket' => 'New Ticket Submitted',
    'status_update' => 'Ticket Status Updated',
    'assigned' => 'Ticket Assigned to You',
    'comment' => 'New Comment on Ticket',
    'priority_change' => 'Priority Changed',
    'resolved' => 'Ticket Resolved'
];

// Get enhanced notifications
function getEnhancedNotifications($user_id = null) {
    global $conn;
    
    $sql = "
        SELECT 
            n.id,
            n.type,
            n.title,
            n.message,
            n.created_at,
            n.is_read,
            t.ticket_id,
            t.subject,
            u.username as sender_username,
            u.profile_picture
        FROM notifications n
        LEFT JOIN tickets t ON n.ticket_id = t.ticket_id
        LEFT JOIN users u ON n.sender_id = u.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 10
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['id'],
            'type' => $row['type'],
            'title' => $row['title'],
            'message' => $row['message'],
            'created_at' => $row['created_at'],
            'is_read' => $row['is_read'],
            'ticket_id' => $row['ticket_id'],
            'subject' => $row['subject'],
            'sender_username' => $row['sender_username'],
            'profile_picture' => $row['profile_picture']
        ];
    }
    
    $stmt->close();
    return $notifications;
}

// Mark notification as read
function markNotificationAsRead($notification_id, $user_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

// Get notification count
function getNotificationCount($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();
    
    return $count;
}

// Main API endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'get_notifications':
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
            $notifications = getEnhancedNotifications($user_id);
            echo json_encode($notifications);
            break;
            
        case 'mark_read':
            $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
            $success = markNotificationAsRead($notification_id, $user_id);
            echo json_encode(["success" => $success]);
            break;
            
        case 'get_count':
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
            $count = getNotificationCount($user_id);
            echo json_encode(["count" => $count]);
            break;
            
        default:
            echo json_encode(["error" => "Invalid action"]);
            break;
    }
}

$conn->close();
?>
