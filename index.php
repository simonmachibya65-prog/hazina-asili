<?php
/**
 * Application Entry Point
 * All users land on researcher dashboard. Admin panel is secret.
 */
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    // Check if admin accessed via secret login (session flag)
    if (isAdmin() && !empty($_SESSION['admin_secret_access'])) {
        redirect('views/admin/dashboard.php');
    } else {
        redirect('views/researcher/dashboard.php');
    }
} else {
    redirect('views/auth/login.php');
}
