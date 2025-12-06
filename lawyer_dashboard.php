<?php
// lawyer_dashboard.php

// --- 1. Session and Authentication Check (MUST BE AT THE TOP) ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// REDIRECT LOGIC: Redirect if not logged in or not a lawyer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'lawyer') {
    header("Location: index.php");
    exit();
}

// --- 2. Database Connection ---
require('config.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- 3. Initialization ---
$lawyer_id = $_SESSION['user_id'];
$lawyer_name = $_SESSION['user_name'];
$alert_message = '';
$alert_type = 'success';
$initial_page = 'available-cases'; // Default view for lawyers

// --- 4. Handle POST Actions (Accepting Cases/Consultations) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $success = false;
    $target_page = ''; 

    // --- A. Accept Registered Case (Case Matching) ---
    if ($_POST['action'] === 'accept_case' && isset($_POST['case_id'])) {
        $case_id = (int)$_POST['case_id'];
        
        // FIX: Update case status to 'Lawyer Assigned' AND save the lawyer's name
        $sql = "UPDATE registered_cases SET status = 'Lawyer Assigned', assigned_lawyer_name = ? WHERE id = ? AND status = 'Pending Review'";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // Bind the lawyer's name and the case ID
            $stmt->bind_param("si", $lawyer_name, $case_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $alert_message = "Case ID #$case_id successfully accepted and assigned to you!";
                $success = true;
            } else {
                $alert_message = "Error: Case already accepted or not found.";
                $alert_type = "error";
            }
            $stmt->close();
        }
        $target_page = 'available-cases'; 
    } 
    
    // --- B. Accept Consultation Request ---
    elseif ($_POST['action'] === 'accept_consultation' && isset($_POST['consultation_id'])) {
        $con_id = (int)$_POST['consultation_id'];
        
        // Update consultation status to 'Confirmed'
        $sql = "UPDATE consultations SET status = 'Confirmed' WHERE id = ? AND lawyer_name = ? AND status = 'Pending'";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("is", $con_id, $lawyer_name);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $alert_message = "Consultation ID #$con_id confirmed! Client contact is now available under 'My Clients'.";
                $success = true;
            } else {
                $alert_message = "Error: Consultation already confirmed or not found.";
                $alert_type = "error";
            }
            $stmt->close();
        }
        $target_page = 'consultation-requests';
    }

    // --- POST-REDIRECT-GET (PRG) PATTERN ---
    if ($success || $alert_type === 'error') {
        $_SESSION['alert_message'] = $alert_message;
        $_SESSION['alert_type'] = $alert_type;
        $_SESSION['target_page'] = $target_page; 
        
        header("Location: lawyer_dashboard.php");
        exit();
    }
}

// --- 5. Display Alert on GET Request (PRG Retrieval) ---
if (isset($_SESSION['alert_message'])) {
    $alert_message = $_SESSION['alert_message'];
    $alert_type = $_SESSION['alert_type'];
    $initial_page = isset($_SESSION['target_page']) ? $_SESSION['target_page'] : 'available-cases';

    // Clear session variables after retrieving
    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_type']);
    unset($_SESSION['target_page']);
}


// --- 6. Fetch Data for Dashboard Tabs ---

// Fetch Available Cases (Status: 'Pending Review')
$available_cases = [];
$sql_avail = "SELECT id, client_id, case_title, case_description FROM registered_cases WHERE status = 'Pending Review' ORDER BY created_at DESC";
$stmt_avail = $conn->prepare($sql_avail);
if ($stmt_avail) {
    $stmt_avail->execute();
    $result_avail = $stmt_avail->get_result();
    while ($row = $result_avail->fetch_assoc()) {
        $available_cases[] = $row;
    }
    $stmt_avail->close();
}

// Fetch Consultation Requests (Status: 'Pending' AND addressed to this lawyer)
$consultation_requests = [];
// Join with users table to get client name for better display (optional but helpful)
$sql_req = "
    SELECT c.id, c.client_id, c.case_title, c.consultation_date, c.consultation_time, c.status, u.name as client_name 
    FROM consultations c
    JOIN users u ON c.client_id = u.id
    WHERE c.lawyer_name = ? AND c.status = 'Pending' 
    ORDER BY consultation_date ASC, consultation_time ASC
