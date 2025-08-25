<?php
session_start();
require_once 'config/database.php';
require_once 'includes/currency_helper.php';

$page_title = 'Budget Management';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $category = $_POST['category'];
                $amount = floatval($_POST['amount']);
                $month = intval($_POST['month']);
                $year = intval($_POST['year']);
                
                // Check if budget already exists for this category and month
                $check_query = "SELECT id FROM budgets WHERE category = ? AND month = ? AND year = ? AND created_by = ?";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->execute([$category, $month, $year, $_SESSION['user_id']]);
                
                if ($check_stmt->fetch()) {
                    $message = 'Budget already exists for this category and month.';
                } else {
                    $query = "INSERT INTO budgets (category, amount, month, year, created_by) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$category, $amount, $month, $year, $_SESSION['user_id']])) {
                        $message = 'Budget created successfully!';
                        $action = 'list';
                    } else {
                        $message = 'Error creating budget.';
                    }
                }
                break;
                
            case 'update':
                $budget_id = $_POST['budget_id'];
                $amount = floatval($_POST['amount']);
                
                $query = "UPDATE budgets SET amount = ? WHERE id = ? AND created_by = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$amount, $budget_id, $_SESSION['user_id']])) {
                    $message = 'Budget updated successfully!';
                    $action = 'list';
                } else {
                    $message = 'Error updating budget.';
                }
                break;
                
            case 'delete':
                $budget_id = $_POST['budget_id'];
                
                $query = "DELETE FROM budgets WHERE id = ? AND created_by = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$budget_id, $_SESSION['user_id']])) {
                    $message = 'Budget deleted successfully!';
                } else {
                    $message = 'Error deleting budget.';
                }
                break;
        }
    }
}

// Get current month and year
$current_month = date('n');
$current_year = date('Y');

// Get budgets for current month
$budgets_query = "SELECT * FROM budgets WHERE month = ? AND year = ? AND created_by = ? ORDER BY category";
$budgets_stmt = $db->prepare($budgets_query);
$budgets_stmt->execute([$current_month, $current_year, $_SESSION['user_id']]);
$budgets = $budgets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get actual expenses for current month
$expenses_query = "SELECT category, SUM(amount) as total_spent 
                   FROM finances 
                   WHERE type = 'Expense' 
                   AND MONTH(date) = ? 
                   AND YEAR(date) = ? 
                   AND created_by = ?
                   GROUP BY category";
$expenses_stmt = $db->prepare($expenses_query);
$expenses_stmt->execute([$current_month, $current_year, $_SESSION['user_id']]);
$expenses = $expenses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Create expenses lookup array
$expenses_lookup = [];
foreach ($expenses as $expense) {
    $expenses_lookup[$expense['category']] = $expense['total_spent'];
}

// Calculate budget vs actual
$budget_data = [];
foreach ($budgets as $budget) {
    $spent = $expenses_lookup[$budget['category']] ?? 0;
    $remaining = $budget['amount'] - $spent;
    $percentage = $budget['amount'] > 0 ? ($spent / $budget['amount']) * 100 : 0;
    
    $budget_data[] = [
        'budget' => $budget,
        'spent' => $spent,
        'remaining' => $remaining,
        'percentage' => $percentage
    ];
}

