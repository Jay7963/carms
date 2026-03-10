<?php
session_start();
require_once __DIR__ . '/../../app/lib/env_loader.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/controllers/ActivityLeaderController.php';
loadEnv(__DIR__ . '/../../.env');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }

$has_leader = false;
if (isset($_SESSION['roles'])) {
    foreach ($_SESSION['roles'] as $r) {
        if (in_array($r['role_name'], ['Activity Leader','Administrator'])) { $has_leader = true; break; }
    }
}
if (!$has_leader) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }

$ctrl    = new ActivityLeaderController($pdo);
$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? '';
$leader_id = $_SESSION['user_id'];

try {
    if ($method === 'GET') {
        switch ($action) {
            case 'my_clubs':
                echo json_encode(['success'=>true,'data'=>$ctrl->getLeaderClubs($leader_id)]); break;
            case 'pending_applications':
                echo json_encode(['success'=>true,'data'=>$ctrl->getPendingApplications($leader_id)]); break;
            case 'club_members':
                echo json_encode(['success'=>true,'data'=>$ctrl->getClubMembers($_GET['club_id'] ?? 0, $leader_id)]); break;
            case 'club_events':
                echo json_encode(['success'=>true,'data'=>$ctrl->getClubEvents($_GET['club_id'] ?? 0, $leader_id)]); break;
            case 'event_registrations':
                echo json_encode(['success'=>true,'data'=>$ctrl->getEventRegistrations($_GET['event_id'] ?? 0, $leader_id)]); break;
            case 'club_details':
                echo json_encode(['success'=>true,'data'=>$ctrl->getClubDetails($_GET['club_id'] ?? 0)]); break;
            case 'notifications':
                echo json_encode(['success'=>true,'data'=>$ctrl->getNotifications($leader_id)]); break;
            case 'club_announcements':
                echo json_encode(['success'=>true,'data'=>$ctrl->getClubAnnouncements($_GET['club_id'] ?? 0, $leader_id)]); break;
            default: http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid action.']);
        }
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        switch ($action) {
            case 'create_club':
                echo json_encode($ctrl->createClub($data['club_name'] ?? '', $data['description'] ?? '', $leader_id, $data['category'] ?? '', $data['icon'] ?? '🏛️')); break;
            case 'delete_club':
                echo json_encode($ctrl->deleteClub($data['club_id'] ?? 0, $leader_id)); break;
            case 'create_event':
                echo json_encode($ctrl->createEvent($data['club_id']??0,$data['event_name']??'',$data['description']??'',$data['event_date']??'',$data['event_time']??'',$data['location']??'',$leader_id)); break;
            case 'delete_event':
                echo json_encode($ctrl->deleteEvent($data['event_id'] ?? 0, $leader_id)); break;
            case 'review_application':
                echo json_encode($ctrl->reviewApplication($data['member_id'] ?? 0, $data['action'] ?? '', $leader_id)); break;
            case 'remove_member':
                echo json_encode($ctrl->removeMember($data['member_id'] ?? 0, $leader_id)); break;
            case 'mark_attendance':
                echo json_encode($ctrl->markAttendance($data['event_id']??0,$data['user_id']??0,$data['status']??'present',$leader_id)); break;
            case 'post_announcement':
                echo json_encode($ctrl->postAnnouncement($data['club_id']??0,$data['title']??'',$data['message']??'',$leader_id)); break;
            case 'mark_notifications_read':
                echo json_encode($ctrl->markNotificationsRead($leader_id)); break;
            default: http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid action.']);
        }
    }
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
