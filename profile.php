<?php
session_start();
require_once 'config/database.php';

$page_title = 'Profile';

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Get current user information
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']) ?: null;
                $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
                $avatar = $_POST['avatar'] ?? 'default-avatar.png';
                
                // Validate phone number format if provided
                if ($phone && !preg_match('/^[\+]?[0-9\s\-\(\)]{7,20}$/', $phone)) {
                    $error = 'Please enter a valid phone number.';
                } else {
                    // Check if email already exists for other users
                    $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
                    $check_stmt = $db->prepare($check_query);
                    $check_stmt->execute([$email, $_SESSION['user_id']]);
                    
                    if ($check_stmt->fetch()) {
                        $error = 'Email already exists.';
                    } else {
                        $query = "UPDATE users SET name = ?, email = ?, phone = ?, birth_date = ?, avatar = ? WHERE id = ?";
                        $stmt = $db->prepare($query);
                        
                        if ($stmt->execute([$name, $email, $phone, $birth_date, $avatar, $_SESSION['user_id']])) {
                            $message = 'Profile updated successfully!';
                            // Update session data
                            $_SESSION['user_name'] = $name;
                            $_SESSION['user_email'] = $email;
                            $_SESSION['user_avatar'] = $avatar;
                            // Refresh user data
                            $user_stmt->execute([$_SESSION['user_id']]);
                            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
                        } else {
                            $error = 'Error updating profile.';
                        }
                    }
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Verify current password
                if (!password_verify($current_password, $user['password'])) {
                    $error = 'Current password is incorrect.';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match.';
                } elseif (strlen($new_password) < 6) {
                    $error = 'New password must be at least 6 characters long.';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $query = "UPDATE users SET password = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                        $message = 'Password changed successfully!';
                    } else {
                        $error = 'Error changing password.';
                    }
                }
                break;
        }
    }
}

// Get user statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM tasks WHERE created_by = ?) as total_tasks,
    (SELECT COUNT(*) FROM events WHERE created_by = ?) as total_events,
    (SELECT COUNT(*) FROM finances WHERE created_by = ?) as total_finances,
    (SELECT COUNT(*) FROM budgets WHERE created_by = ?) as total_budgets";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent activities
