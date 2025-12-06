<?php
// client_dashboard.php

// --- 1. Session, Auth, and Configuration Imports (MUST BE AT THE TOP) ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header("Location: index.php");
    exit();
}

// Include database connection and Razorpay configuration
require('config.php');
require('vendor/autoload.php'); // Include Composer autoloader for Razorpay SDK
use Razorpay\Api\Api;

// --- 2. Database Connection ---
// Connection is established in config.php, use $conn
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- 3. Razorpay Initialization and Constants ---
$api_error = null;
try {
    $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
} catch (Exception $e) {
    $api_error = "Razorpay API setup failed.";
}

$case_registration_fee_paise = 50000; // ₹500.00 in smallest unit (paise)

// --- 4. Mock Lawyer Data (15 Top Lawyers) - Kept for Book Consultation tab ---
$lawyers = [
    ['name' => 'Santosh Mishra', 'specialty' => 'Family Law', 'rating' => 4.8, 'location' => 'Mumbai', 'areas' => 'Goregaon East'],
    ['name' => 'Ananya Sharma', 'specialty' => 'Criminal Law', 'rating' => 4.9, 'location' => 'Delhi', 'areas' => 'Karol Bagh'],
    ['name' => 'Priya Patel', 'specialty' => 'Commercial Law', 'rating' => 4.7, 'location' => 'Bangalore', 'areas' => 'Indiranagar'],
    ['name' => 'Rohan Joshi', 'specialty' => 'Civil Litigation', 'rating' => 4.6, 'location' => 'Pune', 'areas' => 'Hinjewadi'],
    ['name' => 'Meera Krishnan', 'specialty' => 'IPR', 'rating' => 4.8, 'location' => 'Chennai', 'areas' => 'Velachery'],
    ['name' => 'Aditya Verma', 'specialty' => 'Cyber Law', 'rating' => 4.7, 'location' => 'Hyderabad', 'areas' => 'Banjara Hills'],
    ['name' => 'Sunita Reddy', 'specialty' => 'Real Estate', 'rating' => 4.5, 'location' => 'Bangalore', 'areas' => 'Jayanagar'],
    ['name' => 'Karan Malhotra', 'specialty' => 'Tax Law', 'rating' => 4.9, 'location' => 'Delhi', 'areas' => 'Dwarka'],
    ['name' => 'Divya Rao', 'specialty' => 'Family Law', 'rating' => 4.3, 'location' => 'Mumbai', 'areas' => 'Thane'],
    ['name' => 'Jatin Desai', 'specialty' => 'Corporate Law', 'rating' => 4.6, 'location' => 'Ahmedabad', 'areas' => 'Navrangpura'],
    ['name' => 'Neelam Gupta', 'specialty' => 'Criminal Law', 'rating' => 4.7, 'location' => 'Kolkata', 'areas' => 'Salt Lake'],
    ['name' => 'Sanjay Iyer', 'specialty' => 'Civil Litigation', 'rating' => 4.5, 'location' => 'Pune', 'areas' => 'Kalyani Nagar'],
    ['name' => 'Faisal Khan', 'specialty' => 'Consumer Law', 'rating' => 4.4, 'location' => 'Lucknow', 'areas' => 'Aliganj'],
    ['name' => 'Tara Sharma', 'specialty' => 'Environmental Law', 'rating' => 4.2, 'location' => 'Jaipur', 'areas' => 'Vaishali Nagar'],
    ['name' => 'Rajesh Mehra', 'specialty' => 'Arbitration', 'rating' => 5.0, 'location' => 'Mumbai', 'areas' => 'Colaba'],
];

