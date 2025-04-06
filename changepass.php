<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $security_answer = $_POST['security_answer'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];


    $stmt = $conn->prepare('SELECT id, security_answer FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($user_id, $stored_answer);
    if ($stmt->fetch()) {
        if (password_verify($security_answer, $stored_answer)) {
            
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                $stmt->close();
                $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                $stmt->bind_param('si', $hashed_password, $user_id);
                if ($stmt->execute()) {
                    echo "<script>alert('Password changed successfully!'); window.location.href='login.php';</script>";
                } else {
                    echo "<script>alert('Error: " . $stmt->error . "');</script>";
                }
            } else {
                echo "<script>alert('Passwords do not match!');</script>";
            }
        } else {
            echo "<script>alert('Incorrect answer to the security question.');</script>";
        }
    } else {
        echo "<script>alert('Email not found.');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Cypress</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .login-container {
            max-width: 1000px;
        }

        .login-card {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .carousel-item img {
            height: 100%;
            width: 100%;
            object-fit: cover;
        }

        #imageCarousel, .carousel-inner, .carousel-item {
            height: 100%;
        }

        .btn-dark {
            background-color: #343a40;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
        }

        .btn-dark:hover {
            background-color: #23272b;
        }

        .form-label {
            font-size: 14px;
        }

        .form-control {
            border-radius: 8px;
            padding: 8px;
        }

        .text-muted {
            font-size: 14px;
        }

        .link-secondary {
            font-size: 12px;
        }

        h2 {
            font-size: 24px;
            font-weight: bold;
        }

        h4 {
            font-size: 18px;
            color: #343a40;
        }

        .account-text {
            font-size: 16px;
            color: #343a40;
        }

        .sign-in-link {
            font-size: 16px;
            color: #343a40;
            text-decoration: none;
        }

        .sign-in-link:hover {
            color: #23272b;
        }

        .sign-in-link span.arrow {
            text-decoration: none;
        }

        .sign-in-link:hover span.arrow {
            text-decoration: none;
        }

        .sign-in-link span:not(.arrow) {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <section class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="container login-container">
            <div class="card shadow login-card">
                <div class="row g-0">
                    <div class="col-md-6 d-none d-md-block">
                        <div id="imageCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <img src="https://bazis.ca/wp-content/uploads/2020/06/skyline-sailing-summer-city-life-downtown-lifestyle-city-view-toronto-harbourfront-lake-ontario_t20_NGbYwp-2048x1536.jpg" 
                                         class="d-block w-100" alt="Toronto Skyline">
                                </div>
                                <div class="carousel-item">
                                    <img src="https://epicexperiences.ca/wp-content/uploads/2021/12/Toronto-Night-Tour-main.jpg" 
                                         class="d-block w-100" alt="Toronto Night Tour">
                                </div>
                                <div class="carousel-item">
                                    <img src="https://a.travel-assets.com/findyours-php/viewfinder/images/res70/517000/517193-downtown-toronto.jpg" 
                                         class="d-block w-100" alt="Downtown Toronto">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-center p-4">
                        <div class="w-100">
                            <div class="text-center mb-3">
                                <a href="login.php">
                                    <img src="https://img.icons8.com/external-flatart-icons-lineal-color-flatarticons/100/external-cn-tower-canada-independence-day-flatart-icons-lineal-color-flatarticons.png"
                                         alt="Cypress" width="100" height="60">
                                </a>
                            </div>
                            <h2 class="text-center fs-3">Project Cypress</h2>
                            <h4 class="text-center text-muted fs-5">Change Password Here</h4>
                            <form action="changepass.php" method="POST">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" id="email" placeholder="name@example.com" required>
                                </div>
                                <div class="mb-3">
                                    <label for="security_answer" class="form-label">Answer to your Security Question</label>
                                    <input type="text" name="security_answer" id="security_answer" class="form-control" placeholder="Enter your answer" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" id="new_password" placeholder="New Password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                                </div>
                                <div class="d-grid mb-3">
                                    <button class="btn btn-dark" type="submit">Change Password</button>
                                </div>
                            </form>
                            <div class="text-center mt-4">
                                <span class="account-text">Back to login? </span>
                                <a href="login.php" class="sign-in-link">Login <span class="arrow">→</span></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        var myCarousel = new bootstrap.Carousel(document.getElementById('imageCarousel'), {
            interval: 2500,
            ride: 'carousel'
        });
    </script>
</body>

</html>