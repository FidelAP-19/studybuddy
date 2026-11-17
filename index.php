<?php
/**
 * index.php – "Ask for Help" form.
 *
 * GET  : show the form (course list is loaded from the COURSE table).
 * POST : validate every field on the server, then save the data in ONE
 *        transaction:
 *          1. find the STUDENT by e-mail -> UPDATE it, or INSERT a new one
 *          2. INSERT the TUTOR_REQUEST row that points to that student
 *        On success, redirect to the confirmation page (Post/Redirect/Get).
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$errors  = [];
$courses = all_courses();
$courseCodes = array_column($courses, 'Course_code');

// Default (empty) form values; on a failed POST these become the user's input
$old = [
    'fname' => '', 'lname' => '', 'email' => '', 'major' => '', 'year_level' => '',
    'course_code' => $_GET['course'] ?? '', 'topic' => '', 'description' => '',
    'urgency' => 'Medium', 'meeting_mode' => 'Either', 'availability' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // ---- 1. Collect and trim input -------------------------------------
    foreach ($old as $key => $default) {
        if ($key === 'availability') {
            $old[$key] = array_values(array_filter((array) ($_POST[$key] ?? []), 'is_string'));
        } else {
            $old[$key] = trim((string) ($_POST[$key] ?? ''));
        }
    }
    $old['email'] = strtolower($old['email']);

    // ---- 2. Spam protection ---------------------------------------------
    $honeypot  = (string) ($_POST['website'] ?? '');            // hidden field, humans leave it empty
    $startedAt = (int) ($_SESSION['form_started'] ?? 0);
    $lastSent  = (int) ($_SESSION['last_submit'] ?? 0);
    if ($honeypot !== '' || time() - $startedAt < 3) {
        $errors['form'] = 'That was faster than a human can type. Please try again.';
    } elseif (time() - $lastSent < 20) {
        $errors['form'] = 'You just sent a request. Please wait a few seconds before sending another.';
    }

    // ---- 3. Field validation (server side; never trust the browser) -----
    $namePattern = "/^[\\p{L}][\\p{L} '\\-.]{0,39}$/u";
    if (!preg_match($namePattern, $old['fname']))  $errors['fname'] = 'Enter your first name (letters only, up to 40).';
    if (!preg_match($namePattern, $old['lname']))  $errors['lname'] = 'Enter your last name (letters only, up to 40).';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 100) {
        $errors['email'] = 'Enter a valid e-mail address.';
    }
    if (mb_strlen($old['major']) > 60)             $errors['major'] = 'Major must be 60 characters or fewer.';
    if (!in_array($old['year_level'], YEAR_LEVELS, true))   $errors['year_level'] = 'Choose your year.';
    if (!in_array($old['course_code'], $courseCodes, true)) $errors['course_code'] = 'Choose a course from the list.';
    $topicLen = mb_strlen($old['topic']);
    if ($topicLen < 3 || $topicLen > 120)          $errors['topic'] = 'Topic must be 3 to 120 characters.';
    $descLen = mb_strlen($old['description']);
    if ($descLen < 10 || $descLen > 1000)          $errors['description'] = 'Describe what you need in 10 to 1000 characters.';
    if (!in_array($old['urgency'], URGENCIES, true))         $errors['urgency'] = 'Choose an urgency level.';
    if (!in_array($old['meeting_mode'], MEETING_MODES, true)) $errors['meeting_mode'] = 'Choose a meeting mode.';
    $old['availability'] = array_values(array_intersect(DAYS, $old['availability'])); // keep only valid days, in order
    if (!$old['availability'])                     $errors['availability'] = 'Pick at least one day you are free.';

    // ---- 4. Save to the database ----------------------------------------
    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            // 4a. Does this student already exist? (prepared statement, named placeholder)
            $find = $pdo->prepare('SELECT Student_id FROM STUDENT WHERE Email = :email');
            $find->execute([':email' => $old['email']]);
            $studentId = $find->fetchColumn();

            $studentData = [
                ':fname' => $old['fname'],
                ':lname' => $old['lname'],
                ':major' => $old['major'] === '' ? null : $old['major'],
                ':year'  => $old['year_level'],
                ':email' => $old['email'],
            ];

            if ($studentId) {
                $pdo->prepare('UPDATE STUDENT
                                  SET Fname = :fname, Lname = :lname, Major = :major, Year_level = :year
                                WHERE Email = :email')
                    ->execute($studentData);
            } else {
                $pdo->prepare('INSERT INTO STUDENT (Fname, Lname, Major, Year_level, Email)
                               VALUES (:fname, :lname, :major, :year, :email)')
                    ->execute($studentData);
                $studentId = $pdo->lastInsertId();
            }

            // 4b. The request itself
            $insert = $pdo->prepare('INSERT INTO TUTOR_REQUEST
                    (Student_id, Course_code, Topic, Description, Urgency, Meeting_mode, Availability)
                VALUES (?, ?, ?, ?, ?, ?, ?)');
            $insert->execute([
                (int) $studentId,
                $old['course_code'],
                $old['topic'],
                $old['description'],
                $old['urgency'],
                $old['meeting_mode'],
                implode(',', $old['availability']),
            ]);
            $requestId = (int) $pdo->lastInsertId();

            $pdo->commit();

            $_SESSION['last_submit'] = time();
            $_SESSION['my_requests'][] = $requestId;   // lets the student see their own e-mail on the detail page
            flash('success', 'Your request was saved. A tutor will reach out by e-mail.');
            redirect('request.php?id=' . $requestId . '&new=1');

        } catch (PDOException $ex) {
            $pdo->rollBack();
            error_log('StudyBuddy save failed: ' . $ex->getMessage());
            $errors['form'] = 'Sorry, we could not save your request. Please try again in a minute.';
        }
    }
}

$_SESSION['form_started'] = time();   // start the "typed too fast" timer
$pageTitle = 'Ask for Help';
require __DIR__ . '/includes/header.php';

/** Prints the error message under a field, if there is one. */
$err = fn(string $f) => isset($errors[$f]) ? '<p class="field-error">' . e($errors[$f]) . '</p>' : '';
$inv = fn(string $f) => isset($errors[$f]) ? ' aria-invalid="true"' : '';
?>

