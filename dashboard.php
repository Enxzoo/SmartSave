<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/classes/Finance.php';
$user = require_login();
$finance = new Finance($db, (int) $user['user_id']);
$totals = $finance->totals();
$recent = $finance->recent();
$months = $finance->monthly();
$goals = array_slice($finance->goals(), 0, 3);
$maximum = 1;
foreach ($months as $month) { $maximum = max($maximum, $month['income'], $month['expenses']); }
$balance = $totals['income']['total'] - $totals['expenses']['total'];
$active = 'dashboard';
$title = 'Dashboard';
include __DIR__ . '/includes/page_start.php';
?>
<div class="ss-topbar">
    <button class="ss-burger" id="ssBurger" aria-label="Toggle sidebar"><i class="bi bi-list"></i></button>
    <div><h1 class="ss-page-title">Dashboard</h1><p class="ss-page-subtitle">Welcome back, <?= e($user['name']) ?>. Here is your financial overview.</p></div>
    <div class="ss-topbar-actions"><span class="ss-date-label"><i class="bi bi-calendar3"></i> <?= date('F d, Y') ?></span><a href="income.php?add=1" class="btn ss-btn-primary"><i class="bi bi-plus-lg"></i> Add Income</a></div>
</div>
<?php include __DIR__ . '/includes/alerts.php'; ?>
<div class="ss-cards-row">
    <div class="ss-card ss-card-highlight"><span class="ss-card-badge">Overview</span><p class="ss-card-label">Net Balance</p><h2 class="ss-card-value"><?= money($balance) ?></h2><p class="ss-card-foot">Total income less total expenses</p></div>
    <div class="ss-card"><p class="ss-card-label ss-card-label-dark">Income This Month</p><h2 class="ss-card-value ss-positive"><?= money($totals['income']['monthly']) ?></h2><p class="ss-card-foot ss-card-foot-dark"><?= date('F Y') ?> earnings</p></div>
    <div class="ss-card"><p class="ss-card-label ss-card-label-dark">Expenses This Month</p><h2 class="ss-card-value ss-negative"><?= money($totals['expenses']['monthly']) ?></h2><p class="ss-card-foot ss-card-foot-dark"><?= date('F Y') ?> spending</p></div>
</div>
<div class="ss-dashboard-grid">
    <section class="ss-table-card ss-chart-panel" aria-labelledby="cashflow-title">
        <div class="ss-table-card-header"><h5 id="cashflow-title">Income &amp; Expenses</h5><span class="ss-table-count">Last 6 months</span></div>
        <div class="ss-chart-legend"><span><i class="ss-legend-income"></i> Income</span><span><i class="ss-legend-expense"></i> Expenses</span></div>
        <div class="ss-chart" aria-label="Monthly income and expenses">
            <?php foreach ($months as $key => $month): ?>
            <div class="ss-chart-column"><div class="ss-chart-bars"><span class="ss-chart-bar ss-bar-income" style="height: <?= $month['income'] > 0 ? max(2, round($month['income'] / $maximum * 100, 2)) : 0 ?>%" title="<?= e($key) ?> income: PHP <?= e(number_format($month['income'], 2)) ?>"></span><span class="ss-chart-bar ss-bar-expense" style="height: <?= $month['expenses'] > 0 ? max(2, round($month['expenses'] / $maximum * 100, 2)) : 0 ?>%" title="<?= e($key) ?> expenses: PHP <?= e(number_format($month['expenses'], 2)) ?>"></span></div><span class="ss-chart-label"><?= e($month['label']) ?></span></div>
            <?php endforeach; ?>
        </div>
        <details class="ss-chart-data"><summary>Monthly Totals</summary><div class="table-responsive"><table class="ss-table"><thead><tr><th>Month</th><th>Income</th><th>Expenses</th></tr></thead><tbody><?php foreach ($months as $key => $month): ?><tr><td><?= e($key) ?></td><td><?= money($month['income']) ?></td><td><?= money($month['expenses']) ?></td></tr><?php endforeach; ?></tbody></table></div></details>
    </section>
    <section class="ss-table-card ss-goals-panel">
        <div class="ss-table-card-header"><h5>Saving Goals</h5><a href="goals.php" class="ss-text-link">View all <i class="bi bi-arrow-right"></i></a></div>
        <div class="ss-savings-total"><span>Total saved</span><strong><?= money($totals['goals']['saved']) ?></strong></div>
        <?php if (!$goals): ?><div class="ss-empty-state"><i class="bi bi-flag"></i><p>No saving goals yet.</p><a href="goals.php?add=1" class="btn ss-btn-primary"><i class="bi bi-plus-lg"></i> Add Goal</a></div><?php endif; ?>
        <?php foreach ($goals as $goal): $percent = min(100, $goal['target_amount'] > 0 ? round($goal['current_amount'] / $goal['target_amount'] * 100, 1) : 0); ?>
        <a class="ss-dashboard-goal" href="goals.php"><div class="ss-progress-caption"><strong><?= e($goal['goal_name']) ?></strong><span><?= e($percent) ?>%</span></div><div class="progress" role="progressbar" aria-label="<?= e($goal['goal_name']) ?>" aria-valuenow="<?= e($percent) ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: <?= e($percent) ?>%"></div></div><small><?= money($goal['current_amount']) ?> of <?= money($goal['target_amount']) ?></small></a>
        <?php endforeach; ?>
    </section>
</div>
<div class="ss-table-card">
    <div class="ss-table-card-header"><h5>Recent Activity</h5><span class="ss-table-count">Latest <?= count($recent) ?> entries</span></div>
    <div class="table-responsive"><table class="ss-table"><thead><tr><th>Description</th><th>Type</th><th>Date</th><th class="text-end">Amount</th></tr></thead><tbody>
        <?php if (!$recent): ?><tr><td colspan="4" class="ss-empty-state"><i class="bi bi-receipt"></i><p>No activity yet.</p></td></tr><?php endif; ?>
        <?php foreach ($recent as $row): ?><tr><td><div class="ss-expense-cell"><span class="ss-expense-avatar"><i class="bi <?= $row['type'] === 'Income' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?>"></i></span><a class="ss-expense-name" href="<?= $row['type'] === 'Income' ? 'income.php' : 'expense.php' ?>"><?= e($row['description']) ?></a></div></td><td data-label="Type"><span class="ss-pill"><?= e($row['type']) ?></span></td><td data-label="Date"><?= e(date('M d, Y', strtotime($row['date']))) ?></td><td data-label="Amount" class="text-end <?= $row['type'] === 'Income' ? 'ss-positive' : 'ss-negative' ?>"><?= $row['type'] === 'Income' ? '+' : '-' ?> <?= money($row['amount']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</div>
<?php include __DIR__ . '/includes/page_end.php'; ?>
