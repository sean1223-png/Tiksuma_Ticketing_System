function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.getElementById('mainContent');
  
  sidebar.classList.toggle('closed');

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
  const mainContent = document.getElementById('mainContent');
  if (!sidebar.classList.contains('closed')) {
    mainContent.style.marginLeft = "240px";
    mainContent.style.width = "calc(100% - 240px)";
  }
};

function performSearch() {
  const query = document.getElementById('searchInput').value.trim();
  if (query !== '') {
    alert("Searching for: " + query); // Replace with search logic
  }
}
