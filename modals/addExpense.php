<?php
// Expects $categories (array of category_id, category_name) from expense.php
$categories = $categories ?? [];
?>
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ss-modal">

            <form action="backend/expense_code.php" method="POST">
                <?= csrf_field() ?>

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="addExpenseLabel">Add Expense</h5>
                        <p class="ss-modal-subtitle">Record a new expense for your budget.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" name="description" maxlength="45"
                               placeholder="e.g. Grocery shopping" required>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <label class="form-label">Amount (₱)</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" name="amount"
                                       step="0.01" min="0.01" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="date" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                <option value="" disabled selected>Select category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['category_id']) ?>">
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="form-label">User ID</label>
                            <input type="number" class="form-control" name="user_id" min="1"
                                   placeholder="e.g. 1" value="<?= e($user['user_id']) ?>" readonly required>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn ss-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="addExpense" class="btn ss-btn-primary">
                        <i class="bi bi-plus-lg"></i> Add Expense
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
