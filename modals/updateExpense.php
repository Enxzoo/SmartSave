<?php
// Expects $categories (array of category_id, category_name) from expense.php
// This single modal is populated dynamically via JS (assets/js/theme.js)
// using data-* attributes placed on each row's Edit button.
$categories = $categories ?? [];
?>
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ss-modal">

            <form action="backend/expense_code.php" method="POST">
                <?= csrf_field() ?>

                <input type="hidden" name="expenses_id" id="edit_expenses_id">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="editExpenseLabel">Edit Expense</h5>
                        <p class="ss-modal-subtitle">Update the details of this expense.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" name="description" id="edit_description"
                               maxlength="45" required>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <label class="form-label">Amount (₱)</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" name="amount" id="edit_amount"
                                       step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="date" id="edit_date" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" id="edit_category_id" required>
                                <option value="" disabled>Select category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['category_id']) ?>">
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="form-label">User ID</label>
                            <input type="number" class="form-control" name="user_id" id="edit_user_id"
                                   min="1" readonly required>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn ss-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="updateExpense" class="btn ss-btn-primary">
                        <i class="bi bi-check-lg"></i> Save Changes
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
