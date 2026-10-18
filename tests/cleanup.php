<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../connection.php';
$emails = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
if (!is_array($emails)) { throw new RuntimeException('Expected test email addresses.'); }
$db = (new Database())->connect();
$db->beginTransaction();
try {
    foreach ($emails as $email) {
        if (!is_string($email) || !preg_match('/^qa_[a-z0-9_]+@example\.test$/D', $email)) {
            throw new RuntimeException('Cleanup accepts only generated QA email addresses.');
        }
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND name LIKE 'SmartSave QA%'");
        $stmt->execute([$email]);
        $id = $stmt->fetchColumn();
        if (!$id) { continue; }
        foreach (['income', 'expenses', 'savings_goals'] as $table) {
            $stmt = $db->prepare('DELETE FROM ' . $table . ' WHERE user_id = ?');
            $stmt->execute([$id]);
        }
        $stmt = $db->prepare('DELETE FROM users WHERE user_id = ? AND email = ?');
        $stmt->execute([$id, $email]);
    }
    $db->commit();
    echo 'Temporary QA accounts cleaned.' . PHP_EOL;
} catch (Throwable $error) {
    $db->rollBack();
    throw $error;
}
