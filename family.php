<?php
session_start();
require_once 'config/database.php';

$page_title = 'Family Members';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $role = $_POST['role'];
                $phone = trim($_POST['phone']);
                $birth_date = $_POST['birth_date'];
                $avatar = $_POST['avatar'] ?? 'default-avatar.png';
                
                // Check if email already exists
                $check_query = "SELECT id FROM users WHERE email = ?";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->execute([$email]);
                
                if ($check_stmt->fetch()) {
                    $message = 'Email already exists.';
                } else {
                    $query = "INSERT INTO users (name, email, role, phone, birth_date, avatar, created_by) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$name, $email, $role, $phone, $birth_date, $avatar, $_SESSION['user_id']])) {
                        $message = 'Family member added successfully!';
                        $action = 'list';
                    } else {
                        $message = 'Error adding family member.';
                    }
                }
                break;
                
            case 'update':
                $user_id = $_POST['user_id'];
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $role = $_POST['role'];
                $phone = trim($_POST['phone']);
                $birth_date = $_POST['birth_date'];
                $avatar = $_POST['avatar'] ?? 'default-avatar.png';
                
                // Check if email already exists for other users
                $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->execute([$email, $user_id]);
                
                if ($check_stmt->fetch()) {
                    $message = 'Email already exists.';
                } else {
                    $query = "UPDATE users SET name = ?, email = ?, role = ?, phone = ?, birth_date = ?, avatar = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$name, $email, $role, $phone, $birth_date, $avatar, $user_id])) {
                        $message = 'Family member updated successfully!';
                        $action = 'list';
                    } else {
                        $message = 'Error updating family member.';
                    }
                }
                break;
                
            case 'delete':
                $user_id = $_POST['user_id'];
                
                // Don't allow deleting the current user
                if ($user_id == $_SESSION['user_id']) {
                    $message = 'You cannot delete your own account.';
                } else {
                    $query = "DELETE FROM users WHERE id = ?";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$user_id])) {
                        $message = 'Family member deleted successfully!';
                    } else {
                        $message = 'Error deleting family member.';
                    }
                }
                break;
        }
    }
}

// Get all family members
$family_query = "SELECT * FROM users ORDER BY name";
$family_stmt = $db->query($family_query);
$family_members = $family_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get specific family member for editing
$edit_member = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $edit_query = "SELECT * FROM users WHERE id = ?";
    $edit_stmt = $db->prepare($edit_query);
    $edit_stmt->execute([$_GET['id']]);
    $edit_member = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Family Members</h1>
        <p class="page-subtitle">Manage your family member profiles</p>
    </div>
    <?php if ($action == 'list'): ?>
        <a href="?action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Add Family Member</span>
            <span class="d-sm-none">Add</span>
        </a>
    <?php endif; ?>
</div>

