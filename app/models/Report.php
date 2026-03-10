<?php

/**
 * Report Model
 *
 * Handles all database operations related to reports and analytics.
 */

class Report {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Generate participation report
     *
     * @return array
     */
    public function generateParticipationReport() {
        try {
            $query = "SELECT 
                        u.user_id, 
                        u.username, 
                        u.email, 
                        u.first_name, 
                        u.last_name,
                        COUNT(DISTINCT a.event_id) as total_events_attended,
                        COUNT(DISTINCT r.event_id) as total_events_registered
                      FROM users u
                      LEFT JOIN attendance a ON u.user_id = a.user_id
                      LEFT JOIN registrations r ON u.user_id = r.user_id
                      WHERE EXISTS (SELECT 1 FROM user_roles WHERE user_id = u.user_id AND role_id = 1)
                      GROUP BY u.user_id
                      ORDER BY total_events_attended DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error generating participation report: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate club statistics report
     *
     * @return array
     */
    public function generateClubStatisticsReport() {
        try {
            $query = "SELECT 
                        c.club_id,
                        c.club_name,
                        c.status,
                        COUNT(DISTINCT cm.user_id) as member_count,
                        COUNT(DISTINCT e.event_id) as event_count,
                        COUNT(DISTINCT r.registration_id) as total_registrations,
                        COUNT(DISTINCT a.attendance_id) as total_attendance
                      FROM clubs c
                      LEFT JOIN club_members cm ON c.club_id = cm.club_id
                      LEFT JOIN events e ON c.club_id = e.club_id
                      LEFT JOIN registrations r ON e.event_id = r.event_id
                      LEFT JOIN attendance a ON e.event_id = a.event_id
                      GROUP BY c.club_id
                      ORDER BY c.club_name ASC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error generating club statistics report: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate event report
     *
     * @return array
     */
    public function generateEventReport() {
        try {
            $query = "SELECT 
                        e.event_id,
                        e.event_name,
                        c.club_name,
                        e.event_date,
                        e.event_time,
                        COUNT(DISTINCT r.registration_id) as registration_count,
                        COUNT(DISTINCT a.attendance_id) as attendance_count
                      FROM events e
                      JOIN clubs c ON e.club_id = c.club_id
                      LEFT JOIN registrations r ON e.event_id = r.event_id
                      LEFT JOIN attendance a ON e.event_id = a.event_id
                      GROUP BY e.event_id
                      ORDER BY e.event_date DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error generating event report: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user participation details
     *
     * @param int $user_id
     * @return array
     */
    public function getUserParticipationDetails($user_id) {
        try {
            $query = "SELECT 
                        e.event_id,
                        e.event_name,
                        c.club_name,
                        e.event_date,
                        e.event_time,
                        CASE WHEN a.attendance_id IS NOT NULL THEN 'Attended' ELSE 'Registered' END as status
                      FROM registrations r
                      JOIN events e ON r.event_id = e.event_id
                      JOIN clubs c ON e.club_id = c.club_id
                      LEFT JOIN attendance a ON e.event_id = a.event_id AND a.user_id = r.user_id
                      WHERE r.user_id = :user_id
                      ORDER BY e.event_date DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching user participation details: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Save report to database
     *
     * @param string $report_name
     * @param int $generated_by
     * @param array $report_data
     * @return int|false
     */
    public function saveReport($report_name, $generated_by, $report_data) {
        try {
            $report_json = json_encode($report_data);
            $query = "INSERT INTO reports (report_name, generated_by, report_data) 
                      VALUES (:report_name, :generated_by, :report_data)";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':report_name', $report_name);
            $stmt->bindParam(':generated_by', $generated_by, PDO::PARAM_INT);
            $stmt->bindParam(':report_data', $report_json);

            if ($stmt->execute()) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error saving report: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all saved reports
     *
     * @return array
     */
    public function getAllReports() {
        try {
            $query = "SELECT r.*, u.username FROM reports r 
                      JOIN users u ON r.generated_by = u.user_id 
                      ORDER BY r.generated_at DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching reports: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get system statistics
     *
     * @return array
     */
    public function getSystemStatistics() {
        try {
            $stats = [];

            // Total users
            $query = "SELECT COUNT(*) FROM users";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $stats['total_users'] = (int) $stmt->fetchColumn();

            // Total clubs
            $query = "SELECT COUNT(*) FROM clubs";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $stats['total_clubs'] = (int) $stmt->fetchColumn();

            // Approved clubs
            $query = "SELECT COUNT(*) FROM clubs WHERE status = 'approved'";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $stats['approved_clubs'] = (int) $stmt->fetchColumn();

            // Total events
            $query = "SELECT COUNT(*) FROM events";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $stats['total_events'] = (int) $stmt->fetchColumn();

            // Total registrations
            $query = "SELECT COUNT(*) FROM registrations";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $stats['total_registrations'] = (int) $stmt->fetchColumn();

            // Total attendance
            $query = "SELECT COUNT(*) FROM attendance";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $stats['total_attendance'] = (int) $stmt->fetchColumn();

            return $stats;
        } catch (PDOException $e) {
            error_log("Error fetching system statistics: " . $e->getMessage());
            return [];
        }
    }
}
