<?php
// start.php

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------------------------------
// 1. Check Login State (Redirects non-logged-in users)
// -------------------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect them to the home page (where login is linked)
    header("Location: index.php");
    exit();
}

// -------------------------------------------------------------------------
// 2. Client Role Check
// -------------------------------------------------------------------------
// We are making this a client-only page. If a lawyer logs in, they should 
// be redirected to a different dashboard 
if ($_SESSION['user_role'] !== 'client') {
    // Redirect non-clients (e.g., lawyers)
    header("Location: index.php?error=unauthorized");
    exit();
}

// Variables for easy use in HTML/UI
$current_user_name = $_SESSION['user_name'] ?? 'Client';
$current_user_email = $_SESSION['user_email'] ?? '';

?>
