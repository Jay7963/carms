<?php

/**
 * CARMS Front Controller
 */

session_start();

require_once __DIR__ . '/../app/lib/env_loader.php';
require_once __DIR__ . '/../app/config/database.php';

loadEnv(__DIR__ . '/../.env');

// ── AUTH HELPERS ─────────────────────────────────────────

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role_name) {
    if (!isset($_SESSION['roles'])) return false;
    foreach ($_SESSION['roles'] as $role) {
        if ($role['role_name'] === $role_name) return true;
    }
    return false;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login');
        exit;
    }
}

function requireRole($role_name) {
    requireLogin();
    if (!hasRole($role_name)) {
        $redirect = '/login?error=unauthorized';
        if (hasRole('Administrator'))       $redirect = '/admin/dashboard?error=unauthorized';
        elseif (hasRole('Activity Leader')) $redirect = '/leader/dashboard?error=unauthorized';
        elseif (isLoggedIn())               $redirect = '/student/dashboard?error=unauthorized';
        header('Location: ' . $redirect);
        exit;
    }
}

// ── ROUTER ───────────────────────────────────────────────

$request_uri = $_SERVER['REQUEST_URI'];
$route = strtok(ltrim($request_uri, '/'), '?');

switch ($route) {
    case '':
    case 'index.php':
    case 'login':
        if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
            $_SESSION['login_error'] = 'You do not have permission to access that portal.';
        }
        require_once __DIR__ . '/../app/views/auth/login.php';
        break;

    case 'register':
        require_once __DIR__ . '/../app/views/auth/register.php';
        break;

    case 'dashboard':
    case 'student/dashboard':
        requireLogin();
        require_once __DIR__ . '/../app/views/student/dashboard.php';
        break;

    case 'student/schedule':
        requireLogin();
        require_once __DIR__ . '/../app/views/student/schedule.php';
        break;

    case 'student/clubs':
        requireLogin();
        require_once __DIR__ . '/../app/views/student/clubs.php';
        break;

    case 'student/history':
        requireLogin();
        require_once __DIR__ . '/../app/views/student/history.php';
        break;

    case 'admin/dashboard':
    case 'admin/approvals':
    case 'admin/users':
    case 'admin/reports':
        requireRole('Administrator');
        require_once __DIR__ . '/../app/views/admin/dashboard.php';
        break;

    case 'leader/dashboard':
    case 'leader/clubs':
        requireRole('Activity Leader');
        require_once __DIR__ . '/../app/views/leader/dashboard.php';
        break;

    default:
        http_response_code(404);
        echo "404 Not Found";
        break;
}