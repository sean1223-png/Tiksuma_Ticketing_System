<?php
// Basic page layout template
// Include this at the top of pages that need a consistent structure

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// Get user role/location for sidebar customization
$user_role = $_SESSION['role'] ?? '';
$user_location = $_SESSION['location'] ?? '';
$is_it_staff = ($user_role === 'it_staff' || $user_location === 'it-staff.php');
$is_admin = ($user_role === 'Admin' || $user_location === 'admin');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'TIKSUMA Ticketing System'; ?></title>
    <link rel="icon" href="./png/logo-favicon.ico" type="image/x-icon">

    <!-- Include common styles -->
    <link rel="stylesheet" href="./src/style.css">
    <?php if ($is_it_staff): ?>
        <link rel="stylesheet" href="./src/it-staff.css">
        <link rel="stylesheet" href="./src/collapsible-sidebar.css">
    <?php endif; ?>

    <!-- Include page-specific styles if set -->
    <?php if (isset($page_styles)): ?>
        <?php foreach ($page_styles as $style): ?>
            <link rel="stylesheet" href="<?php echo $style; ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="layout-container">
        <?php if ($is_it_staff): ?>
            <!-- IT Staff Sidebar -->
            <nav class="sidebar">
                <div class="sidebar-header">
                    <img src="./png/logo.png" alt="TIKSUMA Logo" class="sidebar-logo">
                    <h3>TIKSUMA</h3>
                </div>

                <ul class="sidebar-menu">
                    <li><a href="it-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="it-tickets.php"><i class="fas fa-ticket-alt"></i> All Tickets</a></li>
                    <li><a href="it-pending.php"><i class="fas fa-clock"></i> Pending</a></li>
                    <li><a href="it-assigned-tickets.php"><i class="fas fa-user-check"></i> Assigned</a></li>
                    <li><a href="it-in_progress.php"><i class="fas fa-spinner"></i> In Progress</a></li>
                    <li><a href="it-resolved.php"><i class="fas fa-check-circle"></i> Resolved</a></li>
                    <li><a href="it-archive.php"><i class="fas fa-archive"></i> Archive</a></li>
                    <li><a href="it-settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="view-it-profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        <?php elseif ($is_admin): ?>
            <!-- Admin Sidebar -->
            <nav class="sidebar">
                <div class="sidebar-header">
                    <img src="./png/logo.png" alt="TIKSUMA Logo" class="sidebar-logo">
                    <h3>TIKSUMA</h3>
                </div>

                <ul class="sidebar-menu">
                    <li><a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="admin-users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="admin-tickets.php"><i class="fas fa-ticket-alt"></i> Tickets</a></li>
                    <li><a href="admin-reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li><a href="view-admin-profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        <?php endif; ?>

        <!-- Main Content Area -->
        <main class="main-content <?php echo $is_it_staff || $is_admin ? 'with-sidebar' : ''; ?>">
            <!-- Header -->
            <header class="page-header">
                <div class="header-left">
                    <?php if ($is_it_staff || $is_admin): ?>
                        <button class="sidebar-toggle" id="sidebarToggle">
                            <i class="fas fa-bars"></i>
                        </button>
                    <?php endif; ?>
                    <h1><?php echo $page_title ?? 'Page Title'; ?></h1>
                </div>

                <div class="header-right">
                    <span class="user-info">
                        Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content">
                <?php
                // Include the specific page content here
                if (isset($content_file)) {
                    include $content_file;
                } else {
                    // Default content or placeholder
                    echo '<div class="content-placeholder">';
                    echo '<h2>Page Content</h2>';
                    echo '<p>This is where your page content goes.</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </main>
    </div>

    <!-- Include common scripts -->
    <script src="./js/tiksuma-template.js"></script>
    <?php if ($is_it_staff): ?>
        <script src="./js/collapsible-sidebar.js"></script>
    <?php endif; ?>

    <!-- Include page-specific scripts if set -->
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Inline scripts if set -->
    <?php if (isset($inline_scripts)): ?>
        <script>
            <?php echo $inline_scripts; ?>
        </script>
    <?php endif; ?>
</body>
</html>
