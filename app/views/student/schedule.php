<?php
/**
 * Student Schedule View
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - CARMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard">CARMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="/student/dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="/student/schedule">My Schedule</a></li>
                    <li class="nav-item"><a class="nav-link" href="/student/clubs">My Clubs</a></li>
                    <li class="nav-item"><a class="nav-link" href="/student/history">Participation History</a></li>
                    <li class="nav-item"><a class="nav-link" href="/api/auth.php?action=logout">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4 px-4">
        <div class="dashboard-header">
            <h1>📅 My Schedule</h1>
            <p>Events you have registered for</p>
        </div>

        <div class="row mt-4" id="schedule-container">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Loading your schedule...</p>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function () {
        loadSchedule();

        function loadSchedule() {
            $.ajax({
                url: '/api/student.php?action=my_schedule',
                method: 'GET',
                dataType: 'json',
                success: function (response) {
                    const container = $('#schedule-container');
                    container.empty();

                    if (!response.success || response.data.length === 0) {
                        container.html(`
                            <div class="col-12 text-center py-5">
                                <div style="font-size:3rem;">📭</div>
                                <h5 class="mt-3 text-muted">No registered events yet</h5>
                                <p class="text-muted">Head to the <a href="/dashboard">Dashboard</a> to register for upcoming events.</p>
                            </div>`);
                        return;
                    }

                    response.data.forEach(function (event) {
                        const date = new Date(event.event_date);
                        const formattedDate = date.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                        const time = formatTime(event.event_time);
                        const isPast = new Date(event.event_date) < new Date();

                        container.append(`
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 event-card">
                                    <div style="background: linear-gradient(135deg, #2c3e50, #3498db); height:6px; border-radius:8px 8px 0 0;"></div>
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-0">${escapeHtml(event.event_name)}</h5>
                                            <span class="badge ${isPast ? 'bg-secondary' : 'bg-success'} ms-2">${isPast ? 'Past' : 'Upcoming'}</span>
                                        </div>
                                        <span class="badge bg-primary mb-2 align-self-start">${escapeHtml(event.club_name)}</span>
                                        <p class="card-text text-muted small mb-3">${escapeHtml(event.description)}</p>
                                        <div class="event-meta mb-3">
                                            <div class="meta-item">📅 ${formattedDate}</div>
                                            <div class="meta-item">🕐 ${time}</div>
                                            <div class="meta-item">📍 ${escapeHtml(event.location)}</div>
                                        </div>
                                        <div class="mt-auto">
                                            <button class="btn btn-outline-danger btn-sm w-100 unregister-btn"
                                                data-event-id="${event.event_id}">
                                                Unregister
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>`);
                    });

                    $('.unregister-btn').on('click', function () {
                        const btn = $(this);
                        const eventId = btn.data('event-id');
                        if (!confirm('Are you sure you want to unregister from this event?')) return;

                        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Removing...');
                        $.ajax({
                            url: '/api/student.php?action=unregister_event',
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({ event_id: eventId }),
                            success: function (res) {
                                if (res.success) {
                                    btn.closest('.col-md-6').fadeOut(400, function () { $(this).remove(); });
                                    showToast('Successfully unregistered.', 'success');
                                } else {
                                    btn.prop('disabled', false).html('Unregister');
                                    showToast(res.message, 'danger');
                                }
                            },
                            error: function () {
                                btn.prop('disabled', false).html('Unregister');
                                showToast('An error occurred.', 'danger');
                            }
                        });
                    });
                },
                error: function (xhr) {
                    if (xhr.status === 401) window.location.href = '/login';
                }
            });
        }

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

        function showToast(message, type) {
            const id = 'toast-' + Date.now();
            const bg = type === 'success' ? 'bg-success' : 'bg-danger';
            $('#toast-container').append(`<div id="${id}" class="toast-notification ${bg} text-white"><span>${escapeHtml(message)}</span></div>`);
            setTimeout(() => $('#' + id).fadeOut(400, function(){ $(this).remove(); }), 3500);
        }
    });
    </script>
</body>
</html>
