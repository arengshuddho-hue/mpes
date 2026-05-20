<?php
// logout.php
// Destroys the current session and redirects to the login page.
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit;
?>
