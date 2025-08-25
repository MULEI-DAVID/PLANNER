<?php
session_start();
require_once 'config/database.php';

$page_title = 'Tasks';

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
                $assignee_id = $_POST['assignee_id'] ?: null;
                $category = $_POST['category'];
                $priority = $_POST['priority'];
                $due_date = $_POST['due_date'] ?: null;
                $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
                $recurring_pattern = $_POST['recurring_pattern'] ?: null;
                
                $query = "INSERT INTO tasks (title, description, assignee_id, category, priority, due_date, is_recurring, recurring_pattern, created_by) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$title, $description, $assignee_id, $category, $priority, $due_date, $is_recurring, $recurring_pattern, $_SESSION['user_id']])) {
                    $message = 'Task created successfully!';
                    $action = 'list';
                } else {
                    $message = 'Error creating task.';
                }
                break;
                
            case 'update':
                $task_id = $_POST['task_id'];
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $assignee_id = $_POST['assignee_id'] ?: null;
                $category = $_POST['category'];
                $priority = $_POST['priority'];
                $status = $_POST['status'];
                $due_date = $_POST['due_date'] ?: null;
                $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
                $recurring_pattern = $_POST['recurring_pattern'] ?: null;
                
                $query = "UPDATE tasks SET title = ?, description = ?, assignee_id = ?, category = ?, priority = ?, status = ?, due_date = ?, is_recurring = ?, recurring_pattern = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$title, $description, $assignee_id, $category, $priority, $status, $due_date, $is_recurring, $recurring_pattern, $task_id])) {
                    $message = 'Task updated successfully!';
                    $action = 'list';
                } else {
                    $message = 'Error updating task.';
                }
                break;
                
            case 'delete':
                $task_id = $_POST['task_id'];
                
                $query = "DELETE FROM tasks WHERE id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$task_id])) {
                    $message = 'Task deleted successfully!';
                } else {
                    $message = 'Error deleting task.';
                }
                break;
        }
    }
}

// Get family members for assignee dropdown
$users_query = "SELECT id, name, role FROM users ORDER BY name";
$users_stmt = $db->query($users_query);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get tasks for listing
$tasks_query = "SELECT t.*, u.name as assignee_name 
                FROM tasks t 
                LEFT JOIN users u ON t.assignee_id = u.id 
                ORDER BY t.due_date ASC, t.priority DESC";
