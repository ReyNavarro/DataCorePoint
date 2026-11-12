<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    require_once __DIR__ . '/includes/session.php';
    dcpStartSession();
}
require_once 'includes/auth.php';
require_once 'includes/branding.php';
sendSecurityHeaders();
header('Cache-Control: no-store, private');

$isComplete = isset($_GET['done']) && !empty($_SESSION['logout_complete']);
$error = '';

if ($isComplete) {
    unset($_SESSION['logout_complete']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        $error = 'Your session check expired. Reload this page and try again.';
    } else {
        $username = $_SESSION['username'] ?? 'Unknown user';
        logActivity("User logged out ($username).");

        $_SESSION = [];
        session_destroy();

        require_once __DIR__ . '/includes/session.php';
        dcpStartSession();
        session_regenerate_id(true);
        $_SESSION['logout_complete'] = true;
        flashSuccess('Thank you for using DataCorePoint. You have been logged out.');

        header('Location: logout.php?done=1');
        exit;
    }
}

if (!$isComplete && !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!$isComplete && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isComplete ? 'Thank you' : 'Confirm logout' ?> | DataCorePoint</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/datacore.css?v=<?= filemtime(__DIR__ . '/assets/css/datacore.css') ?>">
    <style>
        .logout-card {
            position: relative;
            max-width: 520px;
        }

        .logout-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: grid;
            width: 2.5rem;
            height: 2.5rem;
            place-items: center;
            border-radius: 50%;
            color: #64748b;
            text-decoration: none;
        }

        .logout-close:hover,
        .logout-close:focus-visible {
            background: #eef2f7;
            color: #15243a;
        }

        .logout-title {
            font-family: "Instrument Serif", Georgia, serif;
            font-size: 2.6rem;
            font-weight: 400;
        }

        .logout-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .75rem;
        }

        .logout-actions .btn {
            min-width: 150px;
        }

        @media (max-width: 575px) {
            .logout-actions,
            .logout-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body class="login-page">
    <video class="login-background-video" autoplay muted loop playsinline preload="metadata" aria-hidden="true">
        <source src="https://d8j0ntlcm91z4.cloudfront.net/user_38xzZboKViGWJOttwIXH07lWA1P/hf_20260328_083109_283f3553-e28f-428b-a723-d639c617eb2b.mp4" type="video/mp4">
    </video>
    <div class="login-video-scrim" aria-hidden="true"></div>

    <main class="perspective-container w-100 d-flex justify-content-center">
        <section class="login-card logout-card card border-0 p-4 p-sm-5 text-center">
            <?php if (!$isComplete): ?>
                <a class="logout-close" href="index.php" aria-label="No, keep me signed in">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </a>
            <?php endif; ?>

            <div class="login-logo"><?php renderBrandMark(); ?></div>
            <h1 class="logout-title mt-4 mb-2">
                <?= $isComplete ? 'Thank you for using DataCorePoint.' : 'Are you sure?' ?>
            </h1>

            <?php if ($isComplete): ?>
                <?= displayFlash() ?>
                <p class="text-muted mb-2">Your session has ended. We appreciate you using our program.</p>
                <p class="small text-muted mb-4">Returning to sign in in <strong id="logoutCountdown">10</strong> seconds.</p>
                <a class="btn btn-primary px-4" href="login.php" id="returnToSignIn">
                    <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Return to sign in now
                </a>
            <?php else: ?>
                <p class="text-muted mb-4">Are you sure you want to log out of your account?</p>
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" class="logout-actions">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button class="btn btn-danger px-4" type="submit">
                        <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Logout
                    </button>
                    <a class="btn btn-outline-secondary px-4" href="index.php">No, keep me signed in</a>
                </form>
            <?php endif; ?>

            <p class="small text-muted mt-4 mb-0">DataCorePoint &mdash; Navarro, Rey Anderson D.</p>
        </section>
    </main>
<?php if ($isComplete): ?>
<script>
(function () {
    var remaining = 10;
    var countdown = document.getElementById('logoutCountdown');
    var timer = window.setInterval(function () {
        remaining -= 1;
        countdown.textContent = String(remaining);

        if (remaining <= 0) {
            window.clearInterval(timer);
            window.location.replace('login.php');
        }
    }, 1000);
}());
</script>
<?php endif; ?>
</body>
</html>
