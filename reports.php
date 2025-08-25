<?php
session_start();
require_once 'config/database.php';
require_once 'includes/currency_helper.php';

$page_title = 'Reports & Analytics';

$database = new Database();
$db = $database->getConnection();

// Get current month and year
$current_month = date('n');
$current_year = date('Y');

// Get monthly income and expenses for the last 6 months
$monthly_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = $current_month - $i;
    $year = $current_year;
    
    if ($month <= 0) {
        $month += 12;
        $year--;
    }
    
    // Get income for this month
    $income_query = "SELECT SUM(amount) as total FROM finances 
                     WHERE type = 'Income' 
                     AND MONTH(date) = ? 
                     AND YEAR(date) = ? 
                     AND created_by = ?";
    $income_stmt = $db->prepare($income_query);
    $income_stmt->execute([$month, $year, $_SESSION['user_id']]);
    $income = $income_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get expenses for this month
    $expense_query = "SELECT SUM(amount) as total FROM finances 
                      WHERE type = 'Expense' 
                      AND MONTH(date) = ? 
                      AND YEAR(date) = ? 
                      AND created_by = ?";
    $expense_stmt = $db->prepare($expense_query);
    $expense_stmt->execute([$month, $year, $_SESSION['user_id']]);
    $expense = $expense_stmt->fetch(PDO::FETCH_ASSOC);
    
    $monthly_data[] = [
        'month' => date('M Y', mktime(0, 0, 0, $month, 1, $year)),
        'income' => $income['total'] ?? 0,
        'expense' => $expense['total'] ?? 0,
        'balance' => ($income['total'] ?? 0) - ($expense['total'] ?? 0)
    ];
}

// Get expense breakdown by category for current month
$category_expenses_query = "SELECT category, SUM(amount) as total 
                            FROM finances 
                            WHERE type = 'Expense' 
                            AND MONTH(date) = ? 
                            AND YEAR(date) = ? 
                            AND created_by = ?
                            GROUP BY category 
                            ORDER BY total DESC";
$category_expenses_stmt = $db->prepare($category_expenses_query);
$category_expenses_stmt->execute([$current_month, $current_year, $_SESSION['user_id']]);
$category_expenses = $category_expenses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get income breakdown by category for current month
$category_income_query = "SELECT category, SUM(amount) as total 
                          FROM finances 
                          WHERE type = 'Income' 
                          AND MONTH(date) = ? 
                          AND YEAR(date) = ? 
                          AND created_by = ?
                          GROUP BY category 
                          ORDER BY total DESC";
$category_income_stmt = $db->prepare($category_income_query);
$category_income_stmt->execute([$current_month, $current_year, $_SESSION['user_id']]);
$category_income = $category_income_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total statistics
$total_income_query = "SELECT SUM(amount) as total FROM finances WHERE type = 'Income' AND created_by = ?";
$total_income_stmt = $db->prepare($total_income_query);
$total_income_stmt->execute([$_SESSION['user_id']]);
$total_income = $total_income_stmt->fetch(PDO::FETCH_ASSOC);

$total_expense_query = "SELECT SUM(amount) as total FROM finances WHERE type = 'Expense' AND created_by = ?";
$total_expense_stmt = $db->prepare($total_expense_query);
$total_expense_stmt->execute([$_SESSION['user_id']]);
$total_expense = $total_expense_stmt->fetch(PDO::FETCH_ASSOC);

$total_income_amount = $total_income['total'] ?? 0;
$total_expense_amount = $total_expense['total'] ?? 0;
$net_balance = $total_income_amount - $total_expense_amount;

include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Reports & Analytics</h1>
    <p class="page-subtitle">Financial insights and spending analysis</p>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card green">
                <div class="stats-number"><?php echo formatCurrency($total_income_amount, 'KSH'); ?></div>
                <div class="stats-label">Total Income</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card orange">
                <div class="stats-number"><?php echo formatCurrency($total_expense_amount, 'KSH'); ?></div>
                <div class="stats-label">Total Expenses</div>
            </div>
        </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card <?php echo $net_balance >= 0 ? 'blue' : 'pink'; ?>">
            <div class="stats-number">$<?php echo number_format($net_balance, 2); ?></div>
            <div class="stats-label">Net Balance</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card blue">
            <div class="stats-number"><?php echo count($monthly_data); ?></div>
            <div class="stats-label">Months Tracked</div>
        </div>
    </div>
