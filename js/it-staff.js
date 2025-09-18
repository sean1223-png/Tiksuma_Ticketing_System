function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.getElementById('mainContent');

  sidebar.classList.toggle('closed');

  if (sidebar.classList.contains('closed')) {
    mainContent.style.marginLeft = "0";
    mainContent.style.width = "100%";
  } else {
    mainContent.style.marginLeft = "280px";
    mainContent.style.width = "calc(100% - 280px)";
  }
}

// Function to check screen size and adjust sidebar accordingly
function checkScreenSize() {
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.getElementById('mainContent');
  const isMobile = window.innerWidth <= 768;

  if (isMobile) {
    // Close sidebar on mobile
    sidebar.classList.add('closed');
    mainContent.style.marginLeft = "0";
    mainContent.style.width = "100%";
  } else {
    // Open sidebar on desktop
    sidebar.classList.remove('closed');
    mainContent.style.marginLeft = "280px";
    mainContent.style.width = "calc(100% - 280px)";
  }
}

window.onload = function () {
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.getElementById('mainContent');
  if (!sidebar.classList.contains('closed')) {
    mainContent.style.marginLeft = "280px";
    mainContent.style.width = "calc(100% - 280px)";
  }

  // Check screen size on load
  checkScreenSize();
};

// Add resize event listener
window.addEventListener('resize', checkScreenSize);

function performSearch() {
  const query = document.getElementById('searchInput').value.trim();
  if (query !== '') {
    alert("Searching for: " + query); // Replace with search logic
  }
}

// Unified Ticket Action Function
function handleTicketAction(action, ticketId) {
  switch (action) {
    case 'view':
      // View action - redirect to view page
      window.location.href = `view-ticket.php?id=${ticketId}`;
      break;

    case 'assign':
      // Assign ticket to current user
      if (confirm('Assign this ticket to yourself?')) {
        fetch('assign-ticket.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ ticket_id: ticketId })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Ticket assigned successfully!');
            location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error assigning ticket');
        });
      }
      break;

    case 'in-progress':
      // Mark ticket as In Progress
      if (confirm('Mark this ticket as In Progress?')) {
        fetch('update-ticket-status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ 
            ticket_id: ticketId,
            status: 'In Progress'
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Ticket status updated to In Progress!');
            location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error updating ticket status');
        });
      }
      break;

    case 'pending':
      // Mark ticket as Pending
      if (confirm('Mark this ticket as Pending?')) {
        fetch('update-ticket-status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            ticket_id: ticketId,
            status: 'Pending'
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Ticket marked as Pending successfully!');
            location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error marking ticket as Pending');
        });
      }
      break;

    case 'resolve':
      // Mark ticket as Resolved
      if (confirm('Mark this ticket as Resolved?')) {
        fetch('update-ticket-status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            ticket_id: ticketId,
            status: 'Resolved'
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Ticket resolved successfully!');
            location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error resolving ticket');
        });
      }
      break;

    case 'add-note':
      // Add note to ticket
      const note = prompt('Enter your note:');
      if (note !== null && note.trim() !== '') {
        fetch('add-ticket-note.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ 
            ticket_id: ticketId,
            note: note.trim()
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Note added successfully!');
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error adding note');
        });
      }
      break;

    case 'escalate':
      // Escalate ticket priority
      if (confirm('Escalate this ticket to higher priority?')) {
        fetch('escalate-ticket.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ ticket_id: ticketId })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Ticket escalated successfully!');
            location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error escalating ticket');
        });
      }
      break;

    case 'reassign':
      // Reassign ticket to another user
      const newAssignee = prompt('Enter username to reassign to:');
      if (newAssignee !== null && newAssignee.trim() !== '') {
        fetch('reassign-ticket.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            ticket_id: ticketId,
            assignee: newAssignee.trim()
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Ticket reassigned successfully!');
            location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error reassigning ticket');
        });
      }
      break;

    case 'archive':
      // Archive ticket
      if (confirm('Archive this ticket?')) {
        fetch('update-ticket-status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            ticket_id: ticketId,
            status: 'Archived'
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Ticket archived successfully!');
            location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error archiving ticket');
        });
      }
      break;

    default:
      console.error('Unknown action:', action);
      alert('Unknown action requested');
  }
}

// Individual functions for backward compatibility
function assignTicket(ticketId) { handleTicketAction('assign', ticketId); }
function markInProgress(ticketId) { handleTicketAction('in-progress', ticketId); }
function resolveTicket(ticketId) { handleTicketAction('resolve', ticketId); }
function addNote(ticketId) { handleTicketAction('add-note', ticketId); }
function escalateTicket(ticketId) { handleTicketAction('escalate', ticketId); }
function reassignTicket(ticketId) { handleTicketAction('reassign', ticketId); }

// Prevent row click when clicking on action buttons
document.addEventListener('DOMContentLoaded', function() {
  const actionButtons = document.querySelectorAll('.action-buttons button, .action-buttons a');
  actionButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      e.stopPropagation();
    });
  });

  // Theme mode toggle
  const currentTheme = localStorage.getItem('theme') || 'white';

  function applyTheme(theme) {
    if (theme === 'black') {
      document.body.classList.add('black-theme');
    } else {
      document.body.classList.remove('black-theme');
    }
  }

  function toggleThemeMode() {
    const newTheme = currentTheme === 'white' ? 'black' : 'white';
    applyTheme(newTheme);
    localStorage.setItem('theme', newTheme);
    // Update checkbox
    const checkbox = document.getElementById('themeToggle');
    if (checkbox) {
      checkbox.checked = newTheme === 'black';
    }
  }

  // Apply saved theme on load
  applyTheme(currentTheme);

  // Set checkbox based on saved theme
  const themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.checked = currentTheme === 'black';
    themeToggle.addEventListener('change', () => {
      const selectedTheme = themeToggle.checked ? 'black' : 'white';
      applyTheme(selectedTheme);
      localStorage.setItem('theme', selectedTheme);
    });
  }
});
