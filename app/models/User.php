<?php

/**
 * User Model
 *
 * Handles all database operations related to users.
 * Provides methods for creating, reading, updating, and deleting user records.
 */

class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new user
     *
     * @param string $username
     * @param string $password
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @param string $registration_number (optional)
     * @return bool
     */
    public function create($username, $password, $email, $first_name, $last_name, $registration_number = null) {
        try {
            // Hash the password for security
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $query = "INSERT INTO users (username, password, email, first_name, last_name, registration_number) 
                      VALUES (:username, :password, :email, :first_name, :last_name, :registration_number)";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':registration_number', $registration_number);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by username
     *
     * @param string $username
     * @return array|false
     */
    public function getByUsername($username) {
        try {
            $query = "SELECT * FROM users WHERE username = :username";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by ID
     *
     * @param int $user_id
     * @return array|false
     */
    public function getById($user_id) {
        try {
            $query = "SELECT * FROM users WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify password
     *
     * @param string $password
     * @param string $hashed_password
     * @return bool
     */
    public function verifyPassword($password, $hashed_password) {
        return password_verify($password, $hashed_password);
    }

    /**
     * Check if username already exists
     *
     * @param string $username
     * @return bool
     */
    public function usernameExists($username) {
        try {
            $query = "SELECT COUNT(*) FROM users WHERE username = :username";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking username: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if email already exists
     *
     * @param string $email
     * @return bool
     */
    public function emailExists($email) {
        try {
            $query = "SELECT COUNT(*) FROM users WHERE email = :email";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user roles
     *
     * @param int $user_id
     * @return array
     */
    public function getRoles($user_id) {
        try {
            $query = "SELECT r.role_id, r.role_name FROM roles r 
                      JOIN user_roles ur ON r.role_id = ur.role_id 
                      WHERE ur.user_id = :user_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching user roles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Assign role to user
     *
     * @param int $user_id
     * @param int $role_id
     * @return bool
     */
    public function assignRole($user_id, $role_id) {
        try {
            $query = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error assigning role: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user information
     *
     * @param int $user_id
     * @param array $data
     * @return bool
     */
    public function update($user_id, $data) {
        try {
            $allowed_fields = ['first_name', 'last_name', 'email'];
            $updates = [];
            $params = [':user_id' => $user_id];

            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }

            if (empty($updates)) {
                return false;
            }

            $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($query);

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }
}
