
<?php
/**
 * TIKSUMA Ticketing System - Table Template for Main Content
 * 
 * This template provides a reusable table structure for displaying tickets
 * with consistent styling and functionality across the system.
 * 
 * Usage:
 * 1. Include this file where needed
 * 2. Pass the $tickets array with required fields
 * 3. Customize columns as needed
 */

// Ensure $tickets array is available
if (!isset($tickets)) {
    $tickets = [];
}

// Default table configuration
$tableConfig = [
    'id' => 'ticketTable',
    'class' => 'ticket-table',
    'showActions' => true,
    'showPriority' => true,
    'showStatus' => true,
    'actionButtons' => ['view', 'edit', 'delete'],
    'emptyMessage' => 'No tickets found.',
    'printButton' => true,
    'searchBox' => true
];

// Merge with custom config if provided
if (isset($tableConfigCustom)) {
    $tableConfig = array_merge($tableConfig, $tableConfigCustom);
}
?>

<!-- Table Container -->
<div class="table-container">
    <div class="table-controls">
        <?php if ($tableConfig['printButton']): ?>
        <button class="print-btn" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
        <?php endif; ?>
    </div>
    
    <?php if ($tableConfig['searchBox']): ?>
    <div class="table-search">
        <input type="text" id="tableSearch" placeholder="Search tickets..." class="search-input">
    </div>
    <?php endif; ?>

    <!-- Main Table -->
    <table id="<?= htmlspecialchars($tableConfig['id']) ?>" class="<?= htmlspecialchars($tableConfig['class']) ?>">
        <thead>
            <tr>
                <th>Ticket ID</th>
                <th>Subject</th>
                
                <?php if ($tableConfig['showPriority']): ?>
                <th>Priority</th>
                <?php endif; ?>
                
                <?php if ($tableConfig['showStatus']): ?>
                <th>Status</th>
                <?php endif; ?>
                
                <th>Created</th>
                
                <?php if ($tableConfig['showActions']): ?>
                <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        
        <tbody>
            <?php if (!empty($tickets)): ?>
                <?php foreach ($tickets as $ticket): 
                    $pri = strtolower($ticket['priority']);
                    $status = strtolower(str_replace(' ', '-', $ticket['status']));
                ?>
                    <tr data-id="<?= htmlspecialchars($ticket['ticket_id']) ?>">
                        <td>
                            <span class="ticket-id">#<?= htmlspecialchars($ticket['ticket_id']) ?></span>
                        </td>
                        
                        <td>
                            <div class="ticket-subject">
                                <?= htmlspecialchars($ticket['subject']) ?>
                                <div class="ticket-description">
                                    <?= htmlspecialchars($ticket['description']) ?>
                                </div>
                            </div>
                        </td>
                        
                        <?php if ($tableConfig['showPriority']): ?>
                        <td>
                            <span class="priority-badge priority-<?= $pri ?>">
                                <?= htmlspecialchars($ticket['priority']) ?>
                            </span>
                        </td>
                        <?php endif; ?>
                        
                        <?php if ($tableConfig['showStatus']): ?>
                        <td>
                            <span class="status-badge status-<?= $status ?>">
                                <?= htmlspecialchars($ticket['status']) ?>
                            </span>
                        </td>
                        <?php endif; ?>
                        
                        <td>
                            <span class="ticket-date">
                                <?= date('M d, Y', strtotime($ticket['created_at'])) ?>
                            </span>
                        </td>
                        
                        <?php if ($tableConfig['showActions']): ?>
                        <td class="ticket-actions">
                            <div class="action-buttons">
                                <?php if (in_array('view', $tableConfig['actionButtons'])): ?>
                                <a href="javascript:void(0);" 
                                   onclick="handleTicketAction('view', <?= htmlspecialchars($ticket['ticket_id']) ?>)" 
                                   class="btn-view" title="View Ticket">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (in_array('assign', $tableConfig['actionButtons'])): ?>
                                <button onclick="handleTicketAction('assign', <?= htmlspecialchars($ticket['ticket_id']) ?>)" 
                                        class="btn-assign" title="Assign to Me">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                                <?php endif; ?>
                                
                                <?php if (in_array('in-progress', $tableConfig['actionButtons'])): ?>
                                <button onclick="handleTicketAction('in-progress', <?= htmlspecialchars($ticket['ticket_id']) ?>)"
                                        class="btn-progress" title="Mark as In Progress">
                                    <i class="fas fa-spinner"></i>
                                </button>
                                <?php endif; ?>

                                <?php if (in_array('pending', $tableConfig['actionButtons'])): ?>
                                <button onclick="handleTicketAction('pending', <?= htmlspecialchars($ticket['ticket_id']) ?>)"
                                        class="btn-pending" title="Mark as Pending">
                                    <i class="fas fa-clock"></i>
                                </button>
                                <?php endif; ?>

                                <?php if (in_array('resolve', $tableConfig['actionButtons'])): ?>
                                <button onclick="handleTicketAction('resolve', <?= htmlspecialchars($ticket['ticket_id']) ?>)"
                                        class="btn-resolve" title="Resolve Ticket">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                                <?php endif; ?>
                                
                                <?php if (in_array('add-note', $tableConfig['actionButtons'])): ?>
                                <button onclick="handleTicketAction('add-note', <?= htmlspecialchars($ticket['ticket_id']) ?>)" 
                                        class="btn-note" title="Add Note">
                                    <i class="fas fa-sticky-note"></i>
                                </button>
                                <?php endif; ?>
                                
                                <?php if (in_array('escalate', $tableConfig['actionButtons'])): ?>
                                <button onclick="handleTicketAction('escalate', <?= htmlspecialchars($ticket['ticket_id']) ?>)" 
                                        class="btn-escalate" title="Escalate Priority">
                                    <i class="fas fa-arrow-up"></i>
                                </button>
                                <?php endif; ?>
                                
                                <?php if (in_array('reassign', $tableConfig['actionButtons'])): ?>
                                <button onclick="handleTicketAction('reassign', <?= htmlspecialchars($ticket['ticket_id']) ?>)"
                                        class="btn-reassign" title="Reassign Ticket">
                                    <i class="fas fa-exchange-alt"></i>
                                </button>
                                <?php endif; ?>

                                <?php if (in_array('archive', $tableConfig['actionButtons'])): ?>
                                <button onclick="handleTicketAction('archive', <?= htmlspecialchars($ticket['ticket_id']) ?>)"
                                        class="btn-archive" title="Archive Ticket">
                                    <i class="fas fa-archive"></i>
                                </button>
                                <?php endif; ?>

                                <?php if (in_array('edit', $tableConfig['actionButtons'])): ?>
                                <a href="edit-ticket.php?id=<?= urlencode($ticket['ticket_id']) ?>"
                                   class="btn-edit" title="Edit Ticket">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>

                                <?php if (in_array('delete', $tableConfig['actionButtons'])): ?>
                                <button onclick="deleteTicket(<?= htmlspecialchars($ticket['ticket_id']) ?>)"
                                        class="btn-delete" title="Delete Ticket">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $tableConfig['showActions'] ? '6' : '4' ?>" 
                        class="no-data">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p><?= htmlspecialchars($tableConfig['emptyMessage']) ?></p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- CSS Styles -->
