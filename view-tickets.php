<?php
session_start();
include 'db_connect.php';

// Secure authentication check for IT staff
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: index.php");
    exit();
}

// Validate ticket ID with proper error handling
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid ticket ID.");
}

$ticket_id = intval($_GET['id']);

// Update status if form is submitted with proper validation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['status_id'])) {
    $status_id = intval($_POST['status_id']);
    $stmt = $conn->prepare("UPDATE tickets SET status_id = ?, updated_at = NOW() WHERE ticket_id = ?");
    $stmt->bind_param("ii", $status_id, $ticket_id);
    $stmt->execute();
    $stmt->close();
    $message = "Ticket status updated successfully.";
    
    // Mark ticket as not new when viewed
    $stmt = $conn->prepare("UPDATE tickets SET is_new = 0 WHERE ticket_id = ?");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch complete ticket details with user information
$sql = "SELECT t.ticket_id, t.subject, t.description, t.created_at, t.updated_at,
               p.level_name AS priority, s.status_name AS status, 
               u.username, u.full_name, u.email, u.profile_picture,
               u.department, u.contact_number
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

// Fetch all statuses for dropdown
$status_sql = "SELECT id, status_name FROM ticket_statuses ORDER BY id";
$status_result = $conn->query($status_sql);

// Fetch priority levels for dropdown
$priority_sql = "SELECT id, level_name FROM priority_levels ORDER BY id";
$priority_result = $conn->query($priority_sql);

// Fetch ticket attachments
$tickets_sql = "SELECT * FROM tickets WHERE ticket_id = ?";
$stmt = $conn->prepare($tickets_sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$tickets_result = $stmt->get_result();
$tickets = $tickets_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch ticket comments
$comments_sql = "SELECT c.*, u.username, u.full_name, u.profile_picture 
                 FROM ticket_comments c
                 JOIN users u ON c.user_id = u.id
                 WHERE c.ticket_id = ?
                 ORDER BY c.created_at ASC";
$stmt = $conn->prepare($comments_sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$comments_result = $stmt->get_result();
$comments = $comments_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Update ticket as viewed
$stmt = $conn->prepare("UPDATE tickets SET is_new = 0 WHERE ticket_id = ?");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket #<?= htmlspecialchars($ticket['ticket_id']) ?> - TIKSUMA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./src/it-staff.css">
    <style>
        .ticket-container {
            max-width: 1200px;
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
            width: 60px;
            height: 60px;
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
            padding: 10px;
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
        
        .attachments-section {
            margin: 20px 0;
        }
        
        .attachment-item {
            display: inline-block;
            margin: 5px;
            padding: 8px 12px;
            background: #e9ecef;
            border-radius: 4px;
            text-decoration: none;
            color: #495057;
        }
        
        .attachment-item:hover {
            background: #dee2e6;
        }
        
        .comments-section {
            margin-top: 30px;
        }
        
        .comment {
            padding: 15px;
            margin: 10px 0;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }
        
        .comment-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .comment-content {
            margin-left: 50px;
        }
        
        .comment-time {
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>

<?php include 'it-navbar.php'; ?>

<div class="ticket-container">
    <div class="ticket-header">
        <h1><i class="fas fa-ticket-alt"></i> Ticket #<?= htmlspecialchars($ticket['ticket_id']) ?></h1>
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
                <img src="<?= htmlspecialchars($ticket['profile_picture'] ?: 'default-profile.png') ?>" 
                     alt="User" class="user-avatar">
                <div>
                    <strong><?= htmlspecialchars($ticket['full_name']) ?></strong><br>
                    <small><i class="fas fa-user"></i> <?= htmlspecialchars($ticket['username']) ?></small><br>
                    <small><i class="fas fa-envelope"></i> <?= htmlspecialchars($ticket['email']) ?></small><br>
                    <small><i class="fas fa-building"></i> <?= htmlspecialchars($ticket['department'] ?? 'N/A') ?></small><br>
                    <small><i class="fas fa-phone"></i> <?= htmlspecialchars($ticket['contact_number'] ?? 'N/A') ?></small>
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
    </div>

    <div class="description-box">
        <h3><i class="fas fa-file-alt"></i> Subject</h3>
        <p><strong><?= htmlspecialchars($ticket['subject']) ?></strong></p>
        
        <h3><i class="fas fa-align-left"></i> Description</h3>
        <p><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
    </div>

    <?php if (!empty($attachments)): ?>
        <div class="attachments-section">
            <h3><i class="fas fa-paperclip"></i> Attachments</h3>
            <?php foreach ($attachments as $attachment): ?>
                <a href="<?= htmlspecialchars($attachment['file_path']) ?>" 
                   class="attachment-item" target="_blank">
                    <i class="fas fa-file"></i> 
                    <?= htmlspecialchars($attachment['file_name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="action-section">
        <h3><i class="fas fa-cogs"></i> Actions</h3>
        
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
        
        <a href="it-tickets.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to All Tickets
        </a>
    </div>

    <?php if (!empty($comments)): ?>
        <div class="comments-section">
            <h3><i class="fas fa-comments"></i> Comments</h3>
            <?php foreach ($comments as $comment): ?>
                <div class="comment">
                    <div class="comment-header">
                        <img src="<?= htmlspecialchars($comment['profile_picture'] ?: 'default-profile.png') ?>" 
                             alt="User" class="comment-avatar">
                        <div>
                            <strong><?= htmlspecialchars($comment['full_name']) ?></strong>
                            <small class="comment-time">
                                <?= htmlspecialchars($comment['created_at']) ?>
                            </small>
                        </div>
                    </div>
                    <div class="comment-content">
                        <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
