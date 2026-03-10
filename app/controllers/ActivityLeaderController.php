<?php
/**
 * Activity Leader Controller - Enhanced
 */
class ActivityLeaderController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../models/Club.php';
        require_once __DIR__ . '/../models/Event.php';
        require_once __DIR__ . '/../models/Registration.php';
        require_once __DIR__ . '/../models/Attendance.php';
    }

    // -------------------------------------------------------
    // CLUBS
    // -------------------------------------------------------
    public function createClub($club_name, $description, $leader_id, $category = '', $icon = '🏛️') {
        if (empty($club_name) || empty($description))
            return ['success' => false, 'message' => 'Club name and description are required.'];
        if (empty($category))
            return ['success' => false, 'message' => 'Please select a category.'];
        try {
            $stmt = $this->pdo->prepare("INSERT INTO clubs (club_name, description, leader_id, status, category, icon) VALUES (?,?,?,'pending',?,?)");
            $stmt->execute([trim($club_name), trim($description), $leader_id, trim($category), trim($icon)]);
            return ['success' => true, 'message' => 'Club submitted for admin approval.', 'club_id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create club: ' . $e->getMessage()];
        }
    }

    public function deleteClub($club_id, $leader_id) {
        try {
            // Verify ownership
            $check = $this->pdo->prepare("SELECT club_name FROM clubs WHERE club_id = ? AND leader_id = ?");
            $check->execute([$club_id, $leader_id]);
            $club = $check->fetch();
            if (!$club) return ['success' => false, 'message' => 'Club not found or unauthorized.'];

            $this->pdo->prepare("DELETE FROM club_members WHERE club_id = ?")->execute([$club_id]);
            $this->pdo->prepare("DELETE FROM registrations WHERE event_id IN (SELECT event_id FROM events WHERE club_id = ?)")->execute([$club_id]);
            $this->pdo->prepare("DELETE FROM attendance WHERE event_id IN (SELECT event_id FROM events WHERE club_id = ?)")->execute([$club_id]);
            $this->pdo->prepare("DELETE FROM events WHERE club_id = ?")->execute([$club_id]);
            $this->pdo->prepare("DELETE FROM announcements WHERE club_id = ?")->execute([$club_id]);
            $this->pdo->prepare("DELETE FROM clubs WHERE club_id = ?")->execute([$club_id]);
            return ['success' => true, 'message' => "Club '{$club['club_name']}' deleted successfully."];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete club: ' . $e->getMessage()];
        }
    }

    public function getLeaderClubs($leader_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT c.*,
                (SELECT COUNT(*) FROM club_members WHERE club_id = c.club_id AND status = 'approved') as member_count,
                (SELECT COUNT(*) FROM club_members WHERE club_id = c.club_id AND status = 'pending') as pending_count,
                (SELECT COUNT(*) FROM events WHERE club_id = c.club_id) as event_count
                FROM clubs c WHERE c.leader_id = ? ORDER BY c.club_name");
            $stmt->execute([$leader_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    // -------------------------------------------------------
    // MEMBERSHIP APPLICATIONS
    // -------------------------------------------------------
    public function getPendingApplications($leader_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT cm.*, 
                CONCAT(u.first_name,' ',u.last_name) as student_name,
                u.email, u.registration_number,
                c.club_name, c.category
                FROM club_members cm
                JOIN users u ON cm.user_id = u.user_id
                JOIN clubs c ON cm.club_id = c.club_id
                WHERE c.leader_id = ? AND cm.status = 'pending'
                ORDER BY cm.applied_at ASC");
            $stmt->execute([$leader_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    public function reviewApplication($member_id, $action, $leader_id) {
        try {
            // Verify this leader owns the club
            $check = $this->pdo->prepare("SELECT cm.member_id FROM club_members cm 
                JOIN clubs c ON cm.club_id = c.club_id 
                WHERE cm.member_id = ? AND c.leader_id = ?");
            $check->execute([$member_id, $leader_id]);
            if (!$check->fetch()) return ['success' => false, 'message' => 'Unauthorized.'];

            $status = $action === 'approve' ? 'approved' : 'rejected';
            $stmt = $this->pdo->prepare("UPDATE club_members SET status = ?, reviewed_at = NOW() WHERE member_id = ?");
            $stmt->execute([$status, $member_id]);

            // Notify the student
            $q = $this->pdo->prepare("SELECT cm.user_id, c.club_name FROM club_members cm 
                JOIN clubs c ON cm.club_id = c.club_id WHERE cm.member_id = ?");
            $q->execute([$member_id]);
            $row = $q->fetch();
            if ($row) {
                $title = $status === 'approved' ? 'Application Approved ✅' : 'Application Update';
                $msg   = $status === 'approved'
                    ? "Your application to join {$row['club_name']} has been approved! Welcome aboard."
                    : "Your application to join {$row['club_name']} was not approved this time.";
                $this->createNotification($row['user_id'], $title, $msg, $status === 'approved' ? 'success' : 'danger');
            }
            return ['success' => true, 'message' => 'Application ' . $status . '.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to review application.'];
        }
    }

    // -------------------------------------------------------
    // MEMBERS
    // -------------------------------------------------------
    public function getClubMembers($club_id, $leader_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT cm.*, 
                CONCAT(u.first_name,' ',u.last_name) as student_name,
                u.email, u.registration_number, cm.applied_at
                FROM club_members cm
                JOIN users u ON cm.user_id = u.user_id
                JOIN clubs c ON cm.club_id = c.club_id
                WHERE cm.club_id = ? AND c.leader_id = ? AND cm.status = 'approved'
                ORDER BY u.first_name");
            $stmt->execute([$club_id, $leader_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    public function removeMember($member_id, $leader_id) {
        try {
            $check = $this->pdo->prepare("SELECT cm.member_id FROM club_members cm 
                JOIN clubs c ON cm.club_id = c.club_id 
                WHERE cm.member_id = ? AND c.leader_id = ?");
            $check->execute([$member_id, $leader_id]);
            if (!$check->fetch()) return ['success' => false, 'message' => 'Unauthorized.'];

            $stmt = $this->pdo->prepare("DELETE FROM club_members WHERE member_id = ?");
            $stmt->execute([$member_id]);
            return ['success' => true, 'message' => 'Member removed.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to remove member.'];
        }
    }

    // -------------------------------------------------------
    // EVENTS
    // -------------------------------------------------------
    public function createEvent($club_id, $event_name, $description, $event_date, $event_time, $location, $leader_id) {
        if (empty($event_name) || empty($event_date) || empty($location))
            return ['success' => false, 'message' => 'Please fill all required fields.'];
        try {
            // Verify leader owns this club
            $check = $this->pdo->prepare("SELECT club_id FROM clubs WHERE club_id = ? AND leader_id = ?");
            $check->execute([$club_id, $leader_id]);
            if (!$check->fetch()) return ['success' => false, 'message' => 'Unauthorized.'];

            $stmt = $this->pdo->prepare("INSERT INTO events (club_id, event_name, description, event_date, event_time, location) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$club_id, $event_name, $description, $event_date, $event_time, $location]);
            return ['success' => true, 'message' => 'Event created successfully.', 'event_id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create event.'];
        }
    }

    public function getClubEvents($club_id, $leader_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT e.*,
                (SELECT COUNT(*) FROM registrations WHERE event_id = e.event_id) as registration_count,
                (SELECT COUNT(*) FROM attendance WHERE event_id = e.event_id AND status = 'present') as attendance_count
                FROM events e JOIN clubs c ON e.club_id = c.club_id
                WHERE e.club_id = ? AND c.leader_id = ? ORDER BY e.event_date DESC");
            $stmt->execute([$club_id, $leader_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    public function deleteEvent($event_id, $leader_id) {
        try {
            $check = $this->pdo->prepare("SELECT e.event_id FROM events e 
                JOIN clubs c ON e.club_id = c.club_id WHERE e.event_id = ? AND c.leader_id = ?");
            $check->execute([$event_id, $leader_id]);
            if (!$check->fetch()) return ['success' => false, 'message' => 'Unauthorized.'];
            $stmt = $this->pdo->prepare("DELETE FROM events WHERE event_id = ?");
            $stmt->execute([$event_id]);
            return ['success' => true, 'message' => 'Event deleted.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete event.'];
        }
    }

    // -------------------------------------------------------
    // ATTENDANCE
    // -------------------------------------------------------
    public function getEventRegistrations($event_id, $leader_id) {
        try {
            $check = $this->pdo->prepare("SELECT e.event_id FROM events e 
                JOIN clubs c ON e.club_id = c.club_id WHERE e.event_id = ? AND c.leader_id = ?");
            $check->execute([$event_id, $leader_id]);
            if (!$check->fetch()) return [];

            $stmt = $this->pdo->prepare("SELECT r.registration_id, r.user_id,
                CONCAT(u.first_name,' ',u.last_name) as student_name,
                u.registration_number, u.email,
                COALESCE(a.status, 'not_marked') as attendance_status
                FROM registrations r
                JOIN users u ON r.user_id = u.user_id
                LEFT JOIN attendance a ON a.user_id = r.user_id AND a.event_id = r.event_id
                WHERE r.event_id = ? ORDER BY u.first_name");
            $stmt->execute([$event_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    public function markAttendance($event_id, $user_id, $status, $leader_id) {
        try {
            $check = $this->pdo->prepare("SELECT e.event_id FROM events e 
                JOIN clubs c ON e.club_id = c.club_id WHERE e.event_id = ? AND c.leader_id = ?");
            $check->execute([$event_id, $leader_id]);
            if (!$check->fetch()) return ['success' => false, 'message' => 'Unauthorized.'];

            // Upsert attendance
            $stmt = $this->pdo->prepare("INSERT INTO attendance (event_id, user_id, status) VALUES (?,?,?)
                ON DUPLICATE KEY UPDATE status = ?");
            $stmt->execute([$event_id, $user_id, $status, $status]);
            return ['success' => true, 'message' => 'Attendance marked.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to mark attendance.'];
        }
    }

    // -------------------------------------------------------
    // ANNOUNCEMENTS
    // -------------------------------------------------------
    public function postAnnouncement($club_id, $title, $message, $leader_id) {
        try {
            $check = $this->pdo->prepare("SELECT club_id FROM clubs WHERE club_id = ? AND leader_id = ?");
            $check->execute([$club_id, $leader_id]);
            if (!$check->fetch()) return ['success' => false, 'message' => 'Unauthorized.'];

            $stmt = $this->pdo->prepare("INSERT INTO announcements (title, message, club_id, created_by, scope) VALUES (?,?,?,?,'club')");
            $stmt->execute([$title, $message, $club_id, $leader_id]);

            // Notify all approved club members
            $members = $this->pdo->prepare("SELECT user_id FROM club_members WHERE club_id = ? AND status = 'approved'");
            $members->execute([$club_id]);
            foreach ($members->fetchAll() as $m) {
                $this->createNotification($m['user_id'], "📢 $title", $message, 'info');
            }
            return ['success' => true, 'message' => 'Announcement posted to all club members.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to post announcement.'];
        }
    }

    public function getClubAnnouncements($club_id, $leader_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT a.*, CONCAT(u.first_name,' ',u.last_name) as posted_by
                FROM announcements a JOIN users u ON a.created_by = u.user_id
                WHERE a.club_id = ? ORDER BY a.created_at DESC LIMIT 10");
            $stmt->execute([$club_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    // -------------------------------------------------------
    // NOTIFICATIONS (shared helper)
    // -------------------------------------------------------
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

    private function createNotification($user_id, $title, $message, $type = 'info') {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)");
            $stmt->execute([$user_id, $title, $message, $type]);
        } catch (PDOException $e) { error_log("Notif error: " . $e->getMessage()); }
    }

    public function getClubDetails($club_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM clubs WHERE club_id = ?");
            $stmt->execute([$club_id]);
            return $stmt->fetch();
        } catch (PDOException $e) { return null; }
    }
}
