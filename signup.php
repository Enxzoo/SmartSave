<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $name = text_value('name', 'Name');
        $email = strtolower(text_value('email', 'Email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid email address.');
        }
        $password = $_POST['password'] ?? '';
        if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
            throw new InvalidArgumentException('Use a password between 8 and 72 characters.');
        }
        if ($password !== ($_POST['confirm_password'] ?? null)) {
            throw new InvalidArgumentException('The passwords do not match.');
        }
        $userId = transaction($db, function () use ($db, $name, $email, $password) {
            $stmt = $db->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            return (int) $db->lastInsertId();
        });
        login_user($userId);
        flash('Welcome to SmartSave! Your account is ready.');
        redirect('dashboard.php');
    } catch (Throwable $exception) {
        $error = write_error($exception);
    }
}
$signup = true;
include __DIR__ . '/includes/auth_page.php';
