<?php
/** @var string $pageTitle  set by each page before including this file */
$current = basename($_SERVER['SCRIPT_NAME']);
$nav = [
    'index.php'    => 'Ask for Help',
    'requests.php' => 'Request Board',
    'stats.php'    => 'Stats',
];
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'StudyBuddy') ?> · StudyBuddy</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
  <div class="wrap header-row">
    <a class="brand" href="index.php"><span class="brand-mark">SB</span> StudyBuddy</a>
    <nav>
      <?php foreach ($nav as $file => $label): ?>
        <a href="<?= $file ?>" class="<?= $current === $file ? 'active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
      <?php if (is_tutor()): ?>
        <a href="logout.php" class="tutor-pill">Tutor Desk · Log out</a>
      <?php else: ?>
        <a href="login.php" class="<?= $current === 'login.php' ? 'active' : '' ?>">Tutor Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="wrap">
<?php foreach (take_flashes() as $flashMsg): ?>
  <div class="flash flash-<?= e($flashMsg["type"]) ?>"><?= e($flashMsg["message"]) ?></div>
<?php endforeach; ?>
