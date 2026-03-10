<?php

/**
 * Student Portal API Endpoint
 *
 * Handles AJAX requests for student portal operations.
 */

// Start session
session_start();

// Include necessary files
require_once __DIR__ . '/../../app/lib/env_loader.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/StudentPortalController.php';

// Load environment variables
loadEnv(__DIR__ . '/../../.env');

// Set response header
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Initialize controller
$student_controller = new StudentPortalController($pdo);

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET') {
        switch ($action) {
            case 'available_clubs':
                $clubs = $student_controller->getAvailableClubs();
                echo json_encode(['success' => true, 'data' => $clubs]);
                break;

            case 'upcoming_events':
                $events = $student_controller->getUpcomingEvents($_SESSION['user_id']);
                echo json_encode(['success' => true, 'data' => $events]);
                break;

            case 'club_events':
                $club_id = $_GET['club_id'] ?? 0;
                $events = $student_controller->getClubEvents($club_id);
                echo json_encode(['success' => true, 'data' => $events]);
                break;

            case 'my_schedule':
                $schedule = $student_controller->getUserSchedule($_SESSION['user_id']);
                echo json_encode(['success' => true, 'data' => $schedule]);
                break;

            case 'participation_history':
                $history = $student_controller->getParticipationHistory($_SESSION['user_id']);
                echo json_encode(['success' => true, 'data' => $history]);
                break;

            case 'my_clubs':
                $clubs = $student_controller->getUserClubs($_SESSION['user_id']);
                echo json_encode(['success' => true, 'data' => $clubs]);
                break;

            case 'search_events':
                $search_term = $_GET['q'] ?? '';
                $events = $student_controller->searchEvents($search_term);
                echo json_encode(['success' => true, 'data' => $events]);
                break;

            case 'notifications':
                try {
                    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
                    $stmt->execute([$_SESSION['user_id']]);
                    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
                } catch (Exception $e) {
                    echo json_encode(['success' => true, 'data' => []]);
                }
                break;

            case 'my_leadership_applications':
                try {
                    $stmt = $pdo->prepare("SELECT la.*, c.club_name, c.category FROM leadership_applications la
                        JOIN clubs c ON la.club_id = c.club_id
                        WHERE la.user_id = ? ORDER BY la.applied_at DESC");
                    $stmt->execute([$_SESSION['user_id']]);
                    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
                } catch (Exception $e) {
                    echo json_encode(['success' => true, 'data' => []]);
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action.']);
                break;
        }
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        switch ($action) {
            case 'register_event':
                $event_id = $data['event_id'] ?? 0;
                $response = $student_controller->registerForEvent($event_id, $_SESSION['user_id']);
                echo json_encode($response);
                break;

            case 'unregister_event':
                $event_id = $data['event_id'] ?? 0;
                $response = $student_controller->unregisterFromEvent($event_id, $_SESSION['user_id']);
                echo json_encode($response);
                break;

            case 'join_club':
                $club_id = $data['club_id'] ?? 0;
                $response = $student_controller->joinClub($club_id, $_SESSION['user_id']);
                echo json_encode($response);
                break;

            case 'leave_club':
                $club_id = $data['club_id'] ?? 0;
                $response = $student_controller->leaveClub($club_id, $_SESSION['user_id']);
                echo json_encode($response);
                break;

            case 'apply_leadership':
                try {
                    $club_id      = $data['club_id'] ?? 0;
                    $why          = trim($data['why_leader'] ?? '');
                    $experience   = trim($data['experience'] ?? '');
                    $vision       = trim($data['vision'] ?? '');
                    $availability = trim($data['availability'] ?? '');
                    $user_id      = $_SESSION['user_id'];

                    if (!$club_id || !$why || !$experience) {
                        echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
                        break;
                    }

                    // Check if already applied
                    $check = $pdo->prepare("SELECT application_id FROM leadership_applications WHERE user_id = ? AND club_id = ? AND status = 'pending'");
                    $check->execute([$user_id, $club_id]);
                    if ($check->fetch()) {
                        echo json_encode(['success' => false, 'message' => 'You already have a pending application for this club.']);
                        break;
                    }

                    $stmt = $pdo->prepare("INSERT INTO leadership_applications (user_id, club_id, why_leader, experience, vision, availability) VALUES (?,?,?,?,?,?)");
                    $stmt->execute([$user_id, $club_id, $why, $experience, $vision, $availability]);

                    // Notify admins
                    $admins = $pdo->query("SELECT u.user_id FROM users u JOIN user_roles ur ON u.user_id = ur.user_id JOIN roles r ON ur.role_id = r.role_id WHERE r.role_name = 'Administrator'");
                    $club   = $pdo->prepare("SELECT club_name FROM clubs WHERE club_id = ?");
                    $club->execute([$club_id]);
                    $clubName = $club->fetchColumn();
                    $user = $pdo->prepare("SELECT CONCAT(first_name,' ',last_name) as name FROM users WHERE user_id = ?");
                    $user->execute([$user_id]);
                    $userName = $user->fetchColumn();
                    foreach ($admins->fetchAll() as $admin) {
                        $notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)");
                        $notif->execute([$admin['user_id'], 'New Leadership Application', "$userName has applied to lead $clubName.", 'info']);
                    }

                    echo json_encode(['success' => true, 'message' => 'Application submitted successfully!']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Failed to submit application: ' . $e->getMessage()]);
                }
                break;

            case 'mark_notifications_read':
                try {
                    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false]);
                }
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
