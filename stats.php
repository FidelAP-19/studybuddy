<?php
/**
 * stats.php – live numbers from the database.
 * Uses the COURSE_DEMAND view plus aggregate queries (COUNT, GROUP BY).
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pdo = db();

$totals = $pdo->query("SELECT COUNT(*) AS Total,
                              SUM(CASE WHEN Status = 'Open'    THEN 1 ELSE 0 END) AS Open_n,
                              SUM(CASE WHEN Status = 'Matched' THEN 1 ELSE 0 END) AS Matched_n,
                              SUM(CASE WHEN Status = 'Closed'  THEN 1 ELSE 0 END) AS Closed_n
                         FROM TUTOR_REQUEST")->fetch();
$students = (int) $pdo->query('SELECT COUNT(*) FROM STUDENT')->fetchColumn();

$demand = $pdo->query('SELECT * FROM COURSE_DEMAND ORDER BY Total_requests DESC, Course_code')->fetchAll();
$maxDemand = max(1, ...array_map(fn($d) => (int) $d['Total_requests'], $demand ?: [['Total_requests' => 1]]));

$modes = $pdo->query('SELECT Meeting_mode, COUNT(*) AS N FROM TUTOR_REQUEST
                       GROUP BY Meeting_mode ORDER BY N DESC')->fetchAll();

// Busiest days: Availability is stored as "Mon,Wed,Fri", so count in PHP
$dayCounts = array_fill_keys(DAYS, 0);
foreach ($pdo->query("SELECT Availability FROM TUTOR_REQUEST WHERE Status = 'Open'") as $row) {
    foreach (explode(',', $row['Availability']) as $d) {
        if (isset($dayCounts[$d])) $dayCounts[$d]++;
    }
}
$maxDay = max(1, ...array_values($dayCounts));

$pageTitle = 'Stats';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head"><h1>Platform stats</h1></div>

<div class="tiles">
  <div class="card tile"><span class="tile-n"><?= (int) $totals['Total'] ?></span><span>Total requests</span></div>
  <div class="card tile"><span class="tile-n"><?= (int) $totals['Open_n'] ?></span><span>Open</span></div>
  <div class="card tile"><span class="tile-n"><?= (int) $totals['Matched_n'] ?></span><span>Matched</span></div>
  <div class="card tile"><span class="tile-n"><?= (int) $totals['Closed_n'] ?></span><span>Closed</span></div>
  <div class="card tile"><span class="tile-n"><?= $students ?></span><span>Students</span></div>
</div>

<div class="grid-2">
  <section class="card">
    <h2>Demand by course</h2>
    <p class="muted small">From the <code>COURSE_DEMAND</code> view.</p>
    <table class="bars">
      <?php foreach ($demand as $d): ?>
        <tr>
          <th><a href="requests.php?course=<?= e($d['Course_code']) ?>&status="><?= e($d['Course_code']) ?></a></th>
          <td><div class="bar" style="width: <?= round(100 * $d['Total_requests'] / $maxDemand) ?>%"></div></td>
          <td class="num"><?= (int) $d['Total_requests'] ?> <span class="muted small">(<?= (int) $d['Open_requests'] ?> open, <?= (int) $d['High_urgency'] ?> high)</span></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </section>

  <section class="card">
    <h2>When open requests are free</h2>
    <p class="muted small">Best days to schedule tutoring sessions.</p>
    <table class="bars">
      <?php foreach ($dayCounts as $day => $n): ?>
        <tr><th><?= $day ?></th><td><div class="bar bar-alt" style="width: <?= round(100 * $n / $maxDay) ?>%"></div></td><td class="num"><?= $n ?></td></tr>
      <?php endforeach; ?>
    </table>
    <h2 class="mt">Meeting preference</h2>
    <ul class="plain">
      <?php foreach ($modes as $m): ?><li><?= e($m['Meeting_mode']) ?>: <strong><?= (int) $m['N'] ?></strong></li><?php endforeach; ?>
      <?php if (!$modes): ?><li class="muted">No requests yet.</li><?php endif; ?>
    </ul>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
