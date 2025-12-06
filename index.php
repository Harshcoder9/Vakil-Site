<?php
// index.php

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// =======================================================
// FIX: Redirect Logic
// If user is logged in as a client or lawyer, send them to their dashboard
// UNLESS they are trying to reach the index page to log out.
// =======================================================
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'client') {
        header("Location: client_dashboard.php");
        exit();
    } elseif ($_SESSION['user_role'] === 'lawyer') {
        header("Location: lawyer_dashboard.php");
        exit();
    }
}
// Note: If the user is logged in, they should only be able to reach index.php 
// after successfully running logout.php.

// --- MOCK LAWYER DATA (Used for Top Lawyers display) ---
$client_location = isset($_SESSION['client_location']) ? $_SESSION['client_location'] : '';
$lawyers = [
    ['name' => 'Santosh Mishra', 'specialty' => 'Family Law', 'rating' => 4.8, 'location' => 'Mumbai, Goregaon East', 'areas' => 'Andheri, Bandra, Powai'],
    ['name' => 'Ananya Sharma', 'specialty' => 'Criminal Law', 'rating' => 4.9, 'location' => 'Delhi, Karol Bagh', 'areas' => 'Karol Bagh, Rohini, Dwarka'],
    ['name' => 'Priya Patel', 'specialty' => 'Commercial Law', 'rating' => 4.7, 'location' => 'Bangalore, Indiranagar', 'areas' => 'Koramangala, Whitefield, Jayanagar'],
    ['name' => 'Rohan Joshi', 'specialty' => 'Civil Litigation', 'rating' => 4.6, 'location' => 'Pune, Koregaon Park', 'areas' => 'Viman Nagar, Hinjewadi, Kothrud'],
    ['name' => 'Meera Krishnan', 'specialty' => 'IPR', 'rating' => 4.8, 'location' => 'Chennai, T Nagar', 'areas' => 'Adyar, Velachery, Anna Nagar'],
    ['name' => 'Aditya Verma', 'specialty' => 'Cyber Law', 'rating' => 4.7, 'location' => 'Hyderabad, Banjara Hills', 'areas' => 'Jubilee Hills, Gachibowli, Madhapur'],
];