<?php if ($message): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($action == 'list'): ?>
    <!-- Family Members List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-users"></i> Family Members (<?php echo count($family_members); ?>)
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($family_members)): ?>
                <p class="text-muted text-center">No family members found. Add your first family member!</p>
            <?php else: ?>
                <!-- Desktop Table -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Avatar</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Phone</th>
                                <th>Birth Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($family_members as $member): ?>
                                <tr>
                                    <td>
                                        <img src="assets/images/<?php echo $member['avatar']; ?>" 
                                             alt="Avatar" class="user-avatar" style="width: 40px; height: 40px;">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($member['name'] ?? ''); ?></strong>
                                        <?php if ($member['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge badge-success">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($member['email'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $member['role']; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($member['phone'] ?? ''); ?></td>
                                    <td>
                                        <?php echo $member['birth_date'] ? date('M j, Y', strtotime($member['birth_date'])) : 'N/A'; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="?action=edit&id=<?php echo $member['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($member['id'] != $_SESSION['user_id']): ?>
                                                <form method="POST" style="display: inline;" class="delete-btn">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Mobile Cards -->
                <div class="d-md-none">
                    <?php foreach ($family_members as $member): ?>
                        <div class="mobile-table-card mb-3">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <img src="assets/images/<?php echo $member['avatar']; ?>" 
                                             alt="Avatar" class="user-avatar me-2" style="width: 40px; height: 40px;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($member['name'] ?? ''); ?></strong>
                                            <?php if ($member['id'] == $_SESSION['user_id']): ?>
                                                <span class="badge badge-success">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="btn-group" role="group">
                                        <a href="?action=edit&id=<?php echo $member['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($member['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" style="display: inline;" class="delete-btn">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mobile-table-item">
                                    <span class="mobile-table-label">Email:</span>
                                    <span class="mobile-table-value"><?php echo htmlspecialchars($member['email'] ?? ''); ?></span>
                                </div>
                                <div class="mobile-table-item">
                                    <span class="mobile-table-label">Role:</span>
                                    <span class="mobile-table-value"><?php echo $member['role']; ?></span>
                                </div>
                                <div class="mobile-table-item">
                                    <span class="mobile-table-label">Phone:</span>
                                    <span class="mobile-table-value"><?php echo htmlspecialchars($member['phone'] ?? ''); ?></span>
                                </div>
                                <div class="mobile-table-item">
                                    <span class="mobile-table-label">Birth Date:</span>
                                    <span class="mobile-table-value">
                                        <?php echo $member['birth_date'] ? date('M j, Y', strtotime($member['birth_date'])) : 'N/A'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($action == 'add' || $action == 'edit'): ?>
    <!-- Add/Edit Family Member Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-<?php echo $action == 'add' ? 'plus' : 'edit'; ?>"></i>
                <?php echo $action == 'add' ? 'Add Family Member' : 'Edit Family Member'; ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="user_id" value="<?php echo $edit_member['id']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="name" class="form-label">
                                <i class="fas fa-user"></i> Name *
                            </label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo $edit_member ? htmlspecialchars($edit_member['name'] ?? '') : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Email *
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo $edit_member ? htmlspecialchars($edit_member['email'] ?? '') : ''; ?>" 
                                   required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="role" class="form-label">
                                <i class="fas fa-user-tag"></i> Role *
                            </label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="Parent" <?php echo ($edit_member && $edit_member['role'] == 'Parent') ? 'selected' : ''; ?>>Parent</option>
                                <option value="Child" <?php echo ($edit_member && $edit_member['role'] == 'Child') ? 'selected' : ''; ?>>Child</option>
                                <option value="Spouse" <?php echo ($edit_member && $edit_member['role'] == 'Spouse') ? 'selected' : ''; ?>>Spouse</option>
                                <option value="Sibling" <?php echo ($edit_member && $edit_member['role'] == 'Sibling') ? 'selected' : ''; ?>>Sibling</option>
                                <option value="Other" <?php echo ($edit_member && $edit_member['role'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone"></i> Phone
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo $edit_member ? htmlspecialchars($edit_member['phone'] ?? '') : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="birth_date" class="form-label">
                                <i class="fas fa-birthday-cake"></i> Birth Date
                            </label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                   value="<?php echo $edit_member ? $edit_member['birth_date'] : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="avatar" class="form-label">
                                <i class="fas fa-image"></i> Avatar
                            </label>
                            <select class="form-control" id="avatar" name="avatar">
                                <option value="default-avatar.png" <?php echo ($edit_member && $edit_member['avatar'] == 'default-avatar.png') ? 'selected' : ''; ?>>Default Avatar</option>
                                <option value="avatar1.png" <?php echo ($edit_member && $edit_member['avatar'] == 'avatar1.png') ? 'selected' : ''; ?>>Avatar 1</option>
                                <option value="avatar2.png" <?php echo ($edit_member && $edit_member['avatar'] == 'avatar2.png') ? 'selected' : ''; ?>>Avatar 2</option>
                                <option value="avatar3.png" <?php echo ($edit_member && $edit_member['avatar'] == 'avatar3.png') ? 'selected' : ''; ?>>Avatar 3</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <a href="family.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> <span class="d-none d-sm-inline">Back to Family</span>
                        <span class="d-sm-none">Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 
                        <?php echo $action == 'add' ? 'Add Family Member' : 'Update Family Member'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
