<?php

require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireLogin();
flashError('Reports are generated automatically from current database records.');
header('Location: index.php');
exit;
