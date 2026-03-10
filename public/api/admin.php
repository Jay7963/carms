<?php
session_start();
require_once __DIR__ . '/../../app/lib/env_loader.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/AdminDashboardController.php';
loadEnv(__DIR__ . '/../../.env');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }

$has_admin = false;
if (isset($_SESSION['roles'])) {
    foreach ($_SESSION['roles'] as $r) { if ($r['role_name'] === 'Administrator') { $has_admin = true; break; } }
}
if (!$has_admin) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }

$ctrl   = new AdminDashboardController($pdo);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET') {
        switch ($action) {
            case 'system_statistics':
                echo json_encode(['success'=>true,'data'=>$ctrl->getSystemStatistics()]); break;
            case 'pending_clubs':
                echo json_encode(['success'=>true,'data'=>$ctrl->getPendingClubs()]); break;
            case 'all_clubs':
                echo json_encode(['success'=>true,'data'=>$ctrl->getAllClubs()]); break;
            case 'all_users':
                echo json_encode(['success'=>true,'data'=>$ctrl->getAllUsers()]); break;
            case 'pending_applications':
                echo json_encode(['success'=>true,'data'=>$ctrl->getAllPendingApplications()]); break;
            case 'leadership_applications':
                echo json_encode(['success'=>true,'data'=>$ctrl->getLeadershipApplications()]); break;
            case 'announcements':
                echo json_encode(['success'=>true,'data'=>$ctrl->getAnnouncements()]); break;
            case 'notifications':
                echo json_encode(['success'=>true,'data'=>$ctrl->getNotifications($_SESSION['user_id'])]); break;
            case 'user_profile':
                echo json_encode(['success'=>true,'data'=>$ctrl->getUserProfile($_GET['user_id'] ?? 0)]); break;
            default: http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid action.']);
        }
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        switch ($action) {
            case 'approve_club':
                echo json_encode($ctrl->approveClub($data['club_id'] ?? 0)); break;
            case 'reject_club':
                echo json_encode($ctrl->rejectClub($data['club_id'] ?? 0)); break;
            case 'delete_club':
                echo json_encode($ctrl->deleteClub($data['club_id'] ?? 0)); break;
            case 'assign_role':
                echo json_encode($ctrl->assignRoleToUser($data['user_id'] ?? 0, $data['role_id'] ?? 0)); break;
            case 'toggle_user':
                echo json_encode($ctrl->toggleUserStatus($data['user_id'] ?? 0, $data['action'] ?? '')); break;
            case 'override_application':
                echo json_encode($ctrl->overrideApplication($data['member_id'] ?? 0, $data['action'] ?? '')); break;
            case 'review_leadership':
                echo json_encode($ctrl->reviewLeadershipApplication($data['application_id'] ?? 0, $data['action'] ?? '', $_SESSION['user_id'])); break;
            case 'create_announcement':
                echo json_encode($ctrl->createAnnouncement($data['title'] ?? '', $data['message'] ?? '', $_SESSION['user_id'])); break;
            case 'mark_notifications_read':
                echo json_encode($ctrl->markNotificationsRead($_SESSION['user_id'])); break;
            default: http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid action.']);
        }
    }
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
