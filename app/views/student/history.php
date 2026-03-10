<?php
/**
 * Student Participation History View
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participation History - CARMS</title>
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
                    <li class="nav-item"><a class="nav-link" href="/student/schedule">My Schedule</a></li>
                    <li class="nav-item"><a class="nav-link" href="/student/clubs">My Clubs</a></li>
                    <li class="nav-item"><a class="nav-link active" href="/student/history">Participation History</a></li>
                    <li class="nav-item"><a class="nav-link" href="/api/auth.php?action=logout">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4 px-4">
        <div class="dashboard-header">
            <h1>📋 Participation History</h1>
            <p>A record of all your past event attendance</p>
        </div>

        <!-- Summary Stats -->
        <div class="row mt-4 mb-4" id="stats-row">
            <div class="col-md-4 mb-3">
                <div class="card text-center p-3">
                    <div style="font-size:2rem;">🎯</div>
                    <h3 class="mt-2 mb-0" id="total-events">—</h3>
                    <p class="text-muted small mb-0">Events Attended</p>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center p-3">
                    <div style="font-size:2rem;">🏛️</div>
                    <h3 class="mt-2 mb-0" id="total-clubs">—</h3>
                    <p class="text-muted small mb-0">Clubs Joined</p>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center p-3">
                    <div style="font-size:2rem;">📅</div>
                    <h3 class="mt-2 mb-0" id="last-activity">—</h3>
                    <p class="text-muted small mb-0">Last Activity</p>
                </div>
            </div>
        </div>

        <!-- History Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Club</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Attended</th>
                            </tr>
                        </thead>
                        <tbody id="history-container">
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                    Loading history...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function () {
        // Load both history and club count in parallel
        $.when(
            $.ajax({ url: '/api/student.php?action=participation_history', method: 'GET', dataType: 'json' }),
            $.ajax({ url: '/api/student.php?action=my_clubs', method: 'GET', dataType: 'json' })
        ).done(function (historyRes, clubsRes) {
            const history = historyRes[0];
            const clubs   = clubsRes[0];
            const tbody   = $('#history-container');
            tbody.empty();

            // Update stats
            const eventCount = (history.success && history.data) ? history.data.length : 0;
            const clubCount  = (clubs.success && clubs.data)     ? clubs.data.length  : 0;

            $('#total-events').text(eventCount);
            $('#total-clubs').text(clubCount);

            if (eventCount > 0) {
                const last = new Date(history.data[0].attended_at);
                $('#last-activity').text(last.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }));
            } else {
                $('#last-activity').text('N/A');
            }

            // Populate table
            if (!history.success || history.data.length === 0) {
                tbody.html(`
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div style="font-size:2.5rem;">📋</div>
                            <p class="text-muted mt-2">No attendance history yet.<br>
                            Attend events to build your record.</p>
                        </td>
                    </tr>`);
                return;
            }

            history.data.forEach(function (item) {
                const eventDate   = new Date(item.event_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                const attendedAt  = new Date(item.attended_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });

                tbody.append(`
                    <tr>
                        <td><strong>${escapeHtml(item.event_name)}</strong></td>
                        <td><span class="badge bg-primary">${escapeHtml(item.club_name)}</span></td>
                        <td>${eventDate}</td>
                        <td>${escapeHtml(item.location)}</td>
                        <td><span class="badge bg-success">✓ ${attendedAt}</span></td>
                    </tr>`);
            });
        }).fail(function (xhr) {
            if (xhr.status === 401) window.location.href = '/login';
        });

        function escapeHtml(text) {
            if (!text) return '';
            return String(text).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
        }
    });
    </script>
</body>
</html>
