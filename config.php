<?php
// config.php
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "logsign_db"; // Your database name

// --- RAZORPAY CONFIGURATION (TEST MODE) ---
// IMPORTANT: Replace with your actual Test Key ID and Secret
define('RAZORPAY_KEY_ID', 'rzp_test_RYMbNJZ73kwbTg');
define('RAZORPAY_KEY_SECRET', 'mRq3cGyxTjFtDreOhLHTV2EZ');
define('RAZORPAY_CURRENCY', 'INR');
// --- END RAZORPAY CONFIG ---

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>