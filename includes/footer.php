</main>
<footer class="site-footer">
  <div class="wrap">
    StudyBuddy · CIS 344 PHP + database project ·
    connected to <strong><?= e(db_driver() === 'mysql' ? 'MySQL' : 'SQLite (demo mode)') ?></strong>
  </div>
</footer>
</body>
</html>
