<?php
/**
 * Administrative Dashboard View - Enhanced
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CARMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card { border-radius: 12px; padding: 1.5rem; color: white; position: relative; overflow: hidden; }
        .stat-card .stat-icon { font-size: 2.5rem; opacity: 0.3; position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); }
        .stat-card h2 { font-size: 2.2rem; font-weight: 700; margin: 0; }
        .stat-card p  { margin: 0; opacity: 0.85; font-size: 0.9rem; }
        .stat-users   { background: linear-gradient(135deg, #1a73e8, #0d47a1); }
        .stat-clubs   { background: linear-gradient(135deg, #27ae60, #1e8449); }
        .stat-events  { background: linear-gradient(135deg, #e67e22, #ca6f1e); }
        .stat-regs    { background: linear-gradient(135deg, #8e44ad, #6c3483); }
        .stat-attend  { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-pending { background: linear-gradient(135deg, #f39c12, #d68910); }

        .admin-section { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .admin-section h5 { font-weight: 700; color: #2c3e50; border-bottom: 2px solid #f0f0f0; padding-bottom: 0.75rem; margin-bottom: 1rem; }

        .table th { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: #666; background: #f8f9fa; }
        .table td { vertical-align: middle; font-size: 0.9rem; }

        .user-avatar { width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; color: white; background: #3498db; }

        .chart-container { position: relative; height: 260px; }

        .badge-role-admin   { background: #e74c3c; }
        .badge-role-leader  { background: #e67e22; }
        .badge-role-student { background: #3498db; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin/dashboard">C<span style="color:var(--kyu-gold)">ARMS</span> <small style="font-size:0.65rem;opacity:0.75;font-weight:400;">Admin</small></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-3">
                        <span class="text-white-50 small">⏳ Pending: <strong class="text-white" id="nav-pending-count">—</strong></span>
                    </li>
                    <li class="nav-item me-3">
                        <span class="text-white-50 small">👥 Users: <strong class="text-white" id="nav-user-count">—</strong></span>
                    </li>
                    <li class="nav-item me-3">
                        <span class="text-white-50 small" id="nav-clock"></span>
                    </li>
                    <li class="nav-item dropdown">
                        <?php
                            $fn = $_SESSION['first_name'] ?? '';
                            $ln = $_SESSION['last_name'] ?? '';
                            $initials = strtoupper(substr($fn,0,1) . substr($ln,0,1));
                            $fullname = trim($fn . ' ' . $ln) ?: ($_SESSION['username'] ?? 'Admin');
                        ?>
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 p-0" href="#" data-bs-toggle="dropdown">
                            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#e74c3c,#c0392b);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;color:white;flex-shrink:0;">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:200px;">
                            <li class="px-3 py-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#e74c3c,#c0392b);display:flex;align-items:center;justify-content:center;font-weight:700;color:white;flex-shrink:0;">
                                        <?= htmlspecialchars($initials) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small"><?= htmlspecialchars($fullname) ?></div>
                                        <div class="text-muted" style="font-size:0.75rem;">Administrator</div>
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

    <div class="container-fluid mt-4 px-4">

        <!-- Header -->
        <div class="dashboard-header mb-4">
            <h1>Administrative Dashboard</h1>
            <p>Manage clubs, users, events and view system analytics</p>
        </div>

        <!-- Stats Row -->
        <div class="row mb-4" id="stats-row">
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <div class="stat-card stat-users h-100">
                    <p>Total Users</p>
                    <h2 id="stat-users">—</h2>
                    
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <div class="stat-card stat-clubs h-100">
                    <p>Total Clubs</p>
                    <h2 id="stat-clubs">—</h2>
                    
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <div class="stat-card stat-events h-100">
                    <p>Total Events</p>
                    <h2 id="stat-events">—</h2>
                    
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <div class="stat-card stat-regs h-100">
                    <p>Registrations</p>
                    <h2 id="stat-regs">—</h2>
                    
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <div class="stat-card stat-attend h-100">
                    <p>Attendance</p>
                    <h2 id="stat-attend">—</h2>
                    
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <div class="stat-card stat-pending h-100">
                    <p>Pending</p>
                    <h2 id="stat-pending">—</h2>
                    
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-lg-6 mb-3">
                <div class="admin-section h-100">
                    <h5> Clubs by Category</h5>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="admin-section h-100">
                    <h5> Club Status Overview</h5>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Tabs -->
        <div class="admin-section">
            <ul class="nav nav-tabs mb-4" role="tablist" id="admin-tabs">
                <li class="nav-item">
                    <button class="nav-link active" id="tab-pending" data-bs-toggle="tab" data-bs-target="#pane-pending" type="button">
                         Club Approvals <span class="badge bg-danger ms-1" id="tab-pending-count" style="display:none;"></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-applications" data-bs-toggle="tab" data-bs-target="#pane-applications" type="button">
                         Memberships <span class="badge bg-warning text-dark ms-1" id="tab-app-count" style="display:none;"></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-clubs" data-bs-toggle="tab" data-bs-target="#pane-clubs" type="button">
                         All Clubs
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-users" data-bs-toggle="tab" data-bs-target="#pane-users" type="button">
                         Users
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-announce" data-bs-toggle="tab" data-bs-target="#pane-announce" type="button">
                         Announce
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-leadership" data-bs-toggle="tab" data-bs-target="#pane-leadership" type="button">
                         Leadership Applications <span class="badge bg-warning text-dark ms-1" id="tab-leadership-count" style="display:none;"></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-reports" data-bs-toggle="tab" data-bs-target="#pane-reports" type="button">
                         Reports
                    </button>
                </li>
            </ul>

            <div class="tab-content">

                <!-- Pending Club Approvals -->
                <div class="tab-pane fade show active" id="pane-pending" role="tabpanel">
                    <div id="pending-container">
                        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
                    </div>
                </div>

                <!-- Membership Applications (Admin Override) -->
                <div class="tab-pane fade" id="pane-applications" role="tabpanel">
                    <div class="alert alert-info py-2 small mb-3">⚙️ As admin you can override any pending membership application that a leader has not yet reviewed.</div>
                    <div id="applications-container">
                        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
                    </div>
                </div>

                <!-- All Clubs -->
                <div class="tab-pane fade" id="pane-clubs" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <input type="text" class="form-control w-25" id="club-search" placeholder="🔍 Search clubs...">
                        <select class="form-select w-auto" id="club-status-filter">
                            <option value="">All Statuses</option>
                            <option value="approved">Approved</option>
                            <option value="pending">Pending</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div id="clubs-container">
                        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
                    </div>
                </div>

                <!-- Users -->
                <div class="tab-pane fade" id="pane-users" role="tabpanel">
                    <div class="mb-3">
                        <input type="text" class="form-control w-25" id="user-search" placeholder="🔍 Search users...">
                    </div>
                    <div id="users-container">
                        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
                    </div>
                </div>

                <!-- Announcements -->
                <div class="tab-pane fade" id="pane-announce" role="tabpanel">
                    <div class="row">
                        <div class="col-md-5">
                            <h6 class="fw-bold mb-3"> Send System-Wide Announcement</h6>
                            <p class="text-muted small">This will send a notification to ALL active students and leaders.</p>
                            <div class="mb-3"><label class="form-label fw-semibold">Title</label><input type="text" class="form-control" id="admin-announce-title" placeholder="Announcement title"></div>
                            <div class="mb-3"><label class="form-label fw-semibold">Message</label><textarea class="form-control" id="admin-announce-message" rows="4" placeholder="Write your system-wide announcement..."></textarea></div>
                            <button class="btn btn-danger" id="admin-post-announce-btn"> Send to All Users</button>
                        </div>
                        <div class="col-md-7">
                            <h6 class="fw-bold mb-3">Recent Announcements</h6>
                            <div id="admin-announcements-list"><div class="text-center py-3"><div class="spinner-border text-primary"></div></div></div>
                        </div>
                    </div>
                </div>

                <!-- Leadership Applications -->
                <div class="tab-pane fade" id="pane-leadership" role="tabpanel">
                    <div class="alert alert-info py-2 small mb-3">
                         Review student applications for club leadership. Approving will automatically assign the Activity Leader role and notify the applicant.
                    </div>
                    <div id="leadership-container">
                        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
                    </div>
                </div>

                <!-- Reports -->
                <div class="tab-pane fade" id="pane-reports" role="tabpanel">
                    <div id="reports-container">
                        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Assign Role Modal -->
    <div class="modal fade" id="assignRoleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #2c3e50, #3498db); color:white;">
                    <h5 class="modal-title">Assign Role</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="assign_user_id">
                    <p class="text-muted small mb-3">Assigning role to: <strong id="assign_username"></strong></p>
                    <label class="form-label fw-semibold">Select Role</label>
                    <select class="form-select" id="role_id">
                        <option value="">Choose a role...</option>
                        <option value="1"> Student</option>
                        <option value="2"> Activity Leader</option>
                        <option value="3"> Administrator</option>
                    </select>
                    <div id="role-modal-error" class="alert alert-danger py-2 small mt-3" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="assign-role-btn">Assign Role</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/admin.js"></script>
<form id="logout-form" action="/api/auth.php" method="POST" style="display:none;"><input type="hidden" name="action" value="logout"></form>
</body>
</html>
