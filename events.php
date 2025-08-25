<?php
session_start();
require_once 'config/database.php';

$page_title = 'Events';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? 'list';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $event_type = $_POST['event_type'];
                $start_date = $_POST['start_date'] . ' ' . $_POST['start_time'];
                $end_date = $_POST['end_date'] ? ($_POST['end_date'] . ' ' . $_POST['end_time']) : null;
                $assignee_id = $_POST['assignee_id'] ?: null;
                $reminder_time = $_POST['reminder_time'] ?: null;
                
                $query = "INSERT INTO events (title, description, event_type, start_date, end_date, assignee_id, reminder_time, created_by) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$title, $description, $event_type, $start_date, $end_date, $assignee_id, $reminder_time, $_SESSION['user_id']])) {
                    $message = 'Event created successfully!';
                    $action = 'list';
                } else {
                    $message = 'Error creating event.';
                }
                break;
                
            case 'update':
                $event_id = $_POST['event_id'];
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $event_type = $_POST['event_type'];
                $start_date = $_POST['start_date'] . ' ' . $_POST['start_time'];
                $end_date = $_POST['end_date'] ? ($_POST['end_date'] . ' ' . $_POST['end_time']) : null;
                $assignee_id = $_POST['assignee_id'] ?: null;
                $reminder_time = $_POST['reminder_time'] ?: null;
                
                $query = "UPDATE events SET title = ?, description = ?, event_type = ?, start_date = ?, end_date = ?, assignee_id = ?, reminder_time = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$title, $description, $event_type, $start_date, $end_date, $assignee_id, $reminder_time, $event_id])) {
                    $message = 'Event updated successfully!';
                    $action = 'list';
                } else {
                    $message = 'Error updating event.';
                }
                break;
                
            case 'delete':
                $event_id = $_POST['event_id'];
                
                $query = "DELETE FROM events WHERE id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$event_id])) {
                    $message = 'Event deleted successfully!';
                } else {
                    $message = 'Error deleting event.';
                }
                break;
        }
    }
}

// Get family members for assignee dropdown
$users_query = "SELECT id, name, role FROM users ORDER BY name";
$users_stmt = $db->query($users_query);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get events for listing - Show all family events
$events_query = "SELECT e.*, u.name as assignee_name, c.name as created_by_name 
                 FROM events e 
                 LEFT JOIN users u ON e.assignee_id = u.id 
                 LEFT JOIN users c ON e.created_by = c.id 
                 ORDER BY e.start_date ASC";
