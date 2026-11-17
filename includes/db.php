<?php
/**
 * Database connection (one shared PDO object).
 *
 * Textbook link: Elmasri & Navathe, Ch. 11 connects with
 *   $d = DB::connect('mysql://user:pass@host/db');
 * using the PEAR DB library. PEAR DB is no longer maintained, so this
 * platform uses PDO, PHP's built-in replacement. The ideas are the same:
 * one connection string (DSN), error checking after connecting, and
 * "?"/named placeholders so user input is never pasted into SQL text.
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $cfg = require __DIR__ . '/../config.php';

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw on any SQL error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // rows as ['Column' => value]
        PDO::ATTR_EMULATE_PREPARES   => false,                  // real server-side prepared statements
    ];

    try {
        if ($cfg['db_driver'] === 'sqlite') {
            $isNew = !file_exists($cfg['sqlite_path']);
            if (!is_dir(dirname($cfg['sqlite_path']))) {
                mkdir(dirname($cfg['sqlite_path']), 0775, true);
            }
            $pdo = new PDO('sqlite:' . $cfg['sqlite_path'], null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON');
            if ($isNew) {
                $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sqlite.sql'));
            }
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $cfg['db_host'], $cfg['db_port'], $cfg['db_name']
            );
            $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], $options);
        }
    } catch (PDOException $e) {
        // Never show credentials or stack traces to visitors.
        error_log('StudyBuddy DB connection failed: ' . $e->getMessage());
        http_response_code(503);
        $detail = $cfg['debug'] ? '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>' : '';
        exit('<h1>Database unavailable</h1><p>Check your settings in config.local.php '
           . 'and that the database server is running. See README, section "Setup".</p>' . $detail);
    }

    return $pdo;
}

/** Name of the active driver ('mysql' or 'sqlite'), shown in the footer. */
function db_driver(): string
{
    return db()->getAttribute(PDO::ATTR_DRIVER_NAME);
}