<style>
/* Table Container */
.table-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    margin: 20px 0;
}

/* Table Controls */
.table-controls {
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-search {
    padding: 15px 20px;
    border-bottom: 1px solid #dee2e6;
}

.search-input {
    width: 100%;
    max-width: 300px;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

/* Print Button */
.print-btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.print-btn:hover {
    background: #0056b3;
}

/* Table Styles */
.ticket-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

.ticket-table th {
    background: #f8f9fa;
    padding: 15px 12px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    font-size: 14px;
}

.ticket-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
    vertical-align: middle;
}

.ticket-table tbody tr:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

/* Priority Badges */
.priority-badge {
    padding: 4px 8px;
    border-radius: 12px;
    color: white;
    font-size: 0.85em;
    font-weight: 600;
    text-transform: uppercase;
}

.priority-high { background-color: #dc3545; }
.priority-medium { background-color: #fd7e14; }
.priority-low { background-color: #28a745; }

/* Status Badges */
.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    color: white;
    font-size: 0.85em;
    font-weight: 600;
}

.status-open { background-color: #007bff; }
.status-in-progress { background-color: #ffc107; }
.status-pending { background-color: #17a2b8; }
.status-resolved { background-color: #28a745; }
.status-closed { background-color: #6c757d; }

/* Ticket Info */
.ticket-id {
    font-weight: 600;
    color: #007bff;
}

.ticket-subject {
    font-weight: 500;
    color: #495057;
}

.ticket-description {
    font-size: 0.85em;
    color: #6c757d;
    margin-top: 4px;
}

.ticket-date {
    color: #6c757d;
    font-size: 0.9em;
}

/* Action Buttons */
.ticket-actions {
    white-space: nowrap;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.action-buttons a,
.action-buttons button {
    padding: 6px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 12px;
    transition: all 0.2s;
}

.btn-view {
    background: #007bff;
    color: white;
}

.btn-assign {
    background: #17a2b8;
    color: white;
}

.btn-progress {
    background: #ffc107;
    color: #212529;
}

.btn-pending {
    background: #17a2b8;
    color: white;
}

.btn-resolve {
    background: #28a745;
    color: white;
}

.btn-note {
    background: #6f42c1;
    color: white;
}

.btn-escalate {
    background: #fd7e14;
    color: white;
}

.btn-reassign {
    background: #6c757d;
    color: white;
}

.btn-archive {
    background: #6c757d;
    color: white;
}

.btn-edit {
    background: #ffc107;
    color: #212529;
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.action-buttons a:hover,
.action-buttons button:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Empty State */
.no-data {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.empty-state i {
    font-size: 48px;
    color: #dee2e6;
}

.empty-state p {
    margin: 0;
    font-size: 16px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .ticket-table {
        font-size: 14px;
    }
    
    .ticket-table th,
    .ticket-table td {
        padding: 8px;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 2px;
    }
    
    .action-buttons a,
    .action-buttons button {
        padding: 4px 6px;
        font-size: 11px;
    }
}

@media print {
    .table-controls,
    .table-search,
    .ticket-actions {
        display: none;
    }
    
    .ticket-table {
        box-shadow: none;
        border: 1px solid #dee2e6;
    }
}
</style>

<!-- JavaScript -->
<script>
// Search functionality
function initializeTableSearch(tableId, searchInputId) {
    const searchInput = document.getElementById(searchInputId);
    const table = document.getElementById(tableId);
    
    if (!searchInput || !table) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

// Delete ticket confirmation
function deleteTicket(ticketId) {
    if (confirm('Are you sure you want to delete this ticket?')) {
        // Implement delete functionality
        window.location.href = `delete-ticket.php?id=${ticketId}`;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeTableSearch('<?= $tableConfig['id'] ?>', 'tableSearch');
    
    // Add click handlers for rows
    const rows = document.querySelectorAll('#<?= $tableConfig['id'] ?> tbody tr[data-id]');
    rows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't trigger if clicking on action buttons
            if (e.target.closest('.ticket-actions')) return;
            
            const ticketId = this.getAttribute('data-id');
            window.location.href = `get-user-tickets.php?id=${ticketId}`;
        });
    });
});
</script>
