<?php
/** login.php – Tutor Desk login (password is checked against a hash in config.php). */

declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$cfg   = require __DIR__ . '/config.php';
// Where to go after logging in. Only pages of this site are allowed
// (blocks "open redirect" tricks like ?next=https://evil.example).
$next = (string) ($_GET['next'] ?? $_POST['next'] ?? 'requests.php');
$path  = basename((string) parse_url($next, PHP_URL_PATH));
$query = (string) parse_url($next, PHP_URL_QUERY);
$next  = preg_match('/^[a-z]+\.php$/', $path) ? $path . ($query !== '' ? '?' . $query : '') : 'requests.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $attempts = $_SESSION['login_attempts'] ?? 0;
    if ($attempts >= 5 && time() - ($_SESSION['login_last'] ?? 0) < 60) {
        $error = 'Too many attempts. Wait one minute and try again.';
    } elseif (password_verify((string) ($_POST['password'] ?? ''), $cfg['tutor_password_hash'])) {
        session_regenerate_id(true);           // new session id after login (prevents session fixation)
        $_SESSION['is_tutor'] = true;
        $_SESSION['login_attempts'] = 0;
        flash('success', 'Welcome to the Tutor Desk. You can now see e-mails, change status and export.');
        redirect($next);
    } else {
        $_SESSION['login_attempts'] = $attempts + 1;
        $_SESSION['login_last'] = time();
        $error = 'Wrong password.';
    }
}

$pageTitle = 'Tutor Login';
require __DIR__ . '/includes/header.php';
?>
<form method="post" class="card form narrow">
  <h1>Tutor Desk</h1>
  <p class="muted">Tutors log in to see contact details, mark requests as matched or closed, and export data.</p>
  <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
  <?= csrf_field() ?>
  <input type="hidden" name="next" value="<?= e($next) ?>">
  <label>Password <input type="password" name="password" required autofocus></label>
  <button class="btn btn-primary">Log in</button>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
