<?php
require_once __DIR__ . '/../connection.php';

date_default_timezone_set('Asia/Manila');
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$db = (new Database())->connect();

function e($value): string
{
    return htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
}

function money($value): string
{
    return '&#8369;' . number_format((float) $value, 2);
}

function app_url(string $path): string
{
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    if (basename($base) === 'backend') {
        $base = str_replace('\\', '/', dirname($base));
    }
    return rtrim($base, '/.') . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . app_url($path), true, 303);
    exit;
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST'
        || !is_string($_POST['csrf_token'] ?? null)
        || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        exit('This form has expired. Go back, refresh the page, and try again.');
    }
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function require_login(): array
{
    global $db;
    if (empty($_SESSION['user_id'])) {
        redirect('index.php');
    }
    $stmt = $db->prepare('SELECT user_id, name, email FROM users WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        unset($_SESSION['user_id']);
        redirect('index.php');
    }
    return $user;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION = ['user_id' => $userId, 'csrf_token' => bin2hex(random_bytes(32))];
}

// Every write either completes in full or rolls back before its error is reported.
function transaction(PDO $db, callable $work)
{
    $db->beginTransaction();
    try {
        $result = $work();
        $db->commit();
        return $result;
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}

function text_value(string $key, string $label, int $max = 45): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        throw new InvalidArgumentException('Enter a valid ' . strtolower($label) . '.');
    }
    $value = trim($value);
    $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    if ($value === '' || $length > $max) {
        throw new InvalidArgumentException($label . ' is required and must be at most ' . $max . ' characters.');
    }
    return $value;
}

function amount_value(string $key, bool $allowZero = false): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value) || !preg_match('/^\d{1,8}(\.\d{1,2})?$/D', $value)
        || (!$allowZero && (float) $value <= 0)) {
        throw new InvalidArgumentException('Enter an amount ' . ($allowZero ? 'of zero or more' : 'greater than zero') . ' with at most two decimal places.');
    }
    return number_format((float) $value, 2, '.', '');
}

function date_value(string $key = 'date'): string
{
    $value = $_POST[$key] ?? '';
    $date = is_string($value) ? DateTime::createFromFormat('!Y-m-d', $value) : false;
    if (!$date || $date->format('Y-m-d') !== $value || $date->format('Y') < '1000') {
        throw new InvalidArgumentException('Enter a valid date.');
    }
    return $value;
}

function id_value(string $key): int
{
    $value = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($value === false || $value === null) {
        throw new InvalidArgumentException('Select a valid record.');
    }
    return $value;
}

function write_error(Throwable $error): string
{
    if ($error instanceof InvalidArgumentException) {
        return $error->getMessage();
    }
    if ($error instanceof PDOException && $error->getCode() === '23000') {
        return 'That email is already registered, or a selected record is no longer available.';
    }
    error_log((string) $error);
    return 'Your changes could not be saved. Please try again.';
}
