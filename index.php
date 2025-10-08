<?php
session_start();

// Unset all of the session variables.
session_unset();

// Destroy the session.
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OOUTH NASU - Login</title>
    <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"> -->
    <?php require_once 'includes/header.php'; ?>
    <style>
        body,
        html {
            height: 100%;
            margin: 0;
            padding: 0;
            background-image: url('img/background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .login-container {
            margin-top: 50px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 50px;
        }

        .card {
            width: 400px;
            background: transparent;
            border: none;
        }

        .company-logo {
            width: 70px;
            margin: 20px auto;
        }

        .password-field {
            position: relative;
        }

        .password-field input[type=password] {
            padding-right: 30px;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 70%;
            transform: translateY(-50%);
            cursor: pointer;
        }


        #loginMessage {
            display: hidden;
            height: 50px;
            width: 100%;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container h-100 login-container">
        <div class="row justify-content-center align-items-center h-100">
            <div class="col-md-6 justify-content-center align-items-center">
                <div class="text-center">
                    OOUTH NASU WELFARE
                    <img src="img/nasu.png" alt="NASU Logo" class="company-logo">
                </div>
                <div>
                    <div class="card-header text-center">Login</div>
                    <div class="card-body">
                        <form method="post" id="loginForm">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                           
                            <div class="form-group password-field">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Login</button>
                        </form>
                        <div id="loginMessage" class="alert"></div>
                        <!-- Forgot Password Link -->
                        <div class="mt-4 text-center">
                            <a href="forgot-password.php">Forgot Password?</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $("#loginForm").submit(function(event) {
                event.preventDefault(); // Prevent the default form submission
                var username = $("#username").val().trim();
                var password = $("#password").val().trim();

                if (username === "") {
                    alert("Please enter your username.");
                } else if (password === "") {
                    alert("Please enter your password.");
                } else {
                    $.ajax({
                        type: "POST",
                        url: "login_auth.php",
                        data: $(this).serialize(),
                        dataType: "json",
                        success: function(response) {
                            var messageDiv = $("#loginMessage");
                            if (response.status === "success") {
                                messageDiv.removeClass().addClass('alert alert-success').text(response.message).show();
                                setTimeout(function() {
                                    window.location.href = "dashboard.php";
                                }, 3000);
                            } else {
                                messageDiv.removeClass().addClass('alert alert-danger').text(response.message).show();
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $("#loginMessage").removeClass().addClass('alert alert-danger').text("An error occurred. Please try again.").show();
                        }
                    });
                }
            });

            $('#togglePassword').click(function() {
                var type = $('#password').attr('type') === 'password' ? 'text' : 'password';
                $('#password').attr('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>

</html>