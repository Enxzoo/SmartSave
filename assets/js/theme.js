document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll('i.bi').forEach(function (icon) {
        icon.setAttribute('aria-hidden', 'true');
    });

    // ---- Populate Edit Expense modal from the clicked row's data attributes ----
    var editModal = document.getElementById("editExpenseModal");
    if (editModal) {
        editModal.addEventListener("show.bs.modal", function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;

            document.getElementById("edit_expenses_id").value = btn.getAttribute("data-id") || "";
            document.getElementById("edit_description").value = btn.getAttribute("data-description") || "";
            document.getElementById("edit_amount").value = btn.getAttribute("data-amount") || "";
            document.getElementById("edit_date").value = btn.getAttribute("data-date") || "";
            document.getElementById("edit_user_id").value = btn.getAttribute("data-user_id") || "";

            var categorySelect = document.getElementById("edit_category_id");
            if (categorySelect) {
                categorySelect.value = btn.getAttribute("data-category_id") || "";
            }
        });
    }

    // ---- Populate Delete Expense modal + wire up the confirm link ----
    var deleteModal = document.getElementById("deleteExpenseModal");
    if (deleteModal) {
        deleteModal.addEventListener("show.bs.modal", function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;

            var id = btn.getAttribute("data-id") || "";

            document.getElementById("delete_description").textContent = btn.getAttribute("data-description") || "-";
            document.getElementById("delete_amount").textContent = "₱" + (btn.getAttribute("data-amount") || "0.00");
            document.getElementById("delete_date").textContent = btn.getAttribute("data-date") || "-";

            var deleteId = document.getElementById("delete_expenses_id");
            if (deleteId) {
                deleteId.value = id;
            }
        });
    }

    // ---- Mobile sidebar toggle ----
    var burger = document.getElementById("ssBurger");
    var sidebar = document.getElementById("ssSidebar");
    var backdrop = document.getElementById("ssSidebarBackdrop");

    function closeSidebar() {
        sidebar.classList.remove("ss-sidebar-open");
        backdrop.classList.remove("ss-backdrop-visible");
    }

    if (burger && sidebar && backdrop) {
        burger.addEventListener("click", function () {
            sidebar.classList.add("ss-sidebar-open");
            backdrop.classList.add("ss-backdrop-visible");
        });
        backdrop.addEventListener("click", closeSidebar);
    }
});
