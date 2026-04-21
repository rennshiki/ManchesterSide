<?php
/**
 * Manchester Side - Logout Handler
 */
require_once 'includes/config.php';

// Destroy session
session_unset();
session_destroy();

// Set flash message
session_start();
setFlashMessage('success', 'Anda telah berhasil logout. Sampai jumpa lagi!');

// Redirect to homepage
redirect('index.php');
?>