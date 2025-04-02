<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include 'db.php';

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $security_question = $_POST['security_question'];
    $security_answer = password_hash($_POST['security_answer'], PASSWORD_BCRYPT); // Hash the answer for security

    $sql = "SELECT email FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>
                alert('Account with this email already exists! Login Instead');
                window.location.href = 'login.php';
              </script>";
        $stmt->close();
        exit;
    }
    $stmt->close();


    $sql = "INSERT INTO users (name, email, password, security_question, security_answer) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $name, $email, $password, $security_question, $security_answer);

    if ($stmt->execute()) {
        echo "<script>
                alert('Account created successfully! You can now login.');
                window.location.href = 'login.php';
              </script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
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

    .form-image {
      height: 100%;
      width: 100%;
      object-fit: cover;
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

    .account-text {
      font-size: 14px;
      color: #343a40;
    }

    .sign-in-link {
      font-size: 14px;
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
            <img
              src="https://bazis.ca/wp-content/uploads/2020/06/skyline-sailing-summer-city-life-downtown-lifestyle-city-view-toronto-harbourfront-lake-ontario_t20_NGbYwp-2048x1536.jpg"
              class="form-image" alt="Toronto Skyline">
          </div>
          <div class="col-md-6 d-flex align-items-center p-4">
            <div class="w-100">
              <div class="text-center mb-3">
                <a href="login.php">
                  <img
                    src="https://img.icons8.com/external-flatart-icons-lineal-color-flatarticons/100/external-cn-tower-canada-independence-day-flatart-icons-lineal-color-flatarticons.png"
                    alt="Cypress" width="100" height="60"> 
                </a>
              </div>
              <h2 class="text-center">Project Cypress</h2>
              <p class="text-center text-muted">Please Sign Up Here</p>
              <form action="signup.php" method="POST">
                <div class="mb-3">
                  <label for="name" class="form-label">Name</label>
                  <input type="text" class="form-control" name="name" id="name" placeholder="Name" required>
                </div>
                <div class="mb-3">
                  <label for="email" class="form-label">Email</label>
                  <input type="email" class="form-control" name="email" id="email" placeholder="name@example.com"
                    required>
                </div>
                <div class="mb-3">
                  <label for="password" class="form-label">Password</label>
                  <input type="password" class="form-control" name="password" id="password" placeholder="Password"
                    required>
                </div>
                <div class="mb-3">
                  <label for="security_question" class="form-label">Choose a Security Question:</label>
                  <select name="security_question" id="security_question" class="form-control" required>
                    <option value="" disabled selected>Select a security question</option>
                    <option value="What is your pet's name?">What is your pet's name?</option>
                    <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                    <option value="What is your favorite color?">What is your favorite color?</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label for="security_answer" class="form-label">Answer:</label>
                  <input type="text" name="security_answer" id="security_answer" class="form-control" required>
                </div>
                <div class="d-grid mb-3">
                  <button class="btn btn-dark" type="submit">Sign up now</button>
                </div>
              </form>
              <div class="text-center mt-4">
                <span class="account-text">Already have an account? </span>
                <a href="login.php" class="sign-in-link">Sign in <span class="arrow">→</span></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</body>

</html>