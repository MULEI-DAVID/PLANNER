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

// Get user information
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get statistics
// Tasks statistics
$tasks_query = "SELECT 
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_tasks
    FROM tasks WHERE created_by = ?";
$tasks_stmt = $db->prepare($tasks_query);
$tasks_stmt->execute([$user_id]);
$tasks_stats = $tasks_stmt->fetch(PDO::FETCH_ASSOC);

// Financial statistics
$finances_query = "SELECT 
    SUM(CASE WHEN type = 'Income' THEN amount ELSE 0 END) as total_income,
    SUM(CASE WHEN type = 'Expense' THEN amount ELSE 0 END) as total_expense
    FROM finances WHERE created_by = ?";
$finances_stmt = $db->prepare($finances_query);
$finances_stmt->execute([$user_id]);
$finances_stats = $finances_stmt->fetch(PDO::FETCH_ASSOC);

// Calculate balance (ensure it's not null)
$total_income = $finances_stats['total_income'] ?? 0;
$total_expense = $finances_stats['total_expense'] ?? 0;
$balance = $total_income - $total_expense;

// Events statistics
$events_query = "SELECT COUNT(*) as total_events FROM events WHERE created_by = ?";
$events_stmt = $db->prepare($events_query);
$events_stmt->execute([$user_id]);
$events_stats = $events_stmt->fetch(PDO::FETCH_ASSOC);

// Recent tasks
$recent_tasks_query = "SELECT * FROM tasks WHERE created_by = ? ORDER BY created_at DESC LIMIT 5";
$recent_tasks_stmt = $db->prepare($recent_tasks_query);
$recent_tasks_stmt->execute([$user_id]);
$recent_tasks = $recent_tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent finances
$recent_finances_query = "SELECT * FROM finances WHERE created_by = ? ORDER BY date DESC LIMIT 5";
$recent_finances_stmt = $db->prepare($recent_finances_query);
$recent_finances_stmt->execute([$user_id]);
$recent_finances = $recent_finances_stmt->fetchAll(PDO::FETCH_ASSOC);

// Upcoming events
$upcoming_events_query = "SELECT * FROM events WHERE created_by = ? AND start_date >= CURDATE() ORDER BY start_date ASC LIMIT 5";
$upcoming_events_stmt = $db->prepare($upcoming_events_query);
$upcoming_events_stmt->execute([$user_id]);
$upcoming_events = $upcoming_events_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</p>
</div>

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
                                    <small class="text-muted">Due: <?php echo date('M j, Y', strtotime($task['due_date'])); ?></small>
                                </div>
                                <span class="badge bg-<?php echo $task['status'] == 'Completed' ? 'success' : ($task['status'] == 'In Progress' ? 'warning' : 'secondary'); ?> ms-2">
                                    <?php echo $task['status']; ?>
                                </span>
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
                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($finance['date'])); ?></small>
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
