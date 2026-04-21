<?php
/**
 * Manchester Side - Admin Logout
 */
require_once '../includes/config.php';

// Destroy admin session
session_unset();
session_destroy();

// Redirect to admin login
redirect('login.php');
?>