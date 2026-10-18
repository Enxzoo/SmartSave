<?php
require_once __DIR__ . "/includes/bootstrap.php";
require_once "classes/Expense.php";

$user = require_login();

$expense = new Expense($db, (int) $user['user_id']);

$keyword = is_string($_GET['keyword'] ?? '') ? trim($_GET['keyword'] ?? '') : '';

if ($keyword != "") {
    $expenses = $expense->searchExpenses($keyword);
} else {
    $expenses = $expense->getExpenses();
}

$totalExpense = $expense->getTotalExpenses();
$categories = $expense->getCategories();

$expenseCount = count($expenses);

// Small "this month" insight computed from already-fetched records (no schema changes needed)
$thisMonthTotal = 0.0;
$currentMonth = date("Y-m");
foreach ($expenses as $row) {
    if (substr($row["date"], 0, 7) === $currentMonth) {
        $thisMonthTotal += (float) $row["amount"];
    }
}

$active = 'expense';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartSave - Expenses</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/css/smartsave.css">
    <link rel="stylesheet" href="assets/css/css/responsive.css">
</head>
<body>

<div class="ss-app">

    <?php include "includes/navbar.php"; ?>

    <main class="ss-main">

        <!-- Topbar -->
        <div class="ss-topbar">
            <button class="ss-burger" id="ssBurger" aria-label="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>

            <div>
                <h1 class="ss-page-title">Expenses</h1>
                <p class="ss-page-subtitle">Track, add, and manage where your money goes.</p>
            </div>

            <div class="ss-topbar-actions">
                <form method="GET" action="expense.php" class="ss-search">
                    <i class="bi bi-search"></i>
                    <input type="text" name="keyword" placeholder="Search expenses..."
                           value="<?= htmlspecialchars($keyword) ?>">
                </form>

                <button type="button" class="btn ss-btn-primary ss-add-btn" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                    <i class="bi bi-plus-lg"></i> Add Expense
                </button>
            </div>
        </div>

        <?php include __DIR__ . '/includes/alerts.php'; ?>

        <?php if ($keyword != ""): ?>
            <div class="ss-search-tag">
                Showing results for "<strong><?= htmlspecialchars($keyword) ?></strong>"
                <a href="expense.php"><i class="bi bi-x-circle"></i> Clear</a>
            </div>
        <?php endif; ?>

        <!-- Summary cards -->
        <div class="ss-cards-row">

            <div class="ss-card ss-card-highlight">
                <span class="ss-card-badge">Overview</span>
                <p class="ss-card-label">Total Expenses</p>
                <h2 class="ss-card-value">₱<?= number_format($totalExpense, 2) ?></h2>
                <p class="ss-card-foot">Across all recorded transactions</p>
            </div>

            <div class="ss-card">
                <p class="ss-card-label ss-card-label-dark">This Month</p>
                <h2 class="ss-card-value ss-card-value-dark">₱<?= number_format($thisMonthTotal, 2) ?></h2>
                <p class="ss-card-foot ss-card-foot-dark"><?= date("F Y") ?> spending</p>
            </div>

            <div class="ss-card">
                <p class="ss-card-label ss-card-label-dark">Total Records</p>
                <h2 class="ss-card-value ss-card-value-dark"><?= $expenseCount ?></h2>
                <p class="ss-card-foot ss-card-foot-dark">Expense entries logged</p>
            </div>

        </div>

        <!-- Expense table -->
        <div class="ss-table-card">
            <div class="ss-table-card-header">
                <h5>Expense Records</h5>
                <span class="ss-table-count"><?= $expenseCount ?> item<?= $expenseCount === 1 ? '' : 's' ?></span>
            </div>

            <div class="table-responsive">
                <table class="ss-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>User ID</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if (empty($expenses)): ?>
                            <tr>
                                <td colspan="6" class="ss-empty-state">
                                    <i class="bi bi-receipt"></i>
                                    <p>No expense records found.</p>
                                </td>
                            </tr>
                        <?php else: ?>

                            <?php foreach ($expenses as $row): ?>
                                <?php
                                    $initial = strtoupper(substr($row["description"], 0, 1));
                                    $catName = $row["category_name"] ?? "Uncategorized";
                                ?>
                                <tr>
                                    <td>
                                        <div class="ss-expense-cell">
                                            <span class="ss-expense-avatar"><?= htmlspecialchars($initial) ?></span>
                                            <span class="ss-expense-name"><?= htmlspecialchars($row["description"]) ?></span>
                                        </div>
                                    </td>
                                    <td data-label="Category"><span class="ss-pill"><?= htmlspecialchars($catName) ?></span></td>
                                    <td data-label="Date"><?= htmlspecialchars(date("M d, Y", strtotime($row["date"]))) ?></td>
                                    <td data-label="User"><span>#<?= htmlspecialchars($row["user_id"]) ?></span></td>
                                    <td data-label="Amount" class="text-end ss-amount">- ₱<?= number_format($row["amount"], 2) ?></td>
                                    <td class="text-center">
                                        <div class="ss-actions">
                                            <button type="button" class="ss-icon-btn ss-icon-edit"
                                                    data-bs-toggle="modal" data-bs-target="#editExpenseModal"
                                                    data-id="<?= htmlspecialchars($row["expenses_id"]) ?>"
                                                    data-description="<?= htmlspecialchars($row["description"]) ?>"
                                                    data-amount="<?= htmlspecialchars($row["amount"]) ?>"
                                                    data-date="<?= htmlspecialchars($row["date"]) ?>"
                                                    data-user_id="<?= htmlspecialchars($row["user_id"]) ?>"
                                                    data-category_id="<?= htmlspecialchars($row["category_id"]) ?>"
                                                    title="Edit">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button type="button" class="ss-icon-btn ss-icon-delete"
                                                    data-bs-toggle="modal" data-bs-target="#deleteExpenseModal"
                                                    data-id="<?= htmlspecialchars($row["expenses_id"]) ?>"
                                                    data-description="<?= htmlspecialchars($row["description"]) ?>"
                                                    data-amount="<?= htmlspecialchars($row["amount"]) ?>"
                                                    data-date="<?= htmlspecialchars(date("M d, Y", strtotime($row["date"]))) ?>"
                                                    title="Delete">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php include "modals/addExpense.php"; ?>
<?php include "modals/updateExpense.php"; ?>
<?php include "modals/deleteExpense.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/theme.js"></script>

</body>
</html>
