<?php
require_once 'db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare('SELECT id, name, password, role FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: index.php');
            exit();
        } else {
            $error = "Incorrect password. Please try again.";
        }
    } else {
        $error = "Email not found. Please check your credentials.";
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
    <link rel="icon" type="image/x-icon" href="https://img.icons8.com/external-flatart-icons-lineal-color-flatarticons/64/external-cn-tower-canada-independence-day-flatart-icons-lineal-color-flatarticons.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg,rgb(0, 0, 0), #cfdef3);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .img-fluid {
            object-fit: cover;
            height: 100%;
        }
        .form-floating input {
            border-radius: 10px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        .form-floating input:focus {
            box-shadow: 0 0 10px rgba(13, 110, 253, 0.3);
            border-color: #0d6efd;
        }
        .btn-dark {
            background-color: #1a1a2e;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn-dark:hover {
            background-color: #0d6efd;
        }
        h2 {
            font-weight: 600;
            color: #1a1a2e;
        }
        h4 {
            font-weight: 300;
            color: #6c757d;
        }
        .link-secondary {
            color: #0d6efd;
            font-weight: 400;
            transition: color 0.3s ease;
        }
        .link-secondary:hover {
            color: #1a1a2e;
            text-decoration: underline;
        }
        .error-message {
            color: #dc3545;
            text-align: center;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .card {
                margin: 20px;
            }
            .img-fluid {
                height: 200px;
                border-radius: 20px 20px 0 0;
            }
        }
    </style>
</head>
<body>
    <section class="p-3 p-md-4 p-xl-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="card">
                        <div class="row g-0">
                            <div class="col-12 col-md-6">
                                <img class="img-fluid rounded-start" src="https://bazis.ca/wp-content/uploads/2020/06/skyline-sailing-summer-city-life-downtown-lifestyle-city-view-toronto-harbourfront-lake-ontario_t20_NGbYwp-2048x1536.jpg" alt="Toronto Skyline">
                            </div>
                            <div class="col-12 col-md-6 d-flex align-items-center">
                                <div class="card-body p-4 p-md-5">
                                    <div class="text-center mb-4">
                                        <a href="login.php">
                                            <img src="https://img.icons8.com/external-flatart-icons-lineal-color-flatarticons/100/external-cn-tower-canada-independence-day-flatart-icons-lineal-color-flatarticons.png" alt="Cypress" width="80" height="80">
                                        </a>
                                    </div>
                                    <h2 class="text-center">Project Cypress</h2>
                                    <h4 class="text-center">Welcome Back!</h4>
                                    <?php if (isset($error)): ?>
                                        <div class="error-message"><?php echo $error; ?></div>
                                    <?php endif; ?>
                                    <form action="login.php" method="POST">
                                        <div class="form-floating mb-3">
                                            <input type="email" class="form-control" name="email" id="email" placeholder="name@example.com" required>
                                            <label for="email">Email</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                                            <label for="password">Password</label>
                                        </div>
                                        <div class="d-grid">
                                            <button class="btn btn-dark btn-lg" type="submit">Log In</button>
                                        </div>
                                    </form>
                                    <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mt-4">
                                        <a href="signup.php" class="link-secondary">Create Account</a>
                                        <a href="changepass.php" class="link-secondary">Forgot Password?</a>
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