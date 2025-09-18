/**
 * TIKSUMA Ticketing System - Master Template JavaScript
 * Enhanced functionality and user experience
 */

// Global variables
let currentUser = null;
let notifications = [];
let sidebarCollapsed = false;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeTemplate();
    setupEventListeners();
    loadNotifications();
    setupSearch();
});

/**
 * Initialize the template system
 */
function initializeTemplate() {
    // Hide loading overlay
    setTimeout(() => {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
    }, 500);
    
    // Add fade-in animation to main content
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.classList.add('fade-in');
    }
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize responsive features
    initializeResponsiveFeatures();
}

/**
 * Setup event listeners for interactive elements
 */
function setupEventListeners() {
    // Sidebar toggle
    const sidebarToggle = document.querySelector('[data-bs-toggle="collapse"][data-bs-target="#sidebarMenu"]');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    // Search functionality
    const searchInput = document.getElementById('globalSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleSearch();
            }
        });
    }
    
    // Notification bell
    const notificationBell = document.getElementById('notificationBell');
    if (notificationBell) {
        notificationBell.addEventListener('click', toggleNotifications);
    }
    
    // Print functionality
    const printButtons = document.querySelectorAll('.print-btn');
    printButtons.forEach(btn => {
        btn.addEventListener('click', handlePrint);
    });
    
    // Table row click handlers
    setupTableRowClicks();
    
    // Form validation
    setupFormValidation();
}

/**
 * Toggle sidebar visibility on mobile
 */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebarMenu');
    const mainContent = document.querySelector('.main-content');
    
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('show');
    } else {
        sidebarCollapsed = !sidebarCollapsed;
        sidebar.style.width = sidebarCollapsed ? '70px' : '280px';
        mainContent.style.marginLeft = sidebarCollapsed ? '70px' : '280px';
    }
}

/**
 * Initialize Bootstrap tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize responsive features
 */
function initializeResponsiveFeatures() {
    // Handle window resize
    window.addEventListener('resize', function() {
        const sidebar = document.getElementById('sidebarMenu');
        const mainContent = document.querySelector('.main-content');
        
        if (window.innerWidth > 768) {
            sidebar.classList.remove('show');
            sidebar.style.width = sidebarCollapsed ? '70px' : '280px';
            mainContent.style.marginLeft = sidebarCollapsed ? '70px' : '280px';
        } else {
            sidebar.style.width = '';
            mainContent.style.marginLeft = '';
        }
    });
}

/**
 * Load notifications from server
 */
async function loadNotifications() {
    try {
        const response = await fetch('api/notifications.php');
        if (response.ok) {
            notifications = await response.json();
            updateNotificationBadge();
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

/**
 * Update notification badge count
 */
function updateNotificationBadge() {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        badge.textContent = notifications.length;
        badge.style.display = notifications.length > 0 ? 'block' : 'none';
    }
}

/**
 * Toggle notification dropdown
 */
function toggleNotifications() {
    const dropdown = document.querySelector('.notification-dropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

/**
 * Handle global search functionality
 */
function handleSearch() {
    const searchInput = document.getElementById('globalSearch');
    const query = searchInput.value.trim();
    
    if (query.length < 2) return;
    
    // Show loading
    showLoading();
    
    // Perform search
    fetch(`api/search.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(results => displaySearchResults(results))
        .catch(error => {
            console.error('Search error:', error);
            showToast('Search failed', 'error');
        })
        .finally(() => hideLoading());
}

/**
 * Display search results
 */
function displaySearchResults(results) {
    const modal = createSearchModal(results);
    document.body.appendChild(modal);
    new bootstrap.Modal(modal).show();
}

/**
 * Create search results modal
 */
function createSearchModal(results) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Search Results</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ${results.length === 0 ? 
                        '<p class="text-muted">No results found.</p>' :
                        results.map(result => `
                            <div class="search-result-item p-3 border-bottom">
                                <h6><a href="view-ticket.php?id=${result.id}" class="text-decoration-none">
                                    #${result.id} - ${result.subject}
                                </a></h6>
                                <small class="text-muted">Status: ${result.status} | Priority: ${result.priority}</small>
                            </div>
                        `).join('')
                    }
                </div>
            </div>
        </div>
    `;
    
    modal.addEventListener('hidden.bs.modal', () => modal.remove());
    return modal;
}

/**
 * Setup table row click handlers
 */
function setupTableRowClicks() {
    const tables = document.querySelectorAll('.table-hover tbody tr');
    tables.forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function() {
            const ticketId = this.getAttribute('data-id');
            if (ticketId) {
                window.location.href = `view-ticket.php?id=${ticketId}`;
            }
        });
    });
}

/**
 * Setup form validation
 */
function setupFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

/**
 * Handle print functionality
 */
function handlePrint() {
    window.print();
}

/**
 * Show loading overlay
 */
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.remove('hidden');
    }
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.add('hidden');
    }
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toastContainer = document.createElement('div');
    toastContainer.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    `;
    
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show`;
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    toastContainer.appendChild(toast);
    document.body.appendChild(toastContainer);
    
    setTimeout(() => {
        toastContainer.remove();
    }, 5000);
}

/**
 * Debounce function for search
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Format date for display
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Confirm action with SweetAlert
 */
function confirmAction(message, callback) {
    Swal.fire({
        title: 'Are you sure?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed && callback) {
            callback();
        }
    });
}

/**
 * Handle AJAX form submission
 */
async function submitForm(form, endpoint) {
    const formData = new FormData(form);
    
    try {
        showLoading();
        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message || 'Operation successful', 'success');
            return result;
        } else {
            showToast(result.message || 'Operation failed', 'error');
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Form submission error:', error);
        showToast('An error occurred', 'error');
        throw error;
    } finally {
        hideLoading();
    }
}

/**
 * Export data to CSV
 */
function exportToCSV(data, filename) {
    const csvContent = data.map(row => Object.values(row).join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Utility functions for table operations
const TableUtils = {
    /**
     * Initialize DataTable with common options
     */
    initDataTable(selector, options = {}) {
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            return $(selector).DataTable({
                responsive: true,
                pageLength: 10,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        previous: "Previous",
                        next: "Next"
                    }
                },
                ...options
            });
        }
    },
    
    /**
     * Refresh table data
     */
    refreshTable(tableId) {
        const table = $(`#${tableId}`).DataTable();
        if (table) {
            table.ajax.reload();
        }
    }
};

// Export functions for global use
window.TiksumaTemplate = {
    showToast,
    confirmAction,
    submitForm,
    exportToCSV,
    TableUtils,
    formatDate
};
