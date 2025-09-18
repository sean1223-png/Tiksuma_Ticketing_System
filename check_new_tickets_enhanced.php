<?php
session_start();

// Optional: block access if not logged in
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(["error" => "Not authorized"]);
    exit;
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'min',
        's' => 'sec',
    ];
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '') . ' ago';
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) : 'just now';
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'tiksumadb');
if ($conn->connect_error) {
    die(json_encode(["error" => "DB connection failed"]));
}

// Get new ticket count for IT staff (newly submitted tickets)
$new_ticket_count_sql = "
    SELECT COUNT(*) AS new_count
    FROM tickets
    WHERE status_id = (SELECT id FROM ticket_statuses WHERE status_name='Pending')
";
$new_ticket_result = $conn->query($new_ticket_count_sql);
$new_ticket_count = $new_ticket_result ? $new_ticket_result->fetch_assoc()['new_count'] : 0;

// Get latest new tickets that need action
$notif_sql = "
    SELECT 
        tickets.ticket_id, 
        tickets.subject,
        users.username,
        tickets.created_at,
        ticket_statuses.status_name,
        priority_levels.level_name as priority
    FROM tickets
    JOIN users ON tickets.username = users.username
    JOIN ticket_statuses ON tickets.status_id = ticket_statuses.id 
    JOIN priority_levels ON tickets.priority_id = priority_levels.id
    WHERE ticket_statuses.status_name = 'Pending'
    ORDER BY tickets.created_at DESC 
    LIMIT 5
";
$notif_result = $conn->query($notif_sql);

$notifications_html = '';
if ($notif_result && $notif_result->num_rows > 0) {
    while ($note = $notif_result->fetch_assoc()) {
        $priority_class = strtolower($note['priority']);
        $notifications_html .= '
            <li class="notif-item">
                <div><strong>New Ticket #' . htmlspecialchars($note['ticket_id']) . '</strong></div>
                <div><strong>From:</strong> ' . htmlspecialchars($note['username']) . '</div>
                <div><strong>Subject:</strong> ' . htmlspecialchars($note['subject']) . '</div>
                <div><strong>Priority:</strong> <span class="priority-badge priority-' . $priority_class . '">' . htmlspecialchars($note['priority']) . '</span></div>
                <small>' . time_elapsed_string($note['created_at']) . '</small><br>
                <div style="margin-top: 5px;">
                    <a href="view-tickets.php?id=' . urlencode($note['ticket_id']) . '" class="action-btn view-btn">View Ticket</a>
                </div>
            </li>
        ';
    }
} else {
    $notifications_html = '<li class="notif-empty">No new tickets requiring action.</li>';
}

// Get total pending tickets count for sidebar badge
$total_pending_sql = "
    SELECT COUNT(*) as total_pending
    FROM tickets
    WHERE status_id = (SELECT id FROM ticket_statuses WHERE status_name='Pending')
";
$total_pending_result = $conn->query($total_pending_sql);
$total_pending = $total_pending_result ? $total_pending_result->fetch_assoc()['total_pending'] : 0;

$conn->close();

// Return JSON
header('Content-Type: application/json');
echo json_encode([
    "new_count" => (int)$new_ticket_count,
    "total_pending" => (int)$total_pending,
    "notifications_html" => $notifications_html
]);
