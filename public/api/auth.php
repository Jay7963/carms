<?php

/**
 * Authentication API Endpoint
 *
 * Handles AJAX requests for login, registration, and logout.
 */

// Start session
session_start();

// Include necessary files
require_once __DIR__ . '/../../app/lib/env_loader.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/AuthController.php';

// Load environment variables
loadEnv(__DIR__ . '/../../.env');

// Set response header
header('Content-Type: application/json');

// Initialize controller
$auth_controller = new AuthController($pdo);

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle logout immediately — works for both GET and POST
if ($action === 'logout') {
    session_destroy();
    header('Location: /login');
    exit;
}

try {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        switch ($action) {
            case 'login':
                $response = $auth_controller->login(
                    $data['username'] ?? '',
                    $data['password'] ?? ''
                );
                echo json_encode($response);
                break;

            case 'register':
                $response = $auth_controller->register(
                    $data['username'] ?? '',
                    $data['password'] ?? '',
                    $data['confirm_password'] ?? '',
                    $data['email'] ?? '',
                    $data['first_name'] ?? '',
                    $data['last_name'] ?? '',
                    $data['registration_number'] ?? null,
                    $data['role_id'] ?? 1
                );
                echo json_encode($response);
                break;

            case 'logout':
                $response = $auth_controller->logout();
                echo json_encode($response);
                break;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action.']);
                break;
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
