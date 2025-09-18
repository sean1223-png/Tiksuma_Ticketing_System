<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid ticket ID.");
}

include 'db_connect.php';

$username = $_SESSION['username'];
$ticket_id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT t.ticket_id, t.subject, t.description, t.created_at, t.username,
           ts.status_name, pl.level_name AS priority
    FROM tickets t
    JOIN ticket_statuses ts ON t.status_id = ts.id
    JOIN priority_levels pl ON t.priority_id = pl.id
    WHERE t.ticket_id = ?
");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();
$ticket = $result->fetch_assoc();

$stmt->close();

// Access control: Allow Admin, IT Staff, or the ticket owner to view
if ($ticket) {
    $userType = $_SESSION['user_type'] ?? 'User'; // Default to 'User'
    $isOwner = $ticket['username'] === $_SESSION['username'];

    if (!in_array($userType, ['Admin', 'ITStaff']) && !$isOwner) {
        $ticket = null; // deny access
    }
}


// Fetch notes
$notes = [];
if ($ticket) {
    $notes_sql = "SELECT username, note, created_at FROM ticket_notes WHERE ticket_id = ? ORDER BY created_at DESC";
    $notes_stmt = $conn->prepare($notes_sql);
    $notes_stmt->bind_param("i", $ticket['ticket_id']);
    $notes_stmt->execute();
    $notes_result = $notes_stmt->get_result();
    $notes = $notes_result->fetch_all(MYSQLI_ASSOC);
    $notes_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>TIKSUMA TICKET</title>
  <link rel="icon" href="./png/logo-favicon.ico" type="image/x-icon">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
      body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to bottom, #050609ff, #2e4964ff);      
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .ticket-box {
      background: white;
      border-radius: 16px;
      padding: 40px;
      max-width: 100%;
      width:750px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      position: relative;
    }
  

    .ticket-box h1 {
      text-align: center;
      color: #2d3e50;
      font-weight: bold;
      margin-bottom: 5px;
      font-size: 30px;
      font-weight: 800;
      letter-spacing: 1px;
    }

    .ticket-id {
      text-align: center;
      font-size: 14px;
      color: #555;
      margin-bottom: 20px;
    }

    .line {
      height: 1px;
      background-color: #ccc;
      margin: 10px 0 20px;
    }

    .field-label {
      font-weight: 600;
      margin-top: 15px;
      color: #444;
    }

    .field-value {
      margin: 4px 0 10px;
      color: #333;
    }
    .badge.low { background: green; }
    .badge.medium { background: orange; }
    .badge.high { background: red; }
    .badge.new { background: #007bff; }
    
    .badge {
      padding: 5px 12px;
      border-radius: 20px;
      color: white;
      font-size: 13px;
      gap: 5px;
      display: inline-block;
    }

    .print-btn {
      display: block;
      background: #4285f4;
      color: white;
      padding: 10px 30px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      margin: 30px auto 0;
      cursor: pointer;
      transition: 0.3s;
    }

    .print-btn:hover {
      background: #2c6cdf;
    }

    .close-btn {
      position: absolute;
      top: 20px;
      right: 25px;
      font-size: 18px;
      color: #555;
      cursor: pointer;
    }

    @media print {
      .close-btn, .print-btn, .ticket-messaging, .btn-toggle-message, .message-container, .btn-send-message {
        display: none;
      }

      body {
        background: white;
      }

      .ticket-box {
        box-shadow: none;
      }
    }

    /* Messaging Styles */
    .ticket-messaging {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #ccc;
    }

    .btn-toggle-message {
        background: #007bff;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background 0.2s;
    }

    .btn-toggle-message:hover {
        background: #0056b3;
    }

    .message-container {
        margin-top: 15px;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 15px;
        background: #f8f9fa;
    }

    .message-container h3 {
        margin: 0 0 15px 0;
        color: #2d3e50;
        font-size: 18px;
    }

    .messages-list {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 15px;
        background: white;
        border-radius: 4px;
        padding: 10px;
    }

    .message-item {
        background: #e9ecef;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 10px;
    }

    .message-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }

    .message-sender {
        font-weight: 600;
        color: #2d3e50;
    }

    .message-time {
        font-size: 12px;
        color: #6c757d;
    }

    .message-content {
        color: #495057;
        line-height: 1.4;
    }

    .message-input {
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    .message-input textarea {
        flex: 1;
        padding: 10px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        resize: vertical;
        font-family: inherit;
    }

    .btn-send-message {
        background: #28a745;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: background 0.2s;
    }

    .btn-send-message:hover {
        background: #218838;
    }
  </style>
</head>
<body>

<?php if ($ticket): ?>
  <div class="ticket-box">
    <div class="close-btn" onclick="window.history.back()">×</div>

    <h1>TIKSUMA</h1>
    <div class="ticket-id">Ticket ID: <?= str_pad($ticket['ticket_id'], 5, '0', STR_PAD_LEFT) ?></div>
    <hr>

    <div class="label">Issue Type:</div>
    <div class="value"><strong><?= htmlspecialchars($ticket['subject']) ?></strong></div>

    <div class="label">Description:</div>
    <div class="value"><?= nl2br(htmlspecialchars($ticket['description'])) ?></div>

    <div class="label">Priority:</div>
    <div class="value">
      <span class="badge <?= strtolower($ticket['priority']) ?>"><?= $ticket['priority'] ?></span>
    </div>

    <div class="label">Status:</div>
    <div class="value">
      <span class="badge new"><?= $ticket['status_name'] ?></span>
    </div>

    <div class="label">Date:</div>
    <div class="value"><?= date('m/d/Y', strtotime($ticket['created_at'])) ?></div>

    <!-- Ticket Notes Section -->
    <div class="label" style="margin-top: 20px;">Notes:</div>
    <div class="value" style="max-height: 200px; overflow-y: auto; background: #f1f1f1; padding: 10px; border-radius: 8px;">
      <?php if (!empty($notes)): ?>
        <ul style='list-style:none; padding-left:0; margin:0;'>
          <?php foreach ($notes as $note): ?>
            <li style='margin-bottom:10px; padding:8px; background:#fff; border-radius:5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);'>
              <strong><?= htmlspecialchars($note['username']) ?></strong> 
              <em style='color:#666; font-size:0.85em;'>(<?= htmlspecialchars($note['created_at']) ?>)</em><br>
              <?= nl2br(htmlspecialchars($note['note'])) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>No notes added yet.</p>
      <?php endif; ?>
    </div>

    <!-- Messaging Section -->
    <div class="ticket-messaging">
        <button id="toggleMessageBtn" class="btn-toggle-message">
            <i button.innerHTML = '<i class="fas fa-comments"></i> Toggle Messages'"></i> Messages
        </button>
        <div id="messageContainer" class="message-container" style="display: none;">
            <h3>Messages</h3>
            <div id="messagesList" class="messages-list"></div>
            <div class="message-input">
                <textarea id="messageInput" placeholder="Type your message..." rows="3"></textarea>
                <button id="sendMessageBtn" class="btn-send-message">
                    <i class="fas fa-paper-plane"></i> Send
                </button>
            </div>
        </div>
    </div>

    <button class="print-btn" onclick="window.print()">Print</button>
  </div>
<?php else: ?>
  <div class="ticket-box">
    <h1>Ticket Not Found</h1>
    <p>The ticket does not exist or you do not have access.</p>
    <div style="text-align:center;">
      <button class="print-btn" onclick="window.location.href='it-tickets.php'">Back</button>
    </div>
  </div>
<?php endif; ?>

<!-- JavaScript -->
<script>
let currentTicketId = <?php echo json_encode($ticket_id ?? 0); ?>;

// Messaging functions
function toggleMessageContainer() {
    const container = document.getElementById('messageContainer');
    const button = document.getElementById('toggleMessageBtn');

    if (container.style.display === 'none' || container.style.display === '') {
        container.style.display = 'block';
        button.innerHTML = '<i class="fas fa-times"></i> Hide Messages';
        fetchMessages();
    } else {
        container.style.display = 'none';
        button.innerHTML = '<i class="fas fa-comments"></i> Toggle Messages';
    }
}

function fetchMessages() {
    fetch(`fetch-messages.php?ticket_id=${currentTicketId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMessages(data.messages);
            } else {
                console.error('Error fetching messages:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function displayMessages(messages) {
    const messagesList = document.getElementById('messagesList');
    messagesList.innerHTML = '';

    if (messages.length === 0) {
        messagesList.innerHTML = '<p style="text-align: center; color: #6c757d; font-style: italic;">No messages yet.</p>';
        return;
    }

    messages.forEach(message => {
        const messageItem = document.createElement('div');
        messageItem.className = 'message-item';

        messageItem.innerHTML = `
            <div class="message-header">
                <span class="message-sender">${message.sender}</span>
                <span class="message-time">${new Date(message.timestamp).toLocaleString()}</span>
            </div>
            <div class="message-content">${message.message}</div>
        `;

        messagesList.appendChild(messageItem);
    });

    // Scroll to bottom
    messagesList.scrollTop = messagesList.scrollHeight;
}

function sendMessage() {
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();

    if (!message) {
        alert('Please enter a message.');
        return;
    }

    fetch('send-message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            ticket_id: currentTicketId,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageInput.value = '';
            fetchMessages(); // Refresh messages
        } else {
            alert('Error sending message: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error sending message');
    });
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleMessageBtn');
    const sendBtn = document.getElementById('sendMessageBtn');
    const messageInput = document.getElementById('messageInput');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleMessageContainer);
    }

    if (sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }

    if (messageInput) {
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
});
</script>

</body>
</html>
