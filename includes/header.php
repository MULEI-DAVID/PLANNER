<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>C-Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <button class="mobile-menu-toggle d-lg-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <a class="navbar-brand" href="dashboard.php">
                    <i class="fas fa-home"></i> C-Planner
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item d-none d-lg-block">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item d-none d-lg-block">
                            <a class="nav-link" href="tasks.php">
                                <i class="fas fa-tasks"></i> Tasks
                            </a>
                        </li>
                        <li class="nav-item d-none d-lg-block">
                            <a class="nav-link" href="events.php">
                                <i class="fas fa-calendar"></i> Events
                            </a>
                        </li>
                        <li class="nav-item d-none d-lg-block">
                            <a class="nav-link" href="finances.php">
                                <i class="fas fa-dollar-sign"></i> Finances
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                               data-bs-toggle="dropdown">
                                <img src="assets/images/<?php echo $_SESSION['user_avatar'] ?? 'default-avatar.png'; ?>" 
                                     alt="Avatar" class="user-avatar me-2">
                                <span class="d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user"></i> Profile
                                </a></li>
                                <li><a class="dropdown-item" href="settings.php">
                                    <i class="fas fa-cog"></i> Settings
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="auth/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Mobile Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Mobile Sidebar - Outside Bootstrap Grid -->
        <div class="sidebar" id="mobileSidebar">
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="tasks.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tasks"></i> Tasks
                    </a>
                </li>
                <li>
                    <a href="events.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar"></i> Events
                    </a>
                </li>
                <li>
                    <a href="finances.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'finances.php' ? 'active' : ''; ?>">
                        <i class="fas fa-dollar-sign"></i> Finances
                    </a>
                </li>
                <li>
                    <a href="budget.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'budget.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-pie"></i> Budget
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li>
                    <a href="family.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'family.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Family Members
                    </a>
                </li>
                <li>
                    <a href="history.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'history.php' ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i> History
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="container-fluid">
            <div class="row">
                <!-- Desktop Sidebar -->
                <div class="col-lg-2 col-md-3 d-none d-md-block">
                    <div class="sidebar" id="desktopSidebar">
                        <ul class="sidebar-menu">
                            <li>
                                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="tasks.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-tasks"></i> Tasks
                                </a>
                            </li>
                            <li>
                                <a href="events.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-calendar"></i> Events
                                </a>
                            </li>
                            <li>
                                <a href="finances.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'finances.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-dollar-sign"></i> Finances
                                </a>
                            </li>
                            <li>
                                <a href="budget.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'budget.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-chart-pie"></i> Budget
                                </a>
                            </li>
                            <li>
                                <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-chart-bar"></i> Reports
                                </a>
                            </li>
                            <li>
                                <a href="family.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'family.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-users"></i> Family Members
                                </a>
                            </li>
                            <li>
                                <a href="history.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'history.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-history"></i> History
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div class="col-lg-10 col-md-9 col-12">
                    <div class="main-content">

