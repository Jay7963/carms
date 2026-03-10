/**
 * Activity Leader Hub JavaScript - Enhanced
 */
$(document).ready(function () {

    let leaderClubs = [];

    // Init
    loadStats();
    loadApplications();
    loadNotifications();

    // Tab handlers
    $('#tab-clubs').on('click', () => loadClubs());
    $('#tab-members').on('click', () => populateClubSelects());
    $('#tab-events').on('click', () => populateClubSelects());
    $('#tab-announce').on('click', () => populateClubSelects());

    // Club selects
    $('#members-club-select').on('change', function () { if ($(this).val()) loadMembers($(this).val()); });
    $('#events-club-select').on('change', function () { if ($(this).val()) loadEvents($(this).val()); });
    $('#announce-club-select').on('change', function () { if ($(this).val()) loadAnnouncements($(this).val()); });

    // Icon picker
    $(document).on('click', '.icon-pick', function () {
        $('.icon-pick').removeClass('btn-primary').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-primary');
        const icon = $(this).data('icon');
        $('#new-club-icon').val(icon);
        $('#selected-icon-preview').text(icon);
    });

    // Custom category toggle
    $('#new-club-category').on('change', function () {
        $('#custom-category-field').toggle($(this).val() === 'other');
    });

    // Create club
    $('#create-club-btn-open').on('click', () => new bootstrap.Modal(document.getElementById('createClubModal')).show());
    $('#submit-club-btn').on('click', createClub);

    // Create event
    $('#submit-event-btn').on('click', createEvent);

    // Post announcement
    $('#post-announce-btn').on('click', postAnnouncement);

    // Notifications
    $('#notif-btn').on('click', function (e) {
        e.stopPropagation();
        $('#notif-dropdown').toggle();
    });
    $('#mark-read-btn').on('click', markNotificationsRead);
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#notif-dropdown, #notif-btn').length) $('#notif-dropdown').hide();
    });

    // -------------------------------------------------------
    // STATS
    // -------------------------------------------------------
    function loadStats() {
        $.ajax({
            url: '/api/leader.php?action=my_clubs', method: 'GET', dataType: 'json',
            success: function (res) {
                if (!res.success) return;
                leaderClubs = res.data;
                const totalMembers = leaderClubs.reduce((a, c) => a + parseInt(c.member_count || 0), 0);
                const totalPending = leaderClubs.reduce((a, c) => a + parseInt(c.pending_count || 0), 0);
                const totalEvents  = leaderClubs.reduce((a, c) => a + parseInt(c.event_count  || 0), 0);

                $('#leader-stats').html(`
                    <div class="col-6 col-md-3 mb-3"><div class="stat-card h-100" style="background:linear-gradient(135deg,#1a73e8,#0d47a1)">
                        <p>My Clubs</p><h2>${leaderClubs.length}</h2></div></div>
                    <div class="col-6 col-md-3 mb-3"><div class="stat-card h-100" style="background:linear-gradient(135deg,#27ae60,#1e8449)">
                        <p>Total Members</p><h2>${totalMembers}</h2></div></div>
                    <div class="col-6 col-md-3 mb-3"><div class="stat-card h-100" style="background:linear-gradient(135deg,#e67e22,#ca6f1e)">
                        <p>Total Events</p><h2>${totalEvents}</h2></div></div>
                    <div class="col-6 col-md-3 mb-3"><div class="stat-card h-100" style="background:linear-gradient(135deg,#f39c12,#d68910)">
                        <p>Pending Applications</p><h2>${totalPending}</h2></div></div>`);

                if (totalPending > 0) $('#app-count').text(totalPending).show();
            }
        });
    }

    // -------------------------------------------------------
    // APPLICATIONS
    // -------------------------------------------------------
    function loadApplications() {
        $.ajax({
            url: '/api/leader.php?action=pending_applications', method: 'GET', dataType: 'json',
            success: function (res) {
                const c = $('#applications-container');
                if (!res.success || res.data.length === 0) {
                    c.html('<div class="text-center py-5"><div style="font-size:3rem;">✅</div><h5 class="mt-2 text-muted">No pending applications</h5></div>');
                    return;
                }
                $('#app-count').text(res.data.length).show();
                let html = `<div class="table-responsive"><table class="table">
                    <thead><tr><th>Student</th><th>Club</th><th>Reg. No.</th><th>Applied</th><th>Actions</th></tr></thead><tbody>`;
                res.data.forEach(a => {
                    const date = new Date(a.applied_at).toLocaleDateString('en-GB', {day:'numeric',month:'short',year:'numeric'});
                    html += `<tr>
                        <td><strong>${escapeHtml(a.student_name)}</strong><br><small class="text-muted">${escapeHtml(a.email)}</small></td>
                        <td><span class="badge bg-secondary">${escapeHtml(a.club_name)}</span></td>
                        <td class="text-muted small">${escapeHtml(a.registration_number || '—')}</td>
                        <td class="text-muted small">${date}</td>
                        <td>
                            <button class="btn btn-success btn-sm approve-app me-1" data-id="${a.member_id}">✓ Approve</button>
                            <button class="btn btn-danger btn-sm reject-app" data-id="${a.member_id}">✕ Reject</button>
                        </td></tr>`;
                });
                html += '</tbody></table></div>';
                c.html(html);

                $('.approve-app').on('click', function () { reviewApp($(this).data('id'), 'approve', $(this)); });
                $('.reject-app').on('click', function () { reviewApp($(this).data('id'), 'reject', $(this)); });
            }
        });
    }

    function reviewApp(memberId, action, btn) {
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '/api/leader.php?action=review_application', method: 'POST',
            contentType: 'application/json', data: JSON.stringify({ member_id: memberId, action: action }),
            success: function (res) {
                if (res.success) {
                    btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
                    showToast(action === 'approve' ? 'Application approved!' : 'Application rejected.', action === 'approve' ? 'success' : 'danger');
                    loadStats();
                } else showToast(res.message, 'danger');
            }
        });
    }

    // -------------------------------------------------------
    // CLUBS
    // -------------------------------------------------------
    function loadClubs() {
        $.ajax({
            url: '/api/leader.php?action=my_clubs', method: 'GET', dataType: 'json',
            success: function (res) {
                const c = $('#clubs-container');
                if (!res.success || res.data.length === 0) {
                    c.html('<p class="text-muted">No clubs yet. Create your first club!</p>'); return;
                }
                leaderClubs = res.data;
                let html = '<div class="row">';
                res.data.forEach(club => {
                    const statusColors = {approved:'success',pending:'warning',rejected:'danger'};
                    const sc = statusColors[club.status] || 'secondary';
                    html += `<div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100" style="border-top:3px solid #1a73e8;">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between mb-2">
                                    <h5 class="card-title mb-0">${escapeHtml(club.club_name)}</h5>
                                    <span class="badge bg-${sc} ${club.status==='pending'?'text-dark':''}">${club.status}</span>
                                </div>
                                <p class="text-muted small flex-grow-1">${escapeHtml(club.description)}</p>
                                <div class="d-flex gap-3 mb-3 small text-muted">
                                    <span>👥 ${club.member_count} members</span>
                                    <span>📅 ${club.event_count} events</span>
                                    ${club.pending_count > 0 ? `<span class="text-warning fw-bold">⏳ ${club.pending_count} pending</span>` : ''}
                                </div>
                                ${club.status === 'approved' ? `
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary btn-sm flex-fill create-event-btn" data-club-id="${club.club_id}" data-club-name="${escapeHtml(club.club_name)}">+ Event</button>
                                    <button class="btn btn-outline-danger btn-sm delete-club-btn" data-club-id="${club.club_id}" data-club-name="${escapeHtml(club.club_name)}" title="Delete Club">🗑️</button>
                                </div>` : `
                                <div class="d-flex gap-2 align-items-center">
                                    <p class="text-muted small mb-0 flex-grow-1">⏳ Awaiting admin approval</p>
                                    <button class="btn btn-outline-danger btn-sm delete-club-btn" data-club-id="${club.club_id}" data-club-name="${escapeHtml(club.club_name)}" title="Delete Club">🗑️</button>
                                </div>`}
                            </div>
                        </div>
                    </div>`;
                });
                html += '</div>';
                c.html(html);
                $('.create-event-btn').on('click', function () {
                    $('#event-club-id').val($(this).data('club-id'));
                    $('#createEventModal .modal-title').text('Create Event — ' + $(this).data('club-name'));
                    new bootstrap.Modal(document.getElementById('createEventModal')).show();
                });
                $('.delete-club-btn').on('click', function () {
                    const clubId = $(this).data('club-id');
                    const clubName = $(this).data('club-name');
                    if (!confirm(`⚠️ Delete "${clubName}"? This will remove all related events and members. This cannot be undone.`)) return;
                    const btn = $(this);
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                    $.ajax({
                        url: '/api/leader.php?action=delete_club', method: 'POST',
                        contentType: 'application/json', dataType: 'json',
                        data: JSON.stringify({ club_id: parseInt(clubId) }),
                        success: function (res) {
                            if (res.success) { showToast(res.message, 'success'); loadStats(); loadClubs(); }
                            else { btn.prop('disabled', false).html('🗑️'); showToast(res.message, 'danger'); }
                        }
                    });
                });
            }
        });
    }

    function createClub() {
        const name     = $('#new-club-name').val().trim();
        const desc     = $('#new-club-desc').val().trim();
        const catVal   = $('#new-club-category').val();
        const category = catVal === 'other' ? $('#new-club-custom-category').val().trim() : catVal;
        const icon     = $('#new-club-icon').val() || '🏛️';

        $('#club-modal-error').hide();
        if (!name)     { $('#club-modal-error').text('Club name is required.').show(); return; }
        if (!desc)     { $('#club-modal-error').text('Description is required.').show(); return; }
        if (!category) { $('#club-modal-error').text('Please select or enter a category.').show(); return; }

        $('#submit-club-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Submitting...');
        $.ajax({
            url: '/api/leader.php?action=create_club', method: 'POST',
            contentType: 'application/json', dataType: 'json',
            data: JSON.stringify({ club_name: name, description: desc, category: category, icon: icon }),
            success: function (res) {
                $('#submit-club-btn').prop('disabled', false).html('Submit for Approval');
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('createClubModal')).hide();
                    $('#new-club-name, #new-club-desc, #new-club-custom-category').val('');
                    $('#new-club-category').val('');
                    $('#new-club-icon').val('🏛️');
                    $('#selected-icon-preview').text('🏛️');
                    $('.icon-pick').removeClass('btn-primary').addClass('btn-outline-secondary');
                    showToast('Club submitted for admin approval!', 'success');
                    loadStats(); loadClubs();
                } else {
                    $('#club-modal-error').text(res.message).show();
                }
            },
            error: function () {
                $('#submit-club-btn').prop('disabled', false).html('Submit for Approval');
                $('#club-modal-error').text('Server error. Please try again.').show();
            }
        });
    }

    // -------------------------------------------------------
    // MEMBERS
    // -------------------------------------------------------
    function loadMembers(clubId) {
        $.ajax({
            url: '/api/leader.php?action=club_members&club_id=' + clubId, method: 'GET', dataType: 'json',
            success: function (res) {
                const c = $('#members-container');
                if (!res.success || res.data.length === 0) {
                    c.html('<p class="text-muted">No approved members yet.</p>'); return;
                }
                let html = `<div class="table-responsive"><table class="table">
                    <thead><tr><th>Student</th><th>Email</th><th>Reg. No.</th><th>Joined</th><th>Action</th></tr></thead><tbody>`;
                res.data.forEach(m => {
                    const date = new Date(m.applied_at).toLocaleDateString('en-GB');
                    html += `<tr>
                        <td><strong>${escapeHtml(m.student_name)}</strong></td>
                        <td class="text-muted small">${escapeHtml(m.email)}</td>
                        <td class="text-muted small">${escapeHtml(m.registration_number || '—')}</td>
                        <td class="text-muted small">${date}</td>
                        <td><button class="btn btn-outline-danger btn-sm remove-member" data-id="${m.member_id}">Remove</button></td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
                c.html(html);
                $('.remove-member').on('click', function () {
                    if (!confirm('Remove this member from the club?')) return;
                    removeMember($(this).data('id'), $(this));
                });
            }
        });
    }

    function removeMember(memberId, btn) {
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '/api/leader.php?action=remove_member', method: 'POST',
            contentType: 'application/json', data: JSON.stringify({ member_id: memberId }),
            success: function (res) {
                if (res.success) { btn.closest('tr').fadeOut(300, function () { $(this).remove(); }); showToast('Member removed.', 'success'); }
                else { btn.prop('disabled', false).html('Remove'); showToast(res.message, 'danger'); }
            }
        });
    }

    // -------------------------------------------------------
    // EVENTS
    // -------------------------------------------------------
    function loadEvents(clubId) {
        $.ajax({
            url: '/api/leader.php?action=club_events&club_id=' + clubId, method: 'GET', dataType: 'json',
            success: function (res) {
                const c = $('#events-container');
                if (!res.success || res.data.length === 0) {
                    c.html('<p class="text-muted">No events yet. <button class="btn btn-sm btn-primary create-event-inline ms-2">+ Create Event</button></p>');
                    c.find('.create-event-inline').on('click', function () {
                        $('#event-club-id').val(clubId);
                        new bootstrap.Modal(document.getElementById('createEventModal')).show();
                    });
                    return;
                }
                let html = `<div class="mb-2"><button class="btn btn-primary btn-sm create-event-top" data-club-id="${clubId}">+ Create Event</button></div>`;
                html += `<div class="table-responsive"><table class="table">
                    <thead><tr><th>Event</th><th>Date</th><th>Location</th><th>Registrations</th><th>Attendance</th><th>Actions</th></tr></thead><tbody>`;
                res.data.forEach(e => {
                    const date = new Date(e.event_date).toLocaleDateString('en-GB', {weekday:'short',day:'numeric',month:'short',year:'numeric'});
                    html += `<tr>
                        <td><strong>${escapeHtml(e.event_name)}</strong></td>
                        <td class="text-muted small">${date}</td>
                        <td class="text-muted small">${escapeHtml(e.location)}</td>
                        <td><span class="badge bg-info text-dark">${e.registration_count}</span></td>
                        <td><span class="badge bg-success">${e.attendance_count} present</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary take-attend-btn me-1" data-event-id="${e.event_id}" data-event-name="${escapeHtml(e.event_name)}">📊 Attendance</button>
                            <button class="btn btn-sm btn-outline-danger delete-event-btn" data-event-id="${e.event_id}">🗑️</button>
                        </td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
                c.html(html);
                c.find('.create-event-top').on('click', function () {
                    $('#event-club-id').val($(this).data('club-id'));
                    new bootstrap.Modal(document.getElementById('createEventModal')).show();
                });
                $('.take-attend-btn').on('click', function () { openAttendanceModal($(this).data('event-id'), $(this).data('event-name')); });
                $('.delete-event-btn').on('click', function () {
                    if (!confirm('Delete this event?')) return;
                    deleteEvent($(this).data('event-id'), clubId);
                });
            }
        });
    }

    function createEvent() {
        const clubId   = $('#event-club-id').val();
        const name     = $('#new-event-name').val().trim();
        const desc     = $('#new-event-desc').val().trim();
        const date     = $('#new-event-date').val();
        const time     = $('#new-event-time').val();
        const location = $('#new-event-location').val().trim();

        if (!name || !date || !location) { $('#event-modal-error').text('Please fill all required fields.').show(); return; }

        $('#submit-event-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>');
        $.ajax({
            url: '/api/leader.php?action=create_event', method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ club_id: clubId, event_name: name, description: desc, event_date: date, event_time: time, location: location }),
            success: function (res) {
                $('#submit-event-btn').prop('disabled', false).html('Create Event');
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('createEventModal')).hide();
                    $('#new-event-name, #new-event-desc, #new-event-date, #new-event-time, #new-event-location').val('');
                    showToast('Event created!', 'success');
                    loadEvents(clubId);
                } else $('#event-modal-error').text(res.message).show();
            }
        });
    }

    function deleteEvent(eventId, clubId) {
        $.ajax({
            url: '/api/leader.php?action=delete_event', method: 'POST',
            contentType: 'application/json', data: JSON.stringify({ event_id: eventId }),
            success: function (res) {
                if (res.success) { showToast('Event deleted.', 'success'); loadEvents(clubId); }
                else showToast(res.message, 'danger');
            }
        });
    }

    // -------------------------------------------------------
    // ATTENDANCE
    // -------------------------------------------------------
    function openAttendanceModal(eventId, eventName) {
        $('#attend-event-id').val(eventId);
        $('#attendanceModal .modal-title').text('📊 Attendance — ' + eventName);
        new bootstrap.Modal(document.getElementById('attendanceModal')).show();
        $.ajax({
            url: '/api/leader.php?action=event_registrations&event_id=' + eventId, method: 'GET', dataType: 'json',
            success: function (res) {
                const c = $('#attendance-list');
                if (!res.success || res.data.length === 0) { c.html('<p class="text-muted text-center py-3">No registrations for this event.</p>'); return; }
                let html = `<table class="table table-sm"><thead><tr><th>Student</th><th>Reg. No.</th><th>Status</th></tr></thead><tbody>`;
                res.data.forEach(s => {
                    const statuses = ['present','absent','late'];
                    const colors   = {present:'success', absent:'danger', late:'warning', not_marked:'secondary'};
                    const options  = statuses.map(st => `<option value="${st}" ${s.attendance_status===st?'selected':''}>${st.charAt(0).toUpperCase()+st.slice(1)}</option>`).join('');
                    html += `<tr>
                        <td><strong>${escapeHtml(s.student_name)}</strong></td>
                        <td class="text-muted small">${escapeHtml(s.registration_number || '—')}</td>
                        <td>
                            <select class="form-select form-select-sm attend-select w-auto" data-user-id="${s.user_id}" style="min-width:110px;">
                                <option value="">Mark...</option>${options}
                            </select>
                        </td>
                    </tr>`;
                });
                html += '</tbody></table>';
                c.html(html);
                $('.attend-select').on('change', function () {
                    const userId = $(this).data('user-id');
                    const status = $(this).val();
                    if (!status) return;
                    $.ajax({
                        url: '/api/leader.php?action=mark_attendance', method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ event_id: eventId, user_id: userId, status: status }),
                        success: function (res) { if (res.success) showToast('Attendance marked!', 'success'); }
                    });
                });
            }
        });
    }

    // -------------------------------------------------------
    // ANNOUNCEMENTS
    // -------------------------------------------------------
    function loadAnnouncements(clubId) {
        $.ajax({
            url: '/api/leader.php?action=club_announcements&club_id=' + clubId, method: 'GET', dataType: 'json',
            success: function (res) {
                const c = $('#announcements-list');
                if (!res.success || res.data.length === 0) { c.html('<p class="text-muted">No announcements yet.</p>'); return; }
                let html = '';
                res.data.forEach(a => {
                    const date = new Date(a.created_at).toLocaleDateString('en-GB', {day:'numeric',month:'short',year:'numeric'});
                    html += `<div class="card mb-2"><div class="card-body py-2">
                        <div class="d-flex justify-content-between"><strong>${escapeHtml(a.title)}</strong><small class="text-muted">${date}</small></div>
                        <p class="mb-0 small text-muted mt-1">${escapeHtml(a.message)}</p>
                    </div></div>`;
                });
                c.html(html);
            }
        });
    }

    function postAnnouncement() {
        const clubId  = $('#announce-club-select').val();
        const title   = $('#announce-title').val().trim();
        const message = $('#announce-message').val().trim();
        if (!clubId)  { showToast('Please select a club.', 'danger'); return; }
        if (!title)   { showToast('Please enter a title.', 'danger'); return; }
        if (!message) { showToast('Please enter a message.', 'danger'); return; }

        $('#post-announce-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>');
        $.ajax({
            url: '/api/leader.php?action=post_announcement', method: 'POST',
            contentType: 'application/json', data: JSON.stringify({ club_id: clubId, title, message }),
            success: function (res) {
                $('#post-announce-btn').prop('disabled', false).html('📢 Post Announcement');
                if (res.success) {
                    $('#announce-title, #announce-message').val('');
                    showToast('Announcement posted to all club members!', 'success');
                    loadAnnouncements(clubId);
                } else showToast(res.message, 'danger');
            }
        });
    }

    // -------------------------------------------------------
    // NOTIFICATIONS
    // -------------------------------------------------------
    function loadNotifications() {
        $.ajax({
            url: '/api/leader.php?action=notifications', method: 'GET', dataType: 'json',
            success: function (res) {
                if (!res.success) return;
                const unread = res.data.filter(n => !n.is_read).length;
                if (unread > 0) $('#notif-count').text(unread).show();
                else $('#notif-count').hide();

                let html = '';
                if (res.data.length === 0) { html = '<p class="text-muted text-center p-3">No notifications.</p>'; }
                else {
                    res.data.forEach(n => {
                        const typeColors = {success:'#27ae60', danger:'#e74c3c', info:'#3498db'};
                        const color = typeColors[n.type] || '#3498db';
                        const date  = new Date(n.created_at).toLocaleDateString('en-GB');
                        html += `<div class="p-3 border-bottom ${n.is_read ? '' : 'bg-light'}">
                            <div class="d-flex gap-2">
                                <div style="width:4px;background:${color};border-radius:2px;flex-shrink:0;"></div>
                                <div><strong class="small">${escapeHtml(n.title)}</strong><br>
                                <span class="small text-muted">${escapeHtml(n.message)}</span><br>
                                <span class="small text-muted">${date}</span></div>
                            </div>
                        </div>`;
                    });
                }
                $('#notif-list').html(html);
            }
        });
    }

    function markNotificationsRead() {
        $.ajax({
            url: '/api/leader.php?action=mark_notifications_read', method: 'POST',
            contentType: 'application/json', data: JSON.stringify({}),
            success: function () { $('#notif-count').hide(); loadNotifications(); }
        });
    }

    // -------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------
    function populateClubSelects() {
        $.ajax({
            url: '/api/leader.php?action=my_clubs', method: 'GET', dataType: 'json',
            success: function (res) {
                if (!res.success) return;
                leaderClubs = res.data.filter(c => c.status === 'approved');
                const selects = ['#members-club-select', '#events-club-select', '#announce-club-select'];
                selects.forEach(sel => {
                    const current = $(sel).val();
                    $(sel).html('<option value="">Select a club...</option>');
                    leaderClubs.forEach(c => $(sel).append(`<option value="${c.club_id}">${escapeHtml(c.club_name)}</option>`));
                    if (current) $(sel).val(current);
                });
            }
        });
    }

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
