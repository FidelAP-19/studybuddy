<?php
// Copy this file to config.local.php and fill in your own values.
// config.local.php is listed in .gitignore, so your password never reaches GitHub.

// MAMP defaults are filled in below (MySQL port 8889, user root, password root).
// XAMPP: use port 3306, user root, empty password ''.

return [
    'db_driver' => 'mysql',        // or 'sqlite' for the demo mode
    'db_host'   => '127.0.0.1',
    'db_port'   => '8889',         // MAMP = 8889, XAMPP = 3306
    'db_name'   => 'studybuddy',
    'db_user'   => 'root',         // or 'studybuddy_app' after running database/create_user.sql
    'db_pass'   => 'root',         // MAMP default; XAMPP default is ''
    'debug'     => true,           // set to false on a public server
];
