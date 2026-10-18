<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/classes/Finance.php';
$user = require_login();
$finance = new Finance($db, (int) $user['user_id']);
$error = '';
$openModal = isset($_GET['add']) ? 'goalModal' : '';
$form = ['goal_id' => '', 'goal_name' => '', 'target_amount' => '', 'current_amount' => '0.00', 'target_date' => date('Y-m-d')];
$contribution = ['goal_id' => '', 'amount' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'save_goal') {
            $form = array_merge($form, array_intersect_key($_POST, $form));
            $id = empty($_POST['goal_id']) ? null : id_value('goal_id');
            $name = text_value('goal_name', 'Goal name');
            $target = amount_value('target_amount');
            $current = amount_value('current_amount', true);
            $date = date_value('target_date');
            transaction($db, function () use ($finance, $id, $name, $target, $current, $date) { $finance->saveGoal($id, $name, $target, $current, $date); });
            flash($id ? 'Saving goal updated.' : 'Saving goal created.');
        } elseif ($action === 'contribute') {
            $contribution = array_merge($contribution, array_intersect_key($_POST, $contribution));
            $id = id_value('goal_id');
            $amount = amount_value('amount');
            transaction($db, function () use ($finance, $id, $amount) { $finance->contribute($id, $amount); });
            flash('Your contribution has been saved.');
        } elseif ($action === 'delete_goal') {
            $id = id_value('record_id');
            transaction($db, function () use ($finance, $id) { $finance->deleteGoal($id); });
            flash('Saving goal deleted.');
        } else {
            throw new InvalidArgumentException('Choose a valid action.');
        }
        redirect('goals.php');
    } catch (Throwable $exception) {
        $error = write_error($exception);
        $openModal = ($_POST['action'] ?? '') === 'save_goal' ? 'goalModal' : (($_POST['action'] ?? '') === 'contribute' ? 'contributionModal' : '');
    }
}
$keyword = is_string($_GET['keyword'] ?? '') ? trim($_GET['keyword'] ?? '') : '';
$records = $finance->goals($keyword);
$totals = $finance->totals()['goals'];
$active = 'goals';
$title = 'Saving Goals';
include __DIR__ . '/includes/page_start.php';
?>
<div class="ss-topbar">
    <button class="ss-burger" id="ssBurger" aria-label="Toggle sidebar"><i class="bi bi-list"></i></button>
    <div><h1 class="ss-page-title">Saving Goals</h1><p class="ss-page-subtitle">Set your targets and watch your savings grow.</p></div>
    <div class="ss-topbar-actions"><form method="get" class="ss-search"><i class="bi bi-search"></i><input name="keyword" placeholder="Search goals..." aria-label="Search goals" value="<?= e($keyword) ?>"></form><button class="btn ss-btn-primary ss-add-btn" data-bs-toggle="modal" data-bs-target="#goalModal" data-new-form="goalForm"><i class="bi bi-plus-lg"></i> Add Goal</button></div>
</div>
<?php include __DIR__ . '/includes/alerts.php'; ?>
<?php if ($keyword !== ''): ?><div class="ss-search-tag">Showing results for "<strong><?= e($keyword) ?></strong>" <a href="goals.php"><i class="bi bi-x-circle"></i> Clear</a></div><?php endif; ?>
<div class="ss-cards-row">
    <div class="ss-card ss-card-highlight"><span class="ss-card-badge">Overview</span><p class="ss-card-label">Total Saved</p><h2 class="ss-card-value"><?= money($totals['saved']) ?></h2><p class="ss-card-foot">Across all your saving goals</p></div>
    <div class="ss-card"><p class="ss-card-label ss-card-label-dark">Total Target</p><h2 class="ss-card-value ss-card-value-dark"><?= money($totals['target']) ?></h2><p class="ss-card-foot ss-card-foot-dark"><?= e($totals['records']) ?> goal<?= (int) $totals['records'] === 1 ? '' : 's' ?> created</p></div>
    <div class="ss-card"><p class="ss-card-label ss-card-label-dark">Completed Goals</p><h2 class="ss-card-value ss-card-value-dark"><?= e($totals['completed']) ?></h2><p class="ss-card-foot ss-card-foot-dark">Targets you have reached</p></div>
