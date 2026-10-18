<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../classes/Finance.php';
require_once __DIR__ . '/../classes/Expense.php';

function check(bool $condition, string $label): void
{
    if (!$condition) { throw new RuntimeException($label); }
}

$email = 'qa_tx_' . bin2hex(random_bytes(6)) . '@example.test';
$userId = null;
try {
    $userId = transaction($db, function () use ($db, $email) {
        $stmt = $db->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $stmt->execute(['SmartSave QA Transaction', $email, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
        return (int) $db->lastInsertId();
    });
    $finance = new Finance($db, $userId);
    $expense = new Expense($db, $userId);
    $categoryId = (int) $db->query('SELECT MIN(category_id) FROM categories')->fetchColumn();
    check($categoryId > 0, 'An expense category is required.');
    transaction($db, function () use ($finance, $expense, $userId, $categoryId) {
        $finance->saveIncome(null, 'Committed income', '100.00', date('Y-m-d'));
        $finance->saveGoal(null, 'Committed goal', '100.00', '0.00', date('Y-m-d'));
        $expense->addExpense('Committed expense', '25.00', date('Y-m-d'), $userId, $categoryId);
    });
    check(count($finance->income()) === 1 && count($finance->goals()) === 1 && count($expense->getExpenses()) === 1, 'Committed writes must persist.');
    try {
        transaction($db, function () use ($finance, $expense, $userId, $categoryId) {
            $finance->saveIncome(null, 'Rolled back income', '300.00', date('Y-m-d'));
            $expense->addExpense('Rolled back expense', '50.00', date('Y-m-d'), $userId, $categoryId);
            throw new RuntimeException('Forced failure');
        });
    } catch (RuntimeException $error) {
        check($error->getMessage() === 'Forced failure', 'Unexpected rollback error.');
    }
    check(count($finance->income()) === 1 && count($expense->getExpenses()) === 1, 'A failed multi-write transaction must leave no partial changes.');
    check(!$db->inTransaction(), 'A failed transaction must release its transaction.');
    $goalId = (int) $finance->goals()[0]['goal_id'];
    transaction($db, function () use ($finance, $goalId) { $finance->contribute($goalId, '60.00'); });
    check($finance->goals()[0]['status'] === 'Active', 'A partially funded goal must stay active.');
    try {
        transaction($db, function () use ($finance, $goalId) { $finance->contribute($goalId, '40.01'); });
        throw new RuntimeException('Overfunding must fail.');
    } catch (InvalidArgumentException $error) {
        check($finance->goals()[0]['current_amount'] === '60.00', 'Rejected contributions must roll back.');
    }
    transaction($db, function () use ($finance, $goalId) { $finance->contribute($goalId, '40.00'); });
    check($finance->goals()[0]['current_amount'] === '100.00' && $finance->goals()[0]['status'] === 'Completed', 'A goal must complete exactly at its target.');
    $other = new Finance($db, $userId + 1000000);
    check(!$other->income() && !$other->goals(), 'Other accounts cannot read records.');
    try {
        transaction($db, function () use ($other, $goalId) { $other->deleteGoal($goalId); });
        throw new RuntimeException('Another account must not delete this goal.');
    } catch (InvalidArgumentException $error) {
        check(count($finance->goals()) === 1, 'The owner record must remain intact.');
    }
    $totals = $finance->totals();
    check((float) $totals['income']['total'] === 100.0 && (float) $totals['expenses']['total'] === 25.0, 'Dashboard totals must match the account records.');
    check(count($finance->monthly()) === 6 && count($finance->recent()) === 2, 'Dashboard history must load.');
    echo 'PASS: commit, multi-write rollback, goal contributions, ownership, and summaries.' . PHP_EOL;
} finally {
    if ($db->inTransaction()) { $db->rollBack(); }
    if ($userId !== null) {
        transaction($db, function () use ($db, $userId, $email) {
            foreach (['income', 'expenses', 'savings_goals'] as $table) {
                $stmt = $db->prepare('DELETE FROM ' . $table . ' WHERE user_id = ?');
                $stmt->execute([$userId]);
            }
            $stmt = $db->prepare('DELETE FROM users WHERE user_id = ? AND email = ?');
            $stmt->execute([$userId, $email]);
        });
    }
    session_destroy();
}
