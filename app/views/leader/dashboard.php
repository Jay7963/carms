<?php /** Activity Leader Hub - Enhanced */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leader Hub - CARMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .stat-card { border-radius:12px; padding:1.2rem; color:white; }
        .stat-card h2 { font-size:2rem; font-weight:700; margin:0; }
        .stat-card p  { margin:0; opacity:.85; font-size:.85rem; }
        .leader-section { background:white; border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; box-shadow:0 2px 8px rgba(0,0,0,.06); }
        .leader-section h5 { font-weight:700; color:#2c3e50; border-bottom:2px solid #f0f0f0; padding-bottom:.75rem; margin-bottom:1rem; }
        .table th { font-size:.8rem; text-transform:uppercase; letter-spacing:.5px; color:#666; background:#f8f9fa; }
        .table td { vertical-align:middle; font-size:.9rem; }
        .notif-dot { width:10px; height:10px; background:#e74c3c; border-radius:50%; display:inline-block; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/leader/dashboard">CARMS Leader</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link active" href="/leader/dashboard">Dashboard</a></li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="#" id="notif-btn">
                            🔔 <span class="badge bg-danger" id="notif-count" style="display:none;">0</span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <?php
                            $fn = $_SESSION['first_name'] ?? '';
                            $ln = $_SESSION['last_name'] ?? '';
                            $initials = strtoupper(substr($fn,0,1) . substr($ln,0,1));
                            $fullname = trim($fn . ' ' . $ln) ?: ($_SESSION['username'] ?? 'Leader');
                        ?>
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 p-0" href="#" data-bs-toggle="dropdown">
                            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#27ae60,#1e8449);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;color:white;flex-shrink:0;">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:200px;">
                            <li class="px-3 py-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#27ae60,#1e8449);display:flex;align-items:center;justify-content:center;font-weight:700;color:white;flex-shrink:0;">
                                        <?= htmlspecialchars($initials) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small"><?= htmlspecialchars($fullname) ?></div>
                                        <div class="text-muted" style="font-size:0.75rem;">Activity Leader</div>
                                    </div>
                                </div>
                            </li>
                            <li><button class="dropdown-item text-danger small border-0 bg-transparent w-100 text-start" onclick="document.getElementById('logout-form').submit()">🚪 Logout</button></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Notification Dropdown -->
    <div id="notif-dropdown" class="card shadow" style="display:none; position:fixed; top:60px; right:20px; width:340px; z-index:9999; max-height:400px; overflow-y:auto;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Notifications</strong>
            <button class="btn btn-sm btn-outline-secondary" id="mark-read-btn">Mark all read</button>
        </div>
        <div id="notif-list"><div class="text-center p-3 text-muted">Loading...</div></div>
    </div>

    <div class="container-fluid mt-4 px-4">
        <div class="dashboard-header mb-4">
            <h1>Activity Leader Hub</h1>
            <p>Manage your clubs, review applications, create events and track attendance</p>
        </div>

        <!-- Stat Cards -->
        <div class="row mb-4" id="leader-stats"></div>

        <!-- Main Tabs -->
        <div class="leader-section">
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item"><button class="nav-link active" id="tab-applications" data-bs-toggle="tab" data-bs-target="#pane-applications" type="button"> Applications <span class="badge bg-danger ms-1" id="app-count" style="display:none;"></span></button></li>
                <li class="nav-item"><button class="nav-link" id="tab-clubs" data-bs-toggle="tab" data-bs-target="#pane-clubs" type="button"> My Clubs</button></li>
                <li class="nav-item"><button class="nav-link" id="tab-members" data-bs-toggle="tab" data-bs-target="#pane-members" type="button"> Members</button></li>
                <li class="nav-item"><button class="nav-link" id="tab-events" data-bs-toggle="tab" data-bs-target="#pane-events" type="button"> Events</button></li>
                <li class="nav-item"><button class="nav-link" id="tab-announce" data-bs-toggle="tab" data-bs-target="#pane-announce" type="button"> Announce</button></li>
            </ul>

            <div class="tab-content">
                <!-- Applications -->
                <div class="tab-pane fade show active" id="pane-applications">
                    <div id="applications-container"><div class="text-center py-4"><div class="spinner-border text-primary"></div></div></div>
                </div>

                <!-- Clubs -->
                <div class="tab-pane fade" id="pane-clubs">
                    <div class="mb-3">
                        <button class="btn btn-success" id="create-club-btn-open">+ Create New Club</button>
                    </div>
                    <div id="clubs-container"><div class="text-center py-4"><div class="spinner-border text-primary"></div></div></div>
                </div>

                <!-- Members -->
                <div class="tab-pane fade" id="pane-members">
                    <div class="mb-3">
                        <select class="form-select w-auto" id="members-club-select"><option value="">Select a club...</option></select>
                    </div>
                    <div id="members-container"><p class="text-muted">Select a club to view members.</p></div>
                </div>

                <!-- Events -->
                <div class="tab-pane fade" id="pane-events">
                    <div class="mb-3">
                        <select class="form-select w-auto d-inline-block" id="events-club-select" style="width:250px!important;"><option value="">Select a club...</option></select>
                    </div>
                    <div id="events-container"><p class="text-muted">Select a club to view events.</p></div>
                </div>

                <!-- Announcements -->
                <div class="tab-pane fade" id="pane-announce">
                    <div class="row">
                        <div class="col-md-5">
                            <h6 class="fw-bold mb-3">Post New Announcement</h6>
                            <div class="mb-3">
                                <label class="form-label">Club</label>
                                <select class="form-select" id="announce-club-select"><option value="">Select a club...</option></select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" id="announce-title" placeholder="Announcement title">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" id="announce-message" rows="4" placeholder="Write your announcement..."></textarea>
                            </div>
                            <button class="btn btn-primary" id="post-announce-btn"> Post Announcement</button>
                        </div>
                        <div class="col-md-7">
                            <h6 class="fw-bold mb-3">Recent Announcements</h6>
                            <div id="announcements-list"><p class="text-muted">Select a club to see announcements.</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Club Modal -->
    <div class="modal fade" id="createClubModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#27ae60,#1e8449);color:white;">
                    <h5 class="modal-title">➕ Create New Club</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Club Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new-club-name" placeholder="e.g. Photography Club">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="new-club-desc" rows="3" placeholder="Describe the club's purpose and activities..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="new-club-category">
                            <option value="">Select a category...</option>
                            <option value="Sports - Men"> Sports - Men</option>
                            <option value="Sports - Women"> Sports - Women</option>
                            <option value="Academic & Professional"> Academic & Professional</option>
                            <option value="Arts & Culture"> Arts & Culture</option>
                            <option value="Community & Service"> Community & Service</option>
                            <option value="Religion"> Religion</option>
                            <option value="other"> Other (specify below)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="custom-category-field" style="display:none;">
                        <label class="form-label fw-semibold">Custom Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new-club-custom-category" placeholder="Enter your category name...">
                    </div>

                   

                    <div id="club-modal-error" class="alert alert-danger py-2 small" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="submit-club-btn">Submit for Approval</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Event Modal -->
    <div class="modal fade" id="createEventModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#1a73e8,#0d47a1);color:white;">
                    <h5 class="modal-title">Create Event</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="event-club-id">
                    <div class="mb-3"><label class="form-label fw-semibold">Event Name</label><input type="text" class="form-control" id="new-event-name"></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Description</label><textarea class="form-control" id="new-event-desc" rows="2"></textarea></div>
                    <div class="row">
                        <div class="col-6 mb-3"><label class="form-label fw-semibold">Date</label><input type="date" class="form-control" id="new-event-date"></div>
                        <div class="col-6 mb-3"><label class="form-label fw-semibold">Time</label><input type="time" class="form-control" id="new-event-time"></div>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold">Location</label><input type="text" class="form-control" id="new-event-location" placeholder="e.g. Main Hall, Sports Ground"></div>
                    <div id="event-modal-error" class="alert alert-danger py-2 small" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submit-event-btn">Create Event</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Modal -->
    <div class="modal fade" id="attendanceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#8e44ad,#6c3483);color:white;">
                    <h5 class="modal-title"> Take Attendance</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="attend-event-id">
                    <p class="text-muted small mb-3">Mark attendance for each registered student:</p>
                    <div id="attendance-list"><div class="text-center py-3"><div class="spinner-border text-primary"></div></div></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/leader.js"></script>
<form id="logout-form" action="/api/auth.php" method="POST" style="display:none;"><input type="hidden" name="action" value="logout"></form>
</body>
</html>