// --- Helper function for rating display ---
function renderStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        // Use a solid star if rating is greater than or equal to the current iteration
        $stars .= $i <= floor($rating) ? '★' : '☆';
    }
    return $stars;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Vakil - Your Legal Partner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="style.css" />
    <style>
      body {
        font-family: 'Inter', sans-serif;
        scroll-behavior: smooth;
      }
      /* Navigation Styles */
      .nav-link {
        position: relative;
        transition: color 0.3s ease;
      }
      .nav-link.active {
        color: #1d4ed8;
        border-bottom: 2px solid #1d4ed8;
      }
      .nav-link:hover {
        color: #2563eb;
      }
      .rating-stars {
        color: #fbbf24;
      }
      /* Card Animations */
      .lawyer-card {
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        position: relative;
        overflow: hidden;
      }
      .lawyer-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
        transition: left 0.5s ease;
      }
      .lawyer-card:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        transform: translateY(-6px) scale(1.02);
      }
      .lawyer-card:hover::before {
        left: 100%;
      }
      /* Button Animations */
      .btn-pressable {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
      }
      .btn-pressable::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s ease, height 0.6s ease;
      }
      .btn-pressable:hover::before {
        width: 300px;
        height: 300px;
      }
      .btn-pressable:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      }
      .btn-pressable:active {
        transform: translateY(0);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      }
      /* Modal Animations */
      .modal-panel {
        transform: scale(0.95) translateY(20px);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      }
      .modal-open .modal-panel {
        transform: scale(1) translateY(0);
        opacity: 1;
      }
      /* Fade In Up Animation */
      .fade-in-up {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
      }
      .fade-in-up.show {
        opacity: 1;
        transform: translateY(0);
      }
      /* Header Slide Down */
      header {
        animation: slideDown 0.5s ease;
      }
      @keyframes slideDown {
        from {
          transform: translateY(-100%);
          opacity: 0;
        }
        to {
          transform: translateY(0);
          opacity: 1;
        }
      }
      /* Details/Summary Smooth */
      details {
        transition: all 0.3s ease;
      }
      details[open] summary {
        color: #2563eb;
      }
      details summary {
        transition: color 0.2s ease;
      }
      details summary:hover {
        color: #1d4ed8;
      }
      /* Stagger Animation for Cards */
      .lawyer-card {
        animation: fadeInScale 0.5s ease forwards;
        opacity: 0;
      }
      @keyframes fadeInScale {
        to {
          opacity: 1;
          transform: scale(1);
        }
      }
      .lawyer-card:nth-child(1) { animation-delay: 0.1s; }
      .lawyer-card:nth-child(2) { animation-delay: 0.2s; }
      .lawyer-card:nth-child(3) { animation-delay: 0.3s; }
      .lawyer-card:nth-child(4) { animation-delay: 0.4s; }
      .lawyer-card:nth-child(5) { animation-delay: 0.5s; }
      .lawyer-card:nth-child(6) { animation-delay: 0.6s; }
      
      /* Lawyers Banner Carousel */
      .lawyers-banner {
        overflow: hidden;
        position: relative;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      }
      .lawyers-banner::before,
      .lawyers-banner::after {
        content: '';
        position: absolute;
        top: 0;
        width: 100px;
        height: 100%;
        z-index: 10;
        pointer-events: none;
      }
      .lawyers-banner::before {
        left: 0;
        background: linear-gradient(to right, rgba(102, 126, 234, 1), transparent);
      }
      .lawyers-banner::after {
        right: 0;
        background: linear-gradient(to left, rgba(118, 75, 162, 1), transparent);
      }
      .lawyers-track {
        display: flex;
        animation: scroll-left 40s linear infinite;
        width: fit-content;
      }
      .lawyers-banner:hover .lawyers-track {
        animation-play-state: paused;
      }
      @keyframes scroll-left {
        0% {
          transform: translateX(0);
        }
        100% {
          transform: translateX(-50%);
        }
      }
      .banner-lawyer-card {
        flex-shrink: 0;
        width: 320px;
        margin: 0 16px;
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
      }
      .banner-lawyer-card:hover {
        transform: translateY(-8px) scale(1.03);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
      }
    </style>
  </head>
  <body class="bg-gray-100">
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
            <div class="ml-10 flex items-baseline space-x-4">
              <a
                href="#"
                id="nav-home"
                class="nav-link text-gray-600 hover:text-gray-900 px-4 py-2 text-sm font-medium transition-colors active"
              >Home</a>
            </div>
          </nav>
          <div class="hidden md:block ml-8">
            <?php if (!isset($_SESSION['user_id'])): ?>
            <a
              href="login.php"
              class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700"
              >Login / Sign Up</a
            >
            <?php else: ?>
             <a
              href="<?php echo $_SESSION['user_role'] === 'client' ? 'client_dashboard.php' : 'lawyer_dashboard.php'; ?>"
              class="bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700"
              >Go to Dashboard</a
            >
            <?php endif; ?>
          </div>
        </div>
      </div>
    </header>

    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
      <!-- Hero Section -->
      <div class="fade-in-up mb-12">
        <div class="relative bg-gray-800 rounded-xl shadow-xl overflow-hidden">
          <div class="absolute inset-0">
            <img
              class="w-full h-full object-cover"
              src="homeback.jpg"
            />
            <div class="absolute inset-0 bg-gray-900 opacity-60"></div>
          </div>
          <div
            class="relative max-w-4xl mx-auto text-center py-24 px-4 sm:py-32 sm:px-6 lg:px-8"
          >
            <h2
              class="text-4xl md:text-5xl font-extrabold text-white tracking-tight fade-in-up"
            >
              Clarity, Connection, and Confidence in Law
            </h2>
            <p class="mt-6 max-w-xl mx-auto text-lg text-gray-300 fade-in-up" style="transition-delay: 0.2s;">
              Vakil is your dedicated platform for navigating the legal
              landscape. Connect with top-tier legal professionals across India.
            </p>
          </div>
        </div>
      </div>

      <!-- Top Lawyers Section -->
      <div class="fade-in-up mb-12" style="transition-delay: 0.2s;">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">Our Top Legal Experts</h2>
      </div>
      
      <!-- Animated Banner Carousel -->
      <div class="lawyers-banner py-12 mb-12 rounded-2xl fade-in-up" style="transition-delay: 0.3s;">
        <div class="lawyers-track">
          <?php 
          // Duplicate lawyers array for seamless loop
          $duplicatedLawyers = array_merge($lawyers, $lawyers);
          foreach ($duplicatedLawyers as $lawyer): 
          ?>
            <div class="banner-lawyer-card">
              <div class="flex items-start mb-4">
                <a href="book_consultation.php?lawyer=<?php echo urlencode($lawyer['name']); ?>" class="flex-shrink-0">
                  <img 
                    src="https://placehold.co/80x80/3b82f6/ffffff?text=<?php echo substr($lawyer['name'], 0, 1); ?>" 
                    alt="Profile of <?php echo htmlspecialchars($lawyer['name']); ?>" 
                    class="w-20 h-20 rounded-full object-cover shadow-lg"
                  >
                </a>
                <div class="ml-4 flex-1">
                  <a href="book_consultation.php?lawyer=<?php echo urlencode($lawyer['name']); ?>" class="hover:text-blue-600 transition-colors">
                    <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($lawyer['name']); ?></h3>
                  </a>
                  <p class="text-sm text-blue-600 font-semibold"><?php echo htmlspecialchars($lawyer['specialty']); ?></p>
                  <div class="inline-flex items-center text-xs text-gray-700 bg-blue-50 px-2 py-1 rounded-full mt-2 border border-blue-200">
                    <svg class="w-3 h-3 mr-1 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                    </svg>
                    <?php 
                      $locationParts = explode(',', $lawyer['location']);
                      echo htmlspecialchars(trim($locationParts[0])); 
                    ?>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200">
                <div>
                  <span class="rating-stars text-xl"><?php echo renderStars($lawyer['rating']); ?></span>
                  <span class="font-bold text-gray-800 text-sm ml-2"><?php echo number_format($lawyer['rating'], 1); ?>/5.0</span>
                </div>
                <a 
                  href="book_consultation.php?lawyer=<?php echo urlencode($lawyer['name']); ?>"
                  class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-2 rounded-lg text-sm font-bold hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg"
                >
                  Consult
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </main>

    <!-- BNS Sections -->
    <section class="bg-gradient-to-br from-blue-50 to-indigo-100 py-16">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
          <div class="text-center mb-12 fade-in-up">
            <h2 class="text-4xl font-bold text-gray-900 mt-2">Bharatiya Nyaya Sanhita (BNS) Sections</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Section 1 -->
            <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-up border-l-4 border-blue-600" style="animation-delay: 0.1s;">
              <div class="flex items-start justify-between mb-3">
                <span class="text-2xl font-bold text-blue-600">Section 1</span>
                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">Foundation</span>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">Title & Application</h4>
              <p class="text-sm text-gray-600 leading-relaxed">Defines the short title as "Bharatiya Nyaya Sanhita, 2023" and specifies its commencement date. Establishes the territorial jurisdiction and applicability of the code across India.</p>
            </div>

            <!-- Section 2 -->
            <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-up border-l-4 border-purple-600" style="animation-delay: 0.2s;">
              <div class="flex items-start justify-between mb-3">
                <span class="text-2xl font-bold text-purple-600">Section 2</span>
                <span class="bg-purple-100 text-purple-800 text-xs font-semibold px-2.5 py-0.5 rounded">Definitions</span>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">Key Legal Terms</h4>
              <p class="text-sm text-gray-600 leading-relaxed">Provides definitions for crucial terms including 'document', 'electronic record', 'gender', 'movable property', and 'public servant'. Introduces modern terminology like electronic communications.</p>
            </div>

            <!-- Section 4 -->
            <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-up border-l-4 border-red-600" style="animation-delay: 0.3s;">
              <div class="flex items-start justify-between mb-3">
                <span class="text-2xl font-bold text-red-600">Section 4</span>
                <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded">Punishments</span>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">Types of Punishment</h4>
              <p class="text-sm text-gray-600 leading-relaxed">Lists all forms of punishment: death penalty, life imprisonment (rigorous or simple), imprisonment, forfeiture of property, fines, and community service. Defines severity levels for various offenses.</p>
            </div>

            <!-- Section 5 -->
            <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-up border-l-4 border-orange-600" style="animation-delay: 0.4s;">
              <div class="flex items-start justify-between mb-3">
                <span class="text-2xl font-bold text-orange-600">Section 5</span>
                <span class="bg-orange-100 text-orange-800 text-xs font-semibold px-2.5 py-0.5 rounded">Commutation</span>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">Sentence Reduction</h4>
              <p class="text-sm text-gray-600 leading-relaxed">Provides framework for commutation of death sentence to life imprisonment or imprisonment for a term. Establishes guidelines for reducing severity of punishments under special circumstances.</p>
            </div>

            <!-- Section 69 -->
            <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-up border-l-4 border-pink-600" style="animation-delay: 0.5s;">
              <div class="flex items-start justify-between mb-3">
                <span class="text-2xl font-bold text-pink-600">Section 69</span>
                <span class="bg-pink-100 text-pink-800 text-xs font-semibold px-2.5 py-0.5 rounded">Sexual Offenses</span>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">Sexual Intercourse by Deceit</h4>
              <p class="text-sm text-gray-600 leading-relaxed">Addresses sexual intercourse obtained through deceitful means, false promises of marriage, or fraudulent identity. Prescribes imprisonment up to 10 years and fine for such offenses.</p>
            </div>

            <!-- Section 103 -->
            <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-up border-l-4 border-green-600" style="animation-delay: 0.6s;">
              <div class="flex items-start justify-between mb-3">
                <span class="text-2xl font-bold text-green-600">Section 103</span>
                <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">Murder</span>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">Punishment for Murder</h4>
              <p class="text-sm text-gray-600 leading-relaxed">Prescribes death penalty or life imprisonment along with fine for murder. Defines culpable homicide amounting to murder with various circumstances and exceptions explained in detail.</p>
            </div>

            <!-- Section 111 -->
            <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-up border-l-4 border-indigo-600" style="animation-delay: 0.7s;">
              <div class="flex items-start justify-between mb-3">
                <span class="text-2xl font-bold text-indigo-600">Section 111</span>
                <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-0.5 rounded">Organized Crime</span>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">Organized Crime Offenses</h4>
              <p class="text-sm text-gray-600 leading-relaxed">Defines organized crime including kidnapping, robbery, extortion, land grabbing, financial scams, and cyber crimes. Prescribes imprisonment from 5 years to life and substantial fines.</p>
            </div>

            <!-- Section 113 -->
            <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-up border-l-4 border-red-800" style="animation-delay: 0.8s;">
              <div class="flex items-start justify-between mb-3">
                <span class="text-2xl font-bold text-red-800">Section 113</span>
                <span class="bg-red-200 text-red-900 text-xs font-semibold px-2.5 py-0.5 rounded">Terrorism</span>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">Terrorist Acts</h4>
              <p class="text-sm text-gray-600 leading-relaxed">Covers acts intended to threaten unity, integrity, security, or economic security of India. Includes use of bombs, firearms, or hazardous substances. Punishment can extend to death penalty.</p>
            </div>

            <!-- Section 304 -->
            <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 fade-in-up border-l-4 border-yellow-600" style="animation-delay: 0.9s;">
              <div class="flex items-start justify-between mb-3">
                <span class="text-2xl font-bold text-yellow-600">Section 304</span>
                <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded">Kidnapping</span>
              </div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">Kidnapping Offenses</h4>
              <p class="text-sm text-gray-600 leading-relaxed">Addresses kidnapping from India or lawful guardianship. Prescribes imprisonment up to 7 years and fine. Includes special provisions for kidnapping minors under 16 years or persons of unsound mind.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Map Modal -->
    <div id="map-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 modal-open">
      <div class="bg-white w-full max-w-3xl rounded-xl shadow-xl overflow-hidden modal-panel">
        <div class="flex items-center justify-between px-4 py-3 border-b">
          <h3 id="map-modal-title" class="text-lg font-semibold text-gray-900">Location</h3>
          <button class="text-gray-600 hover:text-gray-900" onclick="closeMapModal()">✕</button>
        </div>
        <div class="w-full h-[420px]">
          <iframe id="map-iframe" class="w-full h-full" style="border:0" loading="lazy" allowfullscreen
            referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
        <div class="px-4 py-3 border-t flex justify-end gap-2">
          <a id="map-directions-link" target="_blank" rel="noopener" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700">Open in Google Maps</a>
          <button class="px-4 py-2 rounded-md text-sm font-medium border" onclick="closeMapModal()">Close</button>
        </div>
      </div>
    </div>

    <!-- Legal News Section -->
    <section class="bg-white py-16">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
          <div class="text-center mb-12 fade-in-up">
            <h2 class="text-4xl font-bold text-gray-900 mt-2">Latest Legal Updates</h2>
            <p class="text-gray-600 mt-4 max-w-2xl mx-auto">Recent developments in Indian law that matter to you</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- News 1 -->
            <div class="bg-gradient-to-br from-blue-50 to-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden fade-in-up border border-blue-100" style="animation-delay: 0.1s;">
              <div class="h-48 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                <img src="images/bns.png" alt="BNS Replaces IPC" class="w-full h-full object-cover">
              </div>
              <div class="bg-blue-600 px-6 py-4">
                <span class="text-white text-xs font-semibold uppercase tracking-wide">Criminal Law</span>
              </div>
              <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-3">BNS Replaces IPC After 163 Years</h3>
                <p class="text-sm text-gray-600 leading-relaxed mb-4">The Bharatiya Nyaya Sanhita (BNS) officially replaced the Indian Penal Code on July 1, 2024. Key changes include stronger provisions for cybercrime, terrorism, and organized crime with enhanced penalties.</p>
              </div>
            </div>

            <!-- News 2 -->
            <div class="bg-gradient-to-br from-purple-50 to-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden fade-in-up border border-purple-100" style="animation-delay: 0.2s;">
              <div class="h-48 bg-gradient-to-br from-purple-100 to-purple-200 flex items-center justify-center">
               <img src="images/digital.jpg" alt="Digital Privacy Rights" class="w-full h-full object-cover">
              </div>
              <div class="bg-purple-600 px-6 py-4">
                <span class="text-white text-xs font-semibold uppercase tracking-wide">Digital Rights</span>
              </div>
              <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-3">Supreme Court on Right to Privacy</h3>
                <p class="text-sm text-gray-600 leading-relaxed mb-4">SC upholds right to privacy as a fundamental right under Article 21. Landmark judgment impacts data protection laws, surveillance, and digital consent requirements for all online platforms.</p>
              </div>
            </div>

            <!-- News 3 -->
            <div class="bg-gradient-to-br from-green-50 to-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden fade-in-up border border-green-100" style="animation-delay: 0.3s;">
              <div class="h-48 bg-gradient-to-br from-green-100 to-green-200 flex items-center justify-center">
                <img src="images/stalking.png" alt="Anti-Stalking Laws" class="w-full h-full object-cover">
              </div>
              <div class="bg-green-600 px-6 py-4">
                <span class="text-white text-xs font-semibold uppercase tracking-wide">Women's Rights</span>
              </div>
              <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-3">New Anti-Stalking Provisions</h3>
                <p class="text-sm text-gray-600 leading-relaxed mb-4">BNS Section 78 introduces comprehensive anti-stalking laws covering physical and cyber stalking. Prescribes imprisonment up to 5 years for first offense, with enhanced penalties for repeat offenders.</p>
              </div>
            </div>

            <!-- News 4 -->
            <div class="bg-gradient-to-br from-orange-50 to-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden fade-in-up border border-orange-100" style="animation-delay: 0.4s;">
              <div class="h-48 bg-gradient-to-br from-orange-100 to-orange-200 flex items-center justify-center">
                <img src="images/consumer.jpg" alt="Consumer Protection" class="w-full h-full object-cover">
              </div>
              <div class="bg-orange-600 px-6 py-4">
                <span class="text-white text-xs font-semibold uppercase tracking-wide">Consumer Rights</span>
              </div>
              <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-3">E-commerce Consumer Protection</h3>
                <p class="text-sm text-gray-600 leading-relaxed mb-4">New rules mandate 48-hour complaint resolution, clear return policies, and strict action against fake reviews. Platforms face penalties up to ₹50 lakhs for non-compliance with consumer protection norms.</p>
              </div>
            </div>

            <!-- News 5 -->
            <div class="bg-gradient-to-br from-red-50 to-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden fade-in-up border border-red-100" style="animation-delay: 0.5s;">
              <div class="h-48 bg-gradient-to-br from-red-100 to-red-200 flex items-center justify-center">
                <img src="images/traffic.jpg" alt="Traffic Violations" class="w-full h-full object-cover">
              </div>
              <div class="bg-red-600 px-6 py-4">
                <span class="text-white text-xs font-semibold uppercase tracking-wide">Motor Vehicle Act</span>
              </div>
              <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-3">Stricter Penalties for Traffic Violations</h3>
                <p class="text-sm text-gray-600 leading-relaxed mb-4">Hit-and-run cases now carry minimum 10 years imprisonment under BNS Section 106(2). Drunk driving penalties increased to ₹10,000 fine and 6 months jail for first offense.</p>
              </div>
            </div>

            <!-- News 6 -->
            <div class="bg-gradient-to-br from-indigo-50 to-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden fade-in-up border border-indigo-100" style="animation-delay: 0.6s;">
              <div class="h-48 bg-gradient-to-br from-indigo-100 to-indigo-200 flex items-center justify-center">
                <img src="images/personal.jpg" alt="Data Protection" class="w-full h-full object-cover">
              </div>
              <div class="bg-indigo-600 px-6 py-4">
                <span class="text-white text-xs font-semibold uppercase tracking-wide">Cybersecurity</span>
              </div>
              <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-3">Digital Personal Data Protection Act</h3>
                <p class="text-sm text-gray-600 leading-relaxed mb-4">DPDP Act 2023 grants users right to access, correction, and erasure of personal data. Companies face fines up to ₹250 crores for data breaches. Mandatory consent required for data processing.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section class="bg-gray-50 border-t mt-16">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 fade-in-up">
        <div class="max-w-4xl mx-auto text-center mb-8">
         
          <h2 class="text-3xl font-bold text-gray-900 mt-2">FAQ's</h2>
        
        </div>
        <div class="max-w-4xl mx-auto space-y-4">
          <details class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition" open>
            <summary class="font-semibold text-gray-900 cursor-pointer">How do I book a consultation?</summary>
            <p class="text-sm text-gray-600 mt-2">Browse top lawyers, click “Log In to Consult,” and pick a time slot. You’ll get a confirmation once the lawyer accepts.</p>
          </details>
          <details class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition">
            <summary class="font-semibold text-gray-900 cursor-pointer">Is payment required to register a case?</summary>
            <p class="text-sm text-gray-600 mt-2">A small registration fee may apply for case intake. You’ll see the amount before you confirm.</p>
          </details>
          <details class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition">
            <summary class="font-semibold text-gray-900 cursor-pointer">How are lawyers matched to my case?</summary>
            <p class="text-sm text-gray-600 mt-2">We consider specialty, experience, location, and availability to suggest the best-suited lawyers for you.</p>
          </details>
          <details class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition">
            <summary class="font-semibold text-gray-900 cursor-pointer">Can I reschedule a booked consultation?</summary>
            <p class="text-sm text-gray-600 mt-2">Yes. Use your dashboard to request a new slot; the lawyer will confirm the updated time.</p>
          </details>
        </div>
      </div>
    </section>
    
      <footer class="bg-white mt-16 border-t">
      <div
        class="container mx-auto py-6 px-4 sm:px-6 lg:px-8 text-center text-gray-500"
      >
        <p>&copy; 2025 Vakil. All rights reserved.</p>
      </div>
    </footer>

    <script>
      window.addEventListener('DOMContentLoaded', () => {
        // Fade-in elements with intersection observer for better performance
        const observerOptions = {
          threshold: 0.1,
          rootMargin: '0px 0px -50px 0px'
        };
        const observer = new IntersectionObserver((entries) => {
          entries.forEach((entry, idx) => {
            if (entry.isIntersecting) {
              setTimeout(() => {
                entry.target.classList.add('show');
              }, idx * 100);
              observer.unobserve(entry.target);
            }
          });
        }, observerOptions);
        document.querySelectorAll('.fade-in-up').forEach(el => observer.observe(el));
      });

      // === Map Modal Logic ===
      // Removed Zoho; use Google Maps universally
      // Sample areas per city to focus specific neighborhoods
      const citySampleAreas = {
        'Mumbai': 'Bandra West',
        'Delhi': 'Connaught Place',
        'Bangalore': 'Indiranagar',
        'Pune': 'Koregaon Park',
        'Chennai': 'T Nagar',
        'Hyderabad': 'Banjara Hills'
      };

      function buildMapUrls(locationText) {
        const q = encodeURIComponent(locationText);
        const embedUrl = `https://www.google.com/maps?q=${q}&output=embed`;
        const directionsUrl = `https://www.google.com/maps/search/?api=1&query=${q}`;
        return { embedUrl, directionsUrl };
      }

      function openMapModal(locationText, lawyerName) {
        const modal = document.getElementById('map-modal');
        const iframe = document.getElementById('map-iframe');
        const title = document.getElementById('map-modal-title');
        const directionsLink = document.getElementById('map-directions-link');
        const { embedUrl, directionsUrl } = buildMapUrls(locationText);
        iframe.src = embedUrl;
        directionsLink.href = directionsUrl;
        title.textContent = lawyerName ? `${lawyerName}'s Location` : `Location: ${locationText}`;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        // trigger transition
        requestAnimationFrame(() => modal.classList.add('modal-open'));
      }

      function closeMapModal() {
        const modal = document.getElementById('map-modal');
        const iframe = document.getElementById('map-iframe');
        modal.classList.remove('modal-open');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        // Clear the iframe to stop background loading
        iframe.src = '';
      }
    </script>
  </body>
</html>