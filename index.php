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
    <title>OOUTH NASU — Sign In</title>
    <?php require_once 'includes/header.php'; ?>
    <style>
        body {
            margin: 0 !important;
            padding: 0 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--bg-body);
            background-image: var(--grad-mesh);
            background-attachment: fixed;
        }
        /* Animated background orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
            animation: orbFloat 8s ease-in-out infinite;
        }
        .orb-1 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(34,197,94,0.12) 0%, transparent 70%);
            top: -100px; left: -100px;
        }
        .orb-2 {
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(59,130,246,0.09) 0%, transparent 70%);
            bottom: -80px; right: -80px;
            animation-delay: -4s;
        }
        .orb-3 {
            width: 250px; height: 250px;
            background: radial-gradient(circle, rgba(245,158,11,0.06) 0%, transparent 70%);
            top: 50%; left: 60%;
            animation-delay: -2s;
        }
        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%       { transform: translate(20px, -20px) scale(1.05); }
            66%       { transform: translate(-15px, 15px) scale(0.97); }
        }
        .login-outer {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 1.25rem;
        }
    </style>
</head>
<body>
    <!-- Background orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="login-outer">
        <div class="login-card">
            <!-- Logo & Brand -->
            <div class="login-logo-wrap">
                <img src="img/nasu.png" alt="NASU Logo" onerror="this.src='img/nasu.jpg'">
                <span class="org-name">OOUTH NASU Welfare Cooperative</span>
            </div>

            <p class="login-title">Sign in to your account</p>

            <form method="post" id="loginForm" novalidate>
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-id-card" style="margin-right:0.4rem;color:var(--text-muted);font-size:0.75rem;"></i>
                        Staff ID / Username
                    </label>
                    <input type="text"
                           class="form-control"
                           id="username"
                           name="username"
                           autocomplete="username"
                           inputmode="numeric"
                           placeholder="Enter your staff ID"
                           required>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock" style="margin-right:0.4rem;color:var(--text-muted);font-size:0.75rem;"></i>
                        Password
                    </label>
                    <div class="password-field">
                        <input type="password"
                               class="form-control"
                               id="password"
                               name="password"
                               autocomplete="current-password"
                               placeholder="Enter your password"
                               required>
                        <span class="password-toggle" id="togglePassword" aria-label="Toggle password visibility">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg mt-3" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span id="loginBtnText">Sign In</span>
                </button>
            </form>

            <div id="loginMessage" class="alert mt-3" style="display:none;"></div>

            <div class="mt-4 text-center" style="font-size:0.8rem;color:var(--text-muted);">
                <i class="fas fa-shield-alt" style="color:var(--accent);margin-right:0.3rem;"></i>
                Secured access · OOUTH NASU Welfare Cooperative
            </div>
        </div>

        <!-- Version tag -->
        <p class="text-center mt-3" style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-disabled);">
            v2.0 · &copy; <?= date('Y') ?> NASU OOUTH
        </p>
    </div>

    <script>
    $(document).ready(function () {
        $('#loginForm').submit(function (event) {
            event.preventDefault();
            var username = $('#username').val().trim();
            var password = $('#password').val().trim();

            if (!username) {
                Swal.fire({ icon: 'warning', title: 'Validation', text: 'Please enter your staff ID.', background: 'var(--bg-card-solid)', color: 'var(--text-primary)' });
                return;
            }
            if (!password) {
                Swal.fire({ icon: 'warning', title: 'Validation', text: 'Please enter your password.', background: 'var(--bg-card-solid)', color: 'var(--text-primary)' });
                return;
            }

            var btn = $('#loginBtn');
            btn.prop('disabled', true);
            $('#loginBtnText').text('Signing in…');

            $.ajax({
                type: 'POST',
                url: 'login_auth.php',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    var msg = $('#loginMessage');
                    if (response.status === 'success') {
                        msg.removeClass().addClass('alert alert-success').text(response.message).show();
                        setTimeout(function () { window.location.href = 'dashboard.php'; }, 1200);
                    } else {
                        msg.removeClass().addClass('alert alert-danger').text(response.message).show();
                        btn.prop('disabled', false);
                        $('#loginBtnText').text('Sign In');
                    }
                },
                error: function () {
                    $('#loginMessage').removeClass().addClass('alert alert-danger').text('An error occurred. Please try again.').show();
                    btn.prop('disabled', false);
                    $('#loginBtnText').text('Sign In');
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
