<?php
/**
 * request.php?id=N – one request in full.
 * Tutors (logged in) also see the student's e-mail and can change the
 * Status (Open -> Matched -> Closed) with an UPDATE statement.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);

// ---- Status change (tutors only, POST + CSRF) ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require_tutor();
    $newStatus = (string) ($_POST['status'] ?? '');
    if (in_array($newStatus, STATUSES, true)) {
        $upd = db()->prepare('UPDATE TUTOR_REQUEST SET Status = :status WHERE Request_id = :id');
        $upd->execute([':status' => $newStatus, ':id' => $id]);
        flash('success', "Request #$id is now marked $newStatus.");
    }
    redirect('request.php?id=' . $id);
}

$stmt = db()->prepare('SELECT R.*, C.Course_name, C.Department,
                              S.Fname, S.Lname, S.Email, S.Major, S.Year_level
                         FROM TUTOR_REQUEST R
                         JOIN STUDENT S ON S.Student_id = R.Student_id
                         JOIN COURSE  C ON C.Course_code = R.Course_code
                        WHERE R.Request_id = ?');
$stmt->execute([$id]);
$r = $stmt->fetch();

if (!$r) {
    http_response_code(404);
    $pageTitle = 'Not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="card empty">Request not found. <a href="requests.php">Back to the board</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Same student's other requests (shows the 1:N STUDENT -> TUTOR_REQUEST relationship)
$others = db()->prepare('SELECT Request_id, Topic, Course_code, Status FROM TUTOR_REQUEST
                          WHERE Student_id = ? AND Request_id <> ? ORDER BY Submitted_at DESC');
$others->execute([$r['Student_id'], $id]);
$others = $others->fetchAll();

$isOwner   = in_array($id, $_SESSION['my_requests'] ?? [], true);
$showEmail = is_tutor() || $isOwner;

$pageTitle = 'Request #' . $id;
require __DIR__ . '/includes/header.php';
?>

<p><a href="requests.php">← Request Board</a></p>

<?php if (isset($_GET['new'])): ?>
  <div class="card saved">
    <strong>Saved to the database.</strong> This is exactly what was stored for request #<?= $id ?>.
    Bookmark this page to check its status later.
  </div>
<?php endif; ?>

<article class="card detail urg-<?= strtolower($r['Urgency']) ?>">
  <div class="request-top">
    <span class="course-tag"><?= e($r['Course_code']) ?></span>
    <?= badge($r['Status']) ?> <?= badge($r['Urgency']) ?>
    <span class="muted right">Submitted <?= e(date('l, M j Y \a\t g:i a', strtotime($r['Submitted_at']))) ?></span>
  </div>
  <h1><?= e($r['Topic']) ?></h1>
  <p class="muted"><?= e($r['Course_name']) ?> · <?= e($r['Department']) ?></p>
  <div class="description"><?= nl2br(e($r['Description'])) ?></div>

  <dl class="facts">
    <div><dt>Student</dt><dd><?= e($r['Fname'] . ' ' . ($showEmail ? $r['Lname'] : mb_substr($r['Lname'], 0, 1) . '.')) ?></dd></div>
    <div><dt>Year / Major</dt><dd><?= e($r['Year_level']) ?><?= $r['Major'] ? ' · ' . e($r['Major']) : '' ?></dd></div>
    <div><dt>Meeting</dt><dd><?= e($r['Meeting_mode']) ?></dd></div>
    <div><dt>Free on</dt><dd><?= e(str_replace(',', ', ', $r['Availability'])) ?></dd></div>
    <div><dt>Contact</dt><dd>
      <?php if ($showEmail): ?>
        <a href="mailto:<?= e($r['Email']) ?>?subject=<?= rawurlencode('StudyBuddy: ' . $r['Topic']) ?>"><?= e($r['Email']) ?></a>
      <?php else: ?>
        <span class="muted">Visible to tutors. <a href="login.php?next=<?= urlencode('request.php?id=' . $id) ?>">Tutor login</a></span>
      <?php endif; ?>
    </dd></div>
  </dl>

  <?php if (is_tutor()): ?>
    <form method="post" class="status-form">
      <?= csrf_field() ?>
      <span class="label">Change status</span>
      <?php foreach (STATUSES as $s): ?>
        <button name="status" value="<?= $s ?>" class="btn<?= $r['Status'] === $s ? ' btn-primary' : '' ?>"<?= $r['Status'] === $s ? ' disabled' : '' ?>><?= $s ?></button>
      <?php endforeach; ?>
    </form>
  <?php endif; ?>
</article>

<?php if ($others): ?>
  <section class="card">
    <h2>Other requests from <?= e($r['Fname']) ?></h2>
    <ul class="plain">
      <?php foreach ($others as $o): ?>
        <li><a href="request.php?id=<?= (int) $o['Request_id'] ?>">#<?= (int) $o['Request_id'] ?> <?= e($o['Topic']) ?></a>
          <span class="muted"><?= e($o['Course_code']) ?></span> <?= badge($o['Status']) ?></li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
