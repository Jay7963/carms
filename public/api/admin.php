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
              try {
                $pdo = (new ReflectionClass($ctrl))->getProperty('pdo');
                $pdo->setAccessible(true);
                $db = $pdo->getValue($ctrl);
                $stmt = $db->query("SELECT cm.member_id, cm.user_id, cm.club_id, cm.status, cm.applied_at,
                CONCAT(u.first_name,' ',u.last_name) as student_name, u.email, u.registration_number,
                c.club_name, c.category,
                CONCAT(l.first_name,' ',l.last_name) as leader_name
                FROM club_members cm
                JOIN users u ON cm.user_id = u.user_id
                JOIN clubs c ON cm.club_id = c.club_id
                LEFT JOIN users l ON c.leader_id = l.user_id
                WHERE cm.status = 'pending'
                ORDER BY cm.applied_at ASC");
            if ($stmt === false) {
            echo json_encode(['success'=>false,'error'=>$db->errorInfo()]);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success'=>true,'data'=>$rows,'count'=>count($rows)]);
        }
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    break;
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
