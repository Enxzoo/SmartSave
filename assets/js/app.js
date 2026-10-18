document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.dataset.password);
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            button.title = show ? 'Hide password' : 'Show password';
            button.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    document.querySelectorAll('.modal').forEach(function (modal) {
        modal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;
            var form;
            if (button.dataset.newForm) {
                form = document.getElementById(button.dataset.newForm);
                Array.from(form.elements).forEach(function (input) {
                    if (!input.name || input.name === 'action' || input.name === 'csrf_token') return;
                    input.value = input.dataset.default || '';
                });
                modal.querySelector('.modal-title').textContent = form.dataset.defaultTitle;
            }
            if (button.dataset.fill) {
                form = document.getElementById(button.dataset.fill);
                var record = JSON.parse(button.dataset.record);
                Object.keys(record).forEach(function (name) {
                    var input = form.elements.namedItem(name);
                    if (input) input.value = record[name];
                });
                modal.querySelector('.modal-title').textContent = form.dataset.editTitle;
            }
            if (button.dataset.deleteId) {
                document.getElementById('delete_record_id').value = button.dataset.deleteId;
                document.getElementById('delete_record_label').textContent = button.dataset.deleteLabel;
            }
            if (button.dataset.goalId) {
                document.getElementById('contribution_goal_id').value = button.dataset.goalId;
                document.getElementById('contribution_goal_name').textContent = button.dataset.goalName;
                var amount = document.getElementById('contribution_amount');
                amount.value = '';
                amount.max = button.dataset.remaining;
            }
        });
    });
});
