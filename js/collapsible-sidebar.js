/**
 * Collapsible Sidebar Navigation
 * Handles sidebar toggle, responsive behavior, and keyboard navigation
 */

class CollapsibleSidebar {
  constructor() {
    this.sidebar = null;
    this.hamburgerToggle = null;
    this.mainContent = null;
    this.overlay = null;
    this.isCollapsed = false;
    this.isMobile = window.innerWidth <= 768;
    
    this.init();
  }

  init() {
    this.createElements();
    this.bindEvents();
    this.checkScreenSize();
  }

  createElements() {
    // Create hamburger toggle
    this.hamburgerToggle = document.createElement('button');
    this.hamburgerToggle.className = 'hamburger-toggle';
    this.hamburgerToggle.innerHTML = `
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
    `;
    this.hamburgerToggle.setAttribute('aria-label', 'Toggle navigation');
    this.hamburgerToggle.setAttribute('aria-controls', 'sidebar');
    this.hamburgerToggle.setAttribute('aria-expanded', 'false');

    // Create overlay
    this.overlay = document.createElement('div');
    this.overlay.className = 'sidebar-overlay';
    
    // Get existing elements
    this.sidebar = document.getElementById('sidebar');
    this.mainContent = document.getElementById('mainContent');
    
    // Add elements to DOM
    document.body.appendChild(this.hamburgerToggle);
    document.body.appendChild(this.overlay);
  }

  bindEvents() {
    // Toggle sidebar
    this.hamburgerToggle.addEventListener('click', () => this.toggleSidebar());

    // Close sidebar when clicking overlay
    this.overlay.addEventListener('click', () => this.closeSidebar());

    // Close sidebar when clicking the X button
    const closeBtn = this.sidebar.querySelector('.sidebar-close-btn');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => this.closeSidebar());
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
      if (this.isMobile && !this.sidebar.contains(e.target) &&
          !this.hamburgerToggle.contains(e.target)) {
        this.closeSidebar();
      }
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.closeSidebar();
      }
    });

    // Handle window resize
    window.addEventListener('resize', () => {
      this.checkScreenSize();
    });

    // Handle browser back button on mobile
    window.addEventListener('popstate', () => {
      if (this.isMobile && !this.isCollapsed) {
        this.closeSidebar();
      }
    });
  }

  toggleSidebar() {
    if (this.isCollapsed) {
      this.openSidebar();
    } else {
      this.closeSidebar();
    }
  }

  openSidebar() {
    this.sidebar.classList.remove('collapsed');
    this.hamburgerToggle.classList.add('active');
    this.hamburgerToggle.setAttribute('aria-expanded', 'true');
    this.mainContent.classList.remove('sidebar-collapsed');
    this.overlay.classList.add('active');
    this.isCollapsed = false;
    
    // Prevent body scroll on mobile
    if (this.isMobile) {
      document.body.style.overflow = 'hidden';
    }
  }

  closeSidebar() {
    this.sidebar.classList.add('collapsed');
    this.hamburgerToggle.classList.remove('active');
    this.hamburgerToggle.setAttribute('aria-expanded', 'false');
    this.mainContent.classList.add('sidebar-collapsed');
    this.overlay.classList.remove('active');
    this.isCollapsed = true;
    
    // Restore body scroll
    document.body.style.overflow = '';
  }

  checkScreenSize() {
    const wasMobile = this.isMobile;
    this.isMobile = window.innerWidth <= 768;
    
    // Handle responsive behavior
    if (wasMobile !== this.isMobile) {
      if (this.isMobile) {
        // Mobile mode - always start closed
        this.closeSidebar();
      } else {
        // Desktop mode - restore previous state
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
          this.closeSidebar();
        } else {
          this.openSidebar();
        }
      }
    }
  }

  // Save state to localStorage
  saveState() {
    localStorage.setItem('sidebarCollapsed', this.isCollapsed);
  }

  // Load state from localStorage
  loadState() {
    const saved = localStorage.getItem('sidebarCollapsed');
    if (saved !== null) {
      this.isCollapsed = saved === 'true';
      if (this.isCollapsed) {
        this.closeSidebar();
      } else {
        this.openSidebar();
      }
    }
  }

  // Public methods
  toggle() {
    this.toggleSidebar();
    this.saveState();
  }

  open() {
    this.openSidebar();
    this.saveState();
  }

  close() {
    this.closeSidebar();
    this.saveState();
  }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  const sidebar = new CollapsibleSidebar();
  
  // Make it globally accessible
  window.sidebar = sidebar;
  
  // Load saved state
  sidebar.loadState();
});

// Handle page visibility changes
document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    // Save state when page becomes hidden
    if (window.sidebar) {
      window.sidebar.saveState();
    }
  }
});
