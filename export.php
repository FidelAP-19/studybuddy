<?php
/** export.php – download requests as CSV (tutors only). Honors the course/status filters. */

declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_tutor();

$where = [];
$params = [];
if (!empty($_GET['course']) && is_string($_GET['course'])) { $where[] = 'R.Course_code = ?'; $params[] = $_GET['course']; }
if (in_array($_GET['status'] ?? '', STATUSES, true))      { $where[] = 'R.Status = ?';      $params[] = $_GET['status']; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = db()->prepare("SELECT R.Request_id, R.Submitted_at, R.Status, R.Urgency, R.Course_code,
                              R.Topic, R.Description, R.Meeting_mode, R.Availability,
                              S.Fname, S.Lname, S.Email, S.Major, S.Year_level
                         FROM TUTOR_REQUEST R JOIN STUDENT S ON S.Student_id = R.Student_id
                         $whereSql ORDER BY R.Request_id");
$stmt->execute($params);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="studybuddy-requests-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Request ID', 'Submitted', 'Status', 'Urgency', 'Course', 'Topic', 'Description',
               'Meeting', 'Availability', 'First name', 'Last name', 'E-mail', 'Major', 'Year']);
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    // Stop spreadsheet formula injection: cells starting with = + - @ are prefixed with '
    $row = array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $row);
    fputcsv($out, $row);
}
fclose($out);
