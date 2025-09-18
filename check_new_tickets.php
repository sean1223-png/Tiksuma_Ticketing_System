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

// Get new ticket count
$new_ticket_count_sql = "
    SELECT COUNT(*) AS new_count
    FROM tickets
    WHERE status_id = (SELECT id FROM ticket_statuses WHERE status_name='Pending')
";
$new_ticket_count = $conn->query($new_ticket_count_sql)->fetch_assoc()['new_count'];

// Get latest 5 pending tickets
$notif_sql = "
    SELECT tickets.ticket_id, ticket_statuses.status_name, tickets.created_at 
    FROM tickets
    JOIN ticket_statuses ON tickets.status_id = ticket_statuses.id 
    WHERE ticket_statuses.status_name = 'Pending'
    ORDER BY tickets.created_at DESC 
    LIMIT 5
";
$notif_result = $conn->query($notif_sql);

$notifications_html = '';
if ($notif_result && $notif_result->num_rows > 0) {
    while ($note = $notif_result->fetch_assoc()) {
        $notifications_html .= '
            <li>
                <div><strong>Ticket #' . htmlspecialchars($note['ticket_id']) . '</strong></div>
                <small>' . time_elapsed_string($note['created_at']) . '</small><br>
                <span class="status-pending">' . htmlspecialchars($note['status_name']) . '</span>
            </li>
        ';
    }
} else {
    $notifications_html = '<li>No new tickets.</li>';
}

$conn->close();

// Return JSON
header('Content-Type: application/json');
echo json_encode([
    "new_count" => (int)$new_ticket_count,
    "notifications_html" => $notifications_html
]);
