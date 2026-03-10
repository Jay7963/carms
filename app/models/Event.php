<?php

/**
 * Event Model
 *
 * Handles all database operations related to events.
 */

class Event {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new event
     *
     * @param int $club_id
     * @param string $event_name
     * @param string $description
     * @param string $event_date
     * @param string $event_time
     * @param string $location
     * @return int|false
     */
    public function create($club_id, $event_name, $description, $event_date, $event_time, $location) {
        try {
            $query = "INSERT INTO events (club_id, event_name, description, event_date, event_time, location) 
                      VALUES (:club_id, :event_name, :description, :event_date, :event_time, :location)";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);
            $stmt->bindParam(':event_name', $event_name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':event_date', $event_date);
            $stmt->bindParam(':event_time', $event_time);
            $stmt->bindParam(':location', $location);

            if ($stmt->execute()) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating event: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get event by ID
     *
     * @param int $event_id
     * @return array|false
     */
    public function getById($event_id) {
        try {
            $query = "SELECT e.*, c.club_name FROM events e 
                      JOIN clubs c ON e.club_id = c.club_id 
                      WHERE e.event_id = :event_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching event: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all events
     *
     * @return array
     */
    public function getAll() {
        try {
            $query = "SELECT e.*, c.club_name FROM events e 
                      JOIN clubs c ON e.club_id = c.club_id 
                      ORDER BY e.event_date DESC, e.event_time DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching events: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get events by club
     *
     * @param int $club_id
     * @return array
     */
    public function getByClub($club_id) {
        try {
            $query = "SELECT * FROM events WHERE club_id = :club_id ORDER BY event_date DESC, event_time DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching events: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get upcoming events
     *
     * @return array
     */
    public function getUpcoming($user_id = null) {
        try {
            $query = "SELECT e.*, c.club_name,
                        COALESCE(e.max_capacity, 0) as max_capacity,
                        (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id) as registered_count,
                        " . ($user_id ? "
                        (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.event_id AND r.user_id = :user_id3) as already_registered,
                        (SELECT COALESCE(cm.status, 'none') FROM club_members cm WHERE cm.club_id = e.club_id AND cm.user_id = :user_id4) as membership_status
                        " : "
                        0 as already_registered,
                        'none' as membership_status
                        ") . "
                      FROM events e
                      JOIN clubs c ON e.club_id = c.club_id
                      WHERE e.event_date >= CURDATE()
                      ORDER BY e.event_date ASC, e.event_time ASC
                      LIMIT 30";

            $stmt = $this->pdo->prepare($query);
            if ($user_id) {
                $stmt->execute([':user_id3' => $user_id, ':user_id4' => $user_id]);
            } else {
                $stmt->execute();
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching upcoming events: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update event
     *
     * @param int $event_id
     * @param array $data
     * @return bool
     */
    public function update($event_id, $data) {
        try {
            $allowed_fields = ['event_name', 'description', 'event_date', 'event_time', 'location'];
            $updates = [];
            $params = [':event_id' => $event_id];

            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }

            if (empty($updates)) {
                return false;
            }

            $query = "UPDATE events SET " . implode(', ', $updates) . " WHERE event_id = :event_id";
            $stmt = $this->pdo->prepare($query);

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating event: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete event
     *
     * @param int $event_id
     * @return bool
     */
    public function delete($event_id) {
        try {
            $query = "DELETE FROM events WHERE event_id = :event_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting event: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get event registration count
     *
     * @param int $event_id
     * @return int
     */
    public function getRegistrationCount($event_id) {
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

    /**
     * Get event attendance count
     *
     * @param int $event_id
     * @return int
     */
    public function getAttendanceCount($event_id) {
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
}
