-- Add activity_log table to existing c_planner database
USE c_planner;

-- Activity log table for tracking user activities
CREATE TABLE IF NOT EXISTS activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type ENUM('task_created', 'task_completed', 'task_updated', 'event_created', 'event_updated', 'finance_added', 'finance_updated', 'budget_created', 'budget_updated', 'profile_updated', 'login', 'logout') NOT NULL,
    description TEXT NOT NULL,
    entity_type ENUM('task', 'event', 'finance', 'budget', 'profile', 'system') NOT NULL,
    entity_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert some sample activity log entries for testing
INSERT INTO activity_log (user_id, activity_type, description, entity_type, ip_address, user_agent) VALUES
(1, 'login', 'User logged in successfully to the system', 'system', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'task_created', 'Created new task: Grocery Shopping for the weekend', 'task', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'finance_added', 'Added income: Monthly salary payment of $5000', 'finance', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'event_created', 'Created event: Doctor Appointment on Friday at 2 PM', 'event', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'budget_created', 'Created budget for Groceries category: $500 per month', 'budget', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'profile_updated', 'Updated profile information including phone number and birth date', 'profile', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
