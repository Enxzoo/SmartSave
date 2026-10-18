<div class="modal fade" id="deleteRecordModal" tabindex="-1" aria-labelledby="deleteRecordTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content ss-modal">
    <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="<?= e($deleteAction) ?>"><input type="hidden" name="record_id" id="delete_record_id">
        <div class="modal-header"><div><h5 class="modal-title" id="deleteRecordTitle"><?= e($deleteTitle) ?></h5><p class="ss-modal-subtitle">This action cannot be undone.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body"><p>Are you sure you want to delete this record?</p><div class="ss-delete-preview"><strong id="delete_record_label"></strong></div></div>
        <div class="modal-footer"><button type="button" class="btn ss-btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn ss-btn-danger"><i class="bi bi-trash3"></i> Delete</button></div>
    </form>
</div></div></div>
