<?php

/**
 * Student Portal Controller
 *
 * Handles student-related operations: activity browsing, registration, schedule viewing, and participation history.
 */

class StudentPortalController {
    private $pdo;
    private $club_model;
    private $event_model;
    private $registration_model;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../models/Club.php';
        require_once __DIR__ . '/../models/Event.php';
        require_once __DIR__ . '/../models/Registration.php';
        $this->club_model = new Club($pdo);
        $this->event_model = new Event($pdo);
        $this->registration_model = new Registration($pdo);
    }

    /**
     * Get all approved clubs
     *
     * @return array
     */
    public function getAvailableClubs() {
        return $this->club_model->getApproved();
    }

    /**
     * Get all upcoming events
     *
     * @return array
     */
    public function getUpcomingEvents($user_id) {
        return $this->event_model->getUpcoming($user_id);
    }

    /**
     * Get events by club
     *
     * @param int $club_id
     * @return array
     */
    public function getClubEvents($club_id) {
        return $this->event_model->getByClub($club_id);
    }

    /**
     * Register user for event
     *
     * @param int $event_id
     * @param int $user_id
     * @return array
     */
    public function registerForEvent($event_id, $user_id) {
        $response = ['success' => false, 'message' => ''];

        // Validate event exists
        $event = $this->event_model->getById($event_id);
        if (!$event) {
            $response['message'] = 'Event not found.';
            return $response;
        }

        // Check student is an APPROVED member of the club
        $stmt = $this->pdo->prepare("SELECT member_id FROM club_members WHERE user_id = ? AND club_id = ? AND status = 'approved'");
        $stmt->execute([$user_id, $event['club_id']]);
        if (!$stmt->fetch()) {
            $response['message'] = 'You must be an approved member of this club to register for its events.';
            return $response;
        }

        // Check capacity
        $capStmt = $this->pdo->prepare("SELECT max_capacity, (SELECT COUNT(*) FROM registrations WHERE event_id = ?) as reg_count FROM events WHERE event_id = ?");
        $capStmt->execute([$event_id, $event_id]);
        $capData = $capStmt->fetch();
        if ($capData && $capData['max_capacity'] > 0 && $capData['reg_count'] >= $capData['max_capacity']) {
            $response['message'] = 'Sorry, this event is full.';
            return $response;
        }

        // Check if already registered
        if ($this->registration_model->isRegistered($event_id, $user_id)) {
            $response['message'] = 'You are already registered for this event.';
            return $response;
        }

        // Register user
        if ($this->registration_model->register($event_id, $user_id)) {
            $response['success'] = true;
            $response['message'] = 'Successfully registered for the event.';
        } else {
            $response['message'] = 'Failed to register for the event.';
        }

        return $response;
    }

    /**
     * Unregister user from event
     *
     * @param int $event_id
     * @param int $user_id
     * @return array
     */
    public function unregisterFromEvent($event_id, $user_id) {
        $response = ['success' => false, 'message' => ''];

        // Check if registered
        if (!$this->registration_model->isRegistered($event_id, $user_id)) {
            $response['message'] = 'You are not registered for this event.';
            return $response;
        }

        // Unregister user
        if ($this->registration_model->unregister($event_id, $user_id)) {
            $response['success'] = true;
            $response['message'] = 'Successfully unregistered from the event.';
        } else {
            $response['message'] = 'Failed to unregister from the event.';
        }

        return $response;
    }

    /**
     * Get user's registered events (schedule)
     *
     * @param int $user_id
     * @return array
     */
    public function getUserSchedule($user_id) {
        return $this->registration_model->getUserRegistrations($user_id);
    }

    /**
     * Get user's participation history
     *
     * @param int $user_id
     * @return array
     */
    public function getParticipationHistory($user_id) {
        try {
            $query = "SELECT e.*, c.club_name, a.attended_at 
                      FROM attendance a 
                      JOIN events e ON a.event_id = e.event_id 
                      JOIN clubs c ON e.club_id = c.club_id 
                      WHERE a.user_id = :user_id 
                      ORDER BY e.event_date DESC, e.event_time DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching participation history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Join club
     *
     * @param int $club_id
     * @param int $user_id
     * @return array
     */
    public function joinClub($club_id, $user_id) {
        $response = ['success' => false, 'message' => ''];

        // Validate club exists
        $club = $this->club_model->getById($club_id);
        if (!$club) {
            $response['message'] = 'Club not found.';
            return $response;
        }

        // Check if already a member
        if ($this->club_model->isMember($club_id, $user_id)) {
            $response['message'] = 'You are already a member of this club.';
            return $response;
        }

        // Add member (status = pending, awaits leader approval)
        if ($this->club_model->addMember($club_id, $user_id)) {
            $response['success'] = true;
            $response['message'] = 'Application submitted! Your request is pending leader approval.';
            $response['status'] = 'pending';
        } else {
            $response['message'] = 'Failed to submit application.';
        }

        return $response;
    }

    /**
     * Leave club
     *
     * @param int $club_id
     * @param int $user_id
     * @return array
     */
    public function leaveClub($club_id, $user_id) {
        $response = ['success' => false, 'message' => ''];

        // Check if member
        if (!$this->club_model->isMember($club_id, $user_id)) {
            $response['message'] = 'You are not a member of this club.';
            return $response;
        }

        // Remove member
        if ($this->club_model->removeMember($club_id, $user_id)) {
            $response['success'] = true;
            $response['message'] = 'Successfully left the club.';
        } else {
            $response['message'] = 'Failed to leave the club.';
        }

        return $response;
    }

    /**
     * Get user's clubs
     *
     * @param int $user_id
     * @return array
     */
    public function getUserClubs($user_id) {
        try {
            $query = "SELECT c.*, cm.status as membership_status, cm.member_id FROM clubs c 
                      JOIN club_members cm ON c.club_id = cm.club_id 
                      WHERE cm.user_id = :user_id 
                        AND cm.status IN ('approved','pending')
                      ORDER BY cm.status ASC, cm.applied_at DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching user clubs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search events
     *
     * @param string $search_term
     * @return array
     */
    public function searchEvents($search_term) {
        try {
            $search_term = '%' . $search_term . '%';
            $query = "SELECT e.*, c.club_name FROM events e 
                      JOIN clubs c ON e.club_id = c.club_id 
                      WHERE e.event_name LIKE :search_term OR e.description LIKE :search_term 
                      ORDER BY e.event_date DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':search_term', $search_term);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error searching events: " . $e->getMessage());
            return [];
        }
    }
}
