<?php
session_start();
require_once 'config/database.php';

$page_title = 'Settings';

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_settings':
                $theme = $_POST['theme'];
                $language = $_POST['language'];
                $timezone = $_POST['timezone'];
                $currency = $_POST['currency'];
                
                // In a real application, you would save these to a settings table
                $message = 'Settings updated successfully!';
                break;
        }
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Settings</h1>
    <p class="page-subtitle">Configure your application preferences</p>
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
    <!-- General Settings -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cog"></i> General Settings
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="theme" class="form-label">
                                    <i class="fas fa-palette"></i> Theme
                                </label>
                                <select class="form-control" id="theme" name="theme">
                                    <option value="light">Light</option>
                                    <option value="dark">Dark</option>
                                    <option value="auto">Auto</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="language" class="form-label">
                                    <i class="fas fa-language"></i> Language
                                </label>
                                <select class="form-control" id="language" name="language">
                                    <option value="en">English</option>
                                    <option value="es">Spanish</option>
                                    <option value="fr">French</option>
                                    <option value="de">German</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="timezone" class="form-label">
                                    <i class="fas fa-clock"></i> Timezone
                                </label>
                                <select class="form-control" id="timezone" name="timezone">
                                    <option value="UTC">UTC</option>
                                    <option value="America/New_York">Eastern Time</option>
                                    <option value="America/Chicago">Central Time</option>
                                    <option value="America/Denver">Mountain Time</option>
                                    <option value="America/Los_Angeles">Pacific Time</option>
                                    <option value="Europe/London">London</option>
                                    <option value="Europe/Paris">Paris</option>
                                    <option value="Asia/Tokyo">Tokyo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label for="currency" class="form-label">
                                    <i class="fas fa-dollar-sign"></i> Currency
                                </label>
                                <select class="form-control" id="currency" name="currency">
                                    <option value="USD">USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                    <option value="GBP">GBP (£)</option>
                                    <option value="JPY">JPY (¥)</option>
                                    <option value="CAD">CAD (C$)</option>
                                    <option value="KSH">KSH (KSh)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-tools"></i> Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="profile.php" class="btn btn-outline-primary">
                        <i class="fas fa-user"></i> Edit Profile
                    </a>
                    <a href="history.php" class="btn btn-outline-info">
                        <i class="fas fa-history"></i> View Activity History
                    </a>
                    <button class="btn btn-outline-warning" onclick="exportData()">
                        <i class="fas fa-download"></i> Export Data
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteAccount()">
                        <i class="fas fa-trash"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle"></i> System Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 mb-2">
                        <small class="text-muted">PHP Version</small>
                        <div class="fw-bold"><?php echo PHP_VERSION; ?></div>
                    </div>
                    <div class="col-12 mb-2">
                        <small class="text-muted">Server Time</small>
                        <div class="fw-bold"><?php echo date('Y-m-d H:i:s'); ?></div>
                    </div>
                    <div class="col-12">
                        <small class="text-muted">Application Version</small>
                        <div class="fw-bold">1.0.0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportData() {
    if (confirm('This will download all your data as a JSON file. Continue?')) {
        alert('Export functionality would be implemented here.');
    }
}

function deleteAccount() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
        if (confirm('This is your final warning. All your data will be permanently deleted.')) {
            alert('Account deletion would be implemented here.');
        }
    }
}

// Apply theme changes
document.getElementById('theme').addEventListener('change', function() {
    const theme = this.value;
    document.body.className = theme === 'dark' ? 'dark-theme' : '';
    localStorage.setItem('theme', theme);
});

// Load saved theme
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.getElementById('theme').value = savedTheme;
        if (savedTheme === 'dark') {
            document.body.className = 'dark-theme';
        }
    }
});
</script>

<style>
.dark-theme {
    background-color: #1a1a1a;
    color: #ffffff;
}

.dark-theme .card {
    background-color: #2d2d2d;
    border-color: #404040;
}

.dark-theme .card-header {
    background-color: #404040;
    border-color: #404040;
}

.dark-theme .form-control {
    background-color: #404040;
    border-color: #606060;
    color: #ffffff;
}
</style>

<?php include 'includes/footer.php'; ?>