// --- 5. Logic Execution (PRG pattern) ---
$client_id = $_SESSION['user_id'];
$alert_message = '';
$alert_type = 'success';
$initial_page = 'register-case'; // Default view

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $success = false;
        $target_page = 'my-cases'; // Default page after successful transaction
        
        // --- A. Initiate Razorpay Payment for Case Registration (STEP 1) ---
        if ($_POST['action'] === 'initiate_case_payment') {
            if (!$api_error) {
                $case_title = htmlspecialchars($_POST['reg_case_title']);
                $case_description = htmlspecialchars($_POST['reg_case_description']);
                $case_type = htmlspecialchars($_POST['reg_case_type']);
                
                // Store case details in session for saving after payment confirmation
                $_SESSION['pending_case_registration'] = [
                    'case_title' => $case_title,
                    'case_description' => $case_description,
                    'case_type' => $case_type
                ];

                // 1. Create a Razorpay Order
                $orderData = [
                    'receipt'         => 'rcpt_case_'.uniqid(), 
                    'amount'          => $case_registration_fee_paise, 
                    'currency'        => RAZORPAY_CURRENCY,
                    'payment_capture' => 1
                ];
        
                try {
                    $razorpayOrder = $api->order->create($orderData);
                    $razorpayOrderId = $razorpayOrder['id'];
        
                    $_SESSION['razorpay_order_id'] = $razorpayOrderId;
        
                    // Prepare Checkout data for the front-end (JavaScript)
                    $data = [
                        "key"               => RAZORPAY_KEY_ID,
                        "amount"            => $case_registration_fee_paise, 
                        "currency"          => RAZORPAY_CURRENCY,
                        "name"              => "Vakil Case Registration Fee",
                        "description"       => "Case: " . $case_title,
                        "image"             => "vakillogo.png", 
                        "order_id"          => $razorpayOrderId,
                        "prefill"           => [
                            "name"          => $_SESSION['user_name'],
                            "email"         => $_SESSION['user_email'],
                            "contact"       => "9999999999", 
                        ],
                        "notes"             => [
                            "client_id"     => $client_id,
                            "case_title"    => $case_title
                        ],
                        "theme"             => [
                            "color"         => "#10B981" // Green-500
                        ]
                    ];
                    
                    $_SESSION['razorpay_checkout'] = json_encode($data); 
                    $target_page = 'register-case'; // Stay on the current page to open RZP modal

                } catch (Exception $e) {
                    $alert_message = 'Payment Initiation Failed: ' . $e->getMessage();
                    $alert_type = "error";
                    $target_page = 'register-case';
                }
            } else {
                 $alert_message = 'Payment Service Unavailable. Check API keys in config.php.';
                 $alert_type = "error";
                 $target_page = 'register-case';
            }
            
            // Go to PRG redirect to either show the alert or open the RZP modal via JS
            // No success flag needed here, we use the session data flag $_SESSION['razorpay_checkout']
            $_SESSION['alert_message'] = $alert_message;
            $_SESSION['alert_type'] = $alert_type;
            $_SESSION['target_page'] = $target_page; 
            header("Location: client_dashboard.php");
            exit();
        }

        // --- B. Finalize Case Registration After Successful Payment (STEP 3) ---
        elseif ($_POST['action'] === 'finalize_case_registration' && isset($_POST['razorpay_payment_id'])) {
            $payment_id = htmlspecialchars($_POST['razorpay_payment_id']);
            $razorpay_order_id = htmlspecialchars($_POST['razorpay_order_id']);

            // NOTE: In a production environment, you MUST verify the signature here.

            if (isset($_SESSION['pending_case_registration'])) {
                $p = $_SESSION['pending_case_registration'];
                $case_title = $p['case_title'];
                $case_description = $p['case_description'];
                $case_type = $p['case_type']; 
                
                // Save the case to the database
                // The payment_id column must exist in registered_cases for this to work.
                $sql = "INSERT INTO registered_cases (client_id, case_title, case_description, status, payment_id) VALUES (?, ?, ?, 'Pending Review', ?)";
                $stmt = $conn->prepare($sql);
                
                if ($stmt) {
                    $stmt->bind_param("isss", $client_id, $case_title, $case_description, $payment_id);
                    if ($stmt->execute()) {
                        $alert_message = "Your case has been successfully registered and payment confirmed (ID: {$payment_id}). Lawyers are now reviewing your case.";
                        $success = true;
                    } else {
                        $alert_message = "Error: Case registration failed in database after successful payment. Contact support. (Payment ID: {$payment_id})";
                        $alert_type = "error";
                    }
                    $stmt->close();
                }
                
                // Clear pending session data regardless of DB success
                unset($_SESSION['pending_case_registration']);
                unset($_SESSION['razorpay_order_id']);
            } else {
                $alert_message = "Error: Payment confirmed, but case details are missing. Contact support.";
                $alert_type = "error";
            }
            $target_page = 'my-cases';
        }
        
        // --- C. Book Consultation (Simplified - No Payment Required Here) ---
        elseif ($_POST['action'] === 'book_consultation_final') {
            $lawyer_name = htmlspecialchars($_POST['lawyer_name_booked']);
            $case_title = htmlspecialchars($_POST['book_case_title']);
            $consultation_date = $_POST['consultation_date'];
            $consultation_time = $_POST['consultation_time'];
            
            // NOTE: Removed payment_id reference from the query.
            $sql = "INSERT INTO consultations (client_id, lawyer_name, case_title, consultation_date, consultation_time, status) VALUES (?, ?, ?, ?, ?, 'Pending')";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("issss", $client_id, $lawyer_name, $case_title, $consultation_date, $consultation_time);
                if ($stmt->execute()) {
                    $alert_message = "Consultation booked successfully with {$lawyer_name}. Status: Pending (No fee required for booking.)";
                    $success = true;
                } else {
                    $alert_message = "Error booking consultation.";
                    $alert_type = "error";
                }
                $stmt->close();
            }
            $target_page = 'my-cases';
        }

        // --- POST-REDIRECT-GET (PRG) PATTERN for non-RZP initiation actions ---
        if (in_array($_POST['action'], ['finalize_case_registration', 'book_consultation_final']) && ($success || $alert_type === 'error')) {
            // Store alert in session variables before redirecting
            $_SESSION['alert_message'] = $alert_message;
            $_SESSION['alert_type'] = $alert_type;
            $_SESSION['target_page'] = $target_page; 
            
            // Redirect to the same page using GET request
            header("Location: client_dashboard.php");
            exit();
        }
    }
}