$recent_query = "SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$recent_stmt = $db->prepare($recent_query);
$recent_stmt->execute([$_SESSION['user_id']]);
$recent_activities = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Profile</h1>
    <p class="page-subtitle">Manage your personal information and account settings</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Profile Information -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user"></i> Profile Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user"></i> Full Name *
                                </label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email Address *
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone"></i> Phone Number
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="birth_date" class="form-label">
                                    <i class="fas fa-birthday-cake"></i> Birth Date
                                </label>
                                <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                       value="<?php echo $user['birth_date'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="role" class="form-label">
                                    <i class="fas fa-user-tag"></i> Role
                                </label>
                                <input type="text" class="form-control" id="role" 
                                       value="<?php echo htmlspecialchars($user['role']); ?>" readonly>
                                <small class="text-muted">Role cannot be changed</small>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="avatar" class="form-label">
                                    <i class="fas fa-image"></i> Avatar
                                </label>
                                <select class="form-control" id="avatar" name="avatar">
                                    <option value="default-avatar.png" <?php echo ($user['avatar'] == 'default-avatar.png') ? 'selected' : ''; ?>>Default Avatar</option>
                                    <option value="avatar1.png" <?php echo ($user['avatar'] == 'avatar1.png') ? 'selected' : ''; ?>>Avatar 1</option>
                                    <option value="avatar2.png" <?php echo ($user['avatar'] == 'avatar2.png') ? 'selected' : ''; ?>>Avatar 2</option>
                                    <option value="avatar3.png" <?php echo ($user['avatar'] == 'avatar3.png') ? 'selected' : ''; ?>>Avatar 3</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Change Password -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-lock"></i> Change Password
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="form-group">
                                <label for="current_password" class="form-label">
                                    <i class="fas fa-key"></i> Current Password *
                                </label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="new_password" class="form-label">
                                    <i class="fas fa-lock"></i> New Password *
                                </label>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       minlength="6" required>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock"></i> Confirm New Password *
                                </label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Profile Sidebar -->
    <div class="col-lg-4 mb-4">
        <!-- Profile Card -->
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <img src="assets/images/<?php echo $user['avatar'] ?? 'default-avatar.png'; ?>" 
                         alt="Profile Avatar" class="rounded-circle" id="avatarPreview" style="width: 120px; height: 120px; object-fit: cover;">
                </div>
                <h5 class="card-title"><?php echo htmlspecialchars($user['name']); ?></h5>
                <p class="text-muted"><?php echo htmlspecialchars($user['role']); ?></p>
                <div class="d-grid">
                    <button class="btn btn-outline-primary btn-sm" onclick="document.getElementById('avatar').focus();">
                        <i class="fas fa-camera"></i> Change Avatar
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Account Statistics -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-bar"></i> Account Statistics
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="stats-number text-primary"><?php echo $stats['total_tasks']; ?></div>
                        <div class="stats-label">Tasks</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stats-number text-success"><?php echo $stats['total_events']; ?></div>
                        <div class="stats-label">Events</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stats-number text-warning"><?php echo $stats['total_finances']; ?></div>
                        <div class="stats-label">Finances</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stats-number text-info"><?php echo $stats['total_budgets']; ?></div>
                        <div class="stats-label">Budgets</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activities -->
        <?php if (!empty($recent_activities)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-clock"></i> Recent Activities
                </h6>
            </div>
            <div class="card-body">
                <?php foreach ($recent_activities as $activity): ?>
                    <div class="d-flex align-items-start mb-3">
                        <div class="flex-shrink-0">
                            <?php
                            $icon = '';
                            $color = '';
                            switch ($activity['activity_type']) {
                                case 'task_created':
                                case 'task_completed':
                                case 'task_updated':
                                    $icon = 'fas fa-tasks';
                                    $color = 'text-primary';
                                    break;
                                case 'event_created':
                                case 'event_updated':
                                    $icon = 'fas fa-calendar';
                                    $color = 'text-success';
                                    break;
                                case 'finance_added':
                                case 'finance_updated':
                                    $icon = 'fas fa-dollar-sign';
                                    $color = 'text-warning';
                                    break;
                                case 'budget_created':
                                case 'budget_updated':
                                    $icon = 'fas fa-chart-pie';
                                    $color = 'text-info';
                                    break;
                                case 'profile_updated':
                                    $icon = 'fas fa-user';
                                    $color = 'text-danger';
                                    break;
                                case 'login':
                                case 'logout':
                                    $icon = 'fas fa-sign-in-alt';
                                    $color = 'text-secondary';
                                    break;
                                default:
                                    $icon = 'fas fa-info-circle';
                                    $color = 'text-secondary';
                            }
                            ?>
                            <i class="<?php echo $icon; ?> <?php echo $color; ?>"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-bold small"><?php echo htmlspecialchars(str_replace('_', ' ', $activity['activity_type'])); ?></div>
                            <div class="text-muted small">
                                <?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="text-center">
                    <a href="history.php" class="btn btn-outline-primary btn-sm">
                        View All Activities
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Account Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle"></i> Account Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 mb-2">
                        <small class="text-muted">Member Since</small>
                        <div class="fw-bold">
                            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                        </div>
                    </div>
                    <div class="col-12 mb-2">
                        <small class="text-muted">Last Updated</small>
                        <div class="fw-bold">
                            <?php echo date('M j, Y g:i A', strtotime($user['updated_at'])); ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <small class="text-muted">Account Status</small>
                        <div class="fw-bold text-success">
                            <i class="fas fa-check-circle"></i> Active
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('new_password').addEventListener('input', function() {
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword.value) {
        if (this.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
});

// Avatar preview functionality
document.getElementById('avatar').addEventListener('change', function() {
    const selectedAvatar = this.value;
    const avatarPreview = document.getElementById('avatarPreview');
    avatarPreview.src = 'assets/images/' + selectedAvatar;
    
    // Update header avatar in real-time
    const headerAvatar = document.querySelector('.user-avatar');
    if (headerAvatar) {
        headerAvatar.src = 'assets/images/' + selectedAvatar;
    }
    
    // Show a small notification that avatar preview is updated
    const changeButton = document.querySelector('button[onclick*="avatar"]');
    if (changeButton) {
        const originalText = changeButton.innerHTML;
        changeButton.innerHTML = '<i class="fas fa-check"></i> Avatar Updated';
        changeButton.classList.remove('btn-outline-primary');
        changeButton.classList.add('btn-success');
        
        setTimeout(function() {
            changeButton.innerHTML = originalText;
            changeButton.classList.remove('btn-success');
            changeButton.classList.add('btn-outline-primary');
        }, 2000);
    }
});

// Initialize avatar preview on page load
document.addEventListener('DOMContentLoaded', function() {
    const avatarSelect = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    if (avatarSelect && avatarPreview) {
        avatarPreview.src = 'assets/images/' + (avatarSelect.value || 'default-avatar.png');
    }
    
    // Handle profile update success message
    const successMessage = document.querySelector('.alert-success');
    if (successMessage) {
        // Refresh the page after 2 seconds to ensure header is updated
        setTimeout(function() {
            window.location.reload();
        }, 2000);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
