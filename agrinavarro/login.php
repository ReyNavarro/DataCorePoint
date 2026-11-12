<?php
require_once __DIR__ . '/includes/session.php';
dcpStartSession();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/branding.php';
sendSecurityHeaders();
header('Cache-Control: no-store, private');
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$error = '';
if (empty($_SESSION['login_csrf_token'])) {
    $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!hash_equals($_SESSION['login_csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        $error = 'Your sign-in form expired. Reload the page and try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            $passwordMatchesHash = $user && password_verify($password, $user['password_hash']);
            if ($passwordMatchesHash) {
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    $rehash = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
                    $rehash->execute([password_hash($password, PASSWORD_DEFAULT), $user['user_id']]);
                }
                session_regenerate_id(true);
                unset($_SESSION['login_csrf_token']);
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                logActivity("User signed in ($username).");
                header('Location: index.php?welcome=1');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Throwable $exception) {
            // Keep connection details and stack traces out of the public login page.
            error_log('Login request could not reach the database: ' . $exception->getMessage());
            $error = 'The database is temporarily unavailable. Please try again in a moment.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sign in | DataCorePoint</title>
    <script>
        try {
            if (localStorage.getItem('datacorepoint-theme') === 'dark') {
                document.documentElement.dataset.theme = 'dark';
            }
        } catch (error) {
            console.warn('Theme preference could not be read; using the default theme.', error);
        }
    </script>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <script>
        tailwind = {
            config: {
                corePlugins: { preflight: false },
                darkMode: "class",
                theme: {
                    extend: {
                        fontFamily: { sans: ["Inter", "sans-serif"], mono: ["JetBrains Mono", "monospace"] },
                        colors: {
                            core: {
                                50: "#eff6ff",
                                100: "#dbeafe",
                                400: "#60a5fa",
                                500: "#3b82f6",
                                600: "#2563eb",
                                900: "#1e3a8a",
                                950: "#172554"
                            }
                        }
                    }
                }
            }
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/datacore.css?v=<?= filemtime(__DIR__ . '/assets/css/datacore.css') ?>">
    <script defer src="assets/js/theme.js?v=<?= filemtime(__DIR__ . '/assets/js/theme.js') ?>"></script>
</head>
<body class="login-page">
    <video class="login-background-video" autoplay muted loop playsinline preload="metadata" aria-hidden="true">
        <source src="https://d8j0ntlcm91z4.cloudfront.net/user_38xzZboKViGWJOttwIXH07lWA1P/hf_20260328_083109_283f3553-e28f-428b-a723-d639c617eb2b.mp4" type="video/mp4">
    </video>
    <div class="login-video-scrim" aria-hidden="true"></div>
    <button class="theme-toggle login-theme-toggle btn btn-outline-secondary" type="button" data-theme-toggle aria-label="Switch to dark mode" title="Switch to dark mode" aria-pressed="false">
        <i class="bi bi-moon-stars" aria-hidden="true"></i>
        <span class="visually-hidden">Toggle light and dark mode</span>
    </button>
    <main class="perspective-container w-100 d-flex justify-content-center">
        <section class="login-card tilt-card card border-0 p-4 p-sm-5">
            <div class="text-center mb-4">
                <div class="login-logo"><?php renderBrandMark(); ?></div>
                <h1 class="login-title mt-3 mb-1">DataCorePoint</h1>
                <p class="text-muted small mb-0">Agricultural Operations Platform</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
                <div class="alert alert-warning">You do not have permission to access that page.</div>
            <?php endif; ?>
            <form method="post" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['login_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-person text-primary"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-lock text-primary"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePwd" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-success w-100 py-2 fw-semibold" id="loginSubmitButton">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>
            <p class="text-center text-muted small mt-4 mb-0">&copy; <?= date('Y') ?> DataCorePoint &mdash; Navarro, Rey Anderson BSIS 3-1</p>
        </section>
    </main>
<script>
document.getElementById('loginForm').addEventListener('submit', function () {
  var button = document.getElementById('loginSubmitButton');
  var loadingOverlay = document.createElement('div');

  button.disabled = true;
  button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Signing in…';

  loadingOverlay.className = 'login-submitting-overlay';
  loadingOverlay.setAttribute('aria-live', 'polite');
  loadingOverlay.setAttribute('aria-busy', 'true');
  loadingOverlay.innerHTML = '<section class="loading-panel"><div class="loading-emblem"><i class="bi bi-grid-1x2-fill" aria-hidden="true"></i></div><div class="loading-title">DataCorePoint</div><p class="loading-stage">Signing in and loading your dashboard…</p><div class="loading-track" role="progressbar" aria-label="Signing in"><div class="loading-progress is-loading"></div></div></section>';
  document.querySelector('.login-card').appendChild(loadingOverlay);
});
</script>
<script>
document.getElementById('togglePwd').addEventListener('click', function () {
    var password = document.getElementById('password');
    var icon = this.querySelector('i');

    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
        this.setAttribute('aria-label', 'Hide password');
    } else {
        password.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
        this.setAttribute('aria-label', 'Show password');
    }
});
</script>
<script>
(function () {
    var card = document.querySelector('.tilt-card');
    if (!card || matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    card.addEventListener('pointermove', function (event) {
        var rect = card.getBoundingClientRect();
        var x = (event.clientX - rect.left) / rect.width - 0.5;
        var y = (event.clientY - rect.top) / rect.height - 0.5;
        card.style.transform = 'rotateX(' + (-y * 4) + 'deg) rotateY(' + (x * 6) + 'deg)';
    });

    card.addEventListener('pointerleave', function () {
        card.style.transform = '';
    });
}());
</script>
</body>
</html>