// --- Display Alert on GET Request (After successful POST or error) ---
if (isset($_SESSION['alert_message'])) {
    $alert_message = $_SESSION['alert_message'];
    $alert_type = $_SESSION['alert_type'];
    $initial_page = isset($_SESSION['target_page']) ? $_SESSION['target_page'] : 'register-case';

    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_type']);
    unset($_SESSION['target_page']);
} else {
    $initial_page = 'register-case';
}


// --- 6. Fetch Client Data for 'My Cases' Tab ---
$my_cases = [];
$my_consultations = [];


// FIX: Fetch registered cases (includes payment_id)
$sql_all_reg = "
    SELECT 
        rc.id, rc.case_title, rc.case_description, rc.status, rc.created_at, rc.payment_id,
        u.name AS lawyer_name, u.email AS lawyer_email, u.phone_number AS lawyer_phone
    FROM registered_cases rc
    LEFT JOIN users u ON rc.assigned_lawyer_name = u.name AND u.role = 'lawyer'
    WHERE rc.client_id = ? 
    ORDER BY rc.created_at DESC
";
$stmt_all_reg = $conn->prepare($sql_all_reg);
if ($stmt_all_reg) {
    $stmt_all_reg->bind_param("i", $client_id);
    $stmt_all_reg->execute();
    $result_all_reg = $stmt_all_reg->get_result();
    while ($row = $result_all_reg->fetch_assoc()) {
        $my_cases[] = $row;
    }
    $stmt_all_reg->close();
}


// Fetch booked consultations
$sql_con = "
    SELECT 
        c.id, c.lawyer_name, c.case_title, c.consultation_date, c.consultation_time, c.status, 
        u.email AS lawyer_email, u.phone_number AS lawyer_phone
    FROM consultations c
    LEFT JOIN users u ON c.lawyer_name = u.name AND u.role = 'lawyer'
    WHERE c.client_id = ? 
    ORDER BY c.consultation_date DESC, c.consultation_time DESC
";
$stmt_con = $conn->prepare($sql_con);
if ($stmt_con) {
    $stmt_con->bind_param("i", $client_id);
    $stmt_con->execute();
    $result_con = $stmt_con->get_result();
    while ($row = $result_con->fetch_assoc()) {
        $my_consultations[] = $row;
    }
    $stmt_con->close();
}


$conn->close();

