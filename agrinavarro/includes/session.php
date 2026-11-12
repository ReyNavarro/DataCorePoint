<?php

/** Start the app session with browser protections before PHP sends its cookie. */
function dcpStartSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (string)getenv('APP_HTTPS') === '1';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    $now = time();
    $lastActivity = (int)($_SESSION['last_activity_at'] ?? $now);
    $sessionStarted = (int)($_SESSION['session_started_at'] ?? $now);
    $idleExpired = ($now - $lastActivity) > 1800;
    $absoluteExpired = ($now - $sessionStarted) > 43200;

    if ($idleExpired || $absoluteExpired) {
        $_SESSION = [];
        session_regenerate_id(true);
        $sessionStarted = $now;
    }

    $_SESSION['session_started_at'] = $sessionStarted;
    $_SESSION['last_activity_at'] = $now;
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Asia/Manila');

set_exception_handler(static function (Throwable $exception): void {
    error_log('Unhandled application exception: ' . $exception->getMessage());

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, private');
    }

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Request could not be completed</title></head><body>'
        . '<main style="max-width:38rem;margin:12vh auto;padding:2rem;font:16px/1.5 system-ui,sans-serif">'
        . '<h1>We could not complete that request.</h1>'
        . '<p>Reload the page and check the record before retrying. If this keeps happening, contact the administrator.</p>'
        . '<a href="./">Return to the module</a>'
        . '</main></body></html>';
});