</div>

<!-- Monthly Trend Chart -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line"></i> Monthly Income vs Expenses (Last 6 Months)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Category Breakdown -->
<div class="row">
    <!-- Expense Categories -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie"></i> Expense Breakdown (<?php echo date('F Y'); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($category_expenses)): ?>
                    <p class="text-muted text-center">No expenses recorded this month.</p>
                <?php else: ?>
                    <canvas id="expenseChart" width="400" height="200"></canvas>
                    <div class="mt-3">
                        <h6>Expense Details:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_expenses_month = array_sum(array_column($category_expenses, 'total'));
                                    foreach ($category_expenses as $expense): 
                                        $percentage = $total_expenses_month > 0 ? ($expense['total'] / $total_expenses_month) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo $expense['category']; ?></td>
                                        <td>$<?php echo number_format($expense['total'], 2); ?></td>
                                        <td><?php echo number_format($percentage, 1); ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Income Categories -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie"></i> Income Breakdown (<?php echo date('F Y'); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($category_income)): ?>
                    <p class="text-muted text-center">No income recorded this month.</p>
                <?php else: ?>
                    <canvas id="incomeChart" width="400" height="200"></canvas>
                    <div class="mt-3">
                        <h6>Income Details:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_income_month = array_sum(array_column($category_income, 'total'));
                                    foreach ($category_income as $income): 
                                        $percentage = $total_income_month > 0 ? ($income['total'] / $total_income_month) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo $income['category']; ?></td>
                                        <td>$<?php echo number_format($income['total'], 2); ?></td>
                                        <td><?php echo number_format($percentage, 1); ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Data Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-table"></i> Monthly Summary
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Income</th>
                                <th>Expenses</th>
                                <th>Balance</th>
                                <th>Savings Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_data as $data): ?>
                                <?php 
                                $savings_rate = $data['income'] > 0 ? (($data['income'] - $data['expense']) / $data['income']) * 100 : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo $data['month']; ?></strong></td>
                                    <td class="text-success">$<?php echo number_format($data['income'], 2); ?></td>
                                    <td class="text-danger">$<?php echo number_format($data['expense'], 2); ?></td>
                                    <td class="<?php echo $data['balance'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        $<?php echo number_format($data['balance'], 2); ?>
                                    </td>
                                    <td>
                                        <span class="<?php echo $savings_rate >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo number_format($savings_rate, 1); ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Trend Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyChart = new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthly_data, 'month')); ?>,
            datasets: [{
                label: 'Income',
                data: <?php echo json_encode(array_column($monthly_data, 'income')); ?>,
                borderColor: '#4caf50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                tension: 0.1
            }, {
                label: 'Expenses',
                data: <?php echo json_encode(array_column($monthly_data, 'expense')); ?>,
                borderColor: '#ff9800',
                backgroundColor: 'rgba(255, 152, 0, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Expense Chart
    <?php if (!empty($category_expenses)): ?>
    const expenseCtx = document.getElementById('expenseChart').getContext('2d');
    const expenseChart = new Chart(expenseCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($category_expenses, 'category')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($category_expenses, 'total')); ?>,
                backgroundColor: [
                    '#ff6384', '#36a2eb', '#cc65fe', '#ffce56', '#4bc0c0', '#9966ff'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': $' + context.parsed.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>

    // Income Chart
    <?php if (!empty($category_income)): ?>
    const incomeCtx = document.getElementById('incomeChart').getContext('2d');
    const incomeChart = new Chart(incomeCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($category_income, 'category')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($category_income, 'total')); ?>,
                backgroundColor: [
                    '#4caf50', '#2196f3', '#9c27b0', '#ff9800', '#f44336', '#00bcd4'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': $' + context.parsed.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php include 'includes/footer.php'; ?>
