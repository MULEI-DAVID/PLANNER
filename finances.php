<?php
session_start();
require_once 'config/database.php';
require_once 'includes/currency_helper.php';

$page_title = 'Finances';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $type = $_POST['type'];
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $amount = floatval($_POST['amount']);
                $category = $_POST['category'];
                $date = $_POST['date'];
                $assignee_id = $_POST['assignee_id'] ?: null;
                
                $query = "INSERT INTO finances (type, title, description, amount, category, date, assignee_id, created_by) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$type, $title, $description, $amount, $category, $date, $assignee_id, $_SESSION['user_id']])) {
                    $message = 'Financial record created successfully!';
                    $action = 'list';
                } else {
                    $message = 'Error creating financial record.';
                }
                break;
                
            case 'update':
                $finance_id = $_POST['finance_id'];
                $type = $_POST['type'];
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $amount = floatval($_POST['amount']);
                $category = $_POST['category'];
                $date = $_POST['date'];
                $assignee_id = $_POST['assignee_id'] ?: null;
                
                $query = "UPDATE finances SET type = ?, title = ?, description = ?, amount = ?, category = ?, date = ?, assignee_id = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$type, $title, $description, $amount, $category, $date, $assignee_id, $finance_id])) {
                    $message = 'Financial record updated successfully!';
                    $action = 'list';
                } else {
                    $message = 'Error updating financial record.';
                }
                break;
                
            case 'delete':
                $finance_id = $_POST['finance_id'];
                
                $query = "DELETE FROM finances WHERE id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$finance_id])) {
                    $message = 'Financial record deleted successfully!';
                } else {
                    $message = 'Error deleting financial record.';
                }
                break;
        }
    }
}

// Get family members for assignee dropdown
$users_query = "SELECT id, name, role FROM users ORDER BY name";
$users_stmt = $db->query($users_query);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get financial records for listing - Show all family finances
$finances_query = "SELECT f.*, u.name as assignee_name, c.name as created_by_name 
                   FROM finances f 
                   LEFT JOIN users u ON f.assignee_id = u.id 
                   LEFT JOIN users c ON f.created_by = c.id 
                   ORDER BY f.date DESC";
$finances_stmt = $db->query($finances_query);
$finances = $finances_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_income = 0;
$total_expense = 0;
foreach ($finances as $finance) {
    if ($finance['type'] == 'Income') {
        $total_income += (float)($finance['amount'] ?? 0);
    } else {
        $total_expense += (float)($finance['amount'] ?? 0);
    }
}
$balance = $total_income - $total_expense;

