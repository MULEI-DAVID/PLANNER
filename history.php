<?php
session_start();
require_once 'config/database.php';

$page_title = 'Activity History';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$filter_type = $_GET['type'] ?? 'all';
$filter_date = $_GET['date'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build the query based on filters
$where_conditions = ["user_id = ?"];
$params = [$_SESSION['user_id']];

if ($filter_type != 'all') {
    $where_conditions[] = "entity_type = ?";
    $params[] = $filter_type;
}

if ($filter_date != 'all') {
    switch ($filter_date) {
        case 'today':
            $where_conditions[] = "DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $where_conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
    }
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM activity_log WHERE $where_clause";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $per_page);

// Get activity history
$history_query = "SELECT * FROM activity_log WHERE $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$history_stmt = $db->prepare($history_query);

// Bind the filter parameters
foreach ($params as $key => $value) {
    $history_stmt->bindValue($key + 1, $value);
}

// Bind the pagination parameters as integers
$history_stmt->bindValue(count($params) + 1, (int)$per_page, PDO::PARAM_INT);
$history_stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);

$history_stmt->execute();
$activities = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent activities for summary
$recent_query = "SELECT activity_type, COUNT(*) as count FROM activity_log 
                 WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY activity_type ORDER BY count DESC";
$recent_stmt = $db->prepare($recent_query);
$recent_stmt->execute([$_SESSION['user_id']]);
$recent_activities = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Activity History</h1>
    <p class="page-subtitle">Track your system activities and changes</p>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="type" class="form-label">Activity Type</label>
                <select class="form-control" id="type" name="type">
                    <option value="all" <?php echo $filter_type == 'all' ? 'selected' : ''; ?>>All Activities</option>
                    <option value="task" <?php echo $filter_type == 'task' ? 'selected' : ''; ?>>Tasks</option>
                    <option value="event" <?php echo $filter_type == 'event' ? 'selected' : ''; ?>>Events</option>
                    <option value="finance" <?php echo $filter_type == 'finance' ? 'selected' : ''; ?>>Finances</option>
                    <option value="budget" <?php echo $filter_type == 'budget' ? 'selected' : ''; ?>>Budgets</option>
                    <option value="user" <?php echo $filter_type == 'user' ? 'selected' : ''; ?>>Users</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="date" class="form-label">Time Period</label>
                <select class="form-control" id="date" name="date">
                    <option value="all" <?php echo $filter_date == 'all' ? 'selected' : ''; ?>>All Time</option>
                    <option value="today" <?php echo $filter_date == 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="week" <?php echo $filter_date == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="month" <?php echo $filter_date == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                    <option value="year" <?php echo $filter_date == 'year' ? 'selected' : ''; ?>>Last Year</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="history.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card blue">
            <div class="stats-number"><?php echo $total_records; ?></div>
            <div class="stats-label">Total Activities</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card green">
            <div class="stats-number"><?php echo count($recent_activities); ?></div>
            <div class="stats-label">Activity Types</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card orange">
            <div class="stats-number"><?php echo $total_pages; ?></div>
            <div class="stats-label">Pages</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card pink">
            <div class="stats-number"><?php echo $per_page; ?></div>
            <div class="stats-label">Per Page</div>
        </div>
    </div>
</div>

<!-- Recent Activity Summary -->
<?php if (!empty($recent_activities)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar"></i> Recent Activity Summary (Last 7 Days)
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($recent_activities as $activity): ?>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="activity-icon me-3">
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
                                    <i class="<?php echo $icon; ?> <?php echo $color; ?> fa-2x"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo ucfirst(str_replace('_', ' ', $activity['activity_type'])); ?></div>
                                    <div class="text-muted"><?php echo $activity['count']; ?> activities</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Activity History List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-history"></i> Activity History
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($activities)): ?>
            <p class="text-muted text-center">No activities found for the selected filters.</p>
        <?php else: ?>
            <!-- Desktop Timeline -->
            <div class="d-none d-md-block">
                <div class="timeline">
                    <?php foreach ($activities as $activity): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker">
                                <?php
                                $icon = '';
                                $color = '';
                                switch ($activity['activity_type']) {
                                    case 'task_created':
                                    case 'task_completed':
                                    case 'task_updated':
                                        $icon = 'fas fa-tasks';
                                        $color = 'bg-primary';
                                        break;
                                    case 'event_created':
                                    case 'event_updated':
                                        $icon = 'fas fa-calendar';
                                        $color = 'bg-success';
                                        break;
                                    case 'finance_added':
                                    case 'finance_updated':
                                        $icon = 'fas fa-dollar-sign';
                                        $color = 'bg-warning';
                                        break;
                                    case 'budget_created':
                                    case 'budget_updated':
                                        $icon = 'fas fa-chart-pie';
                                        $color = 'bg-info';
                                        break;
                                    case 'profile_updated':
                                        $icon = 'fas fa-user';
                                        $color = 'bg-danger';
                                        break;
                                    case 'login':
                                    case 'logout':
                                        $icon = 'fas fa-sign-in-alt';
                                        $color = 'bg-secondary';
                                        break;
                                    default:
                                        $icon = 'fas fa-info-circle';
                                        $color = 'bg-secondary';
                                }
                                ?>
                                <i class="<?php echo $icon; ?> text-white"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <h6 class="mb-1"><?php echo htmlspecialchars(str_replace('_', ' ', $activity['activity_type'])); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="timeline-body">
                                    <p class="mb-2"><?php echo htmlspecialchars($activity['description']); ?></p>
                                    <span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', $activity['activity_type'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Mobile Cards -->
            <div class="d-md-none">
                <?php foreach ($activities as $activity): ?>
                    <div class="mobile-table-card mb-3">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
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
                                    <i class="<?php echo $icon; ?> <?php echo $color; ?> me-2"></i>
                                    <span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', $activity['activity_type'])); ?></span>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title"><?php echo htmlspecialchars(str_replace('_', ' ', $activity['activity_type'])); ?></h6>
                            <p class="card-text"><?php echo htmlspecialchars($activity['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Activity history pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Timeline Styles */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #007bff;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.timeline-content {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-left: 20px;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.timeline-body p {
    margin-bottom: 10px;
    color: #6c757d;
}
</style>

<?php include 'includes/footer.php'; ?>
