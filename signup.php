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
    <title> Project Cypress </title>
    <link rel="icon" type="image/x-icon" href="https://img.icons8.com/external-flatart-icons-lineal-color-flatarticons/64/external-cn-tower-canada-independence-day-flatart-icons-lineal-color-flatarticons.png">
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
              <img class="img-fluid rounded-start w-100 h-100 object-fit-cover" loading="lazy" src="https://bazis.ca/wp-content/uploads/2020/06/skyline-sailing-summer-city-life-downtown-lifestyle-city-view-toronto-harbourfront-lake-ontario_t20_NGbYwp-2048x1536.jpg" alt="Welcome back you've been missed!">
            </div>
            <div class="col-12 col-md-6 d-flex align-items-center justify-content-center">
              <div class="col-12 col-lg-11 col-xl-10">
                <div class="card-body p-3 p-md-4 p-xl-5">
                  <div class="row">
                    <div class="col-12">
                      <div class="mb-5">
                        <div class="text-center mb-4">
                          <a href="login.php">
                            <img src="https://img.icons8.com/external-flatart-icons-lineal-color-flatarticons/100/external-cn-tower-canada-independence-day-flatart-icons-lineal-color-flatarticons.png" alt="Cypress" width="135" height="80">
                          </a>
                        </div>
                        <h2 class="text-center">Project Cypress</h2>
                        <h4 class="text-center">Please Sign Up Here</h4>
                      </div>
                    </div>
                  </div>
                  <form action="signup.php" method="POST">
                    <div class="row gy-3 overflow-hidden">
                    <div class="col-12">
                        <div class="form-floating mb-3">
                          <input type="text" class="form-control" name="name" id="name" placeholder="Name" required>
                          <label for="name" class="form-label">Name</label>
                        </div>
                      </div>
                      <div class="col-12">
                        <div class="form-floating mb-3">
                          <input type="email" class="form-control" name="email" id="email" placeholder="name@example.com" required>
                          <label for="email" class="form-label">Email</label>
                        </div>
                      </div>
                      <div class="col-12">
                        <div class="form-floating mb-3">
                          <input type="password" class="form-control" name="password" id="password" value="" placeholder="Password" required>
                          <label for="password" class="form-label">Password</label>
                        </div>
                      </div>
                      <div class="col-12">
                        <div class="mb-3">
                        <label for="security_question">Choose a Security Question:</label>
                            <select name="security_question" id="security_question" class="form-control" required>
                                <option value="" disabled selected>Select a security question</option>
                                <option value="What is your pet's name?">What is your pet's name?</option>
                                <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                <option value="What is your favorite color?">What is your favorite color?</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="security_answer">Answer:</label>
                            <input type="text" name="security_answer" id="security_answer" class="form-control" required>
                        </div>
                      </div>
                      <div class="col-12 mt-4">
                        <div class="d-grid">
                          <button class="btn btn-dark btn-lg" type="submit">Sign up now</button>
                        </div>
                      </div>
                    </div>
                  </form>
                  <div class="row">
                    <div class="col-12">
                      <div class="d-flex gap-2 gap-md-4 flex-column flex-md-row justify-content-md-center mt-5">
                        <a href="login.php" class="link-secondary text-decoration-none">Already have an Account? Login Instead?</a>
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