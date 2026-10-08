<?php
session_start();
session_unset();
session_destroy();

// Redirect with success flag
header("Location: ../public/login.php?success=loggedout");
exit;
?>
