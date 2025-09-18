function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const logoIcon = document.getElementById('logoIcon');

  sidebar.classList.toggle('closed');

  // Toggle logo icon visibility
  if (sidebar.classList.contains('closed')) {
    if (logoIcon) {
      logoIcon.classList.add('show');
    }
  } else {
    if (logoIcon) {
      logoIcon.classList.remove('show');
    }
  }
}

// Function to open sidebar when logo icon is clicked
function openSidebarFromLogo() {
  const sidebar = document.getElementById('sidebar');
  const logoIcon = document.getElementById('logoIcon');

  sidebar.classList.remove('closed');
  if (logoIcon) {
    logoIcon.classList.remove('show');
  }
}

// Function to check screen size and adjust sidebar accordingly
function checkScreenSize() {
  const sidebar = document.getElementById('sidebar');
  const logoIcon = document.getElementById('logoIcon');
  const mainContent = document.getElementById('mainContent');
  const isMobile = window.innerWidth <= 768;

  if (isMobile) {
    // Close sidebar on mobile
    sidebar.classList.add('closed');
    if (logoIcon) {
      logoIcon.classList.add('show');
    }
  } else {
    // Open sidebar on desktop
    sidebar.classList.remove('closed');
    if (logoIcon) {
      logoIcon.classList.remove('show');
    }
  }

  if (sidebar.classList.contains('closed')) {
    mainContent.style.marginLeft = "0";
    mainContent.style.width = "100%";
  } else {
    mainContent.style.marginLeft = "240px";
    mainContent.style.width = "calc(100% - 240px)";
  }
}

window.onload = function () {
  const sidebar = document.getElementById('sidebar');
  const logoIcon = document.getElementById('logoIcon');

  // Initialize logo icon visibility based on sidebar state
  if (sidebar.classList.contains('closed')) {
    if (logoIcon) {
      logoIcon.classList.add('show');
    }
  } else {
    if (logoIcon) {
      logoIcon.classList.remove('show');
    }
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
