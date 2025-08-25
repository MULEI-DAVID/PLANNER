<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

// Database connection
require_once 'config/database.php';
require_once 'includes/currency_helper.php';

$database = new Database();
$db = $database->getConnection();

// Handle quick status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'quick_status_update') {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['new_status'];
    
    $query = "UPDATE tasks SET status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$new_status, $task_id])) {
        // Redirect to refresh the page and show updated data
        header('Location: dashboard.php?status_updated=1');
        exit();
    }
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get statistics
// Tasks statistics - Show all family tasks
$tasks_query = "SELECT 
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_tasks
    FROM tasks";
$tasks_stmt = $db->prepare($tasks_query);
$tasks_stmt->execute();
$tasks_stats = $tasks_stmt->fetch(PDO::FETCH_ASSOC);

// Financial statistics - Show all family finances
$finances_query = "SELECT 
    SUM(CASE WHEN type = 'Income' THEN amount ELSE 0 END) as total_income,
    SUM(CASE WHEN type = 'Expense' THEN amount ELSE 0 END) as total_expense
    FROM finances";
$finances_stmt = $db->prepare($finances_query);
$finances_stmt->execute();
$finances_stats = $finances_stmt->fetch(PDO::FETCH_ASSOC);

// Calculate balance (ensure it's not null)
$total_income = $finances_stats['total_income'] ?? 0;
$total_expense = $finances_stats['total_expense'] ?? 0;
$balance = $total_income - $total_expense;

// Events statistics - Show all family events
$events_query = "SELECT COUNT(*) as total_events FROM events";
$events_stmt = $db->prepare($events_query);
$events_stmt->execute();
$events_stats = $events_stmt->fetch(PDO::FETCH_ASSOC);

// Recent tasks - Show all family tasks with assignee information
$recent_tasks_query = "SELECT t.*, u.name as assignee_name, c.name as created_by_name 
                       FROM tasks t 
                       LEFT JOIN users u ON t.assignee_id = u.id 
                       LEFT JOIN users c ON t.created_by = c.id 
                       ORDER BY t.created_at DESC LIMIT 5";
$recent_tasks_stmt = $db->prepare($recent_tasks_query);
$recent_tasks_stmt->execute();
$recent_tasks = $recent_tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent finances - Show all family finances
$recent_finances_query = "SELECT f.*, u.name as created_by_name 
                         FROM finances f 
                         LEFT JOIN users u ON f.created_by = u.id 
                         ORDER BY f.date DESC LIMIT 5";
$recent_finances_stmt = $db->prepare($recent_finances_query);
$recent_finances_stmt->execute();
$recent_finances = $recent_finances_stmt->fetchAll(PDO::FETCH_ASSOC);

// Upcoming events - Show all family events
$upcoming_events_query = "SELECT e.*, u.name as created_by_name 
                         FROM events e 
                         LEFT JOIN users u ON e.created_by = u.id 
                         WHERE e.start_date >= CURDATE() 
                         ORDER BY e.start_date ASC LIMIT 5";
$upcoming_events_stmt = $db->prepare($upcoming_events_query);
$upcoming_events_stmt->execute();
$upcoming_events = $upcoming_events_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</p>
</div>

