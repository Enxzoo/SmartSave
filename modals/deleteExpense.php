<div class="modal fade" id="deleteExpenseModal" tabindex="-1" aria-labelledby="deleteExpenseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ss-modal">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="deleteExpenseLabel">Delete Expense</h5>
                    <p class="ss-modal-subtitle">This action cannot be undone.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-3">Are you sure you want to delete the following expense?</p>

                <div class="ss-delete-preview">
                    <div class="ss-delete-row">
                        <span class="ss-delete-label">Description</span>
                        <span class="ss-delete-value" id="delete_description">-</span>
                    </div>
                    <div class="ss-delete-row">
                        <span class="ss-delete-label">Amount</span>
                        <span class="ss-delete-value" id="delete_amount">-</span>
                    </div>
                    <div class="ss-delete-row">
                        <span class="ss-delete-label">Date</span>
                        <span class="ss-delete-value" id="delete_date">-</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn ss-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="backend/expense_code.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="deleteExpense" id="delete_expenses_id">
                    <button type="submit" class="btn ss-btn-danger">
                        <i class="bi bi-trash3"></i> Delete
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
