/**
 * Authentication JavaScript - CARMS
 */

$(document).ready(function () {

    // ── LOGIN ────────────────────────────────────────────
    $('#login-form').on('submit', function (e) {
        e.preventDefault();

        const username = $('#username').val().trim();
        const password = $('#password').val();
        const btn      = $(this).find('button[type="submit"]');

        if (!username || !password) {
            showMessage('Please enter your username and password.', 'danger');
            return;
        }

        btn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-2"></span>Signing in...'
        );

        $.ajax({
            url: '/api/auth.php?action=login',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ username, password }),
            success: function (response) {
                if (response.success) {
                    showMessage('Login successful! Redirecting...', 'success');

                    const selectedCard = $('.role-card.selected').data('role');
                    const roles = (response.user && response.user.roles ? response.user.roles : [])
                                    .map(r => r.role_name);

                    // Map role names to portal keys
                    const isAdmin  = roles.includes('Administrator');
                    const isLeader = roles.includes('Activity Leader');
                    const isStudent = !isAdmin && !isLeader;

                    // Check if user is trying to access a portal they don't have rights to
                    if (selectedCard === 'admin' && !isAdmin) {
                        btn.prop('disabled', false).html('Sign In');
                        showMessage('You do not have Administrator privileges. Please select the correct portal.', 'danger');
                        return;
                    }
                    if (selectedCard === 'leader' && !isLeader) {
                        btn.prop('disabled', false).html('Sign In');
                        showMessage('You do not have Activity Leader privileges. Please select the correct portal.', 'danger');
                        return;
                    }

                    // Redirect to the correct portal
                   // Redirect only to selected portal
let redirect = '/student/dashboard';
if (selectedCard === 'admin' && isAdmin)        redirect = '/admin/dashboard';
else if (selectedCard === 'leader' && isLeader) redirect = '/leader/dashboard';
else if (selectedCard === 'student' && !isStudent) {
    btn.prop('disabled', false).html('Sign In');
    showMessage('You do not have Student privileges. Please select the correct portal.', 'danger');
    return;
}

                    setTimeout(function () {
                        window.location.href = redirect;
                    }, 1000);
                } else {
                    btn.prop('disabled', false).html('Sign In');
                    showMessage(response.message || 'Invalid username or password.', 'danger');
                }
            },
            error: function () {
                btn.prop('disabled', false).html('Sign In');
                showMessage('An error occurred. Please try again.', 'danger');
            }
        });
    });

    // ── REGISTER ─────────────────────────────────────────
    $('#register-form').on('submit', function (e) {
        e.preventDefault();

        const formData = {
            username:            $('#username').val(),
            password:            $('#password').val(),
            confirm_password:    $('#confirm_password').val(),
            email:               $('#email').val(),
            first_name:          $('#first_name').val(),
            last_name:           $('#last_name').val(),
            registration_number: $('#registration_number').val(),
            role_id: 1
        };

        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-2"></span>Registering...'
        );

        $.ajax({
            url: '/api/auth.php?action=register',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function (response) {
                if (response.success) {
                    showMessage('Registration successful! Redirecting to login...', 'success');
                    setTimeout(function () {
                        window.location.href = '/login';
                    }, 1500);
                } else {
                    btn.prop('disabled', false).html('Create Account');
                    showMessage(response.message, 'danger');
                }
            },
            error: function () {
                btn.prop('disabled', false).html('Create Account');
                showMessage('An error occurred. Please try again.', 'danger');
            }
        });
    });

    // ── MESSAGE HELPER ────────────────────────────────────
    function showMessage(message, type) {
        const html = `<div class="alert alert-${type} alert-dismissible fade show py-2 small" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;
        $('#message-container').html(html);
        setTimeout(function () {
            $('#message-container .alert').fadeOut(function () { $(this).remove(); });
        }, 5000);
    }
});
