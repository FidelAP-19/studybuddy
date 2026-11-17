<?php
/**
 * requests.php – Request Board.
 * Reads TUTOR_REQUEST joined with STUDENT and COURSE, with optional
 * filters (course, status, urgency, keyword), sorting and paging.
 * Every filter value goes through a placeholder, never into the SQL text.
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$courses = all_courses();
$f = [
    'course'  => (string) ($_GET['course']  ?? ''),
    'status'  => (string) ($_GET['status']  ?? 'Open'),
    'urgency' => (string) ($_GET['urgency'] ?? ''),
    'q'       => trim((string) ($_GET['q']  ?? '')),
    'sort'    => (string) ($_GET['sort']    ?? 'newest'),
];
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

// ---- Build the WHERE clause from the filters that were chosen ----------
$where  = [];
$params = [];
if (in_array($f['course'], array_column($courses, 'Course_code'), true)) {
    $where[] = 'R.Course_code = :course';   $params[':course'] = $f['course'];
}
if (in_array($f['status'], STATUSES, true)) {
    $where[] = 'R.Status = :status';        $params[':status'] = $f['status'];
} else {
    $f['status'] = '';                       // "All statuses"
}
if (in_array($f['urgency'], URGENCIES, true)) {
    $where[] = 'R.Urgency = :urgency';      $params[':urgency'] = $f['urgency'];
}
if ($f['q'] !== '') {
    $where[] = '(R.Topic LIKE :q1 OR R.Description LIKE :q2 OR S.Fname LIKE :q3)';
    $like = '%' . $f['q'] . '%';
    $params += [':q1' => $like, ':q2' => $like, ':q3' => $like];
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Sort options come from a fixed list (column names cannot be placeholders)
$sorts = [
    'newest'  => 'R.Submitted_at DESC, R.Request_id DESC',
    'oldest'  => 'R.Submitted_at ASC, R.Request_id ASC',
    'urgency' => "CASE R.Urgency WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END, R.Submitted_at ASC",
];
if (!isset($sorts[$f['sort']])) $f['sort'] = 'newest';

$from = 'FROM TUTOR_REQUEST R
         JOIN STUDENT S ON S.Student_id = R.Student_id
         JOIN COURSE  C ON C.Course_code = R.Course_code';

$count = db()->prepare("SELECT COUNT(*) $from $whereSql");
$count->execute($params);
$total = (int) $count->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page  = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare("SELECT R.Request_id, R.Topic, R.Description, R.Urgency, R.Meeting_mode,
                              R.Availability, R.Status, R.Submitted_at, R.Course_code, C.Course_name,
                              S.Fname, S.Lname, S.Year_level
                       $from $whereSql
                       ORDER BY {$sorts[$f['sort']]}
                       LIMIT $perPage OFFSET $offset");   // both are integers we computed
$stmt->execute($params);
$rows = $stmt->fetchAll();

/** Rebuild the query string with one value changed (for paging links). */
$qs = fn(array $change) => '?' . http_build_query(array_merge($f, ['page' => $page], $change));

$pageTitle = 'Request Board';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <h1>Request Board</h1>
  <div class="actions">
    <?php if (is_tutor()): ?>
      <a class="btn" href="export.php<?= e($qs([])) ?>">Export CSV</a>
    <?php endif; ?>
    <a class="btn btn-primary" href="index.php">+ New request</a>
  </div>
</div>

<form method="get" class="card filters">
  <input type="search" name="q" placeholder="Search topic, description, name…" value="<?= e($f['q']) ?>">
  <select name="course">
    <option value="">All courses</option>
    <?php foreach ($courses as $c): ?>
      <option value="<?= e($c['Course_code']) ?>"<?= selected($f['course'] === $c['Course_code']) ?>><?= e($c['Course_code']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status">
    <option value="">All statuses</option>
    <?php foreach (STATUSES as $s): ?><option<?= selected($f['status'] === $s) ?>><?= $s ?></option><?php endforeach; ?>
  </select>
  <select name="urgency">
    <option value="">Any urgency</option>
    <?php foreach (URGENCIES as $u): ?><option<?= selected($f['urgency'] === $u) ?>><?= $u ?></option><?php endforeach; ?>
  </select>
  <select name="sort">
    <option value="newest"<?= selected($f['sort'] === 'newest') ?>>Newest first</option>
    <option value="oldest"<?= selected($f['sort'] === 'oldest') ?>>Oldest first</option>
    <option value="urgency"<?= selected($f['sort'] === 'urgency') ?>>Most urgent first</option>
  </select>
  <button class="btn">Apply</button>
</form>

<p class="muted"><?= $total ?> request<?= $total === 1 ? '' : 's' ?> found.</p>

<?php if (!$rows): ?>
  <div class="card empty">No requests match these filters yet. <a href="index.php">Be the first to ask.</a></div>
<?php else: ?>
  <div class="request-list">
    <?php foreach ($rows as $r): ?>
      <a class="card request urg-<?= strtolower($r['Urgency']) ?>" href="request.php?id=<?= (int) $r['Request_id'] ?>">
        <div class="request-top">
          <span class="course-tag"><?= e($r['Course_code']) ?></span>
          <?= badge($r['Status']) ?> <?= badge($r['Urgency']) ?>
          <span class="muted right">#<?= (int) $r['Request_id'] ?> · <?= e(date('M j, g:i a', strtotime($r['Submitted_at']))) ?></span>
        </div>
        <h3><?= e($r['Topic']) ?></h3>
        <p><?= e(mb_strimwidth($r['Description'], 0, 180, '…')) ?></p>
        <div class="request-meta">
          <span><?= e($r['Fname'] . ' ' . mb_substr($r['Lname'], 0, 1) . '.') ?> · <?= e($r['Year_level']) ?></span>
          <span><?= e($r['Meeting_mode']) ?></span>
          <span><?= e(str_replace(',', ' · ', $r['Availability'])) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($pages > 1): ?>
    <nav class="pager">
      <?php if ($page > 1): ?><a href="<?= e($qs(['page' => $page - 1])) ?>">← Prev</a><?php endif; ?>
      <span>Page <?= $page ?> of <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= e($qs(['page' => $page + 1])) ?>">Next →</a><?php endif; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
