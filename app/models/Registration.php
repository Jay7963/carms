<?php

/**
 * Registration Model
 *
 * Handles all database operations related to event registrations.
 */

class Registration {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Register user for event
     *
     * @param int $event_id
     * @param int $user_id
     * @return bool
     */
    public function register($event_id, $user_id) {
        try {
            // Check if already registered
            if ($this->isRegistered($event_id, $user_id)) {
                return false;
            }

            $query = "INSERT INTO registrations (event_id, user_id) VALUES (:event_id, :user_id)";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error registering for event: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unregister user from event
     *
     * @param int $event_id
     * @param int $user_id
     * @return bool
     */
    public function unregister($event_id, $user_id) {
        try {
            $query = "DELETE FROM registrations WHERE event_id = :event_id AND user_id = :user_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error unregistering from event: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is registered for event
     *
     * @param int $event_id
     * @param int $user_id
     * @return bool
     */
    public function isRegistered($event_id, $user_id) {
        try {
            $query = "SELECT COUNT(*) FROM registrations WHERE event_id = :event_id AND user_id = :user_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking registration: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's registered events
     *
     * @param int $user_id
     * @return array
     */
    public function getUserRegistrations($user_id) {
        try {
            $query = "SELECT e.*, c.club_name FROM registrations r 
                      JOIN events e ON r.event_id = e.event_id 
                      JOIN clubs c ON e.club_id = c.club_id 
                      WHERE r.user_id = :user_id 
                      ORDER BY e.event_date DESC, e.event_time DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching registrations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get event registrations
     *
     * @param int $event_id
     * @return array
     */
    public function getEventRegistrations($event_id) {
        try {
            $query = "SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, r.registered_at 
                      FROM registrations r 
                      JOIN users u ON r.user_id = u.user_id 
                      WHERE r.event_id = :event_id 
                      ORDER BY r.registered_at DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching event registrations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get registration count for event
     *
     * @param int $event_id
     * @return int
     */
    public function getCount($event_id) {
        try {
            $query = "SELECT COUNT(*) FROM registrations WHERE event_id = :event_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting registrations: " . $e->getMessage());
            return 0;
        }
    }
}
