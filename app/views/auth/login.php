<?php /** Login View — Role Card Showcase */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CARMS | Kirinyaga University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        html, body { height: 100%; margin: 0; }

        .login-page {
            min-height: 100vh;
            background: linear-gradient(160deg, var(--kyu-green-dark) 0%, var(--kyu-green) 50%, #2a7a4f 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        /* ── HEADER ── */
        .login-header {
            text-align: center;
            color: white;
            margin-bottom: 2.5rem;
        }
        .login-header .carms-logo {
            font-size: 2.8rem;
            font-weight: 900;
            letter-spacing: 4px;
            line-height: 1;
        }
        .login-header .carms-logo span { color: var(--kyu-gold); }
        .login-header .univ-name {
            font-size: 1rem;
            font-weight: 600;
            opacity: 0.9;
            margin-top: 0.25rem;
        }
        .login-header .system-name {
            font-size: 0.78rem;
            opacity: 0.65;
            margin-top: 0.2rem;
        }

        /* ── ROLE CARDS ── */
        .role-cards {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .role-card {
            background: rgba(255,255,255,0.10);
            border: 2px solid rgba(255,255,255,0.20);
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            text-align: center;
            color: white;
            cursor: pointer;
            transition: all 0.25s ease;
            width: 150px;
            backdrop-filter: blur(6px);
            user-select: none;
        }
        .role-card:hover {
            background: rgba(255,255,255,0.18);
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-4px);
        }
        .role-card.selected {
            border-color: var(--kyu-gold);
            background: rgba(200,151,31,0.20);
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(200,151,31,0.30);
        }
        .role-card .role-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            margin: 0 auto 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
        }
        .role-card .role-name {
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }
        .role-card .role-desc {
            font-size: 0.7rem;
            opacity: 0.7;
            line-height: 1.3;
        }
        .role-card.selected .role-desc { opacity: 0.9; }

        .role-card-student  .role-avatar { background: linear-gradient(135deg,#f39c12,#e67e22); }
        .role-card-admin    .role-avatar { background: linear-gradient(135deg,#e74c3c,#c0392b); }
        .role-card-leader   .role-avatar { background: linear-gradient(135deg,#27ae60,#1e8449); }

        /* selected tick */
        .role-card .tick {
            display: none;
            position: absolute;
            top: -8px; right: -8px;
            width: 22px; height: 22px;
            background: var(--kyu-gold);
            border-radius: 50%;
            font-size: 0.7rem;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
        }
        .role-card { position: relative; }
        .role-card.selected .tick { display: flex; }

        /* ── LOGIN FORM CARD ── */
        .login-form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        .login-form-header {
            padding: 1.25rem 1.75rem 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .login-form-header .portal-indicator {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: #d1d5db;
            transition: background 0.3s;
            flex-shrink: 0;
        }
        .login-form-header .portal-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: color 0.3s;
        }
        .login-form-body { padding: 1.5rem 1.75rem; }

        .login-form-body .form-control {
            background: #f9fafb;
            border: 1.5px solid #e5e7eb;
        }
        .login-form-body .form-control:focus {
            background: white;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--kyu-green), var(--kyu-green-dark));
            color: white;
            border: none;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 0.65rem;
            border-radius: 8px;
            transition: all 0.2s;
            letter-spacing: 0.3px;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, var(--kyu-green-dark), #0d3a1f);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(19,77,43,0.35);
        }
        .btn-login:disabled {
            opacity: 0.7;
            transform: none;
        }

        .login-footer {
            background: #f9fafb;
            padding: 0.85rem 1.75rem;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        /* role-colored header accent */
        .login-form-card.role-student .login-form-header .portal-indicator { background: #f39c12; }
        .login-form-card.role-student .login-form-header .portal-label     { color: #e67e22; }
        .login-form-card.role-admin   .login-form-header .portal-indicator { background: #e74c3c; }
        .login-form-card.role-admin   .login-form-header .portal-label     { color: #e74c3c; }
        .login-form-card.role-leader  .login-form-header .portal-indicator { background: #27ae60; }
        .login-form-card.role-leader  .login-form-header .portal-label     { color: #27ae60; }
        .login-form-card.role-student .btn-login { background: linear-gradient(135deg,#f39c12,#e67e22); }
        .login-form-card.role-admin   .btn-login { background: linear-gradient(135deg,#e74c3c,#c0392b); }
        .login-form-card.role-leader  .btn-login { background: linear-gradient(135deg,#27ae60,#1e8449); }

        @media (max-width: 576px) {
            .role-card { width: 130px; padding: 1rem; }
            .carms-logo { font-size: 2.2rem !important; }
        }
    </style>
</head>
<body>
<div class="login-page">

    <!-- Header -->
    <div class="login-header">
        <div class="carms-logo">C<span>ARMS</span></div>
        <div class="univ-name">Kirinyaga University</div>
        <div class="system-name">Co-curricular Activity Registration &amp; Management System</div>
    </div>

    <!-- Role Cards -->
    <div class="role-cards">
        <div class="role-card role-card-student selected" data-role="student" data-label="Student Portal">
            <div class="tick">✓</div>
            <div class="role-avatar">ST</div>
            <div class="role-name">Student</div>
            <div class="role-desc">Browse &amp; join clubs, register for events</div>
        </div>
        <div class="role-card role-card-leader" data-role="leader" data-label="Leader Hub">
            <div class="tick">✓</div>
            <div class="role-avatar">AL</div>
            <div class="role-name">Activity Leader</div>
            <div class="role-desc">Manage your club, members &amp; events</div>
        </div>
        <div class="role-card role-card-admin" data-role="admin" data-label="Admin Dashboard">
            <div class="tick">✓</div>
            <div class="role-avatar">AD</div>
            <div class="role-name">Administrator</div>
            <div class="role-desc">Manage users, clubs &amp; system settings</div>
        </div>
    </div>

    <!-- Login Form -->
    <div class="login-form-card role-student" id="login-form-card">
        <div class="login-form-header">
            <div class="portal-indicator"></div>
            <div class="portal-label" id="portal-label">Student Portal</div>
        </div>
        <div class="login-form-body">
            <?php if (!empty($_SESSION['login_error'])): ?>
                <div class="alert alert-danger py-2 small mb-3">
                    <strong>Access Denied:</strong> <?= htmlspecialchars($_SESSION['login_error']) ?>
                </div>
                <?php unset($_SESSION['login_error']); ?>
            <?php endif; ?>
            <div id="message-container" class="mb-2"></div>
            <form id="login-form">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required autocomplete="username">
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-login w-100">Sign In</button>
            </form>
        </div>
        <div class="login-footer">
            New student? <a href="/register" style="color:var(--kyu-green);font-weight:600;">Register here</a>
            &nbsp;·&nbsp; &copy; <?= date('Y') ?> Kirinyaga University
        </div>
    </div>

</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/auth.js"></script>
<script>
// Role card selection
const roleMap = {
    student: { class: 'role-student', label: 'Student Portal' },
    admin:   { class: 'role-admin',   label: 'Admin Dashboard' },
    leader:  { class: 'role-leader',  label: 'Leader Hub' },
};

$('.role-card').on('click', function () {
    $('.role-card').removeClass('selected');
    $(this).addClass('selected');
    const role = $(this).data('role');
    const card = $('#login-form-card');
    card.removeClass('role-student role-admin role-leader').addClass(roleMap[role].class);
    $('#portal-label').text(roleMap[role].label);
    $('#username').focus();
});
</script>
</body>
</html>
