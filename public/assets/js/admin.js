/**
 * Admin Dashboard JavaScript - Full Unified Version
 */

$(document).ready(function () {

    // -------------------------------------------------------
    // INIT
    // -------------------------------------------------------
    loadStatistics();
    loadPendingClubs();

    // Tab click handlers
    $('#tab-clubs').on('click', function () { loadAllClubs(); });
    $('#tab-users').on('click', function () { loadAllUsers(); });
    $('#tab-reports').on('click', function () { loadReports(); });
    $('#tab-applications').on('click', function () { loadMembershipApplications(); });
    $('#tab-announce').on('click', function () { loadAdminAnnouncements(); });
    $('#tab-leadership').on('click', function () { loadLeadershipApplications(); });

    // Nav link handlers
    $('#nav-approvals').on('click', function (e) {
        e.preventDefault();
        $('#tab-pending').tab('show');
        $('html,body').animate({ scrollTop: $('#admin-tabs').offset().top - 20 }, 400);
    });
    $('#nav-applications').on('click', function (e) {
        e.preventDefault();
        $('#tab-applications').tab('show');
        loadMembershipApplications();
        $('html,body').animate({ scrollTop: $('#admin-tabs').offset().top - 20 }, 400);
    });
    $('#nav-users').on('click', function (e) {
        e.preventDefault();
        $('#tab-users').tab('show');
        loadAllUsers();
        $('html,body').animate({ scrollTop: $('#admin-tabs').offset().top - 20 }, 400);
    });
    $('#nav-announce').on('click', function (e) {
        e.preventDefault();
        $('#tab-announce').tab('show');
        loadAdminAnnouncements();
        $('html,body').animate({ scrollTop: $('#admin-tabs').offset().top - 20 }, 400);
    });
    $('#nav-reports').on('click', function (e) {
        e.preventDefault();
        $('#tab-reports').tab('show');
        loadReports();
        $('html,body').animate({ scrollTop: $('#admin-tabs').offset().top - 20 }, 400);
    });

    // Search & filter
    $('#club-search').on('input', filterClubs);
    $('#club-status-filter').on('change', filterClubs);
    $('#user-search').on('input', filterUsers);

    // Assign role
    $('#assign-role-btn').on('click', assignRole);

    // Announcements
    $('#admin-post-announce-btn').on('click', postAdminAnnouncement);

    // Deactivate/activate user (delegated)
    $(document).on('click', '.toggle-user-btn', function () {
        const userId = $(this).data('user-id');
        const action = $(this).data('action');
        const label  = action === 'deactivate' ? 'Deactivate this account? The student will not be able to log in.' : 'Activate this account?';
        if (!confirm(label)) return;
        const btn = $(this);
        btn.prop('disabled', true);
        $.ajax({
            url: '/api/admin.php?action=toggle_user', method: 'POST',
            contentType: 'application/json', data: JSON.stringify({ user_id: userId, action: action }),
            success: function (res) {
                if (res.success) { showToast(res.message, 'success'); loadAllUsers(); }
                else { btn.prop('disabled', false); showToast(res.message, 'danger'); }
            }
        });
    });

    // Live clock in navbar
    function updateClock() {
        const now = new Date();
        const time = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        const date = now.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
        $('#nav-clock').text(date + ' · ' + time);
    }
    updateClock();
    setInterval(updateClock, 1000);

    // -------------------------------------------------------
    // STATISTICS
    // -------------------------------------------------------
    function loadStatistics() {
        $.ajax({
            url: '/api/admin.php?action=system_statistics',
            method: 'GET', dataType: 'json',
            success: function (response) {
                if (response.success) {
                    const s = response.data;
                    $('#stat-users').text(s.total_users   || 0);
                    $('#stat-clubs').text(s.total_clubs   || 0);
                    $('#stat-events').text(s.total_events || 0);
                    $('#stat-regs').text(s.total_registrations || 0);
                    $('#stat-attend').text(s.total_attendance  || 0);
                    $('#stat-pending').text(s.pending_clubs    || 0);

                    // Update navbar counters
                    $('#nav-pending-count').text((parseInt(s.pending_clubs) || 0) + (parseInt(s.pending_applications) || 0));
                    $('#nav-user-count').text(s.total_users || 0);

                    const pending = parseInt(s.pending_clubs) || 0;
                    if (pending > 0) {
                        $('#tab-pending-count').text(pending).show();
                    }
                    buildCharts(s);
                }
            },
            error: function (xhr) {
                if (xhr.status === 401) window.location.href = '/login';
                if (xhr.status === 403) {
                    $('body').html('<div class="container mt-5 text-center"><h2>⛔ Access Denied</h2><p>You do not have administrator privileges.</p><a href="/dashboard" class="btn btn-primary">Go to Dashboard</a></div>');
                }
            }
        });
    }

    function buildCharts(stats) {
        $.ajax({
            url: '/api/admin.php?action=all_clubs', method: 'GET', dataType: 'json',
            success: function (res) {
                if (!res.success) return;
                const clubs = res.data;
                const catCount = {};
                clubs.forEach(c => {
                    const cat = c.category || 'Other';
                    catCount[cat] = (catCount[cat] || 0) + 1;
                });
                const catColors = ['#1a73e8','#e91e8c','#0d6e6e','#7b2d8b','#e67e22','#27ae60','#c0392b','#f39c12'];

                new Chart(document.getElementById('categoryChart'), {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(catCount),
                        datasets: [{ data: Object.values(catCount), backgroundColor: catColors, borderWidth: 2, borderColor: '#fff' }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { font: { size: 11 } } } } }
                });

                new Chart(document.getElementById('statusChart'), {
                    type: 'bar',
                    data: {
                        labels: ['Approved', 'Pending', 'Rejected'],
                        datasets: [{ label: 'Clubs', data: [stats.approved_clubs || 0, stats.pending_clubs || 0, stats.rejected_clubs || 0], backgroundColor: ['#27ae60','#f39c12','#e74c3c'], borderRadius: 6 }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
                });
            }
        });
    }

    // -------------------------------------------------------
    // PENDING CLUB APPROVALS
    // -------------------------------------------------------
    function loadPendingClubs() {
        $.ajax({
            url: '/api/admin.php?action=pending_clubs', method: 'GET', dataType: 'json',
            success: function (response) {
                const container = $('#pending-container');
                if (!response.success || response.data.length === 0) {
                    container.html('<div class="text-center py-5"><div style="font-size:3rem;">✅</div><h5 class="mt-2 text-muted">No pending club approvals</h5></div>');
                    return;
                }
                let html = `<div class="table-responsive"><table class="table">
                    <thead><tr><th>Club Name</th><th>Category</th><th>Leader</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead><tbody>`;
                response.data.forEach(function (club) {
                    html += `<tr>
                        <td><strong>${escapeHtml(club.club_name)}</strong></td>
                        <td><span class="badge bg-secondary">${escapeHtml(club.category || 'Uncategorized')}</span></td>
                        <td class="text-muted small">${escapeHtml(club.leader_name || '—')}</td>
                        <td class="text-muted small">${escapeHtml((club.description || '').substring(0, 60))}...</td>
                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                        <td>
                            <button class="btn btn-success btn-sm approve-btn me-1" data-club-id="${club.club_id}">✓ Approve</button>
                            <button class="btn btn-danger btn-sm reject-btn" data-club-id="${club.club_id}">✕ Reject</button>
                        </td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
                container.html(html);
                $('.approve-btn').on('click', function () { approveClub($(this).data('club-id'), $(this)); });
                $('.reject-btn').on('click', function () { rejectClub($(this).data('club-id'), $(this)); });
            }
        });
    }

    function approveClub(clubId, btn) {
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '/api/admin.php?action=approve_club', method: 'POST',
            contentType: 'application/json', data: JSON.stringify({ club_id: clubId }),
            success: function (res) {
                if (res.success) { btn.closest('tr').fadeOut(300, function () { $(this).remove(); }); showToast('Club approved!', 'success'); loadStatistics(); }
                else { btn.prop('disabled', false).html('✓ Approve'); showToast(res.message, 'danger'); }
            }
        });
    }

    function rejectClub(clubId, btn) {
        if (!confirm('Reject this club?')) return;
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '/api/admin.php?action=reject_club', method: 'POST',
            contentType: 'application/json', data: JSON.stringify({ club_id: clubId }),
            success: function (res) {
                if (res.success) { btn.closest('tr').fadeOut(300, function () { $(this).remove(); }); showToast('Club rejected.', 'success'); loadStatistics(); }
                else { btn.prop('disabled', false).html('✕ Reject'); showToast(res.message, 'danger'); }
            }
        });
    }

    // -------------------------------------------------------
    // MEMBERSHIP APPLICATIONS
    // -------------------------------------------------------
    function loadMembershipApplications() {
        $.ajax({
            url: '/api/admin.php?action=pending_applications', method: 'GET', dataType: 'json',
            success: function (res) {
                const c = $('#applications-container');
                if (!res.success || res.data.length === 0) {
                    c.html('<div class="text-center py-5"><div style="font-size:3rem;">✅</div><h5 class="mt-2 text-muted">No pending membership applications</h5><p class="text-muted small">All applications have been reviewed by club leaders.</p></div>');
                    return;
                }
                $('#tab-app-count').text(res.data.length).show();
                let html = `<div class="table-responsive"><table class="table">
                    <thead><tr><th>Student</th><th>Club</th><th>Category</th><th>Leader</th><th>Applied</th><th>Admin Override</th></tr></thead><tbody>`;
                res.data.forEach(function (a) {
                    const date = new Date(a.applied_at).toLocaleDateString('en-GB', {day:'numeric',month:'short',year:'numeric'});
                    html += `<tr>
                        <td><strong>${escapeHtml(a.student_name)}</strong><br><small class="text-muted">${escapeHtml(a.email)}</small></td>
                        <td><strong>${escapeHtml(a.club_name)}</strong></td>
                        <td><span class="badge bg-secondary">${escapeHtml(a.category || '—')}</span></td>
                        <td class="text-muted small">${escapeHtml(a.leader_name || 'No leader assigned')}</td>
                        <td class="text-muted small">${date}</td>
                        <td>
                            <button class="btn btn-success btn-sm admin-approve-app me-1" data-id="${a.member_id}">✓ Approve</button>
                            <button class="btn btn-danger btn-sm admin-reject-app" data-id="${a.member_id}">✕ Reject</button>
                        </td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
                c.html(html);
                $('.admin-approve-app').on('click', function () { overrideApplication($(this).data('id'), 'approve', $(this)); });
                $('.admin-reject-app').on('click', function () { overrideApplication($(this).data('id'), 'reject', $(this)); });
            }
        });
    }

    function overrideApplication(memberId, action, btn) {
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '/api/admin.php?action=override_application', method: 'POST',
            contentType: 'application/json', dataType: 'json',
            data: JSON.stringify({ member_id: parseInt(memberId), action: action }),
            success: function (res) {
                if (res.success) { btn.closest('tr').fadeOut(300, function () { $(this).remove(); }); showToast(action === 'approve' ? 'Application approved!' : 'Application rejected.', action === 'approve' ? 'success' : 'danger'); }
                else showToast(res.message, 'danger');
            },
            error: function () { btn.prop('disabled', false).html(action === 'approve' ? '✓ Approve' : '✕ Reject'); showToast('Server error. Please try again.', 'danger'); }
        });
    }

    // -------------------------------------------------------
    // ALL CLUBS
    // -------------------------------------------------------
    let allClubs = [];

    function loadAllClubs() {
        $.ajax({
            url: '/api/admin.php?action=all_clubs', method: 'GET', dataType: 'json',
            success: function (response) { allClubs = response.success ? response.data : []; renderClubs(allClubs); }
        });
    }

    function renderClubs(clubs) {
        const container = $('#clubs-container');
        if (clubs.length === 0) { container.html('<p class="text-center text-muted py-4">No clubs found.</p>'); return; }
        let html = `<div class="table-responsive"><table class="table">
            <thead><tr><th>Club Name</th><th>Category</th><th>Leader</th><th>Status</th><th>Created</th><th>Action</th></tr></thead><tbody>`;
        clubs.forEach(function (club) {
            const statusColors = { approved: 'success', pending: 'warning', rejected: 'danger' };
            const color = statusColors[club.status] || 'secondary';
            const date  = new Date(club.created_at).toLocaleDateString('en-GB', {day:'numeric',month:'short',year:'numeric'});
            html += `<tr>
                <td><strong>${escapeHtml(club.club_name)}</strong></td>
                <td><span class="badge bg-secondary">${escapeHtml(club.category || 'Uncategorized')}</span></td>
                <td class="text-muted small">${escapeHtml(club.leader_name || '—')}</td>
                <td><span class="badge bg-${color} ${club.status==='pending'?'text-dark':''}">${club.status}</span></td>
                <td class="text-muted small">${date}</td>
                <td><button class="btn btn-danger btn-sm delete-club-btn" data-id="${club.club_id}" data-name="${escapeHtml(club.club_name)}">🗑️ Delete</button></td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        container.html(html);
        $('.delete-club-btn').on('click', function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            if (!confirm(`⚠️ Delete "${name}"? This will remove all members, events and attendance records. This cannot be undone.`)) return;
            const btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
            $.ajax({
                url: '/api/admin.php?action=delete_club', method: 'POST',
                contentType: 'application/json', dataType: 'json',
                data: JSON.stringify({ club_id: parseInt(id) }),
                success: function (res) {
                    if (res.success) { btn.closest('tr').fadeOut(300, function () { $(this).remove(); }); showToast(res.message, 'success'); loadStatistics(); }
                    else { btn.prop('disabled', false).html('🗑️ Delete'); showToast(res.message, 'danger'); }
                }
            });
        });
    }

    function filterClubs() {
        const search = $('#club-search').val().toLowerCase();
        const status = $('#club-status-filter').val();
        const filtered = allClubs.filter(c =>
            (!search || c.club_name.toLowerCase().includes(search) || (c.category || '').toLowerCase().includes(search)) &&
            (!status || c.status === status)
        );
        renderClubs(filtered);
    }

    // -------------------------------------------------------
    // USERS
    // -------------------------------------------------------
    let allUsers = [];

    function loadAllUsers() {
        $.ajax({
            url: '/api/admin.php?action=all_users', method: 'GET', dataType: 'json',
            success: function (response) { allUsers = response.success ? response.data : []; renderUsers(allUsers); }
        });
    }

    function renderUsers(users) {
        const container = $('#users-container');
        if (users.length === 0) { container.html('<p class="text-center text-muted py-4">No users found.</p>'); return; }
        let html = `<div class="table-responsive"><table class="table">
            <thead><tr><th>User</th><th>Email</th><th>Reg. Number</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead><tbody>`;
        users.forEach(function (user) {
            const initials   = ((user.first_name||'?')[0] + (user.last_name||'?')[0]).toUpperCase();
            const role       = (user.roles || 'student').toLowerCase();
            const roleColors = { administrator: 'danger', 'activity leader': 'warning', student: 'primary' };
            const roleColor  = roleColors[role] || 'secondary';
            const date       = new Date(user.created_at).toLocaleDateString('en-GB', {day:'numeric',month:'short',year:'numeric'});
            const isActive   = user.is_active == 1;
            html += `<tr class="${isActive ? '' : 'table-secondary'}">
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="user-avatar" style="background:${isActive?'#3498db':'#95a5a6'}">${initials}</div>
                        <div><strong>${escapeHtml(user.first_name)} ${escapeHtml(user.last_name)}</strong><br>
                        <small class="text-muted">@${escapeHtml(user.username)}</small></div>
                    </div>
                </td>
                <td class="text-muted small">${escapeHtml(user.email)}</td>
                <td class="text-muted small">${escapeHtml(user.registration_number || '—')}</td>
                <td><span class="badge bg-${roleColor} ${role==='activity leader'?'text-dark':''}">${escapeHtml(user.roles || 'Student')}</span></td>
                <td><span class="badge ${isActive?'bg-success':'bg-secondary'}">${isActive?'Active':'Inactive'}</span></td>
                <td>
                    <button class="btn btn-sm btn-outline-danger toggle-user-btn me-1"
                        data-user-id="${user.user_id}"
                        data-action="${isActive?'deactivate':'activate'}">
                        ${isActive?'🚫 Deactivate':'✅ Activate'}
                    </button>
                    <button class="btn btn-sm btn-outline-primary assign-role-btn"
                        data-user-id="${user.user_id}"
                        data-username="${escapeHtml(user.username)}">
                        Change Role
                    </button>
                </td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        container.html(html);
        $('.assign-role-btn').on('click', function () {
            $('#assign_user_id').val($(this).data('user-id'));
            $('#assign_username').text($(this).data('username'));
            $('#role_id').val('');
            $('#role-modal-error').hide();
            new bootstrap.Modal(document.getElementById('assignRoleModal')).show();
        });
    }

    function filterUsers() {
        const search = $('#user-search').val().toLowerCase();
        const filtered = allUsers.filter(u =>
            !search ||
            (u.username||'').toLowerCase().includes(search) ||
            (u.first_name||'').toLowerCase().includes(search) ||
            (u.last_name||'').toLowerCase().includes(search) ||
            (u.email||'').toLowerCase().includes(search)
        );
        renderUsers(filtered);
    }

    function assignRole() {
        const userId = $('#assign_user_id').val();
        const roleId = $('#role_id').val();
        if (!roleId) { $('#role-modal-error').text('Please select a role.').show(); return; }
        $('#assign-role-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '/api/admin.php?action=assign_role', method: 'POST',
            contentType: 'application/json', dataType: 'json',
            data: JSON.stringify({ user_id: parseInt(userId), role_id: parseInt(roleId) }),
            success: function (res) {
                $('#assign-role-btn').prop('disabled', false).html('Assign Role');
                if (res.success) { bootstrap.Modal.getInstance(document.getElementById('assignRoleModal')).hide(); showToast('Role assigned successfully!', 'success'); loadAllUsers(); }
                else { $('#role-modal-error').text(res.message).show(); }
            },
            error: function (xhr) {
                $('#assign-role-btn').prop('disabled', false).html('Assign Role');
                $('#role-modal-error').text('Server error. Please try again.').show();
            }
        });
    }

    // -------------------------------------------------------
    // ANNOUNCEMENTS
    // -------------------------------------------------------
    function loadAdminAnnouncements() {
        $.ajax({
            url: '/api/admin.php?action=announcements', method: 'GET', dataType: 'json',
            success: function (res) {
                const c = $('#admin-announcements-list');
                if (!res.success || res.data.length === 0) { c.html('<p class="text-muted">No announcements yet.</p>'); return; }
                let html = '';
                res.data.forEach(function (a) {
                    const date = new Date(a.created_at).toLocaleDateString('en-GB', {day:'numeric',month:'short',year:'numeric'});
                    const scopeBadge = a.scope === 'system' ? '<span class="badge bg-danger ms-1">System-wide</span>' : '<span class="badge bg-primary ms-1">Club</span>';
                    html += `<div class="card mb-2"><div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div><strong>${escapeHtml(a.title)}</strong>${scopeBadge}</div>
                            <small class="text-muted">${date}</small>
                        </div>
                        <p class="mb-0 small text-muted mt-1">${escapeHtml(a.message)}</p>
                        <small class="text-muted">By: ${escapeHtml(a.created_by_name)}</small>
                    </div></div>`;
                });
                c.html(html);
            }
        });
    }

    function postAdminAnnouncement() {
        const title   = $('#admin-announce-title').val().trim();
        const message = $('#admin-announce-message').val().trim();
        if (!title)   { showToast('Please enter a title.', 'danger'); return; }
        if (!message) { showToast('Please enter a message.', 'danger'); return; }
        if (!confirm('Send this announcement to ALL users?')) return;
        $('#admin-post-announce-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Sending...');
        $.ajax({
            url: '/api/admin.php?action=create_announcement', method: 'POST',
            contentType: 'application/json', data: JSON.stringify({ title, message }),
            success: function (res) {
                $('#admin-post-announce-btn').prop('disabled', false).html('📢 Send to All Users');
                if (res.success) { $('#admin-announce-title, #admin-announce-message').val(''); showToast('Announcement sent to all users!', 'success'); loadAdminAnnouncements(); }
                else showToast(res.message, 'danger');
            }
        });
    }

    // -------------------------------------------------------
    // LEADERSHIP APPLICATIONS
    // -------------------------------------------------------
    function loadLeadershipApplications() {
        $.ajax({
            url: '/api/admin.php?action=leadership_applications', method: 'GET', dataType: 'json',
            success: function (res) {
                const c = $('#leadership-container');
                if (!res.success || res.data.length === 0) {
                    c.html('<div class="text-center py-5"><div style="font-size:3rem;">✅</div><h5 class="mt-2 text-muted">No pending leadership applications</h5></div>');
                    return;
                }
                $('#tab-leadership-count').text(res.data.length).show();

                let html = `<div class="table-responsive"><table class="table">
                    <thead><tr>
                        <th>Applicant</th><th>Club</th><th>Why They Want to Lead</th><th>Experience</th><th>Vision</th><th>Availability</th><th>Applied</th><th>Actions</th>
                    </tr></thead><tbody>`;

                res.data.forEach(function (a) {
                    const date = new Date(a.applied_at).toLocaleDateString('en-GB', {day:'numeric',month:'short',year:'numeric'});
                    html += `<tr>
                        <td>
                            <strong>${escapeHtml(a.student_name)}</strong><br>
                            <small class="text-muted">${escapeHtml(a.email)}</small><br>
                            <small class="text-muted">${escapeHtml(a.registration_number || '—')}</small>
                        </td>
                        <td>
                            <strong>${escapeHtml(a.club_name)}</strong><br>
                            <span class="badge bg-secondary small">${escapeHtml(a.category || '—')}</span>
                        </td>
                        <td class="small text-muted" style="max-width:180px;">${escapeHtml((a.why_leader || '').substring(0, 100))}${a.why_leader && a.why_leader.length > 100 ? '...' : ''}</td>
                        <td class="small text-muted" style="max-width:150px;">${escapeHtml((a.experience || '').substring(0, 80))}${a.experience && a.experience.length > 80 ? '...' : ''}</td>
                        <td class="small text-muted" style="max-width:150px;">${escapeHtml((a.vision || 'Not provided').substring(0, 80))}</td>
                        <td class="small text-muted">${escapeHtml(a.availability || '—')}</td>
                        <td class="small text-muted">${date}</td>
                        <td>
                            <button class="btn btn-success btn-sm approve-leadership me-1" data-id="${a.application_id}">✓ Approve</button>
                            <button class="btn btn-danger btn-sm reject-leadership" data-id="${a.application_id}">✕ Reject</button>
                        </td>
                    </tr>`;
                });

                html += '</tbody></table></div>';
                c.html(html);

                $('.approve-leadership').on('click', function () { reviewLeadership($(this).data('id'), 'approve', $(this)); });
                $('.reject-leadership').on('click', function () { reviewLeadership($(this).data('id'), 'reject', $(this)); });
            }
        });
    }

    function reviewLeadership(applicationId, action, btn) {
        const label = action === 'approve'
            ? 'Approve this student as club leader? This will assign them the Activity Leader role.'
            : 'Reject this leadership application?';
        if (!confirm(label)) return;

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '/api/admin.php?action=review_leadership', method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ application_id: applicationId, action: action }),
            success: function (res) {
                if (res.success) {
                    btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
                    showToast(action === 'approve' ? '🏆 Leader approved and role assigned!' : 'Application rejected.', action === 'approve' ? 'success' : 'danger');
                    loadStatistics();
                } else {
                    btn.prop('disabled', false).html(action === 'approve' ? '✓ Approve' : '✕ Reject');
                    showToast(res.message, 'danger');
                }
            }
        });
    }

    // -------------------------------------------------------
    // REPORTS
    // -------------------------------------------------------
    function loadReports() {
        const container = $('#reports-container');
        container.html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
        $.when(
            $.ajax({ url: '/api/admin.php?action=all_clubs',  method: 'GET', dataType: 'json' }),
            $.ajax({ url: '/api/admin.php?action=all_users',  method: 'GET', dataType: 'json' }),
            $.ajax({ url: '/api/admin.php?action=system_statistics', method: 'GET', dataType: 'json' })
        ).done(function (clubsRes, usersRes, statsRes) {
            const clubs = clubsRes[0].data || [];
            const users = usersRes[0].data || [];
            const stats = statsRes[0].data || {};
            const approvedClubs = clubs.filter(c => c.status === 'approved').length;
            const pendingClubs  = clubs.filter(c => c.status === 'pending').length;
            const rejectedClubs = clubs.filter(c => c.status === 'rejected').length;

            const catCount = {};
            clubs.forEach(c => { const cat = c.category || 'Other'; catCount[cat] = (catCount[cat]||0)+1; });
            const roleCount = {};
            users.forEach(u => { const r = u.roles || 'Student'; roleCount[r] = (roleCount[r]||0)+1; });

            container.html(`
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0">📊 System Reports</h6>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success btn-sm" id="export-csv-btn">⬇️ Export CSV</button>
                        <button class="btn btn-primary btn-sm" id="export-json-btn">⬇️ Export JSON</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h6 class="fw-bold text-muted text-uppercase mb-3">📊 System Summary</h6>
                        <table class="table table-sm table-bordered">
                            <tbody>
                                <tr><td>Total Users</td><td><strong>${stats.total_users||0}</strong></td></tr>
                                <tr><td>Total Clubs</td><td><strong>${clubs.length}</strong></td></tr>
                                <tr><td>Approved Clubs</td><td><strong class="text-success">${approvedClubs}</strong></td></tr>
                                <tr><td>Pending Clubs</td><td><strong class="text-warning">${pendingClubs}</strong></td></tr>
                                <tr><td>Rejected Clubs</td><td><strong class="text-danger">${rejectedClubs}</strong></td></tr>
                                <tr><td>Total Events</td><td><strong>${stats.total_events||0}</strong></td></tr>
                                <tr><td>Total Registrations</td><td><strong>${stats.total_registrations||0}</strong></td></tr>
                                <tr><td>Total Attendance</td><td><strong>${stats.total_attendance||0}</strong></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h6 class="fw-bold text-muted text-uppercase mb-3">🏛️ Clubs by Category</h6>
                        <table class="table table-sm table-bordered">
                            <thead><tr><th>Category</th><th>Count</th></tr></thead>
                            <tbody id="cat-report-body"></tbody>
                        </table>
                    </div>
                    <div class="col-12">
                        <h6 class="fw-bold text-muted text-uppercase mb-3">👥 User Roles Breakdown</h6>
                        <table class="table table-sm table-bordered">
                            <thead><tr><th>Role</th><th>Count</th></tr></thead>
                            <tbody id="role-report-body"></tbody>
                        </table>
                    </div>
                </div>`);

            Object.entries(catCount).forEach(([cat,count]) => $('#cat-report-body').append(`<tr><td>${escapeHtml(cat)}</td><td><strong>${count}</strong></td></tr>`));
            Object.entries(roleCount).forEach(([role,count]) => $('#role-report-body').append(`<tr><td>${escapeHtml(role)}</td><td><strong>${count}</strong></td></tr>`));

            // Export CSV
            $('#export-csv-btn').on('click', function () {
                const rows = [
                    ['CARMS System Report - ' + new Date().toLocaleDateString()],
                    [],
                    ['SYSTEM SUMMARY'],
                    ['Metric', 'Value'],
                    ['Total Users', stats.total_users||0],
                    ['Total Clubs', clubs.length],
                    ['Approved Clubs', approvedClubs],
                    ['Pending Clubs', pendingClubs],
                    ['Rejected Clubs', rejectedClubs],
                    ['Total Events', stats.total_events||0],
                    ['Total Registrations', stats.total_registrations||0],
                    ['Total Attendance', stats.total_attendance||0],
                    [],
                    ['CLUBS LIST'],
                    ['Club Name', 'Category', 'Leader', 'Status', 'Created'],
                    ...clubs.map(c => [c.club_name, c.category||'Uncategorized', c.leader_name||'—', c.status, new Date(c.created_at).toLocaleDateString()]),
                    [],
                    ['USERS LIST'],
                    ['Name', 'Username', 'Email', 'Role', 'Status'],
                    ...users.map(u => [(u.first_name||'')+' '+(u.last_name||''), u.username, u.email, u.roles||'Student', u.is_active==1?'Active':'Inactive'])
                ];
                const csv = rows.map(r => Array.isArray(r) ? r.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',') : r).join('\n');
                const blob = new Blob([csv], { type: 'text/csv' });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'CARMS_Report_' + new Date().toISOString().slice(0,10) + '.csv';
                a.click();
                showToast('CSV exported!', 'success');
            });

            // Export JSON
            $('#export-json-btn').on('click', function () {
                const data = { generated_at: new Date().toISOString(), statistics: stats, clubs: clubs, users: users.map(u => ({id:u.user_id,name:(u.first_name||'')+' '+(u.last_name||''),username:u.username,email:u.email,role:u.roles,status:u.is_active==1?'active':'inactive'})) };
                const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'CARMS_Report_' + new Date().toISOString().slice(0,10) + '.json';
                a.click();
                showToast('JSON exported!', 'success');
            });
        });
    }

    // -------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------
    function showToast(message, type) {
        const id = 'toast-' + Date.now();
        const bg = type === 'success' ? 'bg-success' : 'bg-danger';
        if (!$('#toast-container').length) $('body').append('<div id="toast-container"></div>');
        $('#toast-container').append(`
            <div id="${id}" class="toast-notification ${bg} text-white">
                <span>${escapeHtml(message)}</span>
                <button onclick="document.getElementById('${id}').remove()" style="background:none;border:none;color:white;font-size:1.2rem;cursor:pointer;margin-left:10px;">×</button>
            </div>`);
        setTimeout(() => $('#' + id).fadeOut(400, function () { $(this).remove(); }), 4000);
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    }

});
