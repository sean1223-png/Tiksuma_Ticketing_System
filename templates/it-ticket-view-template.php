<?php
/**
 * TIKSUMA Ticketing System - IT Staff Ticket View Template
 *
 * This template displays detailed ticket information for IT staff,
 * including actions like assign, update status, add notes, etc.
 *
 * Usage:
 * 1. Include this file where needed
 * 2. Pass the $ticket array with required fields
 * 3. Ensure $notes array is available if notes are to be displayed
 */

// Ensure $ticket array is available
if (!isset($ticket)) {
    $ticket = [];
}

// Default values
$ticket_id = $ticket['ticket_id'] ?? '';
$subject = $ticket['subject'] ?? '';
$description = $ticket['description'] ?? '';
$priority = $ticket['priority'] ?? '';
$status = $ticket['status'] ?? '';
$created_at = $ticket['created_at'] ?? '';
$updated_at = $ticket['updated_at'] ?? '';
$assigned_to = $ticket['assigned_to'] ?? '';
$username = $ticket['username'] ?? '';
$full_name = $ticket['full_name'] ?? '';
$email = $ticket['email'] ?? '';
$profile_picture = $ticket['profile_picture'] ?? '';

// Notes
$notes = $ticket['notes'] ?? [];
?>

<!-- Ticket View Container -->
<div class="ticket-view-container">
    <div class="ticket-header">
        <h2>Ticket #<?= htmlspecialchars($ticket_id) ?></h2>
        <div class="ticket-meta">
            <span class="meta-item">Created: <?= date('M d, Y H:i', strtotime($created_at)) ?></span>
            <span class="meta-item">Updated: <?= $updated_at ? date('M d, Y H:i', strtotime($updated_at)) : 'Never' ?></span>
        </div>
    </div>

    <div class="ticket-content">
        <div class="ticket-details">
            <div class="detail-section">
                <h3>Ticket Information</h3>
                <div class="detail-row">
                    <label>Subject:</label>
                    <span><?= htmlspecialchars($subject) ?></span>
                </div>
                <div class="detail-row">
                    <label>Description:</label>
                    <div class="description-content">
                        <?= nl2br(htmlspecialchars(mb_strimwidth($description, 0, 50, '...'))) ?>
                    </div>
                </div>
                <div class="detail-row">
                    <label>Priority:</label>
                    <span class="priority-badge priority-<?= strtolower($priority) ?>">
                        <?= htmlspecialchars($priority) ?>
                    </span>
                </div>
                <div class="detail-row">
                    <label>Status:</label>
                    <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $status)) ?>">
                        <?= htmlspecialchars($status) ?>
                    </span>
                </div>
                <div class="detail-row">
                    <label>Assigned To:</label>
                    <span><?= htmlspecialchars($assigned_to ?: 'Unassigned') ?></span>
                </div>
            </div>

            <div class="detail-section">
                <h3>User Information</h3>
                <div class="user-info">
                    <img src="<?= htmlspecialchars($profile_picture ?: 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50"><circle cx="25" cy="25" r="25" fill="#ccc"/><circle cx="25" cy="18" r="8" fill="#999"/><path d="M10 45 Q10 35 25 35 Q40 35 40 45" fill="#999"/></svg>')) ?>" alt="Profile" class="user-avatar">
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars($full_name ?: $username) ?></div>
                        <div class="user-email"><?= htmlspecialchars($email) ?></div>
                        <div class="user-username">@<?= htmlspecialchars($username) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ticket-actions">
            <h3>Actions</h3>
            <div class="action-buttons">
                <button onclick="handleTicketAction('assign', <?= htmlspecialchars($ticket_id) ?>)" class="btn-action btn-assign">
                    <i class="fas fa-user-plus"></i> Assign to Me
                </button>
                <button onclick="handleTicketAction('in-progress', <?= htmlspecialchars($ticket_id) ?>)" class="btn-action btn-progress">
                    <i class="fas fa-spinner"></i> Mark In Progress
                </button>
                <button onclick="handleTicketAction('pending', <?= htmlspecialchars($ticket_id) ?>)" class="btn-action btn-pending">
                    <i class="fas fa-clock"></i> Mark Pending
                </button>
                <button onclick="handleTicketAction('resolve', <?= htmlspecialchars($ticket_id) ?>)" class="btn-action btn-resolve">
                    <i class="fas fa-check-circle"></i> Resolve
                </button>
                <button onclick="handleTicketAction('add-note', <?= htmlspecialchars($ticket_id) ?>)" class="btn-action btn-note">
                    <i class="fas fa-sticky-note"></i> Add Note
                </button>
                <button onclick="handleTicketAction('escalate', <?= htmlspecialchars($ticket_id) ?>)" class="btn-action btn-escalate">
                    <i class="fas fa-arrow-up"></i> Escalate
                </button>
                <button onclick="handleTicketAction('reassign', <?= htmlspecialchars($ticket_id) ?>)" class="btn-action btn-reassign">
                    <i class="fas fa-exchange-alt"></i> Reassign
                </button>
            </div>
        </div>
    </div>

    <div class="ticket-notes">
        <h3>Notes</h3>
        <div class="notes-list">
            <?php if (!empty($notes)): ?>
                <?php foreach ($notes as $note): ?>
                    <div class="note-item">
                        <div class="note-header">
                            <strong><?= htmlspecialchars($note['username']) ?></strong>
                            <span class="note-date"><?= date('M d, Y H:i', strtotime($note['created_at'])) ?></span>
                        </div>
                        <div class="note-content">
                            <?= nl2br(htmlspecialchars($note['note'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-notes">No notes added yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Messaging Section -->
    <div class="ticket-messaging">
        <button id="toggleMessageBtn" class="btn-toggle-message">
            <i class="fas fa-comments"></i> Toggle Messages
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
</div>

<!-- CSS Styles -->
<style>
.ticket-view-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.ticket-header {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.ticket-header h2 {
    margin: 0;
    color: #2d3e50;
    font-size: 24px;
}

.ticket-meta {
    margin-top: 10px;
}

.meta-item {
    display: inline-block;
    margin-right: 20px;
    font-size: 14px;
    color: #6c757d;
}

.ticket-content {
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
}

.ticket-details {
    flex: 2;
}

.detail-section {
    margin-bottom: 25px;
}

.detail-section h3 {
    margin: 0 0 15px 0;
    color: #2d3e50;
    font-size: 18px;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 5px;
}

.detail-row {
    display: flex;
    margin-bottom: 10px;
    align-items: flex-start;
}

.detail-row label {
    font-weight: 600;
    color: #495057;
    min-width: 120px;
    margin-right: 15px;
}

.detail-row span {
    color: #495057;
}

.description-content {
    max-width: 600px;
    line-height: 1.5;
}

.priority-badge, .status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    color: white;
    font-size: 0.85em;
    font-weight: 600;
}

.priority-high { background-color: #dc3545; }
.priority-medium { background-color: #fd7e14; }
.priority-low { background-color: #28a745; }

.status-open { background-color: #007bff; }
.status-in-progress { background-color: #ffc107; }
.status-pending { background-color: #17a2b8; }
.status-resolved { background-color: #28a745; }
.status-closed { background-color: #6c757d; }

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: 2px solid #065fd4;
}

.user-details {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 600;
    color: #2d3e50;
}

.user-email, .user-username {
    font-size: 14px;
    color: #6c757d;
}

.ticket-actions {
    flex: 1;
}

.ticket-actions h3 {
    margin: 0 0 15px 0;
    color: #2d3e50;
    font-size: 18px;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.btn-action {
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-align: left;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.btn-assign { background: #17a2b8; color: white; }
.btn-progress { background: #ffc107; color: #212529; }
.btn-pending { background: #17a2b8; color: white; }
.btn-resolve { background: #28a745; color: white; }
.btn-note { background: #6f42c1; color: white; }
.btn-escalate { background: #fd7e14; color: white; }
.btn-reassign { background: #6c757d; color: white; }

.ticket-notes {
    border-top: 2px solid #e9ecef;
    padding-top: 20px;
}

.ticket-notes h3 {
    margin: 0 0 15px 0;
    color: #2d3e50;
    font-size: 18px;
}

.notes-list {
    max-height: 400px;
    overflow-y: auto;
}

.note-item {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 10px;
}

.note-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.note-header strong {
    color: #2d3e50;
}

.note-date {
    font-size: 12px;
    color: #6c757d;
}

.note-content {
    color: #495057;
    line-height: 1.4;
}

.no-notes {
    text-align: center;
    color: #6c757d;
    font-style: italic;
    padding: 20px;
}

/* Messaging Styles */
.ticket-messaging {
    border-top: 2px solid #e9ecef;
    padding-top: 20px;
    margin-top: 20px;
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

/* Responsive */
@media (max-width: 768px) {
    .ticket-content {
        flex-direction: column;
    }

    .detail-row {
        flex-direction: column;
    }

    .detail-row label {
        min-width: auto;
        margin-bottom: 5px;
    }

    .action-buttons {
        flex-direction: row;
        flex-wrap: wrap;
    }

    .btn-action {
        flex: 1 1 45%;
        justify-content: center;
    }

    .message-input {
        flex-direction: column;
    }

    .message-input textarea {
        width: 100%;
    }
}
</style>

<!-- JavaScript -->
<script>
let currentTicketId = <?= json_encode($ticket_id) ?>;

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

// Placeholder for action handlers - implement as needed
function handleTicketAction(action, ticketId) {
    console.log('Action:', action, 'Ticket ID:', ticketId);
    // Implement specific actions here
    alert('Action: ' + action + ' for ticket ' + ticketId);
}
</script>