<?php if (isset($_GET['status_updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> Task status updated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
        <div class="stats-card blue">
            <div class="stats-number"><?php echo $tasks_stats['total_tasks'] ?? 0; ?></div>
            <div class="stats-label">Total Tasks</div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
        <div class="stats-card green">
            <div class="stats-number"><?php echo $tasks_stats['completed_tasks'] ?? 0; ?></div>
            <div class="stats-label">Completed Tasks</div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
        <div class="stats-card orange">
            <div class="stats-number"><?php echo formatCurrency($total_income, 'KSH'); ?></div>
            <div class="stats-label">Total Income</div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-3">
        <div class="stats-card <?php echo $balance >= 0 ? 'blue' : 'pink'; ?>">
            <div class="stats-number"><?php echo formatCurrency($balance, 'KSH'); ?></div>
            <div class="stats-label">Net Balance</div>
        </div>
    </div>
</div>

<!-- Content Row -->
<div class="row">
    <!-- Recent Tasks -->
    <div class="col-lg-6 col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-tasks"></i> Recent Tasks
                </h5>
                <a href="tasks.php" class="btn btn-sm btn-outline-primary d-none d-md-inline-block">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_tasks)): ?>
                    <p class="text-muted text-center">No tasks found.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_tasks as $task): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 text-truncate"><?php echo htmlspecialchars($task['title']); ?></h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            Due: <?php echo $task['due_date'] ? date('M j, Y', strtotime($task['due_date'])) : 'No due date'; ?>
                                        </small>
                                        <small class="text-muted">
                                            <?php if ($task['assignee_name']): ?>
                                                Assigned to: <?php echo htmlspecialchars($task['assignee_name']); ?>
                                            <?php else: ?>
                                                Unassigned
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <small class="text-info">
                                        Created by: <?php echo htmlspecialchars($task['created_by_name']); ?>
                                    </small>
                                </div>
                                <form method="POST" style="display: inline;" class="quick-status-form">
                                    <input type="hidden" name="action" value="quick_status_update">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <select name="new_status" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 100px;">
                                        <option value="Pending" <?php echo ($task['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="In Progress" <?php echo ($task['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="Completed" <?php echo ($task['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                        <option value="Cancelled" <?php echo ($task['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="mt-3 d-md-none">
                    <a href="tasks.php" class="btn btn-outline-primary btn-sm w-100">View All Tasks</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Finances -->
    <div class="col-lg-6 col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-dollar-sign"></i> Recent Financial Records
                </h5>
                <a href="finances.php" class="btn btn-sm btn-outline-primary d-none d-md-inline-block">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_finances)): ?>
                    <p class="text-muted text-center">No financial records found.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_finances as $finance): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 text-truncate"><?php echo htmlspecialchars($finance['title']); ?></h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($finance['date'])); ?>
                                        </small>
                                        <small class="text-info">
                                            Created by: <?php echo htmlspecialchars($finance['created_by_name']); ?>
                                        </small>
                                    </div>
                                </div>
                                <span class="text-<?php echo $finance['type'] == 'Income' ? 'success' : 'danger'; ?> ms-2">
                                    <?php echo formatCurrency($finance['amount'], 'KSH'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="mt-3 d-md-none">
                    <a href="finances.php" class="btn btn-outline-primary btn-sm w-100">View All Finances</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Events -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-calendar"></i> Upcoming Events
                </h5>
                <a href="events.php" class="btn btn-sm btn-outline-primary d-none d-md-inline-block">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_events)): ?>
                    <p class="text-muted text-center">No upcoming events.</p>
                <?php else: ?>
                    <!-- Desktop Table -->
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Type</th>
                                    <th>Created By</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_events as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($event['start_date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($event['start_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                                        <td><small class="text-info"><?php echo htmlspecialchars($event['created_by_name']); ?></small></td>
                                        <td><?php echo htmlspecialchars(substr($event['description'], 0, 50)) . (strlen($event['description']) > 50 ? '...' : ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Mobile Cards -->
                    <div class="d-md-none">
                        <?php foreach ($upcoming_events as $event): ?>
                            <div class="mobile-table-card mb-3">
                                <div class="card-body">
                                    <div class="mobile-table-item">
                                        <span class="mobile-table-label">Event:</span>
                                        <span class="mobile-table-value"><?php echo htmlspecialchars($event['title']); ?></span>
                                    </div>
                                    <div class="mobile-table-item">
                                        <span class="mobile-table-label">Date:</span>
                                        <span class="mobile-table-value"><?php echo date('M j, Y', strtotime($event['start_date'])); ?></span>
                                    </div>
                                    <div class="mobile-table-item">
                                        <span class="mobile-table-label">Time:</span>
                                        <span class="mobile-table-value"><?php echo date('g:i A', strtotime($event['start_date'])); ?></span>
                                    </div>
                                    <div class="mobile-table-item">
                                        <span class="mobile-table-label">Type:</span>
                                        <span class="mobile-table-value"><?php echo htmlspecialchars($event['event_type']); ?></span>
                                    </div>
                                    <div class="mobile-table-item">
                                        <span class="mobile-table-label">Created by:</span>
                                        <span class="mobile-table-value text-info"><?php echo htmlspecialchars($event['created_by_name']); ?></span>
                                    </div>
                                    <?php if ($event['description']): ?>
                                    <div class="mobile-table-item">
                                        <span class="mobile-table-label">Description:</span>
                                        <span class="mobile-table-value"><?php echo htmlspecialchars(substr($event['description'], 0, 30)) . (strlen($event['description']) > 30 ? '...' : ''); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="mt-3 d-md-none">
                    <a href="events.php" class="btn btn-outline-primary btn-sm w-100">View All Events</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
