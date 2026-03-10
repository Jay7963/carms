<?php

/**
 * Club Model
 *
 * Handles all database operations related to clubs/activities.
 */

class Club {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new club
     *
     * @param string $club_name
     * @param string $description
     * @param int $leader_id
     * @return int|false
     */
    public function create($club_name, $description, $leader_id) {
        try {
            $query = "INSERT INTO clubs (club_name, description, leader_id, status) 
                      VALUES (:club_name, :description, :leader_id, 'pending')";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':club_name', $club_name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':leader_id', $leader_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating club: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get club by ID
     *
     * @param int $club_id
     * @return array|false
     */
    public function getById($club_id) {
        try {
            $query = "SELECT * FROM clubs WHERE club_id = :club_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching club: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all approved clubs
     *
     * @return array
     */
    public function getApproved() {
        try {
            $query = "SELECT * FROM clubs WHERE status = 'approved' ORDER BY club_name ASC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching clubs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all clubs (for admin)
     *
     * @return array
     */
    public function getAll() {
        try {
            $query = "SELECT * FROM clubs ORDER BY created_at DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching clubs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get clubs by leader
     *
     * @param int $leader_id
     * @return array
     */
    public function getByLeader($leader_id) {
        try {
            $query = "SELECT * FROM clubs WHERE leader_id = :leader_id ORDER BY created_at DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':leader_id', $leader_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching clubs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update club status
     *
     * @param int $club_id
     * @param string $status
     * @return bool
     */
    public function updateStatus($club_id, $status) {
        try {
            $query = "UPDATE clubs SET status = :status WHERE club_id = :club_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating club status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add member to club
     *
     * @param int $club_id
     * @param int $user_id
     * @return bool
     */
    public function addMember($club_id, $user_id) {
        try {
            $query = "INSERT INTO club_members (club_id, user_id, status) VALUES (:club_id, :user_id, 'pending')";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error adding member: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove member from club
     *
     * @param int $club_id
     * @param int $user_id
     * @return bool
     */
    public function removeMember($club_id, $user_id) {
        try {
            $query = "DELETE FROM club_members WHERE club_id = :club_id AND user_id = :user_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error removing member: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get club members
     *
     * @param int $club_id
     * @return array
     */
    public function getMembers($club_id) {
        try {
            $query = "SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, cm.joined_at 
                      FROM users u 
                      JOIN club_members cm ON u.user_id = cm.user_id 
                      WHERE cm.club_id = :club_id 
                      ORDER BY cm.joined_at DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching members: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if user is member of club
     *
     * @param int $club_id
     * @param int $user_id
     * @return bool
     */
    public function isMember($club_id, $user_id) {
        try {
            $query = "SELECT COUNT(*) FROM club_members WHERE club_id = :club_id AND user_id = :user_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking membership: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get member count for club
     *
     * @param int $club_id
     * @return int
     */
    public function getMemberCount($club_id) {
        try {
            $query = "SELECT COUNT(*) FROM club_members WHERE club_id = :club_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':club_id', $club_id, PDO::PARAM_INT);
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting members: " . $e->getMessage());
            return 0;
        }
    }
}