</div>
<div class="ss-table-card">
    <div class="ss-table-card-header"><h5>Your Saving Goals</h5><span class="ss-table-count"><?= count($records) ?> item<?= count($records) === 1 ? '' : 's' ?></span></div>
    <div class="table-responsive"><table class="ss-table">
        <thead><tr><th>Goal</th><th>Progress</th><th>Target Date</th><th>Status</th><th class="text-center">Actions</th></tr></thead>
        <tbody>
        <?php if (!$records): ?><tr><td colspan="5" class="ss-empty-state"><i class="bi bi-flag"></i><p>No saving goals found.</p></td></tr><?php endif; ?>
        <?php foreach ($records as $row): ?>
        <?php $progress = min(100, $row['target_amount'] > 0 ? round($row['current_amount'] / $row['target_amount'] * 100, 1) : 0); $complete = $row['current_amount'] >= $row['target_amount']; $overdue = !$complete && $row['target_date'] < date('Y-m-d'); ?>
        <tr>
            <td><div class="ss-expense-cell"><span class="ss-expense-avatar"><i class="bi bi-flag-fill"></i></span><span class="ss-expense-name"><?= e($row['goal_name']) ?></span></div></td>
            <td data-label="Progress"><div class="ss-goal-progress"><div class="ss-progress-caption"><span><?= money($row['current_amount']) ?> / <?= money($row['target_amount']) ?></span><strong><?= e($progress) ?>%</strong></div><div class="progress" role="progressbar" aria-label="<?= e($row['goal_name']) ?> progress" aria-valuenow="<?= e($progress) ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: <?= e($progress) ?>%"></div></div></div></td>
            <td data-label="Target date"><?= e(date('M d, Y', strtotime($row['target_date']))) ?></td>
            <td data-label="Status"><span class="ss-pill <?= $complete ? 'ss-status-complete' : ($overdue ? 'ss-status-overdue' : '') ?>"><?= $complete ? 'Completed' : ($overdue ? 'Overdue' : 'Active') ?></span></td>
            <td class="text-center"><div class="ss-actions">
                <button class="ss-icon-btn ss-icon-edit" title="Add savings" aria-label="Add savings" data-bs-toggle="modal" data-bs-target="#contributionModal" data-goal-id="<?= e($row['goal_id']) ?>" data-goal-name="<?= e($row['goal_name']) ?>" data-remaining="<?= e(number_format(max(0, $row['target_amount'] - $row['current_amount']), 2, '.', '')) ?>" <?= $complete ? 'disabled' : '' ?>><i class="bi bi-plus-lg"></i></button>
                <button class="ss-icon-btn ss-icon-edit" title="Edit goal" aria-label="Edit goal" data-bs-toggle="modal" data-bs-target="#goalModal" data-fill="goalForm" data-record="<?= e(json_encode($row)) ?>"><i class="bi bi-pencil-fill"></i></button>
                <button class="ss-icon-btn ss-icon-delete" title="Delete goal" aria-label="Delete goal" data-bs-toggle="modal" data-bs-target="#deleteRecordModal" data-delete-id="<?= e($row['goal_id']) ?>" data-delete-label="<?= e($row['goal_name']) ?>"><i class="bi bi-trash3-fill"></i></button>
            </div></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<div class="modal fade" id="goalModal" tabindex="-1" aria-labelledby="goalModalTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content ss-modal">
    <form method="post" id="goalForm" data-default-title="Add Goal" data-edit-title="Edit Goal">
        <?= csrf_field() ?><input type="hidden" name="action" value="save_goal"><input type="hidden" name="goal_id" value="<?= e($form['goal_id']) ?>">
        <div class="modal-header"><div><h5 class="modal-title" id="goalModalTitle"><?= $form['goal_id'] ? 'Edit Goal' : 'Add Goal' ?></h5><p class="ss-modal-subtitle">Give your savings a purpose.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
            <?php if ($error && $openModal === 'goalModal'): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
            <div class="mb-3"><label class="form-label" for="goal_name">Goal Name</label><input class="form-control" id="goal_name" name="goal_name" maxlength="45" placeholder="e.g. Emergency fund" value="<?= e($form['goal_name']) ?>" required></div>
            <div class="row"><div class="col-sm-6 mb-3"><label class="form-label" for="goal_target">Target Amount (&#8369;)</label><input class="form-control" id="goal_target" name="target_amount" type="number" step="0.01" min="0.01" max="99999999.99" value="<?= e($form['target_amount']) ?>" required></div><div class="col-sm-6 mb-3"><label class="form-label" for="goal_current">Saved Amount (&#8369;)</label><input class="form-control" id="goal_current" name="current_amount" type="number" step="0.01" min="0" max="99999999.99" value="<?= e($form['current_amount']) ?>" data-default="0.00" required></div></div>
            <div class="mb-3"><label class="form-label" for="goal_date">Target Date</label><input class="form-control" id="goal_date" name="target_date" type="date" value="<?= e($form['target_date']) ?>" data-default="<?= date('Y-m-d') ?>" required></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn ss-btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn ss-btn-primary"><i class="bi bi-check-lg"></i> Save Goal</button></div>
    </form>
</div></div></div>
<div class="modal fade" id="contributionModal" tabindex="-1" aria-labelledby="contributionTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content ss-modal">
    <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="contribute"><input type="hidden" name="goal_id" id="contribution_goal_id" value="<?= e($contribution['goal_id']) ?>">
        <div class="modal-header"><div><h5 class="modal-title" id="contributionTitle">Add Savings</h5><p class="ss-modal-subtitle" id="contribution_goal_name">Add to your saving goal.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
            <?php if ($error && $openModal === 'contributionModal'): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
            <label class="form-label" for="contribution_amount">Amount (&#8369;)</label><input class="form-control" id="contribution_amount" name="amount" type="number" min="0.01" step="0.01" max="99999999.99" value="<?= e($contribution['amount']) ?>" required>
        </div>
        <div class="modal-footer"><button type="button" class="btn ss-btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn ss-btn-primary" type="submit"><i class="bi bi-plus-lg"></i> Add Savings</button></div>
    </form>
</div></div></div>
<?php $deleteAction = 'delete_goal'; $deleteTitle = 'Delete Saving Goal'; include __DIR__ . '/modals/deleteRecord.php'; ?>
<?php include __DIR__ . '/includes/page_end.php'; ?>
