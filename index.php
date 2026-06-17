<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OOUTH NASU — Login</title>
    <?php require_once 'includes/header.php'; ?>
    <style>
        body, html { height: 100%; margin: 0; padding: 0; }
        body {
            margin: 0 !important;
            padding: 0 !important;
            background: var(--bg-body);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.25rem !important;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-logo-wrap">
            <img src="img/nasu.png" alt="NASU Logo">
            <span class="org-name">OOUTH NASU WELFARE COOPERATIVE</span>
        </div>

        <p class="login-title">Sign in to your account</p>

        <form method="post" id="loginForm" novalidate>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text"
                       class="form-control"
                       id="username"
                       name="username"
                       autocomplete="username"
                       inputmode="numeric"
                       required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password"
                           class="form-control"
                           id="password"
                           name="password"
                           autocomplete="current-password"
                           required>
                    <span class="password-toggle" id="togglePassword" aria-label="Toggle password visibility">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-2" id="loginBtn">
                Sign In
            </button>
        </form>

        <div id="loginMessage" class="alert" style="display:none;"></div>

        <div class="mt-3 text-center" style="font-size:0.85rem;">
            <a href="forgot-password.php" style="color:var(--text-muted);">Forgot password?</a>
        </div>
    </div>

    <script>
    $(document).ready(function () {
        $('#loginForm').submit(function (event) {
            event.preventDefault();
            var username = $('#username').val().trim();
            var password = $('#password').val().trim();

            if (username === '') {
                Swal.fire({ icon: 'warning', title: 'Validation', text: 'Please enter your username.', background: '#1E293B', color: '#F8FAFC' });
                return;
            }
            if (password === '') {
                Swal.fire({ icon: 'warning', title: 'Validation', text: 'Please enter your password.', background: '#1E293B', color: '#F8FAFC' });
                return;
            }

            var btn = $('#loginBtn');
            btn.prop('disabled', true).text('Signing in…');

            $.ajax({
                type: 'POST',
                url: 'login_auth.php',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    var msg = $('#loginMessage');
                    if (response.status === 'success') {
                        msg.removeClass().addClass('alert alert-success').text(response.message).show();
                        setTimeout(function () { window.location.href = 'dashboard.php'; }, 1500);
                    } else {
                        msg.removeClass().addClass('alert alert-danger').text(response.message).show();
                        btn.prop('disabled', false).text('Sign In');
                    }
                },
                error: function () {
                    $('#loginMessage').removeClass().addClass('alert alert-danger').text('An error occurred. Please try again.').show();
                    btn.prop('disabled', false).text('Sign In');
                }
            });
        });

        $('#togglePassword').on('click', function () {
            var input = $('#password');
            var icon  = $('#toggleIcon');
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    });
    </script>
</body>
</html>
