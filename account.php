<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $action = $_POST['action'] ?? '';
        if (!in_array($action, ['update_profile', 'change_password'], true)) {
            throw new InvalidArgumentException('Choose a valid action.');
        }
        transaction($db, function () use ($db, $user, $action) {
            $stmt = $db->prepare('SELECT password FROM users WHERE user_id = ? FOR UPDATE');
            $stmt->execute([$user['user_id']]);
            $hash = $stmt->fetchColumn();
            $password = $_POST['current_password'] ?? '';
            if (!$hash || !is_string($password) || !password_verify($password, $hash)) {
                throw new InvalidArgumentException('Your current password is incorrect.');
            }
            if ($action === 'update_profile') {
                $name = text_value('name', 'Name');
                $email = strtolower(text_value('email', 'Email'));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Enter a valid email address.');
                }
                $stmt = $db->prepare('UPDATE users SET name = ?, email = ? WHERE user_id = ?');
                $stmt->execute([$name, $email, $user['user_id']]);
            } else {
                $new = $_POST['new_password'] ?? '';
                if (!is_string($new) || strlen($new) < 8 || strlen($new) > 72) {
                    throw new InvalidArgumentException('Use a password between 8 and 72 characters.');
                }
                if ($new !== ($_POST['confirm_password'] ?? null)) {
                    throw new InvalidArgumentException('The new passwords do not match.');
                }
                $stmt = $db->prepare('UPDATE users SET password = ? WHERE user_id = ?');
                $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $user['user_id']]);
            }
        });
        session_regenerate_id(true);
        flash($action === 'update_profile' ? 'Your profile has been updated.' : 'Your password has been changed.');
        redirect('account.php');
    } catch (Throwable $exception) {
        $error = write_error($exception);
    }
}
$active = 'account';
$title = 'My Account';
include __DIR__ . '/includes/page_start.php';
?>
<div class="ss-topbar"><button class="ss-burger" id="ssBurger" aria-label="Toggle sidebar"><i class="bi bi-list"></i></button><div><h1 class="ss-page-title">My Account</h1><p class="ss-page-subtitle">Manage your profile and account security.</p></div><form method="post" action="logout.php"><?= csrf_field() ?><button class="btn ss-btn-secondary" type="submit"><i class="bi bi-box-arrow-right"></i> Log Out</button></form></div>
<?php include __DIR__ . '/includes/alerts.php'; ?>
<div class="ss-account-grid">
    <section class="ss-table-card ss-settings-panel"><div class="ss-table-card-header"><h5>Personal Details</h5><span class="ss-table-count">Account #<?= e($user['user_id']) ?></span></div>
        <form method="post" class="ss-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="update_profile">
            <div class="mb-3"><label class="form-label" for="account_name">Full Name</label><input class="form-control" id="account_name" name="name" maxlength="45" value="<?= e(($_POST['action'] ?? '') === 'update_profile' ? ($_POST['name'] ?? '') : $user['name']) ?>" autocomplete="name" required></div>
            <div class="mb-3"><label class="form-label" for="account_email">Email Address</label><input class="form-control" id="account_email" name="email" type="email" maxlength="45" value="<?= e(($_POST['action'] ?? '') === 'update_profile' ? ($_POST['email'] ?? '') : $user['email']) ?>" autocomplete="email" required></div>
            <div class="mb-4"><label class="form-label" for="profile_password">Current Password</label><input class="form-control" id="profile_password" name="current_password" type="password" autocomplete="current-password" required></div>
            <button class="btn ss-btn-primary" type="submit"><i class="bi bi-check-lg"></i> Save Changes</button>
        </form>
    </section>
    <section class="ss-table-card ss-settings-panel"><div class="ss-table-card-header"><h5>Change Password</h5><i class="bi bi-shield-lock ss-settings-icon"></i></div>
        <form method="post" class="ss-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="change_password">
            <div class="mb-3"><label class="form-label" for="current_password">Current Password</label><input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
            <div class="mb-3"><label class="form-label" for="new_password">New Password</label><div class="ss-password-field"><input class="form-control" id="new_password" name="new_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required><button type="button" class="ss-password-toggle" data-password="new_password" aria-label="Show password" title="Show password"><i class="bi bi-eye"></i></button></div></div>
            <div class="mb-4"><label class="form-label" for="password_confirmation">Confirm New Password</label><input class="form-control" id="password_confirmation" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required></div>
            <button class="btn ss-btn-primary" type="submit"><i class="bi bi-lock"></i> Update Password</button>
        </form>
    </section>
</div>
<?php include __DIR__ . '/includes/page_end.php'; ?>
