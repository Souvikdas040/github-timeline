<?php
require_once __DIR__ . '/functions.php';

ini_set('display_errors', 'Off');
ini_set('log_errors', 'On');
ini_set('error_log', __DIR__ . '/cron_errors.log');

sendGitHubUpdatesToSubscribers();

?>