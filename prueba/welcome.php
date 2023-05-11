<?php
  session_start();

  if (isset($_SESSION['user_id'])) {
    require 'database.php';

    $records = $conn->prepare('SELECT id, email, password FROM users WHERE id = :id');
    $records->bindParam(':id', $_SESSION['user_id']);
    $records->execute();
    $results = $records->fetch(PDO::FETCH_ASSOC);

    $user = null;

    if (count($results) > 0) {
      $user = $results;
    }
  } else {
    header("Location: /php-login/login.php");
  }
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Welcome</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
  </head>
  <body>
    <?php require 'partials/header.php' ?>

    <?php if(!empty($user)): ?>
      <br />Welcome <?= $user['email']; ?>
      <br /><br />You are successfully logged in!
      <br /><br /><a href="logout.php">Logout?</a>
    <?php endif; ?>
  </body>
</html>