// Get specific finance record for editing
$edit_finance = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $edit_query = "SELECT * FROM finances WHERE id = ?";
    $edit_stmt = $db->prepare($edit_query);
    $edit_stmt->execute([$_GET['id']]);
    $edit_finance = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Financial Management</h1>
        <p class="page-subtitle">Track your family's income and expenses</p>
    </div>
    <?php if ($action == 'list'): ?>
        <a href="?action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Add Financial Record</span>
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
    <!-- Financial Summary -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 col-sm-6 mb-3">
            <div class="stats-card green">
                <div class="stats-number"><?php echo formatCurrency($total_income, 'KSH'); ?></div>
                <div class="stats-label">Total Income</div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 mb-3">
            <div class="stats-card orange">
                <div class="stats-number"><?php echo formatCurrency($total_expense, 'KSH'); ?></div>
                <div class="stats-label">Total Expenses</div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 mb-3">
            <div class="stats-card <?php echo $balance >= 0 ? 'blue' : 'pink'; ?>">
                <div class="stats-number"><?php echo formatCurrency($balance, 'KSH'); ?></div>
                <div class="stats-label">Net Balance</div>
            </div>
        </div>
    </div>

    <!-- Financial Records List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-dollar-sign"></i> Financial Records
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($finances)): ?>
                <p class="text-muted text-center">No financial records found. Add your first record!</p>
            <?php else: ?>
                <!-- Desktop Table -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Assignee</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($finances as $finance): ?>
                                <tr>
                                    <td>
                                        <span class="badge <?php echo $finance['type'] == 'Income' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $finance['type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($finance['title']); ?></strong>
                                        <?php if ($finance['description']): ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($finance['description'], 0, 50)); ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $finance['category']; ?></span>
                                    </td>
                                    <td>
                                        <span class="<?php echo $finance['type'] == 'Income' ? 'text-success' : 'text-danger'; ?>">
                                            <strong>
                                                <?php echo $finance['type'] == 'Income' ? '+' : '-'; ?>
                                                <?php echo formatCurrency($finance['amount'], 'KSH'); ?>
                                            </strong>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($finance['date'])); ?>
                                    </td>
                                    <td>
                                        <?php echo $finance['assignee_name'] ? htmlspecialchars($finance['assignee_name']) : 'Unassigned'; ?>
                                    </td>
                                    <td>
                                        <small class="text-info"><?php echo htmlspecialchars($finance['created_by_name']); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="?action=edit&id=<?php echo $finance['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               data-bs-toggle="tooltip" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" class="delete-btn">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="finance_id" value="<?php echo $finance['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="tooltip" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Mobile Cards -->
                <div class="d-md-none">
                    <?php foreach ($finances as $finance): ?>
                        <div class="mobile-table-card mb-3">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge <?php echo $finance['type'] == 'Income' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $finance['type']; ?>
                                    </span>
                                    <div class="btn-group" role="group">
                                        <a href="?action=edit&id=<?php echo $finance['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" style="display: inline;" class="delete-btn">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="finance_id" value="<?php echo $finance['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mobile-table-item">
                                    <span class="mobile-table-label">Title:</span>
                                    <span class="mobile-table-value"><?php echo htmlspecialchars($finance['title']); ?></span>
                                </div>
                                <div class="mobile-table-item">
                                    <span class="mobile-table-label">Category:</span>
                                    <span class="mobile-table-value"><?php echo $finance['category']; ?></span>
                                </div>
                                <div class="mobile-table-item">
                                    <span class="mobile-table-label">Amount:</span>
                                    <span class="mobile-table-value <?php echo $finance['type'] == 'Income' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $finance['type'] == 'Income' ? '+' : '-'; ?>
                                        <?php echo formatCurrency($finance['amount'], 'KSH'); ?>
                                    </span>
                                </div>
                                <div class="mobile-table-item">
                                    <span class="mobile-table-label">Date:</span>
                                    <span class="mobile-table-value"><?php echo date('M j, Y', strtotime($finance['date'])); ?></span>
                                </div>
                                <div class="mobile-table-item">
                                    <span class="mobile-table-label">Assignee:</span>
                                    <span class="mobile-table-value"><?php echo $finance['assignee_name'] ? htmlspecialchars($finance['assignee_name']) : 'Unassigned'; ?></span>
                                </div>
                                <div class="mobile-table-item">
                                    <span class="mobile-table-label">Created by:</span>
                                    <span class="mobile-table-value text-info"><?php echo htmlspecialchars($finance['created_by_name']); ?></span>
                                </div>
                                <?php if ($finance['description']): ?>
                                <div class="mobile-table-item">
                                    <span class="mobile-table-label">Description:</span>
                                    <span class="mobile-table-value"><?php echo htmlspecialchars(substr($finance['description'], 0, 50)) . (strlen($finance['description']) > 50 ? '...' : ''); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($action == 'add' || $action == 'edit'): ?>
    <!-- Add/Edit Financial Record Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-<?php echo $action == 'add' ? 'plus' : 'edit'; ?>"></i>
                <?php echo $action == 'add' ? 'Add Financial Record' : 'Edit Financial Record'; ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="finance_id" value="<?php echo $edit_finance['id']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="type" class="form-label">
                                <i class="fas fa-exchange-alt"></i> Type *
                            </label>
                            <select class="form-control" id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="Income" <?php echo ($edit_finance && $edit_finance['type'] == 'Income') ? 'selected' : ''; ?>>Income</option>
                                <option value="Expense" <?php echo ($edit_finance && $edit_finance['type'] == 'Expense') ? 'selected' : ''; ?>>Expense</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="amount" class="form-label">
                                <i class="fas fa-dollar-sign"></i> Amount *
                            </label>
                            <input type="number" class="form-control" id="amount" name="amount" 
                                   step="0.01" min="0" 
                                   value="<?php echo $edit_finance ? $edit_finance['amount'] : ''; ?>" 
                                   required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <div class="form-group">
                            <label for="title" class="form-label">
                                <i class="fas fa-tag"></i> Title *
                            </label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo $edit_finance ? htmlspecialchars($edit_finance['title']) : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-group">
                            <label for="category" class="form-label">
                                <i class="fas fa-folder"></i> Category *
                            </label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <!-- Income categories -->
                                <optgroup label="Income Categories" id="income-categories" style="display: none;">
                                    <option value="Salary">Salary</option>
                                    <option value="Side Job">Side Job</option>
                                    <option value="Allowance">Allowance</option>
                                    <option value="Misc">Misc</option>
                                </optgroup>
                                <!-- Expense categories -->
                                <optgroup label="Expense Categories" id="expense-categories" style="display: none;">
                                    <option value="Groceries">Groceries</option>
                                    <option value="Utilities">Utilities</option>
                                    <option value="Education">Education</option>
                                    <option value="Entertainment">Entertainment</option>
                                    <option value="Savings">Savings</option>
                                    <option value="Misc">Misc</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="description" class="form-label">
                        <i class="fas fa-align-left"></i> Description
                    </label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo $edit_finance ? htmlspecialchars($edit_finance['description']) : ''; ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="date" class="form-label">
                                <i class="fas fa-calendar"></i> Date *
                            </label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="<?php echo $edit_finance ? $edit_finance['date'] : date('Y-m-d'); ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="assignee_id" class="form-label">
                                <i class="fas fa-user"></i> Assignee
                            </label>
                            <select class="form-control" id="assignee_id" name="assignee_id">
                                <option value="">Unassigned</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php echo ($edit_finance && $edit_finance['assignee_id'] == $user['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']); ?> (<?php echo $user['role']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <a href="finances.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> <span class="d-none d-sm-inline">Back to Finances</span>
                        <span class="d-sm-none">Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 
                        <?php echo $action == 'add' ? 'Create Record' : 'Update Record'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
// Show/hide category options based on type selection
document.getElementById('type').addEventListener('change', function() {
    const type = this.value;
    const incomeCategories = document.getElementById('income-categories');
    const expenseCategories = document.getElementById('expense-categories');
    
    if (type === 'Income') {
        incomeCategories.style.display = 'block';
        expenseCategories.style.display = 'none';
    } else if (type === 'Expense') {
        incomeCategories.style.display = 'none';
        expenseCategories.style.display = 'block';
    } else {
        incomeCategories.style.display = 'none';
        expenseCategories.style.display = 'none';
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    if (typeSelect.value) {
        typeSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<?php include 'includes/footer.php'; ?>

