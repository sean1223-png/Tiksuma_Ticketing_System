<?php
/**
 * Enhanced Notification Functions for Tiksuma Ticketing System
 * Provides comprehensive notification management for the notification bell
 */

/**
 * Get user ID from username
 */
function getUserIdFromUsername($username) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return $user['id'];
    }
    
    return null;
}

/**
 * Create a new notification
 */
function createNotification($user_id, $type, $title, $message, $ticket_id = null, $sender_id = null) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, title, message, ticket_id, sender_id) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssii", $user_id, $type, $title, $message, $ticket_id, $sender_id);
    
    return $stmt->execute();
}

/**
 * Get enhanced notifications for user
 */
function getEnhancedNotificationsForUser($username) {
    global $conn;
    
    $user_id = getUserIdFromUsername($username);
    if (!$user_id) return [];
    
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
            u.profile_picture,
            p.level_name as priority,
            s.status_name as status
        FROM notifications n
        LEFT JOIN tickets t ON n.ticket_id = t.ticket_id
        LEFT JOIN users u ON n.sender_id = u.id
        LEFT JOIN priority_levels p ON t.priority_id = p.id
        LEFT JOIN ticket_statuses s ON t.status_id = s.id
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
        $notifications[] = $row;
    }
    
    return $notifications;
}

/**
 * Get unread notification count for user
 */
function getUnreadNotificationCount($username) {
    global $conn;
    
    $user_id = getUserIdFromUsername($username);
    if (!$user_id) return 0;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc()['count'] ?? 0;
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notification_id, $username) {
    global $conn;
    
    $user_id = getUserIdFromUsername($username);
    if (!$user_id) return false;
    
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    
    return $stmt->execute();
}

/**
 * Generate HTML for notification dropdown
 */
function generateNotificationDropdownHTML($notifications) {
    if (empty($notifications)) {
        return '<li class="notif-empty">No notifications</li>';
    }
    
    $html = '';
    foreach ($notifications as $note) {
        $priority_class = isset($note['priority']) ? 'priority-' . strtolower($note['priority']) : '';
        $time_ago = time_elapsed_string($note['created_at']);
        
        $html .= '
            <li class="notif-item" data-notification-id="' . $note['id'] . '">
                <div class="notif-header">
                    <strong>' . htmlspecialchars($note['title']) . '</strong>
                    <small>' . $time_ago . '</small>
                </div>
                <div class="notif-content">';
        
        if (!empty($note['message'])) {
            $html .= '<p>' . htmlspecialchars($note['message']) . '</p>';
        }
        
        if (!empty($note['ticket_id'])) {
            $html .= '
                <div class="notif-ticket-info">
                    <strong>Ticket #' . $note['ticket_id'] . '</strong>';
            
            if (!empty($note['subject'])) {
                $html .= ' - ' . htmlspecialchars($note['subject']);
            }
            
            if (!empty($note['priority'])) {
                $html .= ' <span class="priority-badge ' . $priority_class . '">' . htmlspecialchars($note['priority']) . '</span>';
            }
            
            $html .= '</div>';
        }
        
        if (!empty($note['sender_username'])) {
            $html .= '<div class="notif-sender">From: ' . htmlspecialchars($note['sender_username']) . '</div>';
        }
        
        if (!empty($note['ticket_id'])) {
            $html .= '
                <div class="notif-actions">
                    <a href="view-tickets.php?id=' . $note['ticket_id'] . '" class="action-btn view-btn">View Ticket</a>
                </div>';
        }
        
        $html .= '
                </div>
            </li>';
    }
    
    return $html;
}

/**
 * Enhanced notification bell function
 */
function displayNotificationBell($username) {
    global $conn;
    
    // Get notifications and count
    $notifications = getEnhancedNotificationsForUser($username);
    $unread_count = getUnreadNotificationCount($username);
    
    ob_start();
    ?>
    <div class="notification-container" style="position: relative;">
        <i class="fas fa-bell" id="notificationBell" style="cursor:pointer; position: relative; font-size: 1.2rem;">
            <?php if ($unread_count > 0): ?>
                <span class="bell-badge" id="bellBadge"><?= $unread_count ?></span>
            <?php endif; ?>
        </i>
        <div class="notification-dropdown" id="notificationDropdown">
            <div class="notif-header">
                <span>Notifications</span>
                <?php if ($unread_count > 0): ?>
                    <span class="badge" style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px;">
                        <?= $unread_count ?> unread
                    </span>
                <?php endif; ?>
            </div>
            <div class="notif-title">Recent Activity</div>
            <ul class="notif-list">
                <?= generateNotificationDropdownHTML($notifications) ?>
            </ul>
            <?php if (!empty($notifications)): ?>
                <div class="notif-footer">
                    <a href="notification-enhanced-system.php" style="display: block; text-align: center; padding: 10px; background: #f8f9fa; border-top: 1px solid #eee; color: #007bff; text-decoration: none;">
                        View All Notifications
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Auto-create notifications for new tickets
 */
function autoCreateTicketNotifications($ticket_id, $username, $subject, $priority) {
    global $conn;
    
    // Get all IT staff users - fix user_type to 'Admin' for IT staff
    $it_staff_sql = "SELECT id, username FROM users WHERE user_type = 'Admin' AND username != ?";
    $stmt = $conn->prepare($it_staff_sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sender_id = getUserIdFromUsername($username);
    $notifications_created = 0;
    
    while ($staff = $result->fetch_assoc()) {
        $title = "New Ticket Submitted";
        $message = "User {$username} submitted a new ticket: {$subject} (Priority: {$priority})";
        
        if (createNotification($staff['id'], 'new_ticket', $title, $message, $ticket_id, $sender_id)) {
            $notifications_created++;
        }
    }
    
    return $notifications_created;
}
?>
