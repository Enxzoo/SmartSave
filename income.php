<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/classes/Finance.php';
$user = require_login();
$finance = new Finance($db, (int) $user['user_id']);
$error = '';
$openModal = isset($_GET['add']) ? 'incomeModal' : '';
$form = ['income_id' => '', 'source' => '', 'amount' => '', 'date' => date('Y-m-d')];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'save_income') {
            $form = array_merge($form, array_intersect_key($_POST, $form));
            $id = empty($_POST['income_id']) ? null : id_value('income_id');
            $source = text_value('source', 'Source');
            $amount = amount_value('amount');
            $date = date_value();
            transaction($db, function () use ($finance, $id, $source, $amount, $date) { $finance->saveIncome($id, $source, $amount, $date); });
            flash($id ? 'Income updated.' : 'Income added.');
        } elseif ($action === 'delete_income') {
            $id = id_value('record_id');
            transaction($db, function () use ($finance, $id) { $finance->deleteIncome($id); });
            flash('Income deleted.');
        } else {
            throw new InvalidArgumentException('Choose a valid action.');
        }
        redirect('income.php');
    } catch (Throwable $exception) {
        $error = write_error($exception);
        $openModal = ($_POST['action'] ?? '') === 'save_income' ? 'incomeModal' : '';
    }
}
$keyword = is_string($_GET['keyword'] ?? '') ? trim($_GET['keyword'] ?? '') : '';
$records = $finance->income($keyword);
$totals = $finance->totals()['income'];
$active = 'income';
$title = 'Income';
include __DIR__ . '/includes/page_start.php';
?>
<div class="ss-topbar">
    <button class="ss-burger" id="ssBurger" aria-label="Toggle sidebar"><i class="bi bi-list"></i></button>
    <div><h1 class="ss-page-title">Income</h1><p class="ss-page-subtitle">Track, add, and manage the money you earn.</p></div>
    <div class="ss-topbar-actions">
        <form method="get" class="ss-search"><i class="bi bi-search"></i><input name="keyword" placeholder="Search income..." aria-label="Search income" value="<?= e($keyword) ?>"></form>
        <button class="btn ss-btn-primary ss-add-btn" data-bs-toggle="modal" data-bs-target="#incomeModal" data-new-form="incomeForm"><i class="bi bi-plus-lg"></i> Add Income</button>
    </div>
</div>
<?php include __DIR__ . '/includes/alerts.php'; ?>
<?php if ($keyword !== ''): ?><div class="ss-search-tag">Showing results for "<strong><?= e($keyword) ?></strong>" <a href="income.php"><i class="bi bi-x-circle"></i> Clear</a></div><?php endif; ?>
<div class="ss-cards-row">
    <div class="ss-card ss-card-highlight"><span class="ss-card-badge">Overview</span><p class="ss-card-label">Total Income</p><h2 class="ss-card-value"><?= money($totals['total']) ?></h2><p class="ss-card-foot">Across all recorded income</p></div>
    <div class="ss-card"><p class="ss-card-label ss-card-label-dark">This Month</p><h2 class="ss-card-value ss-card-value-dark"><?= money($totals['monthly']) ?></h2><p class="ss-card-foot ss-card-foot-dark"><?= date('F Y') ?> earnings</p></div>
    <div class="ss-card"><p class="ss-card-label ss-card-label-dark">Total Records</p><h2 class="ss-card-value ss-card-value-dark"><?= e($totals['records']) ?></h2><p class="ss-card-foot ss-card-foot-dark">Income entries logged</p></div>
</div>
<div class="ss-table-card">
    <div class="ss-table-card-header"><h5>Income Records</h5><span class="ss-table-count"><?= count($records) ?> item<?= count($records) === 1 ? '' : 's' ?></span></div>
    <div class="table-responsive"><table class="ss-table">
        <thead><tr><th>Source</th><th>Date</th><th>User ID</th><th class="text-end">Amount</th><th class="text-center">Actions</th></tr></thead>
        <tbody>
        <?php if (!$records): ?><tr><td colspan="5" class="ss-empty-state"><i class="bi bi-cash-coin"></i><p>No income records found.</p></td></tr><?php endif; ?>
        <?php foreach ($records as $row): ?>
        <tr>
            <td><div class="ss-expense-cell"><span class="ss-expense-avatar"><?= e(strtoupper(substr($row['source'], 0, 1))) ?></span><span class="ss-expense-name"><?= e($row['source']) ?></span></div></td>
            <td data-label="Date"><?= e(date('M d, Y', strtotime($row['date']))) ?></td>
            <td data-label="User">#<?= e($row['user_id']) ?></td>
            <td data-label="Amount" class="text-end ss-positive">+ <?= money($row['amount']) ?></td>
            <td class="text-center"><div class="ss-actions">
                <button class="ss-icon-btn ss-icon-edit" title="Edit income" aria-label="Edit income" data-bs-toggle="modal" data-bs-target="#incomeModal" data-fill="incomeForm" data-record="<?= e(json_encode($row)) ?>"><i class="bi bi-pencil-fill"></i></button>
                <button class="ss-icon-btn ss-icon-delete" title="Delete income" aria-label="Delete income" data-bs-toggle="modal" data-bs-target="#deleteRecordModal" data-delete-id="<?= e($row['income_id']) ?>" data-delete-label="<?= e($row['source']) ?>"><i class="bi bi-trash3-fill"></i></button>
            </div></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<div class="modal fade" id="incomeModal" tabindex="-1" aria-labelledby="incomeModalTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content ss-modal">
    <form method="post" id="incomeForm" data-default-title="Add Income" data-edit-title="Edit Income">
        <?= csrf_field() ?><input type="hidden" name="action" value="save_income"><input type="hidden" name="income_id" value="<?= e($form['income_id']) ?>">
        <div class="modal-header"><div><h5 class="modal-title" id="incomeModalTitle"><?= $form['income_id'] ? 'Edit Income' : 'Add Income' ?></h5><p class="ss-modal-subtitle">Record income for your budget.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
            <?php if ($error && $openModal): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
            <div class="mb-3"><label class="form-label" for="income_source">Source</label><input class="form-control" id="income_source" name="source" maxlength="45" placeholder="e.g. Monthly salary" value="<?= e($form['source']) ?>" required></div>
            <div class="row"><div class="col-sm-6 mb-3"><label class="form-label" for="income_amount">Amount (&#8369;)</label><input class="form-control" id="income_amount" name="amount" type="number" step="0.01" min="0.01" max="99999999.99" placeholder="0.00" value="<?= e($form['amount']) ?>" required></div><div class="col-sm-6 mb-3"><label class="form-label" for="income_date">Date</label><input class="form-control" id="income_date" name="date" type="date" value="<?= e($form['date']) ?>" data-default="<?= date('Y-m-d') ?>" required></div></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn ss-btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn ss-btn-primary" type="submit"><i class="bi bi-check-lg"></i> Save Income</button></div>
    </form>
</div></div></div>
<?php $deleteAction = 'delete_income'; $deleteTitle = 'Delete Income'; include __DIR__ . '/modals/deleteRecord.php'; ?>
<?php include __DIR__ . '/includes/page_end.php'; ?>
