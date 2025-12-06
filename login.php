<?php
// Start a session to store user data
session_start();

// Database connection details
require('config.php');

// Initialize error message variable
$error_message = '';

// Create a database connection
// $conn object is available from config.php
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the login form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // SQL to retrieve user, including name and role
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $error_message = "A system error occurred during login preparation.";
    } else {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // REDIRECTION LOGIC based on role
                if ($_SESSION['user_role'] === 'client') {
                     header("Location: client_dashboard.php"); 
                } elseif ($_SESSION['user_role'] === 'lawyer') {
                     header("Location: lawyer_dashboard.php"); 
                } else {
                     header("Location: index.php"); // Fallback
                }
                exit();
            } else {
                $error_message = "Invalid email or password.";
            }
        } else {
            $error_message = "Invalid email or password.";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In to Vakil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <style>
        body {
          font-family: 'Inter', sans-serif;
          display: flex;
          flex-direction: column;
          min-height: 100vh;
          background-color: #f3f4f6;
        }
        
        /* New main wrapper for content centering */
        .main-content-wrapper {
            flex-grow: 1;
            display: flex;
            align-items: center; 
            justify-content: center; 
            padding: 2rem 1rem;
        }

        /* Auth Container */
        .auth-container {
          max-width: 480px;
          margin: 0 auto; 
          background-color: #ffffff;
          padding: 2.5rem 3rem; 
          border-radius: 0.75rem;
          box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 4px 10px -2px rgba(0, 0, 0, 0.04);
          width: 100%;
        }

        /* Auth Form Title */
        .auth-form-title {
          font-size: 2rem; 
          font-weight: 700;
          text-align: center;
          color: #1f2937;
          margin-bottom: 2rem;
        }

        /* Auth Form */
        .auth-form {
          display: flex;
          flex-direction: column;
          gap: 1rem; 
        }

        /* Form Group */
        .auth-form-group {
          display: flex;
          flex-direction: column;
          gap: 0.25rem;
          margin-bottom: 0; 
        }

        /* Form Labels */
        .auth-form-group label {
          font-size: 0.875rem;
          font-weight: 600; 
          color: #374151;
        }

        /* Form Inputs and Select */
        .auth-form-group input,
        .auth-form-group select {
          -webkit-appearance: none;
          -moz-appearance: none;
          appearance: none;
          display: block !important; 
          width: 100%;
          padding: 0.75rem 1rem; 
          border: 1px solid #d1d5db;
          border-radius: 0.5rem; 
          box-shadow: inset 0 1px 2px 0 rgba(0, 0, 0, 0.05); 
          font-size: 0.875rem;
          color: #111827;
          transition: all 0.2s;
        }

        .auth-form-group input:focus,
        .auth-form-group select:focus {
          outline: 2px solid transparent;
          outline-offset: 2px;
          box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #3b82f6;
          border-color: #3b82f6;
        }

        /* Button */
        .auth-button {
          width: 100%;
          display: flex;
          justify-content: center;
          padding: 0.75rem 1rem; 
          border: 1px solid transparent;
          border-radius: 0.5rem;
          box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
          font-size: 1rem; 
          font-weight: 600; 
          color: #ffffff;
          background-color: #2563eb;
          transition: background-color 0.2s, transform 0.1s;
          cursor: pointer;
          margin-top: 1.5rem; 
        }

        .auth-button:hover {
          background-color: #1d4ed8;
          transform: translateY(-1px);
        }

        /* Auth Link Footer Wrapper */
        .auth-footer-link {
            text-align: center;
            font-size: 0.875rem;
            color: #4b5563; 
            padding-top: 1.5rem;
            margin-top: 1.5rem;
            border-top: 1px solid #e5e7eb; 
        }

        /* Auth Link */
        .auth-link {
          font-weight: 600;
          color: #2563eb;
          text-decoration: none;
          transition: color 0.2s;
        }

        .auth-link:hover {
          color: #1d4ed8;
          text-decoration: underline;
        }

        /* Error and success messages */
        .error {
            color: #dc2626; 
            font-size: 0.875rem;
            text-align: center;
            margin-bottom: 1rem;
        }

        .success {
            color: #16a34a; 
            font-size: 0.875rem;
            text-align: center;
            margin-bottom: 1rem;
        }

        /* Generic styles for components (No change needed) */
        .nav-link.active { color: #1d4ed8; border-bottom: 2px solid #1d4ed8; }
        .rating-stars { color: #fbbf24; }
    </style>
    </head>
<body>
    <header class="bg-white shadow-sm sticky top-0 z-50">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex-shrink-0">
            <a href="index.php" class="flex items-center">
              <img 
                  src="vakillogo.png" 
                  alt="Vakil Logo" 
                  class="h-10 w-auto" 
                  onerror="this.onerror=null;this.src='https://placehold.co/150x40/ffffff/000000?text=Vakil+Logo'"
              />
            </a>
          </div>
          <nav class="hidden md:block">
            <div class="ml-10 flex items-baseline space-x-8">
              <a
                href="index.php"
                class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors"
                >Home</a
              >
           
              <a
                href="index.php#"
                class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors"
                >Top Lawyers</a
              >
            </div>
          </nav>
          <div class="hidden md:block w-24">
             </div>
        </div>
      </div>
    </header>
    <div class="main-content-wrapper">
        <div class="auth-container">
            <h2 class="auth-form-title">Log In to Your Account</h2>
            <?php if (isset($error_message)) { echo "<p class='error'>$error_message</p>"; } ?>
            <form action="login.php" method="POST" class="auth-form">
                <div class="auth-form-group">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="auth-form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="auth-button">Log In</button>
            </form>
            <p class="auth-footer-link">
                Don't have an account? <a href="register.php" class="auth-link">Sign Up</a>
            </p>
        </div>
    </div>
</body>
</html>