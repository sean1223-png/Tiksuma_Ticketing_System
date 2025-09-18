<?php
session_start();

// Check if user is logged in and is IT staff or admin
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'it_staff') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

// Validate input
if (!isset($_GET['username'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username parameter is required']);
    exit;
}

$target_username = trim($_GET['username']);

if (empty($target_username)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid username']);
    exit;
}

try {
    $conn = new mysqli('localhost', 'root', '', 'tiksumadb');
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Verify the target user exists
    $user_check_sql = "SELECT username, full_name, email FROM users WHERE username = ?";
    $user_stmt = $conn->prepare($user_check_sql);
    $user_stmt->bind_param("s", $target_username);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();

    if ($user_result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $user_info = $user_result->fetch_assoc();
    $user_stmt->close();

    // Fetch user's tickets with full details
    $tickets_sql = "
        SELECT
            t.ticket_id,
            t.subject,
            t.description,
            t.created_at,
            t.updated_at,
            t.assigned_to,
            p.level_name AS priority,
            s.status_name AS status,
            u.username,
            u.full_name,
            u.email,
            u.profile_picture
        FROM tickets t
        JOIN users u ON t.username = u.username
        JOIN priority_levels p ON t.priority_id = p.id
        JOIN ticket_statuses s ON t.status_id = s.id
        WHERE t.username = ?
        ORDER BY t.created_at DESC
    ";

    $tickets_stmt = $conn->prepare($tickets_sql);
    $tickets_stmt->bind_param("s", $target_username);
    $tickets_stmt->execute();
    $tickets_result = $tickets_stmt->get_result();

    $tickets = [];
    while ($ticket = $tickets_result->fetch_assoc()) {
        // Fetch notes for this ticket
        $notes_sql = "SELECT username, note, created_at FROM ticket_notes WHERE ticket_id = ? ORDER BY created_at DESC";
        $notes_stmt = $conn->prepare($notes_sql);
        $notes_stmt->bind_param("i", $ticket['ticket_id']);
        $notes_stmt->execute();
        $notes_result = $notes_stmt->get_result();

        $notes = [];
        while ($note = $notes_result->fetch_assoc()) {
            $notes[] = $note;
        }
        $notes_stmt->close();

        $ticket['notes'] = $notes;
        $tickets[] = $ticket;
    }

    $tickets_stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'user' => [
            'username' => $user_info['username'],
            'full_name' => $user_info['full_name'],
            'email' => $user_info['email']
        ],
        'tickets' => $tickets,
        'total_tickets' => count($tickets)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