<section class="hero">
  <div>
    <p class="eyebrow">Peer tutoring, organized</p>
    <h1>Stuck on something? Ask a classmate who gets it.</h1>
    <p class="lead">Fill out the form once. Your request is saved to the database and appears on the
      <a href="requests.php">Request Board</a>, where student tutors pick it up.</p>
  </div>
</section>

<?php if (isset($errors['form'])): ?>
  <div class="flash flash-error"><?= e($errors['form']) ?></div>
<?php elseif ($errors): ?>
  <div class="flash flash-error">Please fix the <?= count($errors) ?> highlighted field<?= count($errors) > 1 ? 's' : '' ?> below.</div>
<?php endif; ?>

<form method="post" action="index.php" class="card form" novalidate>
  <?= csrf_field() ?>
  <!-- Honeypot: hidden from people, bots tend to fill it in -->
  <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

  <fieldset>
    <legend><span class="step">1</span> About you</legend>
    <div class="grid-2">
      <label>First name
        <input name="fname" maxlength="40" required value="<?= e($old['fname']) ?>"<?= $inv('fname') ?>>
        <?= $err('fname') ?>
      </label>
      <label>Last name
        <input name="lname" maxlength="40" required value="<?= e($old['lname']) ?>"<?= $inv('lname') ?>>
        <?= $err('lname') ?>
      </label>
      <label>E-mail <small>(tutors reply here)</small>
        <input type="email" name="email" maxlength="100" required value="<?= e($old['email']) ?>"<?= $inv('email') ?>>
        <?= $err('email') ?>
      </label>
      <label>Major <small>(optional)</small>
        <input name="major" maxlength="60" value="<?= e($old['major']) ?>"<?= $inv('major') ?>>
        <?= $err('major') ?>
      </label>
      <label>Year
        <select name="year_level" required<?= $inv('year_level') ?>>
          <option value="">Choose…</option>
          <?php foreach (YEAR_LEVELS as $y): ?>
            <option<?= selected($old['year_level'] === $y) ?>><?= $y ?></option>
          <?php endforeach; ?>
        </select>
        <?= $err('year_level') ?>
      </label>
    </div>
  </fieldset>

  <fieldset>
    <legend><span class="step">2</span> What you need</legend>
    <div class="grid-2">
      <label>Course
        <select name="course_code" required<?= $inv('course_code') ?>>
          <option value="">Choose a course…</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= e($c['Course_code']) ?>"<?= selected($old['course_code'] === $c['Course_code']) ?>>
              <?= e($c['Course_code'] . ' – ' . $c['Course_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?= $err('course_code') ?>
      </label>
      <label>Topic
        <input name="topic" maxlength="120" required placeholder="e.g. Normalizing to 3NF"
               value="<?= e($old['topic']) ?>"<?= $inv('topic') ?>>
        <?= $err('topic') ?>
      </label>
    </div>
    <label>Describe the problem
      <textarea name="description" rows="5" maxlength="1000" required data-counter="desc-count"
        placeholder="What have you tried? Where do you get stuck?"<?= $inv('description') ?>><?= e($old['description']) ?></textarea>
      <span class="counter"><span id="desc-count"><?= mb_strlen($old['description']) ?></span>/1000</span>
      <?= $err('description') ?>
    </label>
  </fieldset>

  <fieldset>
    <legend><span class="step">3</span> When and how</legend>
    <div class="grid-2">
      <div>
        <span class="label">Urgency</span>
        <div class="segmented">
          <?php foreach (URGENCIES as $u): ?>
            <label class="seg seg-<?= strtolower($u) ?>"><input type="radio" name="urgency" value="<?= $u ?>"<?= checked($old['urgency'] === $u) ?>><span><?= $u ?></span></label>
          <?php endforeach; ?>
        </div>
        <?= $err('urgency') ?>
      </div>
      <div>
        <span class="label">Meeting mode</span>
        <div class="segmented">
          <?php foreach (MEETING_MODES as $m): ?>
            <label class="seg"><input type="radio" name="meeting_mode" value="<?= $m ?>"<?= checked($old['meeting_mode'] === $m) ?>><span><?= $m ?></span></label>
          <?php endforeach; ?>
        </div>
        <?= $err('meeting_mode') ?>
      </div>
    </div>
    <span class="label">Days you are free</span>
    <div class="days">
      <?php foreach (DAYS as $d): ?>
        <label class="day"><input type="checkbox" name="availability[]" value="<?= $d ?>"<?= checked(in_array($d, $old['availability'], true)) ?>><span><?= $d ?></span></label>
      <?php endforeach; ?>
    </div>
    <?= $err('availability') ?>
  </fieldset>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Save my request</button>
    <span class="hint">Your e-mail is only shown to logged-in tutors.</span>
  </div>
</form>

<script>
  // Live character counter for the description box (works without it, too)
  document.querySelectorAll('[data-counter]').forEach(function (box) {
    var out = document.getElementById(box.dataset.counter);
    box.addEventListener('input', function () { out.textContent = box.value.length; });
  });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