$events_stmt = $db->query($events_query);
$events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get specific event for editing
$edit_event = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $edit_query = "SELECT * FROM events WHERE id = ?";
    $edit_stmt = $db->prepare($edit_query);
    $edit_stmt->execute([$_GET['id']]);
    $edit_event = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Event Management</h1>
        <p class="page-subtitle">Organize and track your family's events</p>
    </div>
    <?php if ($action == 'list'): ?>
        <a href="?action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Event
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
    <!-- Events List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-calendar"></i> All Events
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($events)): ?>
                <p class="text-muted text-center">No events found. Create your first event!</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Type</th>
                                <th>Date & Time</th>
                                <th>Assignee</th>
                                <th>Created By</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <?php 
                                $start_date = new DateTime($event['start_date']);
                                $today = new DateTime();
                                $is_past = $start_date < $today;
                                $is_today = $start_date->format('Y-m-d') == $today->format('Y-m-d');
                                ?>
                                <tr class="<?php echo $is_past ? 'table-secondary' : ($is_today ? 'table-warning' : ''); ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                        <?php if ($event['description']): ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($event['description'], 0, 100)); ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $event['event_type']; ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo $start_date->format('M j, Y'); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo $start_date->format('g:i A'); ?></small>
                                            <?php if ($event['end_date']): ?>
                                                <br>
                                                <small class="text-muted">to <?php echo (new DateTime($event['end_date']))->format('g:i A'); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $event['assignee_name'] ? htmlspecialchars($event['assignee_name']) : 'Unassigned'; ?>
                                    </td>
                                    <td>
                                        <small class="text-info"><?php echo htmlspecialchars($event['created_by_name']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($is_past): ?>
                                            <span class="badge badge-secondary">Past</span>
                                        <?php elseif ($is_today): ?>
                                            <span class="badge badge-warning">Today</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Upcoming</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="?action=edit&id=<?php echo $event['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               data-bs-toggle="tooltip" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" class="delete-btn">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
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
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($action == 'add' || $action == 'edit'): ?>
    <!-- Add/Edit Event Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-<?php echo $action == 'add' ? 'plus' : 'edit'; ?>"></i>
                <?php echo $action == 'add' ? 'Add New Event' : 'Edit Event'; ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="event_id" value="<?php echo $edit_event['id']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group mb-3">
                            <label for="title" class="form-label">
                                <i class="fas fa-tag"></i> Event Title *
                            </label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo $edit_event ? htmlspecialchars($edit_event['title']) : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="event_type" class="form-label">
                                <i class="fas fa-calendar-check"></i> Event Type *
                            </label>
                            <select class="form-control" id="event_type" name="event_type" required>
                                <option value="">Select Type</option>
                                <option value="Birthday" <?php echo ($edit_event && $edit_event['event_type'] == 'Birthday') ? 'selected' : ''; ?>>Birthday</option>
                                <option value="Appointment" <?php echo ($edit_event && $edit_event['event_type'] == 'Appointment') ? 'selected' : ''; ?>>Appointment</option>
                                <option value="Bill Payment" <?php echo ($edit_event && $edit_event['event_type'] == 'Bill Payment') ? 'selected' : ''; ?>>Bill Payment</option>
                                <option value="Maintenance" <?php echo ($edit_event && $edit_event['event_type'] == 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="Other" <?php echo ($edit_event && $edit_event['event_type'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="description" class="form-label">
                        <i class="fas fa-align-left"></i> Description
                    </label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo $edit_event ? htmlspecialchars($edit_event['description']) : ''; ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="start_date" class="form-label">
                                <i class="fas fa-calendar"></i> Start Date *
                            </label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo $edit_event ? date('Y-m-d', strtotime($edit_event['start_date'])) : date('Y-m-d'); ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="start_time" class="form-label">
                                <i class="fas fa-clock"></i> Start Time *
                            </label>
                            <input type="time" class="form-control" id="start_time" name="start_time" 
                                   value="<?php echo $edit_event ? date('H:i', strtotime($edit_event['start_date'])) : '09:00'; ?>" 
                                   required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="end_date" class="form-label">
                                <i class="fas fa-calendar"></i> End Date
                            </label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo $edit_event && $edit_event['end_date'] ? date('Y-m-d', strtotime($edit_event['end_date'])) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="end_time" class="form-label">
                                <i class="fas fa-clock"></i> End Time
                            </label>
                            <input type="time" class="form-control" id="end_time" name="end_time" 
                                   value="<?php echo $edit_event && $edit_event['end_date'] ? date('H:i', strtotime($edit_event['end_date'])) : '10:00'; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="assignee_id" class="form-label">
                                <i class="fas fa-user"></i> Assignee
                            </label>
                            <select class="form-control" id="assignee_id" name="assignee_id">
                                <option value="">Unassigned</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php echo ($edit_event && $edit_event['assignee_id'] == $user['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']); ?> (<?php echo $user['role']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="reminder_time" class="form-label">
                                <i class="fas fa-bell"></i> Reminder Time
                            </label>
                            <input type="datetime-local" class="form-control" id="reminder_time" name="reminder_time" 
                                   value="<?php echo $edit_event && $edit_event['reminder_time'] ? date('Y-m-d\TH:i', strtotime($edit_event['reminder_time'])) : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="events.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Events
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 
                        <?php echo $action == 'add' ? 'Create Event' : 'Update Event'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

