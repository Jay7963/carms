<?php
/**
 * Administrative Dashboard Controller - Enhanced
 */
class AdminDashboardController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../models/Club.php';
        require_once __DIR__ . '/../models/User.php';
        require_once __DIR__ . '/../models/Report.php';
    }

    // -------------------------------------------------------
    // STATISTICS
    // -------------------------------------------------------
    public function getSystemStatistics() {
        try {
            $stats = [];
            $queries = [
                'total_users'         => "SELECT COUNT(*) FROM users WHERE is_active = 1",
                'total_clubs'         => "SELECT COUNT(*) FROM clubs",
                'approved_clubs'      => "SELECT COUNT(*) FROM clubs WHERE status = 'approved'",
                'pending_clubs'       => "SELECT COUNT(*) FROM clubs WHERE status = 'pending'",
                'rejected_clubs'      => "SELECT COUNT(*) FROM clubs WHERE status = 'rejected'",
                'total_events'        => "SELECT COUNT(*) FROM events",
                'total_registrations' => "SELECT COUNT(*) FROM registrations",
                'total_attendance'    => "SELECT COUNT(*) FROM attendance",
                'pending_applications'=> "SELECT COUNT(*) FROM club_members WHERE status = 'pending'",
            ];
            foreach ($queries as $key => $sql) {
                $stmt = $this->pdo->query($sql);
                $stats[$key] = $stmt->fetchColumn();
            }
            return $stats;
        } catch (PDOException $e) {
            error_log("Stats error: " . $e->getMessage());
            return [];
        }
    }

    // -------------------------------------------------------
    // CLUBS
    // -------------------------------------------------------
    public function getAllClubs() {
        try {
            $stmt = $this->pdo->query("SELECT c.*, CONCAT(u.first_name,' ',u.last_name) as leader_name 
                FROM clubs c LEFT JOIN users u ON c.leader_id = u.user_id ORDER BY c.club_name");
            return $stmt->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    public function getPendingClubs() {
        try {
            $stmt = $this->pdo->query("SELECT c.*, CONCAT(u.first_name,' ',u.last_name) as leader_name 
                FROM clubs c LEFT JOIN users u ON c.leader_id = u.user_id 
                WHERE c.status = 'pending' ORDER BY c.created_at ASC");
            return $stmt->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    public function approveClub($club_id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE clubs SET status = 'approved' WHERE club_id = ?");
            $stmt->execute([$club_id]);
            // Notify the leader
            $club = $this->pdo->prepare("SELECT club_name, leader_id FROM clubs WHERE club_id = ?");
            $club->execute([$club_id]);
            $c = $club->fetch();
            if ($c) $this->createNotification($c['leader_id'], 'Club Approved ✅', "Your club '{$c['club_name']}' has been approved by admin!", 'success');
            return ['success' => true, 'message' => 'Club approved successfully.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to approve club.'];
        }
    }

    public function deleteClub($club_id) {
        try {
            $club = $this->pdo->prepare("SELECT club_name FROM clubs WHERE club_id = ?");
            $club->execute([$club_id]);
            $c = $club->fetch();
            if (!$c) return ['success' => false, 'message' => 'Club not found.'];
            // Delete related data
            $this->pdo->prepare("DELETE FROM club_members WHERE club_id = ?")->execute([$club_id]);
            $this->pdo->prepare("DELETE FROM registrations WHERE event_id IN (SELECT event_id FROM events WHERE club_id = ?)")->execute([$club_id]);
            $this->pdo->prepare("DELETE FROM attendance WHERE event_id IN (SELECT event_id FROM events WHERE club_id = ?)")->execute([$club_id]);
            $this->pdo->prepare("DELETE FROM events WHERE club_id = ?")->execute([$club_id]);
            $this->pdo->prepare("DELETE FROM announcements WHERE club_id = ?")->execute([$club_id]);
            $this->pdo->prepare("DELETE FROM clubs WHERE club_id = ?")->execute([$club_id]);
            return ['success' => true, 'message' => "Club '{$c['club_name']}' deleted."];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete club: ' . $e->getMessage()];
        }
    }

    public function rejectClub($club_id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE clubs SET status = 'rejected' WHERE club_id = ?");
            $stmt->execute([$club_id]);
            $club = $this->pdo->prepare("SELECT club_name, leader_id FROM clubs WHERE club_id = ?");
            $club->execute([$club_id]);
            $c = $club->fetch();
            if ($c) $this->createNotification($c['leader_id'], 'Club Rejected', "Your club '{$c['club_name']}' was not approved. Please contact the admin for more info.", 'danger');
            return ['success' => true, 'message' => 'Club rejected.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to reject club.'];
        }
    }

    // -------------------------------------------------------
    // MEMBERSHIP APPLICATIONS (Admin Override)
    // -------------------------------------------------------
    public function getAllPendingApplications() {
        try {
           $stmt = $this->pdo->query("SELECT cm.member_id, cm.user_id, cm.club_id, cm.status, cm.applied_at,
                CONCAT(u.first_name,' ',u.last_name) as student_name, u.email, u.registration_number,
                c.club_name, c.category,
                CONCAT(l.first_name,' ',l.last_name) as leader_name
                FROM club_members cm
                JOIN users u ON cm.user_id = u.user_id
                JOIN clubs c ON cm.club_id = c.club_id
                LEFT JOIN users l ON c.leader_id = l.user_id
                WHERE cm.status = 'pending'
                ORDER BY cm.applied_at ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    public function overrideApplication($member_id, $action) {
        try {
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $stmt = $this->pdo->prepare("UPDATE club_members SET status = ?, reviewed_at = NOW() WHERE member_id = ?");
            $stmt->execute([$status, $member_id]);

            // Get details for notification
            $q = $this->pdo->prepare("SELECT cm.user_id, c.club_name FROM club_members cm 
                JOIN clubs c ON cm.club_id = c.club_id WHERE cm.member_id = ?");
            $q->execute([$member_id]);
            $row = $q->fetch();
            if ($row) {
                if ($status === 'approved') {
                    $this->createNotification($row['user_id'], 'Application Approved ✅', "Your application to join {$row['club_name']} has been approved!", 'success');
                } else {
                    $this->createNotification($row['user_id'], 'Application Rejected', "Your application to join {$row['club_name']} was not approved.", 'danger');
                }
            }
            return ['success' => true, 'message' => 'Application ' . $status . '.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update application.'];
        }
    }

    // -------------------------------------------------------
    // USERS
    // -------------------------------------------------------
    public function getAllUsers() {
        try {
            $stmt = $this->pdo->query("SELECT u.*, GROUP_CONCAT(r.role_name SEPARATOR ', ') as roles
                FROM users u
                LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.role_id
                GROUP BY u.user_id ORDER BY u.created_at DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    public function assignRoleToUser($user_id, $role_id) {
        try {
            // Remove existing roles
            $del = $this->pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
            $del->execute([$user_id]);
            // Insert new role
            $ins = $this->pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $ins->execute([$user_id, $role_id]);
            // Update role_id in users table
            $upd = $this->pdo->prepare("UPDATE users SET role_id = ? WHERE user_id = ?");
            $upd->execute([$role_id, $user_id]);
            return ['success' => true, 'message' => 'Role assigned successfully.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to assign role.'];
        }
    }

    public function toggleUserStatus($user_id, $action) {
        try {
            $is_active = $action === 'activate' ? 1 : 0;
            $stmt = $this->pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
            $stmt->execute([$is_active, $user_id]);
            $msg = $is_active ? 'Account activated.' : 'Account deactivated.';
            return ['success' => true, 'message' => $msg];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update account status.'];
        }
    }

    public function getUserProfile($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT u.*, GROUP_CONCAT(r.role_name) as roles
                FROM users u LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.role_id WHERE u.user_id = ? GROUP BY u.user_id");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            // Clubs
            $clubs = $this->pdo->prepare("SELECT c.club_name, c.category, cm.status, cm.applied_at 
                FROM club_members cm JOIN clubs c ON cm.club_id = c.club_id WHERE cm.user_id = ?");
            $clubs->execute([$user_id]);
            $user['clubs'] = $clubs->fetchAll();

            // Events
            $events = $this->pdo->prepare("SELECT e.event_name, e.event_date, r.registered_at
                FROM registrations r JOIN events e ON r.event_id = e.event_id WHERE r.user_id = ? ORDER BY e.event_date DESC LIMIT 10");
            $events->execute([$user_id]);
            $user['events'] = $events->fetchAll();

            return $user;
        } catch (PDOException $e) { return null; }
    }

    // -------------------------------------------------------
    // ANNOUNCEMENTS
    // -------------------------------------------------------
    public function createAnnouncement($title, $message, $admin_id, $scope = 'system', $club_id = null) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO announcements (title, message, club_id, created_by, scope) VALUES (?,?,?,?,?)");
            $stmt->execute([$title, $message, $club_id, $admin_id, $scope]);

            // Send notification to all active users
            $users = $this->pdo->query("SELECT user_id FROM users WHERE is_active = 1");
            foreach ($users->fetchAll() as $u) {
                $this->createNotification($u['user_id'], $title, $message, 'info');
            }
            return ['success' => true, 'message' => 'Announcement sent to all users.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to send announcement.'];
        }
    }

    public function getAnnouncements() {
        try {
            $stmt = $this->pdo->query("SELECT a.*, CONCAT(u.first_name,' ',u.last_name) as created_by_name
                FROM announcements a JOIN users u ON a.created_by = u.user_id
                ORDER BY a.created_at DESC LIMIT 20");
            return $stmt->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    // -------------------------------------------------------
    // LEADERSHIP APPLICATIONS
    // -------------------------------------------------------
    public function getLeadershipApplications() {
        try {
            $stmt = $this->pdo->query("SELECT la.*,
                CONCAT(u.first_name,' ',u.last_name) as student_name,
                u.email, u.registration_number,
                c.club_name, c.category
                FROM leadership_applications la
                JOIN users u ON la.user_id = u.user_id
                JOIN clubs c ON la.club_id = c.club_id
                WHERE la.status = 'pending'
                ORDER BY la.applied_at ASC");
            return $stmt->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    public function reviewLeadershipApplication($application_id, $action, $admin_id) {
        try {
            $status = $action === 'approve' ? 'approved' : 'rejected';

            // Get application details
            $q = $this->pdo->prepare("SELECT la.*, c.club_name, c.club_id,
                CONCAT(u.first_name,' ',u.last_name) as student_name
                FROM leadership_applications la
                JOIN clubs c ON la.club_id = c.club_id
                JOIN users u ON la.user_id = u.user_id
                WHERE la.application_id = ?");
            $q->execute([$application_id]);
            $app = $q->fetch();
            if (!$app) return ['success' => false, 'message' => 'Application not found.'];

            // Update application status
            $upd = $this->pdo->prepare("UPDATE leadership_applications SET status = ?, reviewed_at = NOW(), reviewed_by = ? WHERE application_id = ?");
            $upd->execute([$status, $admin_id, $application_id]);

            if ($status === 'approved') {
                // Assign Activity Leader role (role_id = 2)
                $del = $this->pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
                $del->execute([$app['user_id']]);
                $ins = $this->pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, 2)");
                $ins->execute([$app['user_id']]);
                $updUser = $this->pdo->prepare("UPDATE users SET role_id = 2 WHERE user_id = ?");
                $updUser->execute([$app['user_id']]);

                // Assign them as leader of the club
                $updClub = $this->pdo->prepare("UPDATE clubs SET leader_id = ? WHERE club_id = ?");
                $updClub->execute([$app['user_id'], $app['club_id']]);

                // Notify student
                $this->createNotification(
                    $app['user_id'],
                    '🏆 Leadership Application Approved!',
                    "Congratulations! Your application to lead {$app['club_name']} has been approved. You are now an Activity Leader. Log out and log back in to access your leader dashboard.",
                    'success'
                );
            } else {
                // Notify student of rejection
                $this->createNotification(
                    $app['user_id'],
                    'Leadership Application Update',
                    "Your application to lead {$app['club_name']} was not approved this time. You are welcome to apply again in the future.",
                    'danger'
                );
            }

            return ['success' => true, 'message' => "Application {$status} successfully."];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to review application: ' . $e->getMessage()];
        }
    }

    // -------------------------------------------------------
    // NOTIFICATIONS
    // -------------------------------------------------------
    public function createNotification($user_id, $title, $message, $type = 'info') {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)");
            $stmt->execute([$user_id, $title, $message, $type]);
        } catch (PDOException $e) {
            error_log("Notification error: " . $e->getMessage());
        }
    }

    public function getNotifications($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    public function markNotificationsRead($user_id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$user_id]);
            return ['success' => true];
        } catch (PDOException $e) { return ['success' => false]; }
    }

    // -------------------------------------------------------
    // REPORTS (legacy)
    // -------------------------------------------------------
    public function generateParticipationReport() { return []; }
    public function generateClubStatisticsReport() { return []; }
    public function generateEventReport() { return []; }
    public function getAllReports() { return []; }
    public function saveReport($n, $u, $d) { return ['success' => true]; }
    public function exportAsCSV($data) { return ''; }
    public function getUserParticipationDetails($id) { return []; }
}
