<?php
session_start();

// Destroy session
session_destroy();

// Redirect to landing page
header("Location: Landing_page.php");
exit();
?>
