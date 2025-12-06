<?php
// Start a session
session_start();

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "logsign_db";

// Initialize error message variables
$success_message = '';
$error_message = '';

// Create a database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the registration form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number']; // NEW FIELD
  $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Hash the password before storing it
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if 'location' column exists; try to add if missing (optional)
    $hasLocationCol = false;
    if ($colCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'location'")) {
      $hasLocationCol = ($colCheck->num_rows > 0);
      $colCheck->close();
    }
    if (!$hasLocationCol) {
      $conn->query("ALTER TABLE users ADD COLUMN location VARCHAR(255) NULL");
      if ($colCheck2 = $conn->query("SHOW COLUMNS FROM users LIKE 'location'")) {
        $hasLocationCol = ($colCheck2->num_rows > 0);
        $colCheck2->close();
      }
    }

    // Prepare SQL depending on column availability
    if ($hasLocationCol) {
      $sql = "INSERT INTO users (name, email, phone_number, location, password, role) VALUES (?, ?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
    } else {
      $sql = "INSERT INTO users (name, email, phone_number, password, role) VALUES (?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
    }

    // Check if the statement preparation failed
    if (!$stmt) {
        $error_message = "A system error occurred. Please check database configuration.";
    } else {
        // Bind parameters based on SQL used
        if ($hasLocationCol) {
          // name, email, phone_number, location, password, role
          $stmt->bind_param("ssssss", $name, $email, $phone_number, $location, $hashed_password, $role);
        } else {
          // name, email, phone_number, password, role
          $stmt->bind_param("sssss", $name, $email, $phone_number, $hashed_password, $role);
        }

        // Attempt execution and check the result
        if ($stmt->execute()) {
            $success_message = "Registration successful! You can now log in.";
        } else {
            // Check for specific MySQL error code for Duplicate Entry (Error Code 1062)
            if ($stmt->errno === 1062) {
                $error_message = "Error: This email may already be registered. Please try logging in.";
            } else {
                // General execution error
              $error_message = "An unexpected database error occurred during registration. Please try again.";
            }
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
    <title>Sign Up to Vakil</title>
    <!-- Add Tailwind and Inter Font links (needed for header styling) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <!-- EMBEDDED CSS START (Identical to login.php for consistent styling) -->
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
    <!-- EMBEDDED CSS END -->
</head>
<body>
    <!-- START: Shared Header (without Login/Signup Button) -->
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
                href="index.php#page-lawyers"
                class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors"
                >Top Lawyers</a
              >
            </div>
          </nav>
          <!-- Placeholder for missing Login / Sign Up button block -->
          <div class="hidden md:block w-24">
             <!-- Placeholder for spacing if needed -->
          </div>
        </div>
      </div>
    </header>
    <!-- END: Shared Header -->

    <div class="main-content-wrapper">
        <div class="auth-container">
            <h2 class="auth-form-title">Create an Account</h2>
            <?php if (isset($success_message)) { echo "<p class='success'>$success_message</p>"; } ?>
            <?php if (isset($error_message)) { echo "<p class='error'>$error_message</p>"; } ?>
            <form action="register.php" method="POST" class="auth-form">
                <div class="auth-form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="auth-form-group">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="auth-form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="tel" id="phone_number" name="phone_number" required>
                </div>
                <div class="auth-form-group">
                  <label for="location">Location (City/Area)</label>
                  <input type="text" id="location" name="location" placeholder="e.g., Mumbai">
                </div>
                
                <div class="auth-form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="auth-form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="client">Client</option>
                        <option value="lawyer">Lawyer</option>
                    </select>
                </div>
                <button type="submit" class="auth-button">Sign Up</button>
            </form>
            <!-- RESTORED and STYLED: Link to the Log In page -->
            <p class="auth-footer-link">
                Already have an account? <a href="login.php" class="auth-link">Log In</a>
            </p>
        </div>
    </div>
</body>
</html>
