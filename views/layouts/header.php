<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <meta name="theme-color" content="#198754">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?><?= APP_NAME ?> | <?= APP_FULL_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <!-- Apply theme before render to prevent flash -->
    <script>
        (function(){
            var t = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>
</head>
<body>

<!-- Skip navigation for accessibility -->
<a href="#main-content" class="visually-hidden-focusable position-absolute top-0 start-0 p-2 bg-success text-white" style="z-index:10000">Skip to content</a>

<!-- Loading overlay -->
<div id="loadingOverlay" aria-hidden="true">
    <div class="text-center">
        <div class="spinner-border text-success mb-2" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="text-muted small">Please wait...</div>
    </div>
</div>

<!-- Toast container for notifications -->
<div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>