";
$stmt_req = $conn->prepare($sql_req);
if ($stmt_req) {
    $stmt_req->bind_param("s", $lawyer_name);
    $stmt_req->execute();
    $result_req = $stmt_req->get_result();
    while ($row = $result_req->fetch_assoc()) {
        $consultation_requests[] = $row;
    }
    $stmt_req->close();
}

// === Fetch Accepted Cases and Confirmed Consultations for "My Clients" ===
$accepted_cases = [];
$confirmed_consultations = [];

// Fetch Accepted Cases (Status: 'Lawyer Assigned') and JOIN with 'users' for client contact
$sql_acc = "
    SELECT rc.id, rc.client_id, rc.case_title, rc.status, rc.created_at, u.name as client_name, u.email, u.phone_number 
    FROM registered_cases rc
    JOIN users u ON rc.client_id = u.id
    WHERE rc.assigned_lawyer_name = ? AND rc.status = 'Lawyer Assigned' 
    ORDER BY rc.created_at DESC
";
$stmt_acc = $conn->prepare($sql_acc);
if ($stmt_acc) {
    $stmt_acc->bind_param("s", $lawyer_name);
    $stmt_acc->execute();
    $result_acc = $stmt_acc->get_result();
    while ($row = $result_acc->fetch_assoc()) {
        $accepted_cases[] = $row;
    }
    $stmt_acc->close();
}

// Fetch Confirmed Consultations and JOIN with 'users' for client contact
$sql_conf = "
    SELECT c.id, c.client_id, c.case_title, c.consultation_date, c.consultation_time, c.status, u.name as client_name, u.email, u.phone_number 
    FROM consultations c
    JOIN users u ON c.client_id = u.id
    WHERE c.lawyer_name = ? AND c.status = 'Confirmed' 
    ORDER BY c.consultation_date DESC
