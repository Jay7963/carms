<?php
/**
 * Student Portal Dashboard View
 *
 * Displays the student portal with activity browsing, registration, and schedule.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - CARMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard">C<span style="color:var(--kyu-gold)">ARMS</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="/student/dashboard">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/student/schedule">My Schedule</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/student/clubs">My Clubs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/student/history">Participation History</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="apply-leader-btn" title="Apply for Club Leadership">
                            🏆 Apply for Leadership
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="#" id="student-notif-btn">
                            🔔 <span class="badge bg-danger" id="student-notif-count" style="display:none;"></span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <?php
                            $fn = $_SESSION['first_name'] ?? '';
                            $ln = $_SESSION['last_name'] ?? '';
                            $initials = strtoupper(substr($fn,0,1) . substr($ln,0,1));
                            $fullname = trim($fn . ' ' . $ln) ?: ($_SESSION['username'] ?? 'User');
                        ?>
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 p-0" href="#" data-bs-toggle="dropdown">
                            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#f39c12,#e67e22);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;color:white;flex-shrink:0;">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:200px;">
                            <li class="px-3 py-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#f39c12,#e67e22);display:flex;align-items:center;justify-content:center;font-weight:700;color:white;flex-shrink:0;">
                                        <?= htmlspecialchars($initials) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small"><?= htmlspecialchars($fullname) ?></div>
                                        <div class="text-muted" style="font-size:0.75rem;">Student</div>
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

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1>Student Portal</h1>
            <p>Browse and register for co-curricular activities</p>
        </div>

        <!-- Smart Filter Bar -->
        <div class="row mb-4 align-items-center g-2">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">🔍</span>
                    <input type="text" class="form-control border-start-0 ps-0" id="search-input" placeholder="Search events or clubs...">
                    <button class="btn btn-outline-secondary" id="search-clear" style="display:none;">✕</button>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filter-category">
                    <option value="">All Categories</option>
                    <option value="Sports - Men">⚽ Sports - Men</option>
                    <option value="Sports - Women">🏐 Sports - Women</option>
                    <option value="Academic & Professional">💻 Academic & Professional</option>
                    <option value="Arts & Culture">🎭 Arts & Culture</option>
                    <option value="Community & Service">🤝 Community & Service</option>
                    <option value="Religious">⛪ Religious</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="filter-status">
                    <option value="">All Clubs</option>
                    <option value="joined">✅ Joined</option>
                    <option value="not_joined">➕ Not Joined</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" id="filter-reset">Reset</button>
            </div>
        </div>

        <!-- Tabs for Navigation -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab">
                    Upcoming Events
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="clubs-tab" data-bs-toggle="tab" data-bs-target="#clubs" type="button" role="tab">
                    Available Clubs
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Upcoming Events Tab -->
            <div class="tab-pane fade show active" id="upcoming" role="tabpanel">
                <div class="row" id="upcoming-events-container">
                    <!-- Events will be loaded here via AJAX -->
                </div>
            </div>

            <!-- Available Clubs Tab -->
            <div class="tab-pane fade" id="clubs" role="tabpanel">
                <div class="row" id="clubs-container">
                    <!-- Clubs will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    </div>


    <!-- Notification Mini-Window -->
    <div id="student-notif-dropdown" style="display:none; position:fixed; top:62px; right:16px; width:360px; z-index:9999;">
        <div class="card shadow-lg" style="border-radius:12px; border:none; overflow:hidden;">
            <div class="card-header d-flex justify-content-between align-items-center py-2 px-3" style="background:linear-gradient(135deg,#2c3e50,#3498db);">
                <div class="d-flex align-items-center gap-2">
                    <span style="font-size:1.1rem;">🔔</span>
                    <strong class="text-white small">Notifications</strong>
                    <span class="badge bg-danger" id="notif-header-count" style="display:none;"></span>
                </div>
                <button class="btn btn-sm text-white py-0" id="student-mark-read-btn" style="background:rgba(255,255,255,0.15);border:none;font-size:0.75rem;">
                    Mark all read
                </button>
            </div>
            <div id="student-notif-list" style="max-height:360px;overflow-y:auto;">
                <div class="text-center p-3 text-muted small">Loading...</div>
            </div>
            <div class="card-footer text-center py-2" style="background:#f8f9fa;">
                <small class="text-muted">Click any notification to expand</small>
            </div>
        </div>
    </div>

    <!-- Notification Full-Text Modal -->
    <div class="modal fade" id="notifDetailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:12px;border:none;">
                <div class="modal-header py-2 px-3" id="notif-detail-header" style="background:linear-gradient(135deg,#2c3e50,#3498db);">
                    <h6 class="modal-title text-white mb-0" id="notif-detail-title"></h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p id="notif-detail-body" class="mb-2" style="line-height:1.7;"></p>
                    <small class="text-muted" id="notif-detail-date"></small>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Leadership Application Modal -->
    <div class="modal fade" id="leadershipModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #f39c12, #d68910);">
                    <div>
                        <h5 class="modal-title mb-0">🏆 Apply for Club Leadership</h5>
                        <small style="opacity:0.85;">Submit your application to become a club leader</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">🏛️ Which club are you applying to lead? <span class="text-danger">*</span></label>
                        <select class="form-select" id="leader-club-select">
                            <option value="">Select a club...</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">💬 Why do you want to be a leader of this club? <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="leader-why" rows="3" placeholder="Explain your motivation and passion for leading this club..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">🎯 What experience or skills do you bring? <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="leader-experience" rows="3" placeholder="Describe any relevant experience, skills, or past leadership roles..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">🌟 What is your vision for this club?</label>
                        <textarea class="form-control" id="leader-vision" rows="3" placeholder="How would you like to grow or improve this club?"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">📅 How much time can you commit per week?</label>
                        <select class="form-select" id="leader-availability">
                            <option value="">Select availability...</option>
                            <option value="1-2 hours">1–2 hours</option>
                            <option value="3-5 hours">3–5 hours</option>
                            <option value="5-10 hours">5–10 hours</option>
                            <option value="10+ hours">10+ hours</option>
                        </select>
                    </div>

                    <hr>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="leader-agree">
                        <label class="form-check-label" for="leader-agree">
                            I understand that being a club leader is a responsibility and I commit to fulfilling my duties
                        </label>
                    </div>

                    <div id="leader-modal-error" class="alert alert-danger py-2 small mt-2" style="display:none;"></div>
                    <div id="leader-modal-success" class="alert alert-success py-2 small mt-2" style="display:none;"></div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn text-white fw-semibold" id="submit-leader-application" style="background: linear-gradient(135deg, #f39c12, #d68910);">
                        Submit Application
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/student.js"></script>
<form id="logout-form" action="/api/auth.php" method="POST" style="display:none;"><input type="hidden" name="action" value="logout"></form>
</body>
</html>
