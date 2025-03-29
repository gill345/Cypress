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
            // Step 2: Check if new passwords match
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
    <title> Project Cypress </title>
    <link rel="icon" type="image/x-icon"
        href="https://img.icons8.com/external-flatart-icons-lineal-color-flatarticons/64/external-cn-tower-canada-independence-day-flatart-icons-lineal-color-flatarticons.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link type="image/png" sizes="32x32" rel="icon" href="images/logo.png">

</head>

<body class="bg-light">
    <section class="bg-light p-3 p-md-4 p-xl-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-xxl-11">
                    <div class="card border-light-subtle shadow-sm">
                        <div class="row g-0">
                            <div class="col-12 col-md-6">
                                <img class="img-fluid rounded-start w-100 h-100 object-fit-cover" loading="lazy"
                                    src="https://bazis.ca/wp-content/uploads/2020/06/skyline-sailing-summer-city-life-downtown-lifestyle-city-view-toronto-harbourfront-lake-ontario_t20_NGbYwp-2048x1536.jpg"
                                    alt="Welcome back you've been missed!">
                            </div>
                            <div class="col-12 col-md-6 d-flex align-items-center justify-content-center">
                                <div class="col-12 col-lg-11 col-xl-10">
                                    <div class="card-body p-3 p-md-4 p-xl-5">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="mb-5">
                                                    <div class="text-center mb-4">
                                                        <a href="login.php">
                                                            <img src="https://img.icons8.com/external-flatart-icons-lineal-color-flatarticons/100/external-cn-tower-canada-independence-day-flatart-icons-lineal-color-flatarticons.png"
                                                                alt="Cypress" width="135" height="80">
                                                        </a>
                                                    </div>
                                                    <h2 class="text-center">Project Cypress</h2>
                                                    <h4 class="text-center">Change Password Here</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <form action="changepass.php" method="POST">
                                            <div class="row gy-3 overflow-hidden">
                                               
                                                <div class="col-12">
                                                    <div class="form-floating mb-3">
                                                        <input type="email" class="form-control" name="email" id="email"
                                                            placeholder="name@example.com" required>
                                                        <label for="email" class="form-label">Email</label>
                                                    </div>
                                                </div>
                                               
                                                <div class="col-12">
                                                    <div class="form-group mb-3">
                                                        <label for="security_answer" class="form-label">Answer to your
                                                            Security Question:</label>
                                                        <input type="text" name="security_answer" id="security_answer"
                                                            class="form-control" placeholder="Enter your answer"
                                                            required>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-12">
                                                    <div class="form-floating mb-3">
                                                        <input type="password" class="form-control" name="new_password"
                                                            id="new_password" placeholder="New Password" required>
                                                        <label for="new_password" class="form-label">New
                                                            Password</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-12">
                                                    <div class="form-floating mb-3">
                                                        <input type="password" class="form-control"
                                                            name="confirm_password" id="confirm_password"
                                                            placeholder="Confirm Password" required>
                                                        <label for="confirm_password" class="form-label">Confirm
                                                            Password</label>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-12 mt-4">
                                                    <div class="d-grid">
                                                        <button class="btn btn-dark btn-lg" type="submit">Change
                                                            Password</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                        <div class="row">
                                            <div class="col-12">
                                                <div
                                                    class="d-flex gap-2 gap-md-4 flex-column flex-md-row justify-content-md-center mt-5">
                                                    <a href="login.php"
                                                        class="link-secondary text-decoration-none">Login Instead?</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</body>

</html>