<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../connection.php';
$db = (new Database())->connect();
$duplicate = $db->query('SELECT COUNT(*) FROM (SELECT email FROM users GROUP BY email HAVING COUNT(*) > 1) AS duplicates')->fetchColumn();
if ($duplicate) {
    throw new RuntimeException('Duplicate account emails must be resolved before setup.');
}
$indexes = $db->query("SHOW INDEX FROM users WHERE Non_unique = 0 AND Column_name = 'email'")->fetchAll();
if (!$indexes) {
    $db->exec('ALTER TABLE users ADD UNIQUE KEY uq_users_email (email)');
}
echo 'Account email uniqueness is ready.' . PHP_EOL;
