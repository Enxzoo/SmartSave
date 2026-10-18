<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartSave - <?= $signup ? 'Sign Up' : 'Log In' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/css/smartsave.css">
    <link rel="stylesheet" href="assets/css/css/app.css">
</head>
<body class="ss-auth-body">
    <main class="ss-auth-panel">
        <a href="index.php" class="ss-auth-brand"><span class="ss-brand-mark"><i class="bi bi-piggy-bank-fill"></i></span><span class="ss-brand-text">SmartSave</span></a>
        <h1 class="ss-page-title"><?= $signup ? 'Create your account' : 'Welcome back' ?></h1>
        <p class="ss-page-subtitle"><?= $signup ? 'Start saving for what matters to you.' : 'Your finances, all in one place.' ?></p>
        <?php include __DIR__ . '/alerts.php'; ?>
        <form method="post" class="ss-form ss-auth-form">
            <?= csrf_field() ?>
            <?php if ($signup): ?>
            <div class="mb-3"><label class="form-label" for="name">Full Name</label><input id="name" name="name" class="form-control" maxlength="45" autocomplete="name" value="<?= e($_POST['name'] ?? '') ?>" required></div>
            <?php endif; ?>
            <div class="mb-3"><label class="form-label" for="email">Email Address</label><input id="email" name="email" type="email" class="form-control" maxlength="45" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" required></div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <div class="ss-password-field"><input id="password" name="password" type="password" class="form-control" <?= $signup ? 'minlength="8" maxlength="72"' : '' ?> autocomplete="<?= $signup ? 'new-password' : 'current-password' ?>" required><button type="button" class="ss-password-toggle" data-password="password" aria-label="Show password" title="Show password"><i class="bi bi-eye"></i></button></div>
            </div>
            <?php if ($signup): ?>
            <div class="mb-4"><label class="form-label" for="confirm_password">Confirm Password</label><input id="confirm_password" name="confirm_password" type="password" class="form-control" minlength="8" maxlength="72" autocomplete="new-password" required></div>
            <?php endif; ?>
            <button class="btn ss-btn-primary w-100 justify-content-center" type="submit"><i class="bi <?= $signup ? 'bi-person-plus' : 'bi-box-arrow-in-right' ?>"></i><?= $signup ? 'Create Account' : 'Log In' ?></button>
        </form>
        <p class="ss-auth-switch"><?= $signup ? 'Already have an account?' : 'New to SmartSave?' ?> <a href="<?= $signup ? 'index.php' : 'signup.php' ?>"><?= $signup ? 'Log in' : 'Sign up' ?></a></p>
    </main>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
