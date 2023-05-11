<?php
session_start();

if (isset($_SESSION['user_id'])) {
  header('Location: /prueba/welcome.php');
}

require 'database.php';

$attempts_remaining = isset($_SESSION['attempts_remaining']) ? $_SESSION['attempts_remaining'] : 3;

if (!empty($_POST['email']) && !empty($_POST['password'])) {
  $records = $conn->prepare('SELECT id, email, password FROM users WHERE email = :email');
  $records->bindParam(':email', $_POST['email']);
  $records->execute();
  $results = $records->fetch(PDO::FETCH_ASSOC);

  $message = '';
  $login_status = 0;
  $navegador = $_SERVER['HTTP_USER_AGENT'];
  $os_type = php_uname('s');
  $ip_address = $_SERVER['REMOTE_ADDR'];

  if ($attempts_remaining > 0) {
    if (count($results) > 0 && password_verify($_POST['password'], $results['password'])) {
      if(isset($_POST['g-recaptcha-response'])) {
        $secretKey = '6Lcn4NMlAAAAACOoBrUlFlTsER4D0i0G55tLDUEC';
        $response = $_POST['g-recaptcha-response'];
        $remoteIP = $_SERVER['REMOTE_ADDR'];
      
        $url = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$response&remoteip=$remoteIP");
        $responseKeys = json_decode($url, true);

        if ($responseKeys["success"]) {
          $_SESSION['user_id'] = $results['id'];
          $login_status = 1;

          // Registra el intento de inicio de sesión en la tabla access_log
          $access_log = $conn->prepare('INSERT INTO access_log (login_time, login_status, navegador, os_type, user_agent, ip_address) VALUES (now(), :login_status, :navegador, :os_type, :user_agent, :ip_address)');
          $access_log->bindParam(':login_status', $login_status);
          $access_log->bindParam(':navegador', $navegador);
          $access_log->bindParam(':os_type', $os_type);
          $access_log->bindParam(':user_agent', $_POST['email']);
          $access_log->bindParam(':ip_address', $ip_address);
          $access_log->execute();

          header("Location: /prueba/welcome.php");
        } else {
          $message = 'reCAPTCHA verification failed. Please try again.';
        }
      } else {
        $message = 'Please verify that you are not a robot.';
      }
    } else {
      $message = 'Sorry, those credentials do not match';
      $_SESSION['attempts_remaining'] = --$attempts_remaining;
      
      if ($attempts_remaining == 0) {
        $message = 'Login has been locked. Please wait for one minute before trying again.';
        header("Refresh: 60");
      }
    }
  } else {
    $message = 'Login has been locked. Please wait for one minute before trying again.';
    header("Refresh: 60");
  }
}

?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
      .center {
        margin: auto;
        width: 20%;
        padding: 10px;
      }
    </style>
  </head>
  <body>
    <?php require 'partials/header.php' ?>

    <div class="center">
      <?php if(!empty($message)): ?>
        <p> <?= $message ?></p>
      <?php endif; ?>

      <h1>Login</h1>
      <span>or <a href="signup.php">SignUp</a></span>


      <form action="login.php" method="POST">
        <input name="email" type="text" placeholder="Enter your email">
        <input name="password" type="password" placeholder="Enter your Password">
        <div class="g-recaptcha" data-sitekey="6Lcn4NMlAAAAADnx1irCKZ2Gs6gx0qCVO5wOVeNO"></div>
        <input type="submit" value="Submit">
      </form>
    </div>
 

  </body>
</html>