";
$stmt_conf = $conn->prepare($sql_conf);
if ($stmt_conf) {
    $stmt_conf->bind_param("s", $lawyer_name);
    $stmt_conf->execute();
    $result_conf = $stmt_conf->get_result();
    while ($row = $result_conf->fetch_assoc()) {
        $confirmed_consultations[] = $row;
    }
    $stmt_conf->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lawyer Dashboard - Vakil</title>
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
        .alert-success { background-color: #d1fae5; color: #065f46; border: 1px solid #10b981; } /* Tailwind green-100/700 */
        .alert-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #f87171; }   /* Tailwind red-100/700 */
        .profile-dropdown {
            top: 100%;
            right: 0;
            z-index: 50;
            min-width: 150px;
            transform: translateY(0.5rem); /* Dropdown spacing */
            transition: opacity 0.2s, transform 0.2s;
        }
        /* UPGRADED STATUS TAGS */
        .status-tag-pending { background-color: #fef3c7; color: #b45309; } /* Amber */
        .status-tag-confirmed { background-color: #d1fae5; color: #047857; } /* Green */
        .status-tag-assigned { background-color: #bfdbfe; color: #1d4ed8; } /* Blue */
        .status-tag {
            padding: 0.3rem 0.9rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
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
        /* NEW BADGE STYLING */
        .case-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 1.25rem;
            width: 1.25rem;
            min-width: 1.25rem; /* Ensure minimum width for single digits */
            background-color: #ef4444; /* Red-500 */
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 9999px; /* Full circle */
            margin-left: 0.5rem;
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
                        <button type="button" id="tab-available-cases" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors flex items-center" onclick="showDashboardPage('available-cases', this)">
                            Available Cases
                            <?php if (count($available_cases) > 0): ?>
                            <span class="case-count-badge"><?php echo count($available_cases); ?></span>
                            <?php endif; ?>
                        </button>
                        <button type="button" id="tab-consultation-requests" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors flex items-center" onclick="showDashboardPage('consultation-requests', this)">
                            Consultation Requests
                            <?php if (count($consultation_requests) > 0): ?>
                            <span class="case-count-badge bg-yellow-500"><?php echo count($consultation_requests); ?></span>
                            <?php endif; ?>
                        </button>
                        <button type="button" id="tab-my-clients" class="nav-link text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium transition-colors" onclick="showDashboardPage('my-clients', this)">
                            My Clients
                        </button>
                    </div>
                </nav>
                <div class="relative hidden md:block">
                    <button id="profile-menu-button" class="flex items-center text-gray-700 hover:text-gray-900 focus:outline-none p-2 rounded-full hover:bg-gray-100 transition-colors" aria-expanded="false">
                        <span class="mr-2 font-medium text-sm">Lawyer, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
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
        if (!empty($alert_message)) {
            echo "<div id='global-alert' class='p-3 mb-4 rounded-lg text-center font-semibold alert-" . htmlspecialchars($alert_type) . "'>
                    " . htmlspecialchars($alert_message) . "
                  </div>";
        }
        ?>

        <div id="page-available-cases">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">Available Cases for Matching (<?php echo count($available_cases); ?>)</h1>
            <div class="space-y-6">
                <?php if (empty($available_cases)): ?>
                    <div class="bg-white p-8 rounded-xl shadow-md text-center text-gray-500">
                        <p class="text-xl font-medium">No new cases are currently available for matching.</p>
                        <p class="text-sm mt-2">Check back later or refresh the page.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($available_cases as $case): ?>
                        <div class="bg-white p-6 rounded-xl case-card border-l-4 border-green-500 hover:shadow-xl transition duration-300">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Case: <?php echo htmlspecialchars($case['case_title']); ?></h3>
                            <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars($case['case_description']); ?></p>
                            <div class="mt-4 pt-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center">
                                <p class="text-sm font-medium text-gray-700 mb-2 sm:mb-0">Client ID: <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($case['client_id']); ?></span></p>
                                <form action="lawyer_dashboard.php" method="POST">
                                    <input type="hidden" name="action" value="accept_case">
                                    <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-green-700 transition-colors shadow-md">
                                        Accept Case & Get Details
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div id="page-consultation-requests" class="hidden">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">Pending Consultation Requests (<?php echo count($consultation_requests); ?>)</h1>
            <div class="space-y-6">
                 <?php if (empty($consultation_requests)): ?>
                    <div class="bg-white p-8 rounded-xl shadow-md text-center text-gray-500">
                        <p class="text-xl font-medium">No new consultation requests pending.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($consultation_requests as $request): ?>
                        <div class="bg-white p-6 rounded-xl case-card border-l-4 border-yellow-500 hover:shadow-xl transition duration-300">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Consultation for: <?php echo htmlspecialchars($request['case_title']); ?></h3>
                            <p class="text-sm text-gray-600 mb-4">Client: <?php echo htmlspecialchars($request['client_name'] ?? 'N/A'); ?> (ID: <?php echo htmlspecialchars($request['client_id']); ?>)</p>
                            <div class="mt-4 pt-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center">
                                <p class="text-sm font-semibold text-gray-700 mb-2 sm:mb-0">
                                    Requested Time: 
                                    <span class="text-gray-900 font-bold"><?php echo date('F j, Y', strtotime($request['consultation_date'])); ?> @ <?php echo date('g:i A', strtotime($request['consultation_time'])); ?></span>
                                </p>
                                <form action="lawyer_dashboard.php" method="POST">
                                    <input type="hidden" name="action" value="accept_consultation">
                                    <input type="hidden" name="consultation_id" value="<?php echo $request['id']; ?>">
                                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors shadow-md">
                                        Confirm Consultation
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div id="page-my-clients" class="hidden">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">My Active Clients & Engagements</h1>
            <div class="space-y-8">
                
                <h2 class="text-2xl font-bold text-blue-800 border-b pb-2">Confirmed Consultations (<?php echo count($confirmed_consultations); ?>)</h2>
                <div class="space-y-4">
                    <?php if (empty($confirmed_consultations)): ?>
                        <p class="text-gray-500 italic">No consultations have been confirmed yet.</p>
                    <?php else: ?>
                        <?php foreach ($confirmed_consultations as $con): ?>
                            <div class="bg-white p-4 rounded-xl case-card border-l-4 border-green-500 hover:shadow-xl transition duration-300">
                                <div class="flex justify-between items-center">
                                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($con['case_title']); ?></p>
                                    <span class="status-tag status-tag-confirmed">Confirmed</span>
                                </div>
                                <p class="text-sm text-gray-600">Client: <?php echo htmlspecialchars($con['client_name'] ?? 'N/A'); ?> (ID: <?php echo htmlspecialchars($con['client_id']); ?>)</p>
                                <p class="text-sm text-gray-600 font-medium mt-1">Client Email: <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($con['email']); ?></span></p>
                                <?php if (!empty($con['phone_number'])): ?>
                                <p class="text-sm text-gray-600 font-medium">Client Phone: <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($con['phone_number']); ?></span></p>
                                <?php endif; ?>
                                <p class="text-sm text-gray-700 font-medium mt-2">
                                    Scheduled: <?php echo date('F j, Y', strtotime($con['consultation_date'])); ?> @ <?php echo date('g:i A', strtotime($con['consultation_time'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h2 class="text-2xl font-bold text-green-800 border-b pb-2 pt-6">Accepted Cases (<?php echo count($accepted_cases); ?>)</h2>
                <div class="space-y-4">
                    <?php if (empty($accepted_cases)): ?>
                        <p class="text-gray-500 italic">No cases have been accepted by you yet.</p>
                    <?php else: ?>
                         <?php foreach ($accepted_cases as $case): ?>
                            <div class="bg-white p-4 rounded-xl case-card border-l-4 border-blue-500 hover:shadow-xl transition duration-300">
                                <div class="flex justify-between items-center">
                                     <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($case['case_title']); ?></p>
                                     <span class="status-tag status-tag-assigned">Assigned</span>
                                </div>
                                <p class="text-sm text-gray-600">Client: <?php echo htmlspecialchars($case['client_name'] ?? 'N/A'); ?> (ID: <?php echo htmlspecialchars($case['client_id']); ?>)</p>
                                <p class="text-sm text-gray-600 font-medium mt-1">Client Email: <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($case['email']); ?></span></p>
                                <?php if (!empty($case['phone_number'])): ?>
                                <p class="text-sm text-gray-600 font-medium">Client Phone: <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($case['phone_number']); ?></span></p>
                                <?php endif; ?>
                                <p class="text-sm text-blue-700 font-medium mt-2">Assigned On: <?php echo date('M j, Y', strtotime($case['created_at'])); ?></p>
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
                        src="https://placehold.co/100x100/10b981/ffffff?text=L" 
                        alt="Lawyer Profile Icon" 
                        class="w-24 h-24 rounded-full mx-auto mb-4 border-4 border-green-500"
                    >
                    <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($lawyer_name); ?></h2>
                    <p class="text-md text-gray-600"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                </div>

                <div class="space-y-4 border-t pt-4">
                    <p class="font-semibold text-gray-700">Account Details</p>
                    <div class="grid grid-cols-2 text-sm">
                        <span class="text-gray-500">Role:</span>
                        <span class="font-medium text-gray-800 capitalize"><?php echo htmlspecialchars($_SESSION['user_role']); ?></span>
                    </div>
                    <div class="grid grid-cols-2 text-sm">
                        <span class="text-gray-500">Lawyer ID:</span>
                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($lawyer_id); ?></span>
                    </div>
                    <button class="w-full bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-300 mt-4" onclick="alert('Profile Editing feature coming soon!')">Edit Profile</button>
                </div>
            </div>
        </div>

    </main>

    <script>
        // Set the initial active page based on PHP's $initial_page variable
        let currentPage = '<?php echo $initial_page; ?>'; 

        const navButtons = document.querySelectorAll('.nav-link');
        const dashboardPages = document.querySelectorAll('[id^="page-"]');
        const profileMenuButton = document.getElementById('profile-menu-button');
        const profileMenu = document.getElementById('profile-menu');
        const globalAlert = document.getElementById('global-alert'); 
        
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

        // Initialize: Set the correct tab on load based on PHP's $initial_page variable
        window.onload = function() {
             showDashboardPage('<?php echo $initial_page; ?>'); 
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
                // closeBookingModal(); // No longer necessary as modal is for booking, not RZP
            }
        });
    </script>
</body>
</html>