<?php

require_once 'db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare('SELECT id, name, password, role FROM users WHERE email  = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            echo 'Successful Login! Welcome ' . $user['name'];
                header('Location: index.php');
            
            exit();

        } else {
            echo "<script>alert('Incorrect password. Please try again.');</script>";
        }
    } else {
        echo "<script>alert('Email not found. Please check your credentials.');</script>";
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
        }
        .btn-dark:hover {
            background-color: #23272b;
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
                    <div class="col-md-6 d-flex align-items-center p-5">
                        <div class="w-100">
                            <div class="text-center mb-4">
                                <a href="login.php">
                                    <img src="https://img.icons8.com/external-flatart-icons-lineal-color-flatarticons/100/external-cn-tower-canada-independence-day-flatart-icons-lineal-color-flatarticons.png" 
                                         alt="Cypress" width="150" height="90">
                                </a>
                            </div>
                            <h2 class="text-center">Project Cypress</h2>
                            <p class="text-center text-muted">Welcome back! You've been missed.</p>
                            <form action="login.php" method="POST">
                                <div class="mb-4">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" id="email" placeholder="name@example.com" required>
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                                </div>
                                <div class="d-grid mb-4">
                                    <button class="btn btn-dark btn-lg" type="submit">Log in now</button>
                                </div>
                            </form>
                            <div class="text-center mt-4">
                                <a href="signup.php" class="link-secondary text-decoration-none">Create new account</a> |
                                <a href="changepass.php" class="link-secondary text-decoration-none">Forgot password?</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <script>
        
        var myCarousel = new bootstrap.Carousel(document.getElementById('imageCarousel'), {
            interval: 5000,
            ride: 'carousel'
        });
    </script>
</body>

</html>

