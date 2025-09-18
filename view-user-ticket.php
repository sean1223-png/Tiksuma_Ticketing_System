<?php
session_start();

// Secure authentication check
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'tiksumadb');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Validate ticket ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid ticket ID.");
}

$ticket_id = intval($_GET['id']);

// Handle ticket acceptance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accept_ticket'])) {
    $stmt = $conn->prepare("UPDATE tickets SET assigned_to = ?, status_id = (SELECT id FROM ticket_statuses WHERE status_name='In Progress'), updated_at = NOW() WHERE ticket_id = ?");
    $stmt->bind_param("si", $_SESSION['username'], $ticket_id);
    $stmt->execute();
    $stmt->close();
    $message = "Ticket accepted successfully!";
}

// Handle status updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['status_id'])) {
    $status_id = intval($_POST['status_id']);
    $stmt = $conn->prepare("UPDATE tickets SET status_id = ?, updated_at = NOW() WHERE ticket_id = ?");
    $stmt->bind_param("ii", $status_id, $ticket_id);
    $stmt->execute();
    $stmt->close();
    $message = "Ticket status updated successfully!";
}

// Fetch complete ticket details
$sql = "SELECT t.ticket_id, t.subject, t.description, t.created_at, t.updated_at,
               p.level_name AS priority, s.status_name AS status, 
               u.username, u.full_name, u.email, u.profile_picture,
               t.assigned_to
        FROM tickets t
        JOIN users u ON t.username = u.username
        JOIN priority_levels p ON t.priority_id = p.id
        JOIN ticket_statuses s ON t.status_id = s.id
        WHERE t.ticket_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();
$ticket = $result->fetch_assoc();
$stmt->close();

if (!$ticket) {
    die("Ticket not found.");
}

// Access control: Allow IT staff or the ticket owner to view
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'it_staff' && $ticket['username'] !== $_SESSION['username'])) {
    die("You do not have permission to view this ticket.");
}

// Check if ticket is assigned to current user
$is_assigned = ($ticket['assigned_to'] === $_SESSION['username']);
$can_accept = empty($ticket['assigned_to']) && $_SESSION['role'] == 'it_staff';

