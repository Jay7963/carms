<?php /** Student My Clubs View */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Clubs - CARMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard">C<span style="color:var(--kyu-gold)">ARMS</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="/student/dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/student/schedule">My Schedule</a></li>
                    <li class="nav-item"><a class="nav-link active" href="/student/clubs">My Clubs</a></li>
                    <li class="nav-item"><a class="nav-link" href="/student/history">Participation History</a></li>
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
                            <li><button class="dropdown-item text-danger small border-0 bg-transparent w-100 text-start" onclick="document.getElementById('logout-form').submit()">Logout</button></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4 px-4">
        <div class="dashboard-header">
            <h1>My Clubs</h1>
            <p>Clubs you are currently a member of</p>
        </div>

        <!-- Summary counts -->
        <div class="row mb-4" id="clubs-summary" style="display:none!important;"></div>

        <div class="row mt-2" id="my-clubs-container">
            <div class="col-12 text-center py-5">
                <div class="spinner-border" style="color:var(--kyu-green);" role="status"></div>
                <p class="mt-2 text-muted">Loading your clubs...</p>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>
    <form id="logout-form" action="/api/auth.php" method="POST" style="display:none;">
        <input type="hidden" name="action" value="logout">
    </form>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function () {
        const categoryColors = {
            'Sports - Men':'#1a73e8','Sports - Women':'#e91e8c',
            'Academic & Professional':'#0d6e6e','Arts & Culture':'#7b2d8b',
            'Community & Service':'#e67e22','Religious':'#27ae60',
        };
        const colorDarken = {
            '#1a73e8':'#1255a8','#e91e8c':'#aa1567',
            '#0d6e6e':'#095050','#7b2d8b':'#581f65',
            '#e67e22':'#b86117','#27ae60':'#1e8449',
        };
        function clubInitials(name) {
            const words = name.replace(/[()]/g,'').trim().split(/\s+/);
            if (words.length >= 2) return (words[0][0]+words[1][0]).toUpperCase();
            return name.substring(0,2).toUpperCase();
        }
        function clubAvatar(name, color) {
            const initials = clubInitials(name);
            const dark = colorDarken[color] || color;
            return `<div style="width:54px;height:54px;border-radius:12px;background:linear-gradient(135deg,${color},${dark});display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.15rem;color:white;letter-spacing:1px;box-shadow:0 3px 8px ${color}66;">${initials}</div>`;
        }

        loadMyClubs();

        function loadMyClubs() {
            $.ajax({
                url: '/api/student.php?action=my_clubs',
                method: 'GET', dataType: 'json',
                success: function (res) {
                    const container = $('#my-clubs-container');
                    container.empty();

                    if (!res.success || res.data.length === 0) {
                        container.html(`
                            <div class="col-12 text-center py-5">
                                <div style="font-size:3rem;">🏛️</div>
                                <h5 class="mt-3 text-muted">You haven't joined any clubs yet</h5>
                                <p class="text-muted small">Browse available clubs from the <a href="/student/dashboard" style="color:var(--kyu-green);">Dashboard</a>.</p>
                            </div>`);
                        return;
                    }

                    const approved = res.data.filter(c => c.membership_status === 'approved');
                    const pending  = res.data.filter(c => c.membership_status === 'pending');

                    // Summary bar
                    if (res.data.length > 0) {
                        $('#clubs-summary').html(`
                            <div class="col-auto">
                                <div class="stat-card px-4 py-2">
                                    <div class="stat-number">${approved.length}</div>
                                    <div class="stat-label">Active Memberships</div>
                                </div>
                            </div>
                            ${pending.length > 0 ? `<div class="col-auto">
                                <div class="stat-card px-4 py-2">
                                    <div class="stat-number" style="color:var(--kyu-gold);">${pending.length}</div>
                                    <div class="stat-label">Pending Applications</div>
                                </div>
                            </div>` : ''}
                        `).show().css('display', 'flex');
                    }

                    // Approved clubs first, then pending
                    [...approved, ...pending].forEach(function (club) {
                        const isApproved = club.membership_status === 'approved';
                        const color = categoryColors[club.category] || 'var(--kyu-green)';
                        const borderColor = isApproved ? color : 'var(--kyu-gold)';
                        const avatar = clubAvatar(club.club_name, isApproved ? color : '#c8971f');
                        const statusBadge = isApproved
                            ? `<span class="badge" style="background:var(--kyu-green);font-size:0.7rem;">Active</span>`
                            : `<span class="badge" style="background:var(--kyu-gold);font-size:0.7rem;">Pending</span>`;

                        container.append(`
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 club-card" style="border-top:3px solid ${borderColor};">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-center gap-3 mb-3">
                                            ${avatar}
                                            <div style="min-width:0;">
                                                <h5 class="card-title mb-1" style="font-size:0.95rem;line-height:1.3;">${escapeHtml(club.club_name)}</h5>
                                                ${statusBadge}
                                            </div>
                                        </div>
                                        <span class="badge bg-secondary mb-2 align-self-start" style="font-size:0.72rem;">${escapeHtml(club.category || '')}</span>
                                        <p class="card-text text-muted small flex-grow-1">${escapeHtml(club.description || '')}</p>
                                        ${isApproved ? `
                                        <div class="mt-auto pt-2">
                                            <button class="btn btn-outline-danger btn-sm w-100 leave-btn" data-club-id="${club.club_id}" data-club-name="${escapeHtml(club.club_name)}">
                                                Leave Club
                                            </button>
                                        </div>` : `
                                        <div class="mt-auto pt-2">
                                            <div class="alert alert-warning py-1 px-2 mb-0 small text-center">Awaiting leader approval</div>
                                        </div>`}
                                    </div>
                                </div>
                            </div>`);
                    });

                    $('.leave-btn').on('click', function () {
                        const btn    = $(this);
                        const clubId = btn.data('club-id');
                        const clubName = btn.data('club-name');
                        if (!confirm('Are you sure you want to leave ' + clubName + '?')) return;
                        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Leaving...');
                        $.ajax({
                            url: '/api/student.php?action=leave_club',
                            method: 'POST', contentType: 'application/json',
                            data: JSON.stringify({ club_id: clubId }),
                            success: function (res) {
                                if (res.success) {
                                    btn.closest('.col-md-6, .col-lg-4, .col-md-6.col-lg-4').fadeOut(400, function () { $(this).remove(); });
                                    showToast('You have left ' + clubName + '.', 'success');
                                } else {
                                    btn.prop('disabled', false).html('Leave Club');
                                    showToast(res.message, 'danger');
                                }
                            },
                            error: function () { btn.prop('disabled', false).html('Leave Club'); showToast('An error occurred.', 'danger'); }
                        });
                    });
                },
                error: function (xhr) { if (xhr.status === 401) window.location.href = '/login'; }
            });
        }

        function escapeHtml(t) {
            if (!t) return '';
            return String(t).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
        }

        function showToast(msg, type) {
            const id = 'toast-' + Date.now();
            const bg = type === 'success' ? 'bg-success' : 'bg-danger';
            if (!$('#toast-container').length) $('body').append('<div id="toast-container"></div>');
            $('#toast-container').append(`<div id="${id}" class="toast-notification ${bg} text-white"><span>${escapeHtml(msg)}</span></div>`);
            setTimeout(() => $('#' + id).fadeOut(400, function(){ $(this).remove(); }), 3500);
        }
    });
    </script>
</body>
</html>
