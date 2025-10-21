<?php
session_start();
session_destroy();  // Destroy session
header("Location: index.html");  // Redirect to login page
?>