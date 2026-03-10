<?php

/**
 * Attendance Model
 *
 * Handles all database operations related to event attendance.
 */

class Attendance {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Record attendance for user at event
     *
     * @param int $event_id
     * @param int $user_id
     * @return bool
     */
    public function recordAttendance($event_id, $user_id) {
        try {
            // Check if already recorded
            if ($this->isAttended($event_id, $user_id)) {
                return false;
            }

            $query = "INSERT INTO attendance (event_id, user_id) VALUES (:event_id, :user_id)";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error recording attendance: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user attended event
     *
     * @param int $event_id
     * @param int $user_id
     * @return bool
     */
    public function isAttended($event_id, $user_id) {
        try {
            $query = "SELECT COUNT(*) FROM attendance WHERE event_id = :event_id AND user_id = :user_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking attendance: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get event attendees
     *
     * @param int $event_id
     * @return array
     */
    public function getEventAttendees($event_id) {
        try {
            $query = "SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, a.attended_at 
                      FROM attendance a 
                      JOIN users u ON a.user_id = u.user_id 
                      WHERE a.event_id = :event_id 
                      ORDER BY a.attended_at DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching attendees: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get attendance count for event
     *
     * @param int $event_id
     * @return int
     */
    public function getCount($event_id) {
        try {
            $query = "SELECT COUNT(*) FROM attendance WHERE event_id = :event_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting attendance: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get user's attendance count
     *
     * @param int $user_id
     * @return int
     */
    public function getUserAttendanceCount($user_id) {
        try {
            $query = "SELECT COUNT(*) FROM attendance WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting user attendance: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Remove attendance record
     *
     * @param int $event_id
     * @param int $user_id
     * @return bool
     */
    public function removeAttendance($event_id, $user_id) {
        try {
            $query = "DELETE FROM attendance WHERE event_id = :event_id AND user_id = :user_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error removing attendance: " . $e->getMessage());
            return false;
        }
    }
}
