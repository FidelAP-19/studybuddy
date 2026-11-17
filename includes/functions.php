<?php
/**
 * Shared helpers: sessions, output escaping, CSRF tokens, flash messages,
 * tutor login, and the fixed option lists used by the form and the database.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

// ---- Session (secure cookie settings) -----------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,          // JavaScript cannot read the cookie
        'samesite' => 'Lax',         // blocks most cross-site form posts
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

// ---- Allowed values (must match the CHECK constraints in the schema) ----
const YEAR_LEVELS   = ['Freshman', 'Sophomore', 'Junior', 'Senior', 'Graduate'];
const URGENCIES     = ['Low', 'Medium', 'High'];
const MEETING_MODES = ['In person', 'Online', 'Either'];
const STATUSES      = ['Open', 'Matched', 'Closed'];
const DAYS          = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

/** Escape text for HTML output (prevents cross-site scripting). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirect and stop. Used for Post/Redirect/Get so a refresh never re-submits. */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

// ---- CSRF protection -----------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Invalid or expired form token. Go back, refresh the page and try again.');
    }
}

// ---- Flash messages (shown once on the next page) ------------------------
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---- Tutor Desk login ----------------------------------------------------
function is_tutor(): bool
{
    return !empty($_SESSION['is_tutor']);
}

function require_tutor(): void
{
    if (!is_tutor()) {
        flash('error', 'Please log in to the Tutor Desk first.');
        redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? 'requests.php'));
    }
}

// ---- Small view helpers --------------------------------------------------
function badge(string $value): string
{
    return '<span class="badge badge-' . e(strtolower(str_replace(' ', '-', $value))) . '">' . e($value) . '</span>';
}

function selected(bool $cond): string { return $cond ? ' selected' : ''; }
function checked(bool $cond): string  { return $cond ? ' checked' : ''; }

/** Courses for drop-down lists, read from the COURSE table. */
function all_courses(): array
{
    return db()->query('SELECT Course_code, Course_name FROM COURSE ORDER BY Course_code')->fetchAll();
}
