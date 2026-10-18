<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $email = text_value('email', 'Email');
        $password = $_POST['password'] ?? '';
        $stmt = $db->prepare('SELECT user_id, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $account = $stmt->fetch();
        if (!$account || !is_string($password) || !password_verify($password, $account['password'])) {
            throw new InvalidArgumentException('The email or password is incorrect.');
        }
        login_user((int) $account['user_id']);
        redirect('dashboard.php');
    } catch (Throwable $exception) {
        $error = write_error($exception);
    }
}
$signup = false;
include __DIR__ . '/includes/auth_page.php';
