<?php
require 'database.php';

$message = '';

if (!empty($_POST['email']) && !empty($_POST['password'])) {
  // Check if email already exists in database
  $records = $conn->prepare('SELECT COUNT(*) AS count FROM users WHERE email = :email');
  $records->bindParam(':email', $_POST['email']);
  $records->execute();
  $result = $records->fetch(PDO::FETCH_ASSOC);

  if ($result['count'] > 0) {
    $message = 'Sorry, that email is already registered.';
  } else {
    // Verify reCAPTCHA
    if(isset($_POST['g-recaptcha-response'])) {
      $secretKey = '6Lcn4NMlAAAAACOoBrUlFlTsER4D0i0G55tLDUEC';
      $response = $_POST['g-recaptcha-response'];
      $remoteIP = $_SERVER['REMOTE_ADDR'];

      $url = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$response&remoteip=$remoteIP");
      $responseKeys = json_decode($url, true);

      if ($responseKeys["success"]) {
        // Insert new user into database
        $sql = "INSERT INTO users (email, password) VALUES (:email, :password)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt->bindParam(':password', $password);

        if ($stmt->execute()) {
          $message = 'Successfully created new user';
        } else {
          $message = 'Sorry there must have been an issue creating your account';
        }
      } else {
        $message = 'reCAPTCHA verification failed. Please try again.';
      }
    } else {
      $message = 'Please verify that you are not a robot.';
    }
  }
}
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>SignUp</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
      .g-recaptcha {
        margin: auto;
        width: 304px;
      }
    </style>
  </head>
  <body>

    <?php require 'partials/header.php' ?>

    <?php if(!empty($message)): ?>
      <p> <?= $message ?></p>
    <?php endif; ?>

    <h1>SignUp</h1>
    <span>or <a href="login.php">Login</a></span>

    <form action="signup.php" method="POST">
      <input name="email" type="text" placeholder="Enter your email">
      <input name="password" type="password" placeholder="Enter your Password">
      <input name="confirm_password" type="password" placeholder="Confirm Password">
      <div class="g-recaptcha" data-sitekey="6Lcn4NMlAAAAADnx1irCKZ2Gs6gx0qCVO5wOVeNO"></div>
      <input type="submit" value="Submit">
    </form>

  </body>
</html>
