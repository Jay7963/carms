/**
 * Student Portal JavaScript
 */

$(document).ready(function () {

    loadUpcomingEvents();
    loadAvailableClubs();      // load clubs on page load so filters work immediately
    loadStudentNotifications();

    // Tab handlers - don't reload if data already cached
    $('#clubs-tab').on('click', function () {
        if (!allClubsData.length) loadAvailableClubs();
    });
    $('#upcoming-tab').on('click', function () { loadUpcomingEvents(); });

    // ── SMART FILTER BAR ──────────────────────────────────
    let searchTimeout;
    $('#search-input').on('input', function () {
        const val = $(this).val();
        $('#search-clear').toggle(val.length > 0);
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function () { applyFilters(); }, 300);
    });

    $('#search-clear').on('click', function () {
        $('#search-input').val('');
        $(this).hide();
        applyFilters();
    });

    $('#filter-category, #filter-status').on('change', function () {
        // category/status filters always apply to clubs tab - switch to it
        if ($('.nav-tabs .nav-link.active').attr('id') !== 'clubs-tab') {
            $('#clubs-tab').tab('show');
        }
        applyFilters();
    });

    $('#filter-reset').on('click', function () {
        $('#search-input').val('');
        $('#search-clear').hide();
        $('#filter-category, #filter-status').val('');
        loadUpcomingEvents();
        if (allClubsData.length) displayClubsByCategory(allClubsData, $('#clubs-container'));
        else loadAvailableClubs();
    });

    function applyFilters() {
        const activeTab = $('.nav-tabs .nav-link.active').attr('id');
        const category  = $('#filter-category').val();
        const status    = $('#filter-status').val();

        // If a club-specific filter is active, always filter clubs
        if (category || status) {
            if (activeTab !== 'clubs-tab') $('#clubs-tab').tab('show');
            filterClubs();
        } else if (activeTab === 'clubs-tab') {
            filterClubs();
        } else {
            searchEvents();
        }
    }

    function searchEvents() {
        const term = $('#search-input').val().trim();
        if (!term) { loadUpcomingEvents(); return; }
        const container = $('#upcoming-events-container');
        container.html(loadingSpinner());
        $.ajax({
            url: '/api/student.php?action=search_events&q=' + encodeURIComponent(term),
            method: 'GET', dataType: 'json',
            success: function (res) {
                if (res.success && res.data.length > 0) displayEvents(res.data, container);
                else container.html(emptyState('No events found for "' + escapeHtml(term) + '".'));
            },
            error: function () { container.html(errorState('Search failed.')); }
        });
    }

    // ── CLUB AVATAR HELPERS ───────────────────────────────
    function clubInitials(name) {
        const words = name.replace(/[()]/g, '').trim().split(/\s+/);
        if (words.length >= 2) return (words[0][0] + words[1][0]).toUpperCase();
        return name.substring(0, 2).toUpperCase();
    }

    const colorDarken = {
        '#1a73e8':'#1255a8','#e91e8c':'#aa1567',
        '#0d6e6e':'#095050','#7b2d8b':'#581f65',
        '#e67e22':'#b86117','#27ae60':'#1e8449',
    };

    function clubAvatar(name, color) {
        const initials = clubInitials(name);
        const dark = colorDarken[color] || color;
        return `<div style="width:54px;height:54px;border-radius:12px;background:linear-gradient(135deg,${color},${dark});display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.15rem;color:white;letter-spacing:1px;box-shadow:0 3px 8px ${color}66;">${initials}</div>`;
    }

    const categoryOrder = ['Sports - Men','Sports - Women','Academic & Professional','Arts & Culture','Community & Service','Religious'];

    const categoryColors = {
        'Sports - Men':'#1a73e8','Sports - Women':'#e91e8c',
        'Academic & Professional':'#0d6e6e','Arts & Culture':'#7b2d8b',
        'Community & Service':'#e67e22','Religious':'#27ae60',
    };

    let userClubIds = [];     // approved members only
    let pendingClubIds = [];  // pending applications
    let allClubsData = [];

    function refreshMyClubs(callback) {
        $.ajax({
            url: '/api/student.php?action=my_clubs', method: 'GET', dataType: 'json',
            success: function (res) {
                if (res.success) {
                    userClubIds = [];
                    pendingClubIds = [];
                    res.data.forEach(function (c) {
                        if (c.membership_status === 'approved') userClubIds.push(parseInt(c.club_id));
                        else if (c.membership_status === 'pending') pendingClubIds.push(parseInt(c.club_id));
                    });
                }
                if (callback) callback();
            }
        });
    }
    refreshMyClubs();

    // ── LOAD EVENTS ───────────────────────────────────────
    function loadUpcomingEvents() {
        const container = $('#upcoming-events-container');
        container.html(loadingSpinner());
        $.ajax({
            url: '/api/student.php?action=upcoming_events', method: 'GET', dataType: 'json',
            success: function (res) {
                if (res.success && res.data.length > 0) displayEvents(res.data, container);
                else container.html(emptyState('No upcoming events at the moment.'));
            },
            error: function (xhr) {
                if (xhr.status === 401) window.location.href = '/login';
                else container.html(errorState('Failed to load events.'));
            }
        });
    }

    // ── LOAD CLUBS ────────────────────────────────────────
    function loadAvailableClubs(callback) {
        const container = $('#clubs-container');
        container.html(loadingSpinner());
        // Always refresh membership state alongside clubs
        $.ajax({
            url: '/api/student.php?action=my_clubs', method: 'GET', dataType: 'json',
            success: function (memberRes) {
                if (memberRes.success) {
                    userClubIds = [];
                    pendingClubIds = [];
                    memberRes.data.forEach(function (c) {
                        if (c.membership_status === 'approved') userClubIds.push(parseInt(c.club_id));
                        else if (c.membership_status === 'pending') pendingClubIds.push(parseInt(c.club_id));
                    });
                }
                $.ajax({
                    url: '/api/student.php?action=available_clubs', method: 'GET', dataType: 'json',
                    success: function (res) {
                        if (res.success && res.data.length > 0) {
                            allClubsData = res.data;
                            displayClubsByCategory(res.data, container);
                            if (callback) callback();
                        } else container.html(emptyState('No clubs available.'));
                    },
                    error: function (xhr) {
                        if (xhr.status === 401) window.location.href = '/login';
                        else container.html(errorState('Failed to load clubs.'));
                    }
                });
            },
            error: function () {
                // fallback: load clubs without membership refresh
                $.ajax({
                    url: '/api/student.php?action=available_clubs', method: 'GET', dataType: 'json',
                    success: function (res) {
                        if (res.success && res.data.length > 0) {
                            allClubsData = res.data;
                            displayClubsByCategory(res.data, container);
                            if (callback) callback();
                        } else container.html(emptyState('No clubs available.'));
                    },
                    error: function (xhr) {
                        if (xhr.status === 401) window.location.href = '/login';
                        else container.html(errorState('Failed to load clubs.'));
                    }
                });
            }
        });
    }

    function filterClubs() {
        const search   = $('#search-input').val().toLowerCase().trim();
        const category = $('#filter-category').val();
        const status   = $('#filter-status').val();

        if (!allClubsData.length) {
            // data not loaded yet — load then re-filter
            loadAvailableClubs(function () { filterClubs(); });
            return;
        }

        const filtered = allClubsData.filter(function(c) {
            const matchSearch   = !search ||
                c.club_name.toLowerCase().includes(search) ||
                (c.description || '').toLowerCase().includes(search) ||
                (c.category || '').toLowerCase().includes(search);
            const matchCategory = !category || c.category === category;
            const isMember      = userClubIds.includes(parseInt(c.club_id));
            const isPending     = pendingClubIds.includes(parseInt(c.club_id));
            const matchStatus   =
                !status ||
                (status === 'joined'     && isMember) ||
                (status === 'not_joined' && !isMember && !isPending);
            return matchSearch && matchCategory && matchStatus;
        });

        const container = $('#clubs-container');
        if (filtered.length === 0) {
            container.html(emptyState('No clubs match your filters.'));
        } else {
            displayClubsByCategory(filtered, container);
        }
    }

    // ── DISPLAY EVENTS ────────────────────────────────────
    function displayEvents(events, container) {
        container.empty();
        events.forEach(function (event) {
            const date           = new Date(event.event_date);
            const formattedDate  = date.toLocaleDateString('en-GB', {weekday:'short',day:'numeric',month:'short',year:'numeric'});
            const formattedTime  = formatTime(event.event_time);
            const alreadyReg     = parseInt(event.already_registered) > 0;
            const membership     = event.membership_status || 'none'; // 'approved', 'pending', 'none'
            const maxCap         = parseInt(event.max_capacity) || 0;
            const regCount       = parseInt(event.registered_count) || 0;
            const isFull         = maxCap > 0 && regCount >= maxCap;
            const spotsLeft      = maxCap > 0 ? maxCap - regCount : null;

            // Capacity bar
            const capacityHtml = maxCap > 0 ? `
                <div class="mb-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>👥 Registered</span>
                        <span>${regCount} / ${maxCap}</span>
                    </div>
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar ${isFull ? 'bg-danger' : spotsLeft <= 5 ? 'bg-warning' : 'bg-success'}"
                            style="width:${Math.min((regCount/maxCap)*100,100)}%"></div>
                    </div>
                    ${isFull ? '<small class="text-danger">Event is full</small>' : spotsLeft <= 5 ? `<small class="text-warning">Only ${spotsLeft} spot${spotsLeft===1?'':'s'} left!</small>` : ''}
                </div>` : '';

            // Action button based on membership + capacity + registration status
            let actionHtml;
            if (alreadyReg) {
                actionHtml = `<button class="btn btn-success btn-sm w-100" disabled>✓ Already Registered</button>`;
            } else if (isFull) {
                actionHtml = `<button class="btn btn-secondary btn-sm w-100" disabled>🚫 Event Full</button>`;
            } else if (membership === 'approved') {
                actionHtml = `<button class="btn btn-primary btn-sm w-100 register-btn"
                    data-event-id="${event.event_id}"
                    data-event-name="${escapeHtml(event.event_name)}"
                    data-club-name="${escapeHtml(event.club_name)}">
                    Register for Event
                </button>`;
            } else if (membership === 'pending') {
                actionHtml = `<div class="alert alert-info py-2 px-3 mb-0 small text-center">
                    ⏳ Membership pending — you can register once approved
                </div>`;
            } else {
                actionHtml = `<div class="alert alert-warning py-2 px-3 mb-0 small text-center">
                    🔒 Members only — <a href="#" class="text-warning fw-bold switch-to-club"
                        data-club-id="${event.club_id}"
                        data-club-name="${escapeHtml(event.club_name)}">Join ${escapeHtml(event.club_name)} →</a>
                </div>`;
            }

            container.append(`
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 event-card">
                        <div style="background:linear-gradient(135deg,var(--kyu-green-dark),var(--kyu-green));height:6px;border-radius:8px 8px 0 0;"></div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title mb-1">${escapeHtml(event.event_name)}</h5>
                            <span class="badge bg-primary mb-2 align-self-start">${escapeHtml(event.club_name)}</span>
                            <p class="card-text text-muted small mb-3 flex-grow-1">${escapeHtml(event.description)}</p>
                            <div class="event-meta mb-3">
                                <div class="meta-item">📅 ${formattedDate}</div>
                                <div class="meta-item">🕐 ${formattedTime}</div>
                                <div class="meta-item">📍 ${escapeHtml(event.location)}</div>
                            </div>
                            ${capacityHtml}
                            ${actionHtml}
                        </div>
                    </div>
                </div>`);
        });

        // Register button — confirmation popup
        container.find('.register-btn').on('click', function () {
            const eventId   = $(this).data('event-id');
            const eventName = $(this).data('event-name');
            const clubName  = $(this).data('club-name');
            const btn       = $(this);

            $('#eventConfirmModal').remove();
            $('body').append(`
            <div class="modal fade" id="eventConfirmModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius:12px;border:none;">
                        <div class="modal-header text-white" style="background:linear-gradient(135deg,#2c3e50,#3498db);">
                            <div>
                                <h5 class="modal-title mb-0">Confirm Registration</h5>
                                <small style="opacity:0.85;">${escapeHtml(clubName)}</small>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center py-4">
                            <div style="font-size:3rem;">🎯</div>
                            <h6 class="mt-3">${escapeHtml(eventName)}</h6>
                            <p class="text-muted small">Are you sure you want to register for this event?</p>
                        </div>
                        <div class="modal-footer justify-content-center gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary px-4" id="confirm-event-register-btn">Yes, Register Me</button>
                        </div>
                    </div>
                </div>
            </div>`);

            const confirmModal = new bootstrap.Modal(document.getElementById('eventConfirmModal'));
            confirmModal.show();

            $('#confirm-event-register-btn').on('click', function () {
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Registering...');
                $.ajax({
                    url: '/api/student.php?action=register_event',
                    method: 'POST', contentType: 'application/json',
                    data: JSON.stringify({ event_id: eventId }),
                    success: function (res) {
                        confirmModal.hide();
                        if (res.success) {
                            btn.removeClass('btn-primary').addClass('btn-success').prop('disabled', true).html('✓ Registered');
                            showToast('Successfully registered for ' + eventName + '!', 'success');
                        } else {
                            showToast(res.message || 'Registration failed.', 'danger');
                        }
                    },
                    error: function () {
                        confirmModal.hide();
                        showToast('An error occurred. Please try again.', 'danger');
                    }
                });
            });

            document.getElementById('eventConfirmModal').addEventListener('hidden.bs.modal', function () {
                $('#eventConfirmModal').remove();
            });
        });

        // Join club link — switch to clubs tab and scroll to that club
        container.find('.switch-to-club').on('click', function (e) {
            e.preventDefault();
            const targetClubId = $(this).data('club-id');
            $('#clubs-tab').tab('show');

            function scrollToClub() {
                const card = $(`[data-club-card-id="${targetClubId}"]`);
                if (card.length) {
                    $('html, body').animate({ scrollTop: card.offset().top - 100 }, 500);
                    card.find('.card').css('box-shadow', '0 0 0 3px #f39c12');
                    setTimeout(() => card.find('.card').css('box-shadow', ''), 2500);
                }
            }

            if (allClubsData.length) {
                displayClubsByCategory(allClubsData, $('#clubs-container'));
                setTimeout(scrollToClub, 300);
            } else {
                loadAvailableClubs(function () { setTimeout(scrollToClub, 300); });
            }
        });
    }

    // ── DISPLAY CLUBS BY CATEGORY ─────────────────────────
    function displayClubsByCategory(clubs, container) {
        container.empty();
        const grouped = {};
        clubs.forEach(c => { const cat = c.category || 'Other'; if (!grouped[cat]) grouped[cat] = []; grouped[cat].push(c); });
        const orderedKeys = categoryOrder.filter(c => grouped[c]).concat(Object.keys(grouped).filter(c => !categoryOrder.includes(c)));

        orderedKeys.forEach(function (category) {
            const color = categoryColors[category] || '#2c3e50';
            const categoryClubs = grouped[category];
            container.append(`
                <div class="col-12 mb-2 mt-3">
                    <div class="category-header" style="border-left:4px solid ${color};padding-left:12px;">
                        <h5 class="mb-0" style="color:${color};">${escapeHtml(category)}</h5>
                        <small class="text-muted">${categoryClubs.length} ${categoryClubs.length===1?'club':'clubs'}</small>
                    </div>
                    <hr class="mt-2 mb-3">
                </div>`);

            categoryClubs.forEach(function (club) {
                const avatar     = clubAvatar(club.club_name, color);
                const isApproved = userClubIds.includes(parseInt(club.club_id));
                const isPending  = pendingClubIds.includes(parseInt(club.club_id));
                const statusBadge = isApproved
                    ? `<span class="badge" style="background:var(--kyu-green);font-size:0.7rem;">Member</span>`
                    : isPending
                    ? `<span class="badge" style="background:var(--kyu-gold);font-size:0.7rem;">Pending</span>`
                    : '';
                let btnHtml;
                if (isApproved) {
                    btnHtml = `<button class="btn btn-outline-danger btn-sm w-100 leave-btn"
                        data-club-id="${club.club_id}" data-club-name="${escapeHtml(club.club_name)}">Leave Club</button>`;
                } else if (isPending) {
                    btnHtml = `<button class="btn btn-sm w-100" style="background:var(--kyu-gold);color:white;border:none;" disabled>Pending Approval</button>`;
                } else {
                    btnHtml = `<button class="btn btn-sm w-100 join-btn" style="background:${color};color:white;border:none;"
                        data-club-id="${club.club_id}" data-club-name="${escapeHtml(club.club_name)}" data-club-color="${color}">Join Club</button>`;
                }
                container.append(`
                    <div class="col-md-6 col-lg-4 mb-4" data-club-card-id="${club.club_id}">
                        <div class="card h-100 club-card" style="border-top:3px solid ${color};">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    ${avatar}
                                    <div style="min-width:0;">
                                        <h5 class="card-title mb-1" style="font-size:0.95rem;line-height:1.3;">${escapeHtml(club.club_name)}</h5>
                                        ${statusBadge}
                                    </div>
                                </div>
                                <p class="card-text text-muted small flex-grow-1">${escapeHtml(club.description)}</p>
                                <div class="mt-auto pt-2">${btnHtml}</div>
                            </div>
                        </div>
                    </div>`);
            });
        });

        container.find('.join-btn').on('click', function () {
            openJoinModal($(this).data('club-id'), $(this).data('club-name'), $(this).data('club-color'), $(this));
        });
        container.find('.leave-btn').on('click', function () {
            openLeaveModal($(this).data('club-id'), $(this).data('club-name'), $(this));
        });
    }

    // ── JOIN CLUB MODAL ───────────────────────────────────
    function openJoinModal(clubId, clubName, color, triggerBtn) {
        $('#joinClubModal').remove();
        $('body').append(`
        <div class="modal fade" id="joinClubModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background:linear-gradient(135deg,${color},#2c3e50);">
                        <div>
                            <h5 class="modal-title mb-0">Club Application</h5>
                            <small style="opacity:0.85;">Applying to join: <strong>${escapeHtml(clubName)}</strong></small>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">🎂 Age <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="join-age" min="16" max="40" placeholder="e.g. 20">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">📚 Year of Study <span class="text-danger">*</span></label>
                                <select class="form-select" id="join-year">
                                    <option value="">Select year...</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">💬 Why do you want to join this club? <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="join-reason" rows="3" placeholder="Tell us why you're interested in joining ${escapeHtml(clubName)}..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">📅 How often can you attend club meetings?</label>
                            <select class="form-select" id="join-attendance">
                                <option value="">Select frequency...</option>
                                <option value="Every meeting">Every meeting</option>
                                <option value="Most meetings">Most meetings (3 out of 4)</option>
                                <option value="Occasionally">Occasionally (1–2 per month)</option>
                                <option value="When available">Only when available</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">🌟 Interested in a leadership role in future?</label>
                            <div class="d-flex gap-4 mt-1">
                                <div class="form-check"><input class="form-check-input" type="radio" name="leadership" id="lead-yes" value="Yes"><label class="form-check-label" for="lead-yes">Yes</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="leadership" id="lead-no" value="No"><label class="form-check-label" for="lead-no">No</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="leadership" id="lead-maybe" value="Maybe"><label class="form-check-label" for="lead-maybe">Maybe</label></div>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="join-agree-rules">
                                <label class="form-check-label" for="join-agree-rules">I agree to follow club rules and code of conduct</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="join-agree-participate">
                                <label class="form-check-label" for="join-agree-participate">I will actively participate in club activities</label>
                            </div>
                        </div>
                        <div id="join-modal-error" class="alert alert-danger py-2 small" style="display:none;"></div>
                        <div id="join-modal-success" class="alert alert-success py-2 small" style="display:none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn text-white" id="submit-join-btn" style="background:${color};">Submit Application</button>
                    </div>
                </div>
            </div>
        </div>`);

        const modalEl = new bootstrap.Modal(document.getElementById('joinClubModal'));
        modalEl.show();

        $('#submit-join-btn').on('click', function () {
            const age        = $('#join-age').val().trim();
            const year       = $('#join-year').val();
            const reason     = $('#join-reason').val().trim();
            const attendance = $('#join-attendance').val();
            const leadership = $('input[name="leadership"]:checked').val();
            const agreeRules = $('#join-agree-rules').is(':checked');
            const agreePart  = $('#join-agree-participate').is(':checked');

            if (!age || age < 16 || age > 40) { showJoinError('Please enter a valid age.'); return; }
            if (!year)   { showJoinError('Please select your year of study.'); return; }
            if (!reason || reason.length < 10) { showJoinError('Please tell us why you want to join (at least 10 characters).'); return; }
            if (!leadership) { showJoinError('Please indicate your interest in leadership roles.'); return; }
            if (!agreeRules) { showJoinError('You must agree to follow club rules.'); return; }
            if (!agreePart)  { showJoinError('You must commit to actively participating.'); return; }

            const btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Submitting...');
            $('#join-modal-error').hide();

            $.ajax({
                url: '/api/student.php?action=join_club',
                method: 'POST', contentType: 'application/json',
                data: JSON.stringify({ club_id: clubId }),
                success: function (res) {
                    if (res.success) {
                        btn.prop('disabled', true).html('✅ Application Sent');
                        $('#join-modal-success').html(`
                            <strong>📬 Application sent for approval!</strong><br>
                            Your application to join <strong>${escapeHtml(clubName)}</strong> has been submitted.
                            The club leader will review it and you'll receive a notification once it's approved or rejected.
                        `).show();
                        triggerBtn.css({'background-color':'#f39c12'}).html('⏳ Pending').prop('disabled', true);
                        userClubIds.push(parseInt(clubId));
                        setTimeout(function () { modalEl.hide(); }, 3000);
                        showToast('Application sent! Awaiting leader approval.', 'success');
                        loadStudentNotifications();
                    } else {
                        btn.prop('disabled', false).html('Submit Application');
                        showJoinError(res.message);
                    }
                },
                error: function () {
                    btn.prop('disabled', false).html('Submit Application');
                    showJoinError('An error occurred. Please try again.');
                }
            });
        });

        document.getElementById('joinClubModal').addEventListener('hidden.bs.modal', function () {
            $('#joinClubModal').remove();
        });
    }

    function showJoinError(msg) { $('#join-modal-error').text(msg).slideDown(200); }

    // ── LEAVE CLUB MODAL ──────────────────────────────────
    function openLeaveModal(clubId, clubName, triggerBtn) {
        $('#leaveClubModal').remove();
        $('body').append(`
        <div class="modal fade" id="leaveClubModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header" style="background:#e74c3c;color:white;">
                        <h5 class="modal-title">Leave Club</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning d-flex align-items-start gap-2">
                            <span style="font-size:1.4rem;">⚠️</span>
                            <div>
                                <strong>Are you sure you want to leave <em>${escapeHtml(clubName)}</em>?</strong>
                                <ul class="mb-0 mt-1 small">
                                    <li>You will lose access to all club events</li>
                                    <li>You will be removed from the club member list</li>
                                    <li>You can rejoin later if the club is still open</li>
                                </ul>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">📝 Reason for leaving <span class="text-muted small">(optional)</span></label>
                            <select class="form-select" id="leave-reason">
                                <option value="">Select a reason...</option>
                                <option>Too busy with academics</option>
                                <option>Not what I expected</option>
                                <option>Scheduling conflicts</option>
                                <option>Joining another club</option>
                                <option>Personal reasons</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="leave-confirm">
                            <label class="form-check-label" for="leave-confirm">I understand I will lose access to club events and activities</label>
                        </div>
                        <div id="leave-modal-error" class="alert alert-danger py-2 small" style="display:none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel — Stay in Club</button>
                        <button type="button" class="btn btn-danger" id="confirm-leave-btn">Leave Club</button>
                    </div>
                </div>
            </div>
        </div>`);

        const modalEl = new bootstrap.Modal(document.getElementById('leaveClubModal'));
        modalEl.show();

        $('#confirm-leave-btn').on('click', function () {
            if (!$('#leave-confirm').is(':checked')) { $('#leave-modal-error').text('Please check the confirmation box.').slideDown(200); return; }
            const btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Leaving...');
            $.ajax({
                url: '/api/student.php?action=leave_club',
                method: 'POST', contentType: 'application/json',
                data: JSON.stringify({ club_id: clubId }),
                success: function (res) {
                    if (res.success) {
                        modalEl.hide();
                        triggerBtn.closest('.col-md-6').fadeOut(400, function () { $(this).remove(); });
                        userClubIds = userClubIds.filter(id => id !== parseInt(clubId));
                        showToast('You have left ' + clubName + '.', 'success');
                    } else {
                        btn.prop('disabled', false).html('Leave Club');
                        $('#leave-modal-error').text(res.message).slideDown(200);
                    }
                }
            });
        });

        document.getElementById('leaveClubModal').addEventListener('hidden.bs.modal', function () { $('#leaveClubModal').remove(); });
    }

    // ── EVENT REGISTRATION MODAL ──────────────────────────
    function openRegistrationModal(eventId, eventName, clubName, triggerBtn) {
        $('#registrationModal').remove();
        $('body').append(`
        <div class="modal fade" id="registrationModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header" style="background:linear-gradient(135deg,#2c3e50,#3498db);color:white;">
                        <div>
                            <h5 class="modal-title mb-0">Event Registration</h5>
                            <small style="opacity:0.85;">${escapeHtml(eventName)} — ${escapeHtml(clubName)}</small>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">📞 Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="reg-phone" placeholder="e.g. 0712 345 678">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">🏃 Available for practice sessions?</label>
                            <div class="d-flex gap-3 mt-1">
                                <div class="form-check"><input class="form-check-input" type="radio" name="practice_available" id="practice-yes" value="yes"><label class="form-check-label" for="practice-yes">Yes</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="practice_available" id="practice-no" value="no"><label class="form-check-label" for="practice-no">No</label></div>
                            </div>
                        </div>
                        <div class="mb-3" id="practice-day-section" style="display:none;">
                            <label class="form-label fw-semibold">📅 Preferred Practice Day</label>
                            <div class="d-flex gap-3 mt-1">
                                <div class="form-check"><input class="form-check-input" type="radio" name="practice_day" id="day-weekdays" value="Weekdays"><label class="form-check-label" for="day-weekdays">Weekdays</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="practice_day" id="day-weekends" value="Weekends"><label class="form-check-label" for="day-weekends">Weekends</label></div>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-2"><div class="form-check"><input class="form-check-input" type="checkbox" id="agree-rules"><label class="form-check-label" for="agree-rules">I agree to follow the event rules and code of conduct</label></div></div>
                        <div class="mb-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="agree-attend"><label class="form-check-label" for="agree-attend">I confirm that I will attend if selected</label></div></div>
                        <div id="modal-error" class="alert alert-danger py-2 small" style="display:none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirm-register-btn">Confirm Registration</button>
                    </div>
                </div>
            </div>
        </div>`);

        const modalEl = new bootstrap.Modal(document.getElementById('registrationModal'));
        modalEl.show();

        $('input[name="practice_available"]').on('change', function () {
            $('#practice-day-section').toggle($(this).val() === 'yes');
            if ($(this).val() !== 'yes') $('input[name="practice_day"]').prop('checked', false);
        });

        $('#confirm-register-btn').on('click', function () {
            const phone      = $('#reg-phone').val().trim();
            const practiceAv = $('input[name="practice_available"]:checked').val();
            const practiceDay= $('input[name="practice_day"]:checked').val();
            const agreeRules = $('#agree-rules').is(':checked');
            const agreeAttend= $('#agree-attend').is(':checked');

            if (!phone)       { showModalError('Please enter your phone number.'); return; }
            if (!practiceAv)  { showModalError('Please indicate your practice session availability.'); return; }
            if (practiceAv === 'yes' && !practiceDay) { showModalError('Please select your preferred practice day.'); return; }
            if (!agreeRules)  { showModalError('You must agree to follow the event rules.'); return; }
            if (!agreeAttend) { showModalError('You must confirm your attendance commitment.'); return; }

            const btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Registering...');
            $.ajax({
                url: '/api/student.php?action=register_event',
                method: 'POST', contentType: 'application/json',
                data: JSON.stringify({ event_id: eventId }),
                success: function (res) {
                    if (res.success) {
                        modalEl.hide();
                        triggerBtn.removeClass('btn-primary').addClass('btn-success').prop('disabled', true).html('✓ Registered');
                        showToast('Successfully registered for ' + eventName + '!', 'success');
                    } else {
                        btn.prop('disabled', false).html('Confirm Registration');
                        showModalError(res.message);
                    }
                }
            });
        });

        document.getElementById('registrationModal').addEventListener('hidden.bs.modal', function () { $('#registrationModal').remove(); });
    }

    function showModalError(msg) { $('#modal-error').text(msg).slideDown(200); }

    // ── NOTIFICATIONS ─────────────────────────────────────
    function loadStudentNotifications() {
        $.ajax({
            url: '/api/student.php?action=notifications', method: 'GET', dataType: 'json',
            success: function (res) {
                if (!res.success) return;
                const unread = res.data.filter(n => !n.is_read).length;
                if (unread > 0) {
                    $('#student-notif-count').text(unread).show();
                    $('#notif-header-count').text(unread).show();
                } else {
                    $('#student-notif-count').hide();
                    $('#notif-header-count').hide();
                }

                let html = '';
                if (res.data.length === 0) {
                    html = '<div class="text-center p-4"><div style="font-size:2.5rem;">🔔</div><p class="text-muted small mt-2">No notifications yet</p></div>';
                } else {
                    res.data.forEach(function (n) {
                        const typeColors = { success:'#27ae60', danger:'#e74c3c', info:'#3498db' };
                        const color = typeColors[n.type] || '#3498db';
                        const date  = new Date(n.created_at).toLocaleDateString('en-GB', {day:'numeric', month:'short', year:'numeric'});
                        const isLong = n.message.length > 70;
                        const preview = isLong ? n.message.substring(0, 70) + '...' : n.message;

                        html += `<div class="notif-item px-3 py-2 border-bottom ${n.is_read ? '' : 'bg-light'}"
                            style="cursor:${isLong?'pointer':'default'};"
                            data-title="${escapeHtml(n.title)}"
                            data-message="${escapeHtml(n.message)}"
                            data-date="${date}"
                            data-color="${color}">
                            <div class="d-flex gap-2 align-items-start">
                                <div style="width:3px;min-height:36px;background:${color};border-radius:2px;flex-shrink:0;margin-top:3px;"></div>
                                <div style="flex:1;min-width:0;">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <strong class="small text-truncate" style="max-width:200px;">${escapeHtml(n.title)}</strong>
                                        <small class="text-muted ms-2 flex-shrink-0">${date}</small>
                                    </div>
                                    <p class="mb-0 small text-muted" style="line-height:1.4;">${escapeHtml(preview)}</p>
                                    ${isLong ? `<small style="color:${color};font-size:0.72rem;">Click to read more →</small>` : ''}
                                </div>
                            </div>
                        </div>`;
                    });
                }
                $('#student-notif-list').html(html);

                // Click to open full message in modal
                $(document).off('click.notif').on('click.notif', '.notif-item', function () {
                    const title   = $(this).data('title');
                    const message = $(this).data('message');
                    const date    = $(this).data('date');
                    const color   = $(this).data('color');
                    $('#notif-detail-title').text(title);
                    $('#notif-detail-body').text(message);
                    $('#notif-detail-date').text('📅 ' + date);
                    $('#notif-detail-header').css('background', `linear-gradient(135deg, ${color}, #2c3e50)`);
                    new bootstrap.Modal(document.getElementById('notifDetailModal')).show();
                });
            }
        });
    }

    $('#student-notif-btn').on('click', function (e) {
        e.stopPropagation();
        const dropdown = $('#student-notif-dropdown');
        dropdown.toggle();
        if (dropdown.is(':visible')) loadStudentNotifications();
    });

    $('#student-mark-read-btn').on('click', function () {
        $.ajax({
            url: '/api/student.php?action=mark_notifications_read',
            method: 'POST', contentType: 'application/json', data: JSON.stringify({}),
            success: function () { loadStudentNotifications(); }
        });
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#student-notif-dropdown, #student-notif-btn').length) {
            $('#student-notif-dropdown').hide();
        }
    });

    // ── LEADERSHIP APPLICATION ────────────────────────────
    $('#apply-leader-btn').on('click', function (e) {
        e.preventDefault();
        $('#leader-modal-error, #leader-modal-success').hide();
        $('#leader-club-select').html('<option value="">Loading clubs...</option>');
        $.ajax({
            url: '/api/student.php?action=available_clubs', method: 'GET', dataType: 'json',
            success: function (res) {
                $('#leader-club-select').html('<option value="">Select a club...</option>');
                if (res.success) res.data.forEach(c => {
                    $('#leader-club-select').append(`<option value="${c.club_id}">${escapeHtml(c.club_name)} — ${escapeHtml(c.category||'')}</option>`);
                });
            }
        });
        new bootstrap.Modal(document.getElementById('leadershipModal')).show();
    });

    $('#submit-leader-application').on('click', function () {
        const clubId     = $('#leader-club-select').val();
        const why        = $('#leader-why').val().trim();
        const experience = $('#leader-experience').val().trim();
        const vision     = $('#leader-vision').val().trim();
        const avail      = $('#leader-availability').val();
        const agreed     = $('#leader-agree').is(':checked');
        $('#leader-modal-error').hide();
        if (!clubId)              { $('#leader-modal-error').text('Please select a club.').show(); return; }
        if (why.length < 20)      { $('#leader-modal-error').text('Please explain why you want to lead (at least 20 characters).').show(); return; }
        if (experience.length < 20) { $('#leader-modal-error').text('Please describe your experience (at least 20 characters).').show(); return; }
        if (!agreed)              { $('#leader-modal-error').text('You must commit to the responsibilities of a club leader.').show(); return; }
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Submitting...');
        $.ajax({
            url: '/api/student.php?action=apply_leadership', method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ club_id: clubId, why_leader: why, experience, vision, availability: avail }),
            success: function (res) {
                btn.prop('disabled', false).html('Submit Application');
                if (res.success) {
                    $('#leader-modal-success').text('✅ Application submitted! The admin will review it shortly.').show();
                    $('#leader-why, #leader-experience, #leader-vision').val('');
                    $('#leader-club-select, #leader-availability').val('');
                    $('#leader-agree').prop('checked', false);
                    setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('leadershipModal')).hide(), 2500);
                } else $('#leader-modal-error').text(res.message).show();
            },
            error: function () { btn.prop('disabled', false).html('Submit Application'); $('#leader-modal-error').text('An error occurred.').show(); }
        });
    });

    // ── UI HELPERS ────────────────────────────────────────
    function showToast(message, type) {
        const id = 'toast-' + Date.now();
        const bg = type === 'success' ? 'bg-success' : 'bg-danger';
        if (!$('#toast-container').length) $('body').append('<div id="toast-container"></div>');
        $('#toast-container').append(`
            <div id="${id}" class="toast-notification ${bg} text-white">
                <span>${escapeHtml(message)}</span>
                <button onclick="document.getElementById('${id}').remove()" style="background:none;border:none;color:white;font-size:1.2rem;cursor:pointer;margin-left:10px;">×</button>
            </div>`);
        setTimeout(() => $('#' + id).fadeOut(400, function () { $(this).remove(); }), 4500);
    }

    function loadingSpinner() { return '<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Loading...</p></div>'; }
    function emptyState(msg)  { return `<div class="col-12 text-center py-5"><div style="font-size:3rem;">📭</div><p class="text-muted mt-2">${escapeHtml(msg)}</p></div>`; }
    function errorState(msg)  { return `<div class="col-12 text-center py-5"><div style="font-size:3rem;">⚠️</div><p class="text-danger mt-2">${escapeHtml(msg)}</p></div>`; }

    function formatTime(t) {
        if (!t) return '';
        const [h, m] = t.split(':');
        const hr = parseInt(h);
        return ((hr % 12) || 12) + ':' + m + ' ' + (hr >= 12 ? 'PM' : 'AM');
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    }
});
