<?php
// $active should be set by the including page: 'dashboard' | 'income' | 'expense' | 'goals'
$active = $active ?? '';
?>
<aside class="ss-sidebar" id="ssSidebar">
    <div class="ss-sidebar-brand">
        <span class="ss-brand-mark"><i class="bi bi-piggy-bank-fill"></i></span>
        <span class="ss-brand-text">SmartSave</span>
    </div>

    <nav class="ss-nav">
        <a href="dashboard.php" class="ss-nav-link <?= $active === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>
        <a href="income.php" class="ss-nav-link <?= $active === 'income' ? 'active' : '' ?>">
            <i class="bi bi-cash-coin"></i>
            <span>Income</span>
        </a>
        <a href="expense.php" class="ss-nav-link <?= $active === 'expense' ? 'active' : '' ?>">
            <i class="bi bi-wallet2"></i>
            <span>Expense</span>
        </a>
        <a href="goals.php" class="ss-nav-link <?= $active === 'goals' ? 'active' : '' ?>">
            <i class="bi bi-flag-fill"></i>
            <span>Saving Goals</span>
        </a>
    </nav>

    <div class="ss-sidebar-footer">
        <a href="account.php" class="ss-user-chip" aria-label="My Account">
            <span class="ss-user-avatar"><i class="bi bi-person-fill"></i></span>
            <div class="ss-user-meta">
                <span class="ss-user-name">My Account</span>
                <span class="ss-user-sub">Personal plan</span>
            </div>
        </a>
    </div>
</aside>
<div class="ss-sidebar-backdrop" id="ssSidebarBackdrop"></div>
