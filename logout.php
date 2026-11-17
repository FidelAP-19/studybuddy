<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

unset($_SESSION['is_tutor']);
session_regenerate_id(true);
flash('success', 'You are logged out of the Tutor Desk.');
redirect('index.php');