$tasks_stmt = $db->query($tasks_query);
$tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get specific task for editing
$edit_task = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $edit_query = "SELECT * FROM tasks WHERE id = ?";
    $edit_stmt = $db->prepare($edit_query);
    $edit_stmt->execute([$_GET['id']]);
    $edit_task = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Task Management</h1>
        <p class="page-subtitle">Organize and track your family's tasks</p>
    </div>
    <?php if ($action == 'list'): ?>
        <a href="?action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Task
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
    <!-- Task List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-tasks"></i> All Tasks
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($tasks)): ?>
                <p class="text-muted text-center">No tasks found. Create your first task!</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Assignee</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($task['description'], 0, 100)); ?>...</small>
                                    </td>
                                    <td>
                                        <?php echo $task['assignee_name'] ? htmlspecialchars($task['assignee_name']) : 'Unassigned'; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $task['category']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-priority-<?php echo strtolower($task['priority']); ?>">
                                            <?php echo $task['priority']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-status-<?php echo strtolower($task['status']); ?>">
                                            <?php echo $task['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($task['due_date']) {
                                            $due_date = new DateTime($task['due_date']);
                                            $today = new DateTime();
                                            $diff = $due_date->diff($today);
                                            
                                            if ($due_date < $today && $task['status'] != 'Completed') {
                                                echo '<span class="text-danger">' . $due_date->format('M j, Y') . ' (Overdue)</span>';
                                            } else {
                                                echo $due_date->format('M j, Y');
                                            }
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="?action=edit&id=<?php echo $task['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               data-bs-toggle="tooltip" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" class="delete-btn">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
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
    <!-- Add/Edit Task Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-<?php echo $action == 'add' ? 'plus' : 'edit'; ?>"></i>
                <?php echo $action == 'add' ? 'Add New Task' : 'Edit Task'; ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="task_id" value="<?php echo $edit_task['id']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group mb-3">
                            <label for="title" class="form-label">
                                <i class="fas fa-tag"></i> Task Title *
                            </label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo $edit_task ? htmlspecialchars($edit_task['title']) : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="category" class="form-label">
                                <i class="fas fa-folder"></i> Category *
                            </label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Household" <?php echo ($edit_task && $edit_task['category'] == 'Household') ? 'selected' : ''; ?>>Household</option>
                                <option value="School" <?php echo ($edit_task && $edit_task['category'] == 'School') ? 'selected' : ''; ?>>School</option>
                                <option value="Groceries" <?php echo ($edit_task && $edit_task['category'] == 'Groceries') ? 'selected' : ''; ?>>Groceries</option>
                                <option value="Side Jobs" <?php echo ($edit_task && $edit_task['category'] == 'Side Jobs') ? 'selected' : ''; ?>>Side Jobs</option>
                                <option value="Events" <?php echo ($edit_task && $edit_task['category'] == 'Events') ? 'selected' : ''; ?>>Events</option>
                                <option value="Miscellaneous" <?php echo ($edit_task && $edit_task['category'] == 'Miscellaneous') ? 'selected' : ''; ?>>Miscellaneous</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="description" class="form-label">
                        <i class="fas fa-align-left"></i> Description
                    </label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo $edit_task ? htmlspecialchars($edit_task['description']) : ''; ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="assignee_id" class="form-label">
                                <i class="fas fa-user"></i> Assignee
                            </label>
                            <select class="form-control" id="assignee_id" name="assignee_id">
                                <option value="">Unassigned</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php echo ($edit_task && $edit_task['assignee_id'] == $user['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']); ?> (<?php echo $user['role']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="priority" class="form-label">
                                <i class="fas fa-exclamation-triangle"></i> Priority *
                            </label>
                            <select class="form-control" id="priority" name="priority" required>
                                <option value="">Select Priority</option>
                                <option value="Low" <?php echo ($edit_task && $edit_task['priority'] == 'Low') ? 'selected' : ''; ?>>Low</option>
                                <option value="Medium" <?php echo ($edit_task && $edit_task['priority'] == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                                <option value="High" <?php echo ($edit_task && $edit_task['priority'] == 'High') ? 'selected' : ''; ?>>High</option>
                                <option value="Urgent" <?php echo ($edit_task && $edit_task['priority'] == 'Urgent') ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="due_date" class="form-label">
                                <i class="fas fa-calendar"></i> Due Date
                            </label>
                            <input type="date" class="form-control" id="due_date" name="due_date" 
                                   value="<?php echo $edit_task ? $edit_task['due_date'] : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <?php if ($action == 'edit'): ?>
                    <div class="form-group mb-3">
                        <label for="status" class="form-label">
                            <i class="fas fa-check-circle"></i> Status *
                        </label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="Pending" <?php echo ($edit_task['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="In Progress" <?php echo ($edit_task['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Completed" <?php echo ($edit_task['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo ($edit_task['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring" 
                                   <?php echo ($edit_task && $edit_task['is_recurring']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_recurring">
                                <i class="fas fa-redo"></i> Recurring Task
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3" id="recurring_pattern_group" style="display: none;">
                            <label for="recurring_pattern" class="form-label">
                                <i class="fas fa-clock"></i> Recurring Pattern
                            </label>
                            <select class="form-control" id="recurring_pattern" name="recurring_pattern">
                                <option value="">Select Pattern</option>
                                <option value="daily" <?php echo ($edit_task && $edit_task['recurring_pattern'] == 'daily') ? 'selected' : ''; ?>>Daily</option>
                                <option value="weekly" <?php echo ($edit_task && $edit_task['recurring_pattern'] == 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                                <option value="monthly" <?php echo ($edit_task && $edit_task['recurring_pattern'] == 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="tasks.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Tasks
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 
                        <?php echo $action == 'add' ? 'Create Task' : 'Update Task'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
// Show/hide recurring pattern field
document.getElementById('is_recurring').addEventListener('change', function() {
    const patternGroup = document.getElementById('recurring_pattern_group');
    if (this.checked) {
        patternGroup.style.display = 'block';
    } else {
        patternGroup.style.display = 'none';
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const isRecurring = document.getElementById('is_recurring');
    if (isRecurring && isRecurring.checked) {
        document.getElementById('recurring_pattern_group').style.display = 'block';
    }
});
</script>

<?php include 'includes/footer.php'; ?>

