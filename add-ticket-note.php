<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$ticket_id = $data['ticket_id'] ?? null;
$note = $data['note'] ?? null;

if (!$ticket_id || !$note) {
    echo json_encode(['success' => false, 'message' => 'Ticket ID and note required']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'tiksumadb');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get current user's username
$current_user = $_SESSION['username'];

// Insert note into ticket_notes table (assuming this table exists)
// If the table doesn't exist, we'll need to create it
$sql = "INSERT INTO ticket_notes (ticket_id, username, note, created_at) VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $ticket_id, $current_user, $note);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Note added successfully']);
} else {
    // If table doesn't exist, create it and try again
    if ($conn->errno == 1146) { // Table doesn't exist error
        $create_table_sql = "CREATE TABLE ticket_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            username VARCHAR(50) NOT NULL,
            note TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE
        )";
        
        if ($conn->query($create_table_sql)) {
            // Try inserting again
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Note added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding note: ' . $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error creating notes table: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding note: ' . $conn->error]);
    }
}

$stmt->close();
$conn->close();
?>