// --- 7. Helper Functions for UI (No Change) ---
function generateTimeSlots() {
    $slots = '';
    // Generate 30-minute slots from 9:00 AM (09:00) to 9:00 PM (21:00)
    for ($i = 9; $i <= 21; $i++) {
        $time_hour = str_pad($i, 2, '0', STR_PAD_LEFT);
        $display_hour = ($i > 12 ? $i - 12 : $i);
        $ampm = ($i >= 12 ? 'PM' : 'AM');
        
        // Full hour slot
        $slots .= "<option value=\"$time_hour:00:00\">$display_hour:00 $ampm</option>";
        
        // Half hour slot (only until 8:30 PM)
        if ($i < 21) {
            $slots .= "<option value=\"$time_hour:30:00\">$display_hour:30 $ampm</option>";
        }
    }
    return $slots;
}

function renderStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating ? '★' : '☆';
    }
    return $stars;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Vakil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        /* UPGRADED NAVIGATION STYLES */
        .nav-link {
            transition: all 0.2s ease-in-out;
            position: relative;
        }
        .nav-link:hover {
            color: #1d4ed8;
            background-color: #eef2ff; /* Blue-50 hover background */
            border-radius: 0.5rem;
        }
        .nav-link.active {
            color: #1d4ed8;
            border-bottom: 2px solid #1d4ed8;
            font-weight: 600;
        }
        .page-content {
            min-height: 70vh;
        }
        /* Alert Styles */
        .alert-success { background-color: #d1fae5; color: #065f46; border: 1px solid #10b981; } /* Tailwind green-100/700 */
        .alert-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #f87171; }   /* Tailwind red-100/700 */
        
        .profile-dropdown {
            top: 100%;
            right: 0;
            z-index: 50;
            min-width: 150px;
            transform: translateY(0.5rem); 
            transition: opacity 0.2s, transform 0.2s;
        }
        .rating-stars {
            color: #fbbf24;
        }
        .modal {
            background-color: rgba(0, 0, 0, 0.75);
            z-index: 60;
        }
        .map-frame {
            width: 100%;
            height: 360px;
            border: 0;
        }
        /* INTERACTIVE CARD STYLING */
        .case-card {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .case-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
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
                    <div class="ml-10 flex items-baseline space-x-2">
                        <button type="button" id="tab-register-case" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors" onclick="showDashboardPage('register-case', this)">
                            Register Case
                        </button>
                        <button type="button" id="tab-book-consultation" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors" onclick="showDashboardPage('book-consultation', this)">
                            Book Consultation
                        </button>
                        <button type="button" id="tab-my-cases" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors" onclick="showDashboardPage('my-cases', this)">
                            My Cases
                        </button>
                    </div>
                </nav>
                <div class="relative hidden md:block">
                    <button id="profile-menu-button" class="flex items-center text-gray-700 hover:text-gray-900 focus:outline-none p-2 rounded-full hover:bg-gray-100 transition-colors" aria-expanded="false">
                        <span class="mr-2 font-medium text-sm">Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <svg class="h-6 w-6 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M16 10a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>

                    <div id="profile-menu" class="profile-dropdown absolute mt-2 hidden bg-white rounded-lg shadow-xl py-1 border border-gray-100">
                        <button type="button" onclick="showDashboardPage('my-profile', this)" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</button>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 border-t border-gray-100">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12 page-content">
        <?php 
        // Global Alert Message (Displayed once due to PRG)
        if (!empty($alert_message)) {
            echo "<div id='global-alert' class='p-3 mb-4 rounded-lg text-center font-semibold alert-" . htmlspecialchars($alert_type) . "'>
                    " . htmlspecialchars($alert_message) . "
                  </div>";
        }
        ?>

        <div id="page-register-case">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Register Your Case for Lawyer Matching</h1>
            <div class="max-w-2xl mx-auto bg-white p-8 rounded-xl case-card shadow-2xl">
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Case Details</h3>
                <form action="client_dashboard.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="initiate_case_payment">
                    <div>
                        <label for="reg_case_title" class="block text-sm font-medium text-gray-700">Case Title (Summary)</label>
                        <input type="text" id="reg_case_title" name="reg_case_title" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="reg_case_description" class="block text-sm font-medium text-gray-700">Detailed Description</label>
                        <textarea id="reg_case_description" name="reg_case_description" rows="4" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    <div>
                        <label for="reg_case_type" class="block text-sm font-medium text-gray-700">Case Type / Specialty Needed</label>
                        <select id="reg_case_type" name="reg_case_type" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Specialty</option>
                            <option value="Criminal">Criminal Law</option>
                            <option value="Family">Family Law</option>
                            <option value="Commercial">Commercial Law</option>
                            <option value="IPR">IPR / Patent Law</option>
                            <option value="Real Estate">Real Estate</option>
                        </select>
                    </div>
                    
                    <p class="text-md font-semibold text-gray-700 pt-2">Registration Fee: ₹500.00 (Test Mode)</p>
                    <p class="text-xs text-gray-500">A one-time test fee is required to register your case for lawyer review and matching.</p>
                    
                    <button type="submit" class="w-full bg-green-600 text-white px-5 py-3 rounded-lg text-lg font-semibold hover:bg-green-700 transition-colors shadow-lg">Pay ₹500.00 & Register Case</button>
                </form>
            </div>
        </div>

        <div id="page-book-consultation" class="hidden">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">Book Consultation with Top Lawyers</h1>
            <div id="lawyer-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($lawyers as $lawyer): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 p-6 case-card hover:shadow-xl transition duration-300">
                    <div class="flex items-start mb-4">
                        <img 
                            src="https://placehold.co/60x60/3b82f6/ffffff?text=<?php echo substr($lawyer['name'], 0, 1) . substr($lawyer['specialty'], 0, 1); ?>" 
                            alt="Profile of <?php echo htmlspecialchars($lawyer['name']); ?>" 
                            class="w-16 h-16 rounded-full object-cover shadow-md flex-shrink-0"
                        >
                        <div class="ml-4">
                            <h3 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($lawyer['name']); ?></h3>
                            <p class="text-sm text-blue-600 font-medium"><?php echo htmlspecialchars($lawyer['specialty']); ?></p>
                            <button 
                                type="button"
                                onclick="openMapModal('<?php echo htmlspecialchars($lawyer['location']); ?>', '<?php echo htmlspecialchars($lawyer['name']); ?>')"
                                class="inline-flex items-center text-xs text-gray-700 bg-green-50 px-2 py-1 rounded-full mt-1 border border-green-200 hover:bg-green-100 transition-colors"
                                aria-label="View map for <?php echo htmlspecialchars($lawyer['location']); ?>"
                            >
                                📍 <?php echo htmlspecialchars($lawyer['location']); ?>
                            </button>
                            <p class="text-[11px] text-gray-500 mt-1">Areas: <?php echo htmlspecialchars($lawyer['areas']); ?></p>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between mt-4 border-t pt-4">
                        <div>
                            <span class="rating-stars text-lg"><?php echo renderStars($lawyer['rating']); ?></span>
                            <span class="font-semibold text-gray-700 text-sm ml-2"><?php echo number_format($lawyer['rating'], 1); ?>/5.0</span>
                        </div>
                        <button 
                            type="button"
                            onclick="openBookingModal('<?php echo htmlspecialchars($lawyer['name']); ?>')"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors shadow-md"
                        >
                            Book 30 Min
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="page-my-cases" class="hidden">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">My Cases & Consultations</h1>
            <div class="space-y-8">
                
                <h2 class="text-2xl font-bold text-blue-800 border-b pb-2">Booked Consultations</h2>
                <div class="space-y-4">
                    <?php if (empty($my_consultations)): ?>
                        <p class="text-gray-500 italic">No consultations booked yet.</p>
                    <?php else: ?>
                        <?php foreach ($my_consultations as $con): ?>
                            <div class="bg-white p-6 rounded-xl case-card border-l-4 
                                <?php echo $con['status'] === 'Confirmed' ? 'border-green-500' : 'border-yellow-500'; ?>
                                hover:shadow-lg transition duration-300">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($con['case_title']); ?></h3>
                                        <p class="text-sm text-gray-600 mt-1">Lawyer: <span class="font-semibold text-blue-700"><?php echo htmlspecialchars($con['lawyer_name']); ?></span></p>
                                    </div>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                        <?php echo $con['status'] === 'Confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>
                                        ">
                                        <?php echo htmlspecialchars($con['status']); ?>
                                    </span>
                                </div>
                                <div class="mt-4 pt-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between text-sm">
                                    <div>
                                        <p class="font-medium text-gray-700">Date: <span class="font-semibold text-gray-900"><?php echo date('F j, Y', strtotime($con['consultation_date'])); ?></span></p>
                                        <p class="font-medium text-gray-700">Time: <span class="font-semibold text-gray-900"><?php echo date('g:i A', strtotime($con['consultation_time'])); ?> IST</span></p>
                                    </div>
                                    <?php if ($con['status'] === 'Confirmed'): ?>
                                        <div class="mt-4 sm:mt-0 p-2 border border-green-200 rounded-lg bg-green-50">
                                            <p class="font-semibold text-green-700">Lawyer Contact:</p>
                                            <p class="text-xs text-gray-800">Email: <?php echo htmlspecialchars($con['lawyer_email'] ?? 'N/A'); ?></p>
                                            <?php if (!empty($con['lawyer_phone'])): ?>
                                                <p class="text-xs text-gray-800">Phone: <?php echo htmlspecialchars($con['lawyer_phone']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h2 class="text-2xl font-bold text-green-800 border-b pb-2 pt-6">Cases Pending Review</h2>
                <div class="space-y-4">
                    <?php if (empty($my_cases)): ?>
                        <p class="text-gray-500 italic">No cases registered for matching yet.</p>
                    <?php else: ?>
                        <?php foreach ($my_cases as $reg_case): ?>
                            <div class="bg-white p-6 rounded-xl case-card border-l-4 
                                <?php echo $reg_case['status'] === 'Lawyer Assigned' ? 'border-blue-500' : 'border-red-500'; ?>
                                hover:shadow-lg transition duration-300">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($reg_case['case_title']); ?></h3>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($reg_case['case_description']); ?></p>
                                        <p class="text-xs text-gray-500 mt-1">Payment ID: <span class="font-mono"><?php echo htmlspecialchars($reg_case['payment_id'] ?? 'N/A'); ?></span></p>
                                    </div>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                        <?php echo $reg_case['status'] === 'Lawyer Assigned' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'; ?>
                                        ">
                                        <?php echo htmlspecialchars($reg_case['status']); ?>
                                    </span>
                                </div>
                                <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between text-sm">
                                    <p class="font-medium text-gray-700">Status: <span class="font-semibold text-green-700"><?php echo htmlspecialchars($reg_case['status']); ?></span></p>
                                    <p class="font-medium text-gray-700">Registered On: <span class="font-semibold text-gray-900"><?php echo date('M j, Y', strtotime($reg_case['created_at'])); ?></span></p>
                                </div>
                                <?php if ($reg_case['status'] === 'Lawyer Assigned' && !empty($reg_case['lawyer_name'])): ?>
                                     <div class="mt-4 sm:mt-0 p-2 border border-blue-200 rounded-lg bg-blue-50">
                                        <p class="font-semibold text-blue-700">Assigned Lawyer: <?php echo htmlspecialchars($reg_case['lawyer_name']); ?></p>
                                        <p class="text-xs text-gray-800">Email: <?php echo htmlspecialchars($reg_case['lawyer_email'] ?? 'N/A'); ?></p>
                                        <?php if (!empty($reg_case['lawyer_phone'])): ?>
                                            <p class="text-xs text-gray-800">Phone: <?php echo htmlspecialchars($reg_case['lawyer_phone']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="page-my-profile" class="hidden">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">My Profile</h1>
            <div class="max-w-xl mx-auto bg-white p-8 rounded-xl shadow-2xl space-y-6">
                <div class="text-center">
                    <img 
                        src="https://placehold.co/100x100/3b82f6/ffffff?text=<?php echo substr($_SESSION['user_name'], 0, 1); ?>" 
                        alt="Profile Icon" 
                        class="w-24 h-24 rounded-full mx-auto mb-4 border-4 border-blue-500"
                    >
                    <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h2>
                    <p class="text-md text-gray-600"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                </div>

                <div class="space-y-4 border-t pt-4">
                    <p class="font-semibold text-gray-700">Account Details</p>
                    <div class="grid grid-cols-2 text-sm">
                        <span class="text-gray-500">Role:</span>
                        <span class="font-medium text-gray-800 capitalize"><?php echo htmlspecialchars($_SESSION['user_role']); ?></span>
                    </div>
                    <div class="grid grid-cols-2 text-sm">
                        <span class="text-gray-500">Client ID:</span>
                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($_SESSION['user_id']); ?></span>
                    </div>
                    <div class="grid grid-cols-2 text-sm">
                        <span class="text-gray-500">Location:</span>
                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($_SESSION['user_location'] ?? 'Add your location'); ?></span>
                    </div>
                    <button class="w-full bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-300 mt-4" onclick="alert('Profile Editing feature coming soon!')">Edit Profile</button>
                </div>
            </div>
        </div>
    </main>

    <div id="booking-modal" class="modal fixed inset-0 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 relative">
            <button onclick="closeBookingModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Book Consultation with:</h2>
            <p id="modal-lawyer-name" class="text-xl font-medium text-blue-600 mb-6"></p>
            
            <form action="client_dashboard.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="book_consultation_final">
                <input type="hidden" name="lawyer_name_booked" id="lawyer-name-input">

                <div>
                    <label for="book_case_title" class="block text-sm font-medium text-gray-700">Case Title (Summary)</label>
                    <input type="text" id="book_case_title" name="book_case_title" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="consultation_date_modal" class="block text-sm font-medium text-gray-700">Date</label>
                        <input type="date" id="consultation_date_modal" name="consultation_date" required min="<?php echo date('Y-m-d'); ?>" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="consultation_time_modal" class="block text-sm font-medium text-gray-700">Time (30 min slot)</label>
                        <select id="consultation_time_modal" name="consultation_time" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Time Slot</option>
                            <?php echo generateTimeSlots(); ?>
                        </select>
                    </div>
                </div>
                
                <p class="text-xs text-gray-500 pt-2">By confirming, you submit a request for a 30-minute introductory consultation. Status will be 'Pending' until confirmed by the lawyer.</p>

                <button type="submit" class="w-full bg-green-600 text-white px-6 py-3 rounded-lg text-lg font-semibold hover:bg-green-700 transition-colors shadow-lg">Request Consultation</button>
            </form>
        </div>
    </div>

    <div id="map-modal" class="modal fixed inset-0 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl p-4 sm:p-6 relative">
            <button onclick="closeMapModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Lawyer Location</h2>
            <p id="map-modal-location" class="text-sm text-gray-600 mb-4"></p>
            <div class="w-full overflow-hidden rounded-lg border border-gray-200 shadow-inner bg-gray-50">
                <iframe id="map-modal-frame" class="map-frame" title="Lawyer Location" src="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            <div class="mt-3 flex items-center justify-between text-sm text-gray-600">
                <span>Tip: Drag or zoom the map to explore nearby landmarks.</span>
                <a id="map-modal-open-link" href="#" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 font-semibold">Open in Google Maps</a>
            </div>
        </div>
    </div>
    <form id="razorpay-final-form" action="client_dashboard.php" method="POST" style="display: none;">
        <input type="hidden" name="action" value="finalize_case_registration">
        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
    </form>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        // Set the initial active page based on PHP's $initial_page variable
        let currentPage = '<?php echo $initial_page; ?>'; 

        const navButtons = document.querySelectorAll('.nav-link');
        const dashboardPages = document.querySelectorAll('[id^="page-"]');
        const profileMenuButton = document.getElementById('profile-menu-button');
        const profileMenu = document.getElementById('profile-menu');
        const globalAlert = document.getElementById('global-alert'); 
        const mapModal = document.getElementById('map-modal');
        const mapFrame = document.getElementById('map-modal-frame');
        const mapLocation = document.getElementById('map-modal-location');
        const mapOpenLink = document.getElementById('map-modal-open-link');
        
        function openBookingModal(lawyerName) {
            document.getElementById('modal-lawyer-name').textContent = lawyerName;
            document.getElementById('lawyer-name-input').value = lawyerName;
            document.getElementById('booking-modal').classList.remove('hidden');
        }

        function closeBookingModal() {
            document.getElementById('booking-modal').classList.add('hidden');
        }

        function showDashboardPage(pageId, button = null) {
            // Hide alert message on tab change
            if (globalAlert) {
                globalAlert.style.display = 'none';
            }

            // Hide all content pages
            dashboardPages.forEach(page => {
                page.classList.add('hidden');
            });

            // Show the target page
            const targetPage = document.getElementById(`page-${pageId}`);
            if (targetPage) {
                targetPage.classList.remove('hidden');
                currentPage = pageId;
            }

            // Update active navigation link
            navButtons.forEach(btn => {
                btn.classList.remove('active');
            });

            // Set active class on main tabs, or keep profile menu button active if viewing profile
            if (pageId.startsWith('my-profile')) {
                // No active styling for the profile button itself
            } else if (button) {
                button.classList.add('active');
            } else {
                const matchingNavButton = document.getElementById(`tab-${pageId}`);
                if (matchingNavButton) {
                     matchingNavButton.classList.add('active');
                }
            }
            
            // Close the profile dropdown after selection
            profileMenu.classList.add('hidden');
            profileMenuButton.setAttribute('aria-expanded', 'false');
        }

        function openMapModal(locationQuery, lawyerName) {
            if (!mapModal || !mapFrame || !mapOpenLink) return;
            const label = lawyerName ? `${lawyerName} — ${locationQuery}` : locationQuery;
            const encodedQuery = encodeURIComponent(locationQuery || '');
            mapFrame.src = `https://www.google.com/maps?q=${encodedQuery}&output=embed`;
            mapOpenLink.href = `https://www.google.com/maps/search/?api=1&query=${encodedQuery}`;
            if (mapLocation) {
                mapLocation.textContent = label;
            }
            mapModal.classList.remove('hidden');
        }

        function closeMapModal() {
            if (!mapModal || !mapFrame) return;
            mapFrame.src = '';
            mapModal.classList.add('hidden');
        }
        
        // --- RAZORPAY HANDLER FUNCTIONS (NEW) ---
        
        // Function to handle the successful payment response from Razorpay
        function handleRazorpayResponse(response) {
            document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
            document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
            document.getElementById('razorpay_signature').value = response.razorpay_signature;
            
            // Submit the hidden form to finalize the case registration in the PHP script
            document.getElementById('razorpay-final-form').submit();
        }
        
        // Function to initialize Razorpay when data is available
        function openRazorpayCheckout(options) {
            // Attach the response handler
            options.handler = handleRazorpayResponse;
            
            options.modal = {
                ondismiss: function() {
                    console.log("Razorpay modal dismissed by user. Reloading to clear state.");
                    // Reloading keeps the user on the same page and clears the session state
                    window.location.reload(); 
                },
                escape: true,
                backdropclose: false
            };

            var rzp = new Razorpay(options);
            rzp.open();
        }
        // --- END RAZORPAY HANDLER FUNCTIONS ---


        // Initialize: Set the correct tab on load based on PHP's $initial_page variable
        window.onload = function() {
             showDashboardPage('<?php echo $initial_page; ?>'); 
             
             // Check if Razorpay Checkout data is available in the session (triggered after POST)
             <?php if (isset($_SESSION['razorpay_checkout'])): ?>
                var options = <?php echo $_SESSION['razorpay_checkout']; ?>;
                openRazorpayCheckout(options);
                // Clear the session variable after use (handled by the redirect, but kept clean)
                <?php unset($_SESSION['razorpay_checkout']); ?>
             <?php endif; ?>
        };

        // Profile Dropdown Toggle
        profileMenuButton.addEventListener('click', function() {
            profileMenu.classList.toggle('hidden');
            this.setAttribute('aria-expanded', profileMenu.classList.contains('hidden') ? 'false' : 'true');
        });

        // Close dropdown if user clicks outside
        document.addEventListener('click', function(event) {
            const isClickOutsideProfile = !profileMenuButton.contains(event.target) && !profileMenu.contains(event.target);
            const isClickOutsideModal = !document.getElementById('booking-modal').contains(event.target);

            if (isClickOutsideProfile) {
                profileMenu.classList.add('hidden');
                profileMenuButton.setAttribute('aria-expanded', 'false');
            }
            
            // Close modal if clicking outside of the modal content area
             if (event.target === document.getElementById('booking-modal')) {
                closeBookingModal();
            }

            // Close map modal if clicking on the overlay
            if (mapModal && event.target === mapModal) {
                closeMapModal();
            }
        });

        // Close modals on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeBookingModal();
                closeMapModal();
            }
        });
    </script>
</body>
</html>