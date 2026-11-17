<?php
/**
 * StudyBuddy configuration.
 *
 * Do NOT put real passwords in this file. Copy config.local.sample.php to
 * config.local.php (ignored by Git) and override the values there, or set
 * the matching environment variables (SB_DB_DRIVER, SB_DB_HOST, ...).
 */

$config = [
    // 'mysql'  = real web database (XAMPP / MAMP / LAMP / hosting)
    // 'sqlite' = zero-setup demo mode, stores data in data/studybuddy.sqlite
    'db_driver'   => getenv('SB_DB_DRIVER') ?: 'mysql',

    'db_host'     => getenv('SB_DB_HOST')   ?: '127.0.0.1',
    'db_port'     => getenv('SB_DB_PORT')   ?: '3306',
    'db_name'     => getenv('SB_DB_NAME')   ?: 'studybuddy',
    'db_user'     => getenv('SB_DB_USER')   ?: 'studybuddy_app',
    'db_pass'     => getenv('SB_DB_PASS')   ?: '',

    'sqlite_path' => __DIR__ . '/data/studybuddy.sqlite',

    // Password for the Tutor Desk (change status, export CSV).
    // Store a HASH, never the plain password. Generate one with:
    //   php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"
    // Default hash below is for the password: tutor123   (change it!)
    'tutor_password_hash' => getenv('SB_TUTOR_HASH')
        ?: '$2y$10$NxpCHqoCco63g6Lx9IDpW.kOOd2MRhE2kKsVY30WgOoN/ko9t0kXW',

    // Show detailed DB errors (true only on your own machine)
    'debug'       => (getenv('SB_DEBUG') ?: '0') === '1',
];

if (file_exists(__DIR__ . '/config.local.php')) {
    $config = array_merge($config, require __DIR__ . '/config.local.php');
}

return $config;
