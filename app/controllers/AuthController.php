<?php

/**
 * Authentication Controller
 *
 * Handles user authentication including login, registration, and logout.
 * Manages session state and role-based access control.
 */

class AuthController {
    private $pdo;
    private $user_model;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../models/User.php';
        $this->user_model = new User($pdo);
    }

    /**
     * Handle user login
     *
     * @param string $username
     * @param string $password
     * @return array
     */
    public function login($username, $password) {
        $response = ['success' => false, 'message' => '', 'user' => null];

        // Validate input
        if (empty($username) || empty($password)) {
            $response['message'] = 'Username and password are required.';
            return $response;
        }

        // Sanitize username (prevent SQL injection)
        $username = htmlspecialchars(trim($username), ENT_QUOTES, 'UTF-8');

        // Get user from database
        $user = $this->user_model->getByUsername($username);

        if (!$user) {
            $response['message'] = 'Invalid username or password.';
            return $response;
        }

        // Verify password
        if (!$this->user_model->verifyPassword($password, $user['password'])) {
            $response['message'] = 'Invalid username or password.';
            return $response;
        }

        // Get user roles
        $roles = $this->user_model->getRoles($user['user_id']);

        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['roles'] = $roles;

        $response['success'] = true;
        $response['message'] = 'Login successful.';
        $response['user'] = [
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'roles' => $roles
        ];

        return $response;
    }

    /**
     * Handle user registration
     *
     * @param string $username
     * @param string $password
     * @param string $confirm_password
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @param string $registration_number (optional)
     * @param int $role_id
     * @return array
     */
    public function register($username, $password, $confirm_password, $email, $first_name, $last_name, $registration_number = null, $role_id = 1) {
        $response = ['success' => false, 'message' => ''];

        // Validate input
        if (empty($username) || empty($password) || empty($email) || empty($first_name) || empty($last_name)) {
            $response['message'] = 'All fields are required.';
            return $response;
        }

        // Validate password match
        if ($password !== $confirm_password) {
            $response['message'] = 'Passwords do not match.';
            return $response;
        }

        // Validate password strength
        if (strlen($password) < 8) {
            $response['message'] = 'Password must be at least 8 characters long.';
            return $response;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Invalid email format.';
            return $response;
        }

        // Sanitize inputs
        $username = htmlspecialchars(trim($username), ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars(trim($email), ENT_QUOTES, 'UTF-8');
        $first_name = htmlspecialchars(trim($first_name), ENT_QUOTES, 'UTF-8');
        $last_name = htmlspecialchars(trim($last_name), ENT_QUOTES, 'UTF-8');

        // Check if username already exists
        if ($this->user_model->usernameExists($username)) {
            $response['message'] = 'Username already exists.';
            return $response;
        }

        // Check if email already exists
        if ($this->user_model->emailExists($email)) {
            $response['message'] = 'Email already exists.';
            return $response;
        }

        // Create user
        if ($this->user_model->create($username, $password, $email, $first_name, $last_name, $registration_number)) {
            // Get the newly created user
            $user = $this->user_model->getByUsername($username);

            // Assign role to user (default: Student)
            $this->user_model->assignRole($user['user_id'], $role_id);

            $response['success'] = true;
            $response['message'] = 'Registration successful. You can now log in.';
        } else {
            $response['message'] = 'An error occurred during registration. Please try again.';
        }

        return $response;
    }

    /**
     * Handle user logout
     *
     * @return array
     */
    public function logout() {
        session_destroy();
        header('Location: /login');
        exit;
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Check if user has a specific role
     *
     * @param string $role_name
     * @return bool
     */
    public function hasRole($role_name) {
        if (!$this->isAuthenticated()) {
            return false;
        }

        if (!isset($_SESSION['roles'])) {
            return false;
        }

        foreach ($_SESSION['roles'] as $role) {
            if ($role['role_name'] === $role_name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has any of the specified roles
     *
     * @param array $role_names
     * @return bool
     */
    public function hasAnyRole($role_names) {
        if (!$this->isAuthenticated()) {
            return false;
        }

        foreach ($role_names as $role_name) {
            if ($this->hasRole($role_name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get current user
     *
     * @return array|null
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'first_name' => $_SESSION['first_name'],
            'last_name' => $_SESSION['last_name'],
            'roles' => $_SESSION['roles'] ?? []
        ];
    }
}
