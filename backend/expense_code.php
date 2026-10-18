<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../classes/Expense.php';
$user = require_login();
verify_csrf();
$expense = new Expense($db, (int) $user['user_id']);
try {
    if (isset($_POST['deleteExpense'])) {
        $id = id_value('deleteExpense');
        transaction($db, function () use ($expense, $id) { $expense->deleteExpense($id); });
        flash('Expense deleted.');
    } elseif (isset($_POST['addExpense']) || isset($_POST['updateExpense'])) {
        $description = text_value('description', 'Description');
        $amount = amount_value('amount');
        $date = date_value();
        $categoryId = id_value('category_id');
        $userId = (int) $user['user_id'];
        $updating = isset($_POST['updateExpense']);
        $id = $updating ? id_value('expenses_id') : null;
        transaction($db, function () use ($expense, $description, $amount, $date, $userId, $categoryId, $id, $updating) {
            if ($updating) {
                $expense->updateExpense($id, $description, $amount, $date, $userId, $categoryId);
            } else {
                $expense->addExpense($description, $amount, $date, $userId, $categoryId);
            }
        });
        flash($updating ? 'Expense updated.' : 'Expense added.');
    } else {
        throw new InvalidArgumentException('Choose a valid expense action.');
    }
} catch (Throwable $exception) {
    flash(write_error($exception), 'danger');
}
redirect('expense.php');