// Fetch all statuses for dropdown
$status_sql = "SELECT id, status_name FROM ticket_statuses ORDER BY id";
$status_result = $conn->query($status_sql);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User Ticket #<?= htmlspecialchars($ticket['ticket_id']) ?> - TIKSUMA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./src/it-staff.css">
      <link rel="icon" type="image/x-icon" href="./png/logo-favicon.ico" />

    <style>
        .ticket-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }
        
        .ticket-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .meta-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .priority-badge {
            padding: 6px 12px;
            border-radius: 20px;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8em;
            display: inline-block;
        }
        
        .priority-high { background: #dc3545; }
        .priority-medium { background: #fd7e14; }
        .priority-low { background: #28a745; }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            background: #6c757d;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8em;
            display: inline-block;
        }
        
        .description-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #007bff;
        }
        
        .action-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #1e7e34;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .back-link {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .success-message {
            padding: 15px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .error-message {
            padding: 15px;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .section-title {
            color: #007bff;
            margin-bottom: 15px;
            font-size: 1.2em;
        }
        
        .accept-section {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>

<?php include 'it-navbar.php'; ?>

<div class="ticket-container">
    <div class="ticket-header">
        <h1><i class="fas fa-ticket-alt"></i> User Ticket #<?= htmlspecialchars($ticket['ticket_id']) ?></h1>
        <a href="it-tickets.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to All Tickets
        </a>
    </div>

    <?php if (isset($message)): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="ticket-meta">
        <div class="meta-item">
            <strong><i class="fas fa-user"></i> Submitted By:</strong><br>
            <div class="user-info">
                <img src="<?= htmlspecialchars($ticket['profile_picture'] ?: 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="#ccc"/><circle cx="50" cy="35" r="15" fill="#999"/><path d="M20 80 Q20 60 50 60 Q80 60 80 80" fill="#999"/></svg>')) ?>" 
                     alt="User" class="user-avatar">
                <div>
                    <strong><?= htmlspecialchars($ticket['full_name']) ?></strong><br>
                    <small><?= htmlspecialchars($ticket['username']) ?></small><br>
                    <small><?= htmlspecialchars($ticket['email']) ?></small>
                </div>
            </div>
        </div>
        
        <div class="meta-item">
            <strong><i class="fas fa-calendar"></i> Created:</strong><br>
            <?= htmlspecialchars($ticket['created_at']) ?>
        </div>
        
        <div class="meta-item">
            <strong><i class="fas fa-clock"></i> Last Updated:</strong><br>
            <?= htmlspecialchars($ticket['updated_at'] ?? $ticket['created_at']) ?>
        </div>
        
        <div class="meta-item">
            <strong><i class="fas fa-flag"></i> Priority:</strong><br>
            <span class="priority-badge priority-<?= strtolower(htmlspecialchars($ticket['priority'])) ?>">
                <?= htmlspecialchars($ticket['priority']) ?>
            </span>
        </div>
        
        <div class="meta-item">
            <strong><i class="fas fa-info-circle"></i> Status:</strong><br>
            <span class="status-badge">
                <?= htmlspecialchars($ticket['status']) ?>
            </span>
        </div>
        
        <div class="meta-item">
            <strong><i class="fas fa-user-check"></i> Assigned To:</strong><br>
            <?= htmlspecialchars($ticket['assigned_to'] ?? 'Not assigned') ?>
        </div>
    </div>

    <div class="description-box">
        <h3 class="section-title"><i class="fas fa-file-alt"></i> Subject</h3>
        <p><strong><?= htmlspecialchars($ticket['subject']) ?></strong></p>
        
        <h3 class="section-title"><i class="fas fa-align-left"></i> Description</h3>
        <p><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
    </div>

    <?php if ($can_accept): ?>
        <div class="accept-section">
            <h3 class="section-title"><i class="fas fa-handshake"></i> Accept Ticket</h3>
            <p>This ticket is not yet assigned. Would you like to accept it?</p>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="accept_ticket" value="1">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Accept Ticket
                </button>
            </form>
        </div>
    <?php elseif ($is_assigned): ?>
        <div class="action-section">
            <h3 class="section-title"><i class="fas fa-cogs"></i> Manage Ticket</h3>
            <p><i class="fas fa-info-circle"></i> This ticket is assigned to you.</p>
            
            <form method="POST" style="display: inline;">
                <div class="form-group">
                    <label for="status_id"><strong>Update Status:</strong></label>
                    <select name="status_id" id="status_id" class="form-control" required>
                        <?php 
                        $status_result->data_seek(0);
                        while ($status = $status_result->fetch_assoc()): 
                        ?>
                            <option value="<?= $status['id'] ?>" 
                                    <?= ($status['status_name'] == $ticket['status']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($status['status_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="action-section">
            <h3 class="section-title"><i class="fas fa-info-circle"></i> Ticket Status</h3>
            <p><i class="fas fa-user"></i> This ticket is assigned to: <strong><?= htmlspecialchars($ticket['assigned_to']) ?></strong></p>
        </div>
    <?php endif; ?>

    <!-- Ticket Notes Section -->
    <div class="description-box">
        <h3 class="section-title"><i class="fas fa-sticky-note"></i> Notes</h3>
        <?php
        // Reconnect to DB to fetch notes
        $conn = new mysqli('localhost', 'root', '', 'tiksumadb');
        if ($conn->connect_error) {
            echo "<p class='error-message'>Failed to load notes.</p>";
        } else {
            $notes_sql = "SELECT username, note, created_at FROM ticket_notes WHERE ticket_id = ? ORDER BY created_at DESC";
            $notes_stmt = $conn->prepare($notes_sql);
            $notes_stmt->bind_param("i", $ticket_id);
            $notes_stmt->execute();
            $notes_result = $notes_stmt->get_result();

            if ($notes_result->num_rows > 0) {
                echo "<ul style='list-style:none; padding-left:0;'>";
                while ($note = $notes_result->fetch_assoc()) {
                    echo "<li style='margin-bottom:15px; padding:10px; background:#f1f1f1; border-radius:5px;'>";
                    echo "<strong>" . htmlspecialchars($note['username']) . "</strong> <em style='color:#666; font-size:0.9em;'>(" . htmlspecialchars($note['created_at']) . ")</em><br>";
                    echo nl2br(htmlspecialchars($note['note']));
                    echo "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>No notes added yet.</p>";
            }
            $notes_stmt->close();
        }
        ?>
    </div>

    <!-- Other Tickets by User Section -->
    <div class="description-box">
        <h3 class="section-title"><i class="fas fa-list"></i> Other Tickets by <?= htmlspecialchars($ticket['full_name']) ?></h3>
        <?php
        // Fetch other tickets by the same user
        $other_tickets_sql = "
            SELECT t.ticket_id, t.subject, t.created_at,
                   p.level_name AS priority, s.status_name AS status
            FROM tickets t
            JOIN priority_levels p ON t.priority_id = p.id
            JOIN ticket_statuses s ON t.status_id = s.id
            WHERE t.username = ? AND t.ticket_id != ?
            ORDER BY t.created_at DESC
            LIMIT 10
        ";
        $other_stmt = $conn->prepare($other_tickets_sql);
        $other_stmt->bind_param("si", $ticket['username'], $ticket_id);
        $other_stmt->execute();
        $other_result = $other_stmt->get_result();

        if ($other_result->num_rows > 0) {
            echo "<div style='overflow-x: auto;'>";
            echo "<table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>";
            echo "<thead>";
            echo "<tr style='background: #f8f9fa;'>";
            echo "<th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Ticket ID</th>";
            echo "<th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Subject</th>";
            echo "<th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Priority</th>";
            echo "<th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Status</th>";
            echo "<th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Created</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            while ($other_ticket = $other_result->fetch_assoc()) {
                $pri_class = 'priority-' . strtolower($other_ticket['priority']);
                $status_class = 'status-' . strtolower(str_replace(' ', '-', $other_ticket['status']));

                echo "<tr style='border: 1px solid #ddd;'>";
                echo "<td style='padding: 10px;'><a href='view-user-ticket.php?id=" . urlencode($other_ticket['ticket_id']) . "' style='color: #007bff; text-decoration: none;'>#" . htmlspecialchars($other_ticket['ticket_id']) . "</a></td>";
                echo "<td style='padding: 10px;'><a href='view-user-ticket.php?id=" . urlencode($other_ticket['ticket_id']) . "' style='color: #007bff; text-decoration: none;'>" . htmlspecialchars($other_ticket['subject']) . "</a></td>";
                echo "<td style='padding: 10px;'><span class='priority-badge $pri_class' style='padding: 4px 8px; border-radius: 12px; color: white; font-size: 0.8em; text-transform: uppercase;'>" . htmlspecialchars($other_ticket['priority']) . "</span></td>";
                echo "<td style='padding: 10px;'><span class='status-badge $status_class' style='padding: 4px 8px; border-radius: 12px; background: #6c757d; color: white; font-size: 0.8em; text-transform: uppercase;'>" . htmlspecialchars($other_ticket['status']) . "</span></td>";
                echo "<td style='padding: 10px;'>" . date('M d, Y', strtotime($other_ticket['created_at'])) . "</td>";
                echo "</tr>";
            }

            echo "</tbody>";
            echo "</table>";
            echo "</div>";
        } else {
            echo "<p>No other tickets found for this user.</p>";
        }

        $other_stmt->close();
        $conn->close();
        ?>
    </div>

    <!-- Add Note Form -->
    <?php if ($is_assigned): ?>
    <div class="action-section">
        <h3 class="section-title"><i class="fas fa-plus-circle"></i> Add Note</h3>
        <form id="add-note-form">
            <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket_id) ?>">
            <div class="form-group">
                <textarea name="note" id="note" class="form-control" rows="4" placeholder="Enter your note here..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Submit Note
            </button>
        </form>
        <div id="note-message" style="margin-top:10px;"></div>
    </div>
    <?php endif; ?>

    <a href="it-tickets.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to All Tickets
    </a>
</div>

<script>
document.getElementById('add-note-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const note = form.note.value.trim();
    if (!note) return;

    fetch('add-ticket-note.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            ticket_id: form.ticket_id.value,
            note: note
        })
    })
    .then(response => response.json())
    .then(data => {
        const messageDiv = document.getElementById('note-message');
        if (data.success) {
            messageDiv.style.color = 'green';
            messageDiv.textContent = data.message;
            form.note.value = '';
            // Reload the page to show new note
            setTimeout(() => location.reload(), 1000);
        } else {
            messageDiv.style.color = 'red';
            messageDiv.textContent = data.message || 'Failed to add note.';
        }
    })
    .catch(() => {
        const messageDiv = document.getElementById('note-message');
        messageDiv.style.color = 'red';
        messageDiv.textContent = 'Failed to add note.';
    });
});
</script>

</body>
</html>