// Get specific budget for editing
$edit_budget = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $edit_query = "SELECT * FROM budgets WHERE id = ? AND created_by = ?";
    $edit_stmt = $db->prepare($edit_query);
    $edit_stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $edit_budget = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Budget Management</h1>
        <p class="page-subtitle">Track your monthly spending against budgets</p>
    </div>
    <?php if ($action == 'list'): ?>
        <a href="?action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Add Budget</span>
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
    <!-- Budget Summary -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar"></i> 
                        Budget for <?php echo date('F Y', mktime(0, 0, 0, $current_month, 1, $current_year)); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($budget_data)): ?>
                        <p class="text-muted text-center">No budgets set for this month. Add your first budget!</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($budget_data as $data): ?>
                                <div class="col-lg-4 col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo $data['budget']['category']; ?></h6>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Budget: <?php echo formatCurrency($data['budget']['amount'], 'KSH'); ?></span>
                                                <span>Spent: <?php echo formatCurrency($data['spent'], 'KSH'); ?></span>
                                            </div>
                                            <div class="progress mb-2" style="height: 8px;">
                                                <?php 
                                                $progress_class = $data['percentage'] > 100 ? 'bg-danger' : 
                                                               ($data['percentage'] > 80 ? 'bg-warning' : 'bg-success');
                                                ?>
                                                <div class="progress-bar <?php echo $progress_class; ?>" 
                                                     style="width: <?php echo min($data['percentage'], 100); ?>%"></div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <?php echo number_format($data['percentage'], 1); ?>% used
                                                </small>
                                                <span class="<?php echo $data['remaining'] < 0 ? 'text-danger' : 'text-success'; ?>">
                                                    $<?php echo number_format($data['remaining'], 2); ?> remaining
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Budget List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list"></i> Budget List
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($budgets)): ?>
                <p class="text-muted text-center">No budgets found. Add your first budget!</p>
            <?php else: ?>
                <!-- Desktop Table -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Month/Year</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($budgets as $budget): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-info"><?php echo $budget['category']; ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo formatCurrency($budget['amount'], 'KSH'); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo date('F Y', mktime(0, 0, 0, $budget['month'], 1, $budget['year'])); ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="?action=edit&id=<?php echo $budget['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" class="delete-btn">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="budget_id" value="<?php echo $budget['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
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
                    <?php foreach ($budgets as $budget): ?>
                        <div class="mobile-table-card mb-3">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge badge-info"><?php echo $budget['category']; ?></span>
                                    <div class="btn-group" role="group">
                                        <a href="?action=edit&id=<?php echo $budget['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" style="display: inline;" class="delete-btn">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="budget_id" value="<?php echo $budget['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mobile-table-item">
                                    <span class="mobile-table-label">Amount:</span>
                                    <span class="mobile-table-value"><?php echo formatCurrency($budget['amount'], 'KSH'); ?></span>
                                </div>
                                <div class="mobile-table-item">
                                    <span class="mobile-table-label">Period:</span>
                                    <span class="mobile-table-value">
                                        <?php echo date('F Y', mktime(0, 0, 0, $budget['month'], 1, $budget['year'])); ?>
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
    <!-- Add/Edit Budget Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-<?php echo $action == 'add' ? 'plus' : 'edit'; ?>"></i>
                <?php echo $action == 'add' ? 'Add Budget' : 'Edit Budget'; ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="budget_id" value="<?php echo $edit_budget['id']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="category" class="form-label">
                                <i class="fas fa-folder"></i> Category *
                            </label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Groceries" <?php echo ($edit_budget && $edit_budget['category'] == 'Groceries') ? 'selected' : ''; ?>>Groceries</option>
                                <option value="Utilities" <?php echo ($edit_budget && $edit_budget['category'] == 'Utilities') ? 'selected' : ''; ?>>Utilities</option>
                                <option value="Education" <?php echo ($edit_budget && $edit_budget['category'] == 'Education') ? 'selected' : ''; ?>>Education</option>
                                <option value="Entertainment" <?php echo ($edit_budget && $edit_budget['category'] == 'Entertainment') ? 'selected' : ''; ?>>Entertainment</option>
                                <option value="Savings" <?php echo ($edit_budget && $edit_budget['category'] == 'Savings') ? 'selected' : ''; ?>>Savings</option>
                                <option value="Misc" <?php echo ($edit_budget && $edit_budget['category'] == 'Misc') ? 'selected' : ''; ?>>Misc</option>
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
                                   value="<?php echo $edit_budget ? $edit_budget['amount'] : ''; ?>" 
                                   required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="month" class="form-label">
                                <i class="fas fa-calendar"></i> Month *
                            </label>
                            <select class="form-control" id="month" name="month" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" 
                                            <?php echo ($edit_budget && $edit_budget['month'] == $m) || (!$edit_budget && $m == $current_month) ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-group">
                            <label for="year" class="form-label">
                                <i class="fas fa-calendar"></i> Year *
                            </label>
                            <select class="form-control" id="year" name="year" required>
                                <?php for ($y = $current_year - 1; $y <= $current_year + 1; $y++): ?>
                                    <option value="<?php echo $y; ?>" 
                                            <?php echo ($edit_budget && $edit_budget['year'] == $y) || (!$edit_budget && $y == $current_year) ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <a href="budget.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> <span class="d-none d-sm-inline">Back to Budget</span>
                        <span class="d-sm-none">Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 
                        <?php echo $action == 'add' ? 'Create Budget' : 'Update Budget'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
