<?php
// Include the database connection and configuration
// NOTE: Make sure 'config.php' establishes and returns the $conn mysqli connection object.
include "config.php";

// Start a new session or resume an existing one
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: auth.php");
    exit();
}

// Include a function to sanitize user input
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    // Use ENT_QUOTES for better security against double quotes in attribute values
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Fetch user data from the database using the stored user ID in the session
$id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT fullName, userType, program, year, semester FROM user WHERE id = ?");
if (!$stmt) {
    // Handle prepare error
    error_log("Prepare failed: " . $conn->error);
    session_destroy();
    header("Location: auth.php?error=db");
    exit();
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

$user = null;
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    // If user data can't be found, log them out and redirect
    error_log("No user found for ID: " . $id);
    session_destroy();
    header("Location: auth.php?error=user_not_found");
    exit();
}
$stmt->close();

// Set variables for the dynamic parts of the page
$fullName = sanitize_input($user['fullName']);
$userType = sanitize_input($user['userType']);
$program = sanitize_input($user['program']);
$year = intval($user['year']); // Ensure year is an integer
$semester = intval($user['semester']); // Ensure semester is an integer

// Keeping the database connection open for now, but will close it before the HTML starts.
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEAU Student Dashboard - CourseFinder AI Agent</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ===============================
        1. COLOR PALETTE
        ================================= */
        :root {
            --primary-green: #4CAF50;
            --dark-green: #2E7D32;
            --light-bg: #E8F5E8;
            --card-bg: #ffffff;
            --welcome-text: #2E7D32;
            --badge-bg: #E6F7E6;
            --badge-border: #9BCF9B;
            --sidebar-width: 250px; /* Increased for better desktop look */
            --active-yellow: #FFEB3B;
            --chat-input-height: 70px; /* Estimated height for fixed input area */
            --footer-height: 40px; /* Fixed height for the footer */
        }

        /* ===============================
        2. BASE & UTILITIES
        ================================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-bg);
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            font-size: 0.9rem;
        }

        /* --- Main Content Layout --- */
        .main {
            margin-left: var(--sidebar-width);
            padding: 15px;
            flex: 1;
            transition: margin-left 0.3s ease-in-out;
            min-width: 300px;
            width: auto;
            max-width: none;
            /* Add padding at the bottom equal to footer + chat input height */
            padding-bottom: calc(var(--footer-height) + var(--chat-input-height) + 10px); /* Added 10px buffer */
        }
        
        /* ===============================
            SIDEBAR
        ================================= */
        .sidebar {
            width: 240px;
            background: linear-gradient(to bottom, #2E7D32, #4CAF50);
            color: white;
            height: 100vh;
            padding: 15px 0;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            border-radius: 0 12px 12px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3); 
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
        }
        
        /* FIX: Class applied by JS to show the sidebar on mobile */
        .sidebar.show {
            transform: translateX(0); /* Bring the sidebar into view */
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.5); 
        }

       .sidebar h2 {
        text-align: left;
        margin-bottom: 20px;
        margin-left:20px;
        font-size: 1.25rem;
        color: #fff;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
    }
        .sidebar h2 .logo-icon {
            font-size: 1em;
            margin-right: 6px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar li {
            margin: 3px 0;
        }

        .sidebar a {
            display: block;
            padding: 10px 15px;
            color: #e9ecef;
            text-decoration: none;
            border-radius: 6px;
            margin: 0 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #FFEB3B;
            color: #2E7D32;
            font-weight: 600;
        }
        
        /* New Overlay CSS */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5); /* Semi-transparent black */
    z-index: 999; /* Below the sidebar (1000) but above everything else */
    display: none; /* Hidden by default */
    cursor: pointer; /* Show it's clickable */
}

.sidebar.show + .sidebar-overlay {
    display: block; /* Show the overlay when the sidebar has the .show class */
}


        /* ===============================
        4. HEADER & MENU TOGGLE
        ================================= */
        .header {
            background: var(--card-bg);
            padding: 20px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: space-between;
            border-left: 4px solid var(--primary-green);
        }

        .header h2 {
            font-size: 1.5rem;
            color: var(--welcome-text);
            margin: 0;
            font-weight: 600;
            flex-grow: 1;
        }
        
        .info-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            margin: 0;
            font-size: 0.85rem;
            font-weight: 500;
            border-radius: 6px;
            background-color: var(--badge-bg);
            border: 1px solid var(--badge-border);
            color: var(--welcome-text);
            white-space: nowrap;
        }

        .info-badge i {
            margin-right: 6px;
            color: var(--primary-green);
        }

        .navbar-toggle {
            background: none;
            border: none;
            color: var(--dark-green);
            font-size: 1.8rem;
            cursor: pointer;
            padding: 5px;
            order: -1;
            display: none; /* Hide on desktop, show via media query */
        }

        /* ===============================
        5. CHAT STYLING & FIXED INPUT
        ================================= */
        .card {
            background: var(--card-bg);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.1);
            border: none;
            margin-bottom: 0;
        }

        .card h3 {
            color: var(--dark-green);
            margin-bottom: 10px;
            border-bottom: 2px solid var(--primary-green);
            padding-bottom: 5px;
            font-size: 1.2rem;
            font-weight: 600;
        }

        /* Adjust chat container height to fit above the fixed input */
        .chat-container {
            /* 100vh - (header+padding) - fixed-input-height - footer-height - a bit of margin */
            height: calc(100vh - 200px); 
            min-height: 350px;
            border: 1px solid #E0E0E0;
            border-radius: 10px;
            overflow-y: auto;
            padding: 15px;
            background: #F8F9FA;
            display: flex;
            flex-direction: column;
            scroll-behavior: smooth;
            margin-bottom: 15px;
        }
        
        .message {
            margin: 8px 0;
            padding: 12px 15px;
            border-radius: 18px;
            max-width: 90%;
            word-wrap: break-word;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .user-message {
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }

        .ai-message {
            background: var(--light-bg);
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
            border: 1px solid var(--badge-border);
        }

        /* FIX: Make Chat Input Fixed at the bottom and ensure it's on top of the footer */
        .fixed-chat-input-wrapper {
            position: fixed;
            bottom: var(--footer-height); /* CRITICAL FIX: Positioned right above the footer */
            left: var(--sidebar-width);
            right: 0;
            z-index: 1035; /* CRITICAL FIX: Higher than footer (1030) */
            background: var(--card-bg);
            padding: 10px 15px;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            transition: left 0.3s ease-in-out;
            height: var(--chat-input-height);
        }
        
        .chat-input {
            display: flex;
            margin-top: 0;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .chat-input input {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid var(--primary-green);
            border-radius: 20px 0 0 20px;
            outline: none;
            font-size: 0.9rem;
        }

        .chat-input button {
            padding: 10px 25px;
            background: var(--primary-green);
            color: white;
            border: none;
            border-radius: 0 20px 20px 0;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .chat-input button:hover {
            background: var(--dark-green);
        }

        /* ===============================
        6. FOOTER (UPDATED)
        ================================= */
        :root {
            /* Increase footer height to accommodate stacked text/icons */
            --footer-height: 70px; 
            /* Adjust chat input position to stay above new, taller footer */
            --chat-input-height: 70px; 
        }
        
        /* FIX: Make Chat Input Fixed at the bottom and ensure it's on top of the footer */
        .fixed-chat-input-wrapper {
            position: fixed;
            bottom: var(--footer-height); /* CRITICAL FIX: Positioned right above the footer */
            left: var(--sidebar-width);
            right: 0;
            z-index: 1035; 
            background: var(--card-bg);
            padding: 10px 15px;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            transition: left 0.3s ease-in-out;
            height: var(--chat-input-height);
        }
        
        .footer {
            background-color: #4CAF50;
            color: white;
            /* CRITICAL: Make the footer fixed */
            position: fixed;
            bottom: 0;
            right: 0;
            left: var(--sidebar-width); 
            z-index: 1030;
            height: var(--footer-height); 
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: left 0.3s ease-in-out;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.2);
        }

        .footer .container {
            display: flex;
            flex-direction: column; /* Stack the content vertically */
            align-items: center; /* Center horizontally */
            justify-content: center; /* Center vertically (within its own space) */
            width: 100%;
            max-width: 1200px; 
            margin: 0 auto;
        }

        .footer p {
            margin: 0; 
            padding: 0;
            text-align: center; /* Center the text */
            font-size: 0.9rem;
        }
        
        .social-icons {
            display: flex;
            gap: 15px; /* Added more space for better look */
            padding-top: 3px; /* Small gap between text and icons */
        }

        .social-icons a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
            font-size: 1.2rem; /* Slightly larger icons */
        }
        
        .social-icons a:hover {
            color: var(--active-yellow);
        }

        /* Responsive Fix for Footer and Chat Input */
        @media (max-width: 967px) {
            .footer {
                left: 0; /* Makes the footer full-width on mobile */
            }
            .fixed-chat-input-wrapper {
                left: 0;
            }
        }
        
        /* Ensure main content padding is adjusted for the new footer height */
        .main {
            /* Increased padding to account for the taller footer and chat input area */
            padding-bottom: calc(var(--footer-height) + var(--chat-input-height) + 10px); 
        }
        /* ===============================
        7. RESPONSIVE MEDIA QUERIES
        ================================= */
        
        /* Medium Screens (max-width: 967px) - Mobile/Tablet View */
        @media (max-width: 967px) {
            
            .header {
                flex-direction: row;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between; 
                gap: 10px; 
                padding: 15px 15px;
            }

            .navbar-toggle {
                display: block !important;
                order: -1;
                width: auto;
                text-align: left;
                margin-right: 0;
                padding: 5px;
            }

            .header h2 {
                flex-grow: 1; 
                order: 0;
                text-align: right; 
                font-size: 1.2rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .header-badges {
                flex-grow: 1;
                order: 1; 
                width: 100%; 
                justify-content: flex-start; 
                margin-top: 10px;
                gap: 10px;
            }

            /* Sidebar and Main content adjustment for screens <= 967px */
            .main {
                margin-left: 0;
                padding: 15px;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }

            .sidebar.show {
                transform: translateX(0);
            }
            
            /* CRITICAL: Fix input and footer position for mobile (full width) */
            .fixed-chat-input-wrapper {
                left: 0;
                padding: 10px 15px;
            }
            .footer {
                left: 0; /* Makes the footer full-width on mobile */
            }
            
            .chat-container {
                /* Adjusted height due to smaller header on mobile */
                height: calc(100vh - 220px); 
            }
        }
        
        /* Desktop (min-width: 968px) - Sidebar is visible, button is hidden */
        @media (min-width: 968px) {
            .navbar-toggle {
                display: none;
            }
            .main {
                margin-left: var(--sidebar-width);
            }
             .fixed-chat-input-wrapper {
                left: var(--sidebar-width);
            }
        }

        /* Small Mobile Devices (max-width: 575.98px) */
        @media (max-width: 575.98px) {
            
            .header {
                flex-direction: row; 
                align-items: center; 
                justify-content: flex-start; 
                padding: 15px 10px;
                gap: 0; 
            }

            .navbar-toggle {
                order: -3;
                width: auto;
                margin-bottom: 0; 
                margin-right: 0; 
                padding: 5px 0;
                font-size: 1.6rem;
            }
            
            .header h2 {
                font-size: 1.05rem;
                order: -2;
                flex-grow: 0; 
                margin-left: 5px;
                text-align: left; 
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .header-badges {
                flex-direction: row; 
                flex-wrap: wrap;
                gap: 5px;
                width: 100%;
                justify-content: center; 
                order: -1; 
                margin-top: 10px;
            }
            
            .chat-container {
                height: calc(100vh - 200px); 
                min-height: 300px;
            }
            
            .chat-input input, .chat-input button {
                font-size: 0.8rem;
                padding: 8px 15px;
            }
        }
        
    </style>
</head>

<body>
    <nav class="sidebar" id="sidebar">
        <h2><i class="fas fa-university"></i> TEAU Portal</h2>
        <ul>
            <li><a href="student_dashboard.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
            <li><a href="MyUnits.php"><i class="fas fa-book-open me-2"></i>Enrolled Units</a></li>
            <li><a href="Progress.php"><i class="fas fa-rocket me-2"></i>Progress</a></li>
            <li><a href="studentCourseFinder.php"  class="active"><i class="fas fa-comments me-2"></i>CourseFinder</a></li>
            <li><a href="academic_calendar.php"><i class="fas fa-calendar-alt me-2"></i>Academic Calendar</a></li>
            <li><a href="profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
        </ul>
    </nav>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main">

        <div class="header">
            <button class="navbar-toggle mx-3" id="sidebarToggle"><i class="fas fa-bars"></i></button>

            <h2>Welcome  <?php echo htmlspecialchars($fullName); ?>!</h2>

            <div class="header-badges d-flex flex-wrap gap-3">
                <span class="info-badge">
                    <i class="fas fa-graduation-cap"></i> Program: <?php echo htmlspecialchars($program); ?>
                </span>
                <span class="info-badge">
                    <i class="fas fa-calendar-alt"></i> Current Year: Year <?php echo htmlspecialchars($year); ?>, Sem <?php echo htmlspecialchars($semester); ?>
                </span>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h3><i class="fas fa-robot me-2"></i> CourseFinder AI Assistant</h3>
                    <p class="text-muted small">Ask me anything! E.g., "What is <strong>ITC 1200</strong>?", "units for <strong>CS year 3 sem 1</strong>", or "courses on <strong>networking</strong>"</p>
                    <div class="chat-container" id="chatContainer">
                        <div class="message ai-message">
                            <p>Hi! I'm CourseFinder, your TEAU AI assistant. How can I help with your course inquiries?</p>
                            <small class="text-secondary">I'll use your current context (<strong><?php echo htmlspecialchars($program); ?></strong>, Year <strong><?php echo htmlspecialchars($year); ?></strong>, Sem <strong><?php echo htmlspecialchars($semester); ?></strong>) as a default.</small>
                        </div>
                    </div>
                    
                    </div>
            </div>
        </div>
    </main>
    
    <div class="fixed-chat-input-wrapper" id="fixedChatInputWrapper">
        <div class="chat-input">
            <input type="text" id="chatInput" placeholder="Type your question..." class="form-control" onkeypress="if(event.keyCode == 13) { sendMessage(); return false; }">
            <button onclick="sendMessage()" class="btn teaus-primary">Send</button>
        </div>
    </div>

   <footer class="footer">
        <div class="container">
            <p>Created with <i class="fas fa-heart text-danger"></i> for TEAU. &copy; <?php echo date("Y"); ?>.</p>
            <div class="social-icons">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>
 
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

     <script>
         document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.createElement('div');
    sidebarOverlay.className = 'sidebar-overlay';
    sidebarOverlay.id = 'sidebarOverlay';
    document.body.appendChild(sidebarOverlay); // Ensure overlay is at body level

    function toggleSidebar() {
        sidebar.classList.toggle('show');
        sidebarOverlay.style.display = sidebar.classList.contains('show') ? 'block' : 'none';
        
        // Fix for main content positioning if needed, though your CSS handles this via margin
        // You might need to adjust the fixed chat input wrapper and footer 'left' property
        const fixedChatInputWrapper = document.getElementById('fixedChatInputWrapper');
        const footer = document.querySelector('.footer');
        
        if (window.innerWidth < 968) {
             // On mobile, force to 0 when sidebar is hidden/shown
             fixedChatInputWrapper.style.left = '0';
             footer.style.left = '0';
        } else {
             // On desktop, adjust only if you implement hiding the sidebar on desktop too
             // Currently, your CSS hides the toggle, so this isn't strictly necessary
        }
    }

    // Attach event listener to the button
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }

    // Close sidebar when clicking the overlay on mobile
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }
});

// ... rest of your existing JS functions ...
         
     // ...existing code...
        // CRITICAL FIX: Capture logged-in user context from PHP to use as intelligent defaults
        const currentUserFullName = "<?php echo htmlspecialchars($fullName); ?>";
        const currentUserProgram = "<?php echo htmlspecialchars($program); ?>";
        const currentUserYear = <?php echo intval($year); ?>;
        const currentUserSemester = <?php echo intval($semester); ?>;

        // New asynchronous function to get AI response from the database
        async function getAIResponse(query) {
            query = query.toLowerCase().trim();

            // --- NEW: Handle quick reactions & feedback-like messages ---
            const thanksWords = ['thanks', 'thank you', 'thx', 'ty', 'cheers'];
            if (thanksWords.some(w => query.includes(w))) {
                return `<p>You're welcome, ${currentUserFullName}! 🙌 If that solved your problem, you can tap 👍 to mark it helpful. If not, tell me what went wrong or type "simplify" for a shorter answer.</p>`;
            }

            const frustratedWords = ['not helpful', "doesn't help", 'wrong', 'incorrect', "don't understand", 'confusing', 'useless', 'hate this', 'annoying'];
            if (frustratedWords.some(w => query.includes(w))) {
                return `<p>Sorry about that — I want to help. Could you tell me what's wrong? (e.g., "too long", "not relevant", "missing course code"). You can also press 👎 to send feedback.</p>`;
            }

            const briefWords = ['short', 'brief', 'concise', 'summary', 'tl;dr', 'simplify'];
            const wantsBrief = briefWords.some(w => query.includes(w));

            // --- 1. HANDLE STATIC/SIMPLE RESPONSES FIRST ---
            const greetings = ['hi', 'hello', 'helo', 'morning', 'afternoon', 'evening', 'hey'];
            if (greetings.some(g => query.split(/\s+/).some(word => greetings.includes(word)))) {
                return `<p>Hello, ${currentUserFullName}! I'm CourseFinder, your TEAU AI assistant. What course information can I help you with today? (e.g., "units for CS year 3 sem 1" or "web development units")</p>`;
            }
            if (query.includes('who are you') || query.includes('your name')) {
                return `<p>I am <strong>CourseFinder</strong>, your dedicated AI assistant for TEAU University course inquiries. I can look up courses by program, year, semester, or keyword.</p>`;
            }
            if (query.includes('contact') || query.includes('support')) {
                return `<p>For general support, please visit the <a href="#" onclick="alert('Simulated link to Help Center');">TEAU Help Center</a> or email <a href="mailto:support@teau.edu">support@teau.edu</a>. For technical issues, contact the IT department.</p>`;
            }
            if (query.includes('my units') || query.includes('my courses') || query.includes('my enrolled')) {
                // Trigger the default search logic by returning a specific instruction
                return `SEARCH_DEFAULT_UNITS`; 
            }

            // --- 2. INITIALIZE SEARCH PARAMETERS & FALLBACKS ---
            let program = null;
            let year = null;
            let semester = null;
            let keyword = null;
            let courseCode = null;
            let isSpecificQuery = false;
            let url = 'courses.php?';
            let searchContext = 'Found results for: ';

            // --- 3. ADVANCED QUERY PARSING ---
            // 3.1 Course Code Check (Highest priority: e.g., "tell me about ITC 1200")
            const codeMatch = query.match(/\b([a-z]{2,5})\s*(\d{2,4})\b/i);
            if (codeMatch) {
                courseCode = (codeMatch[1].toUpperCase() + codeMatch[2]);
                isSpecificQuery = true;
                searchContext = `I found a specific course for code <strong>${courseCode}</strong>:` + (wantsBrief ? ' (brief)' : '');
                url = `courses.php?code=${encodeURIComponent(courseCode)}` + (wantsBrief ? '&brief=1' : '');
            }

            // 3.2 Program, Year, Semester Search
            if (!courseCode) {
                let pysFound = false; 

                // PROGRAM PARSING
                const programMap = {
                    'information technology': 'Information Technology', 'it': 'Information Technology',
                    'computer science': 'Computer Science', 'cs': 'Computer Science', 'computing': 'Computer Science',
                    'business administration': 'Business Administration', 'bus admin': 'Business Administration', 'bus': 'Business Administration', 'ba': 'Business Administration', 'business': 'Business Administration', 'management': 'Business Administration',
                    'education': 'Education', 'edu': 'Education',
                    'nursing': 'Nursing', 'nurse': 'Nursing'
                };
                for (const key in programMap) {
                    if (query.includes(key)) {
                        program = programMap[key];
                        pysFound = true; 
                    }
                }

                // Year Parsing
                const numberWords = {'first': 1, 'second': 2, 'third': 3, 'fourth': 4, 'fifth': 5, '1st': 1, '2nd': 2, '3rd': 3, '4th': 4};
                for (const word in numberWords) {
                    if (query.includes(word) && (query.includes('year') || query.includes('yr') || query.includes('y '))) {
                        year = numberWords[word];
                        pysFound = true; 
                        break; 
                    }
                }
                if (!year) {
                    const yearMatch = query.match(/(?:year|yr|y)\s*(\d)|(\d)(?:st|nd|rd|th)?\s*(?:year|yr|y)/);
                    if (yearMatch) {
                        year = parseInt(yearMatch[1] || yearMatch[2]);
                        pysFound = true;
                    }
                }

                // Semester Parsing
                const semesterMatch = query.match(/(?:sem|semester|s)\s*(\d)/);
                if (semesterMatch) {
                    semester = parseInt(semesterMatch[1]);
                    pysFound = true; 
                }

                if (pysFound) {
                    keyword = null; // Prioritize PYS search over keyword search
                    isSpecificQuery = true; 

                    // Default program if missing
                    if (!program) program = currentUserProgram;

                    // Build URL for PYS search
                    url = 'courses.php?';
                    if (program) url += `program=${encodeURIComponent(program)}&`;
                    if (year) url += `year=${encodeURIComponent(year)}&`;
                    if (semester) url += `semester=${encodeURIComponent(semester)}&`;
                    if (wantsBrief) url += 'brief=1&';
                    url = url.endsWith('&') ? url.slice(0, -1) : url;

                    searchContext = `I found a specific course list for <strong>${program || 'Any Program'}</strong>` + (wantsBrief ? ' (brief)' : '');
                    if (year) searchContext += `, Year <strong>${year}</strong>`;
                    if (semester) searchContext += `, Semester <strong>${semester}</strong>`;
                    searchContext += '.';
                }
            }

            // 3.3 Keyword Search (Only runs if no Course Code AND no specific PYS query was detected)
            if (!isSpecificQuery) {
                const keywords = ['web development', 'database', 'network', 'ai', 'artificial intelligence', 'machine learning', 'cybersecurity', 'business', 'management', 'nursing', 'computing', 'programming', 'software engineering', 'data structures', 'algorithms', 'finance', 'marketing', 'pedagogy', 'curriculum', 'education', 'information systems'];
                for (const kw of keywords) {
                    if (query.includes(kw)) {
                        keyword = kw;
                        isSpecificQuery = true;
                        searchContext = `I found courses related to <strong>${keyword}</strong> in the TEAU catalog:` + (wantsBrief ? ' (brief)' : '');
                        url = `courses.php?keyword=${encodeURIComponent(keyword)}` + (wantsBrief ? '&brief=1' : '');
                        break;
                    }
                }
            }

            // 3.4 Fallback to Current Context (If still no specific query found)
            if (!isSpecificQuery) {
                // If it's a simple query that didn't match a keyword or PYS structure, use user's current context
                program = currentUserProgram;
                year = currentUserYear;
                semester = currentUserSemester;

                url = `courses.php?program=${encodeURIComponent(program)}&year=${encodeURIComponent(year)}&semester=${encodeURIComponent(semester)}`;
                if (wantsBrief) url += '&brief=1';
                searchContext = `Here are your default enrolled courses for <strong>${program}</strong> Year <strong>${year}</strong> Semester <strong>${semester}</strong>:` + (wantsBrief ? ' (brief)' : '');
            }
            
            // Handle the specific 'my units' instruction
            if (query === 'SEARCH_DEFAULT_UNITS') {
                 // Re-use the default logic from 3.4
                 program = currentUserProgram;
                 year = currentUserYear;
                 semester = currentUserSemester;
                 url = `courses.php?program=${encodeURIComponent(program)}&year=${encodeURIComponent(year)}&semester=${encodeURIComponent(semester)}` + (wantsBrief ? '&brief=1' : '');
                 searchContext = `Here are your default enrolled courses for <strong>${program}</strong> Year <strong>${year}</strong> Semester <strong>${semester}</strong>:` + (wantsBrief ? ' (brief)' : '');
            }

            // --- 4. EXECUTE FETCH AND PROCESS RESULTS ---
            let responseHtml = '';
            
            // Fetch results from the backend 'courses.php'
            try {
                const response = await fetch(url);
                const courses = await response.json(); // Assuming courses.php returns a JSON array of courses

                if (courses.error) {
                    responseHtml = `<p class="text-danger"><strong>Error:</strong> ${courses.error}</p>`;
                } else if (courses.length > 0) {
                    responseHtml += `<p class="search-results-box">${searchContext}</p>`;
                    responseHtml += `<div class="list-group">`;

                    if (courseCode) {
                        // Specific course code query
                        const course = courses[0];
                        const desc = wantsBrief ? (course.description_short || (course.description ? course.description.substring(0,140)+'...' : '')) : course.description;
                        responseHtml += `<div class="list-group-item">
                                            <strong>${course.code}: ${course.name}</strong>
                                            <p class="text-muted mb-0">${desc}</p>
                                            <small class="text-success">${course.program} | Year ${course.year}, Sem ${course.semester}</small>
                                        </div>`;
                    } else {
                        // PYS or Keyword query
                        courses.forEach(course => {
                            const p = course.program || 'N/A Program';
                            const y = course.year || 'N/A Year';
                            const s = course.semester || 'N/A Sem';
                            const desc = wantsBrief ? (course.description_short || (course.description ? course.description.substring(0, 100) + '...' : '')) : course.description;

                            responseHtml += `<div class="list-group-item">
                                                <strong>${course.code} - ${course.name}</strong>
                                                <p class="text-muted mb-0">${desc}</p>
                                                <small class="text-success">${p} | Year ${y}, Sem ${s}</small>
                                            </div>`;
                        });
                    }
                    responseHtml += `</div>`;
                    responseHtml += `<p class="mt-2 text-muted small">Showing ${courses.length} result(s). For a full course description, please check the TEAU catalog.</p>`;

                } else {
                    responseHtml = `<p>I couldn't find any courses matching your request.<br>Please try a different keyword or check your program/year/semester input.</p>`;
                }
            } catch (error) {
                console.error('Fetch error:', error);
                responseHtml = `<p class="text-danger"><strong>Error:</strong> Could not connect to the course database. Please try again later.</p>`;
            }

            return responseHtml;
        }

        // Append message (unchanged) but now will support adding feedback controls externally
        function appendMessage(sender, message) {
            const container = $('#chatContainer');
            const newMessage = $('<div>').addClass('message').addClass(sender + '-message').html(message);
            container.append(newMessage);
            // Scroll to the bottom
            container.scrollTop(container[0].scrollHeight);
        }

        // NEW: send feedback to server (logs or DB)
        async function sendFeedback(payload) {
            try {
                await fetch('feedback.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
            } catch (e) {
                console.error('Feedback send error', e);
            }
        }

        // Modified sendMessage to attach feedback controls after each AI response
        async function sendMessage() {
            const inputField = $('#chatInput');
            let userQuery = inputField.val().trim();

            if (userQuery === "") {
                return;
            }

            // Append user message to chat container
            appendMessage('user', userQuery);
            inputField.val(''); // Clear input

            // Append a "thinking" message placeholder
            appendMessage('ai', `<i class="fas fa-spinner fa-spin me-2"></i> CourseFinder is thinking...`);
            const thinkingMessage = $('#chatContainer').children().last();
            
            let response = await getAIResponse(userQuery);
            
            // Check for the specific instruction for default search
            if (response === 'SEARCH_DEFAULT_UNITS') {
                thinkingMessage.html(`<i class="fas fa-search me-2"></i> Searching your current units...`);
                // Re-run the function with a known-fail query to trigger default fallback
                response = await getAIResponse('___trigger_default_search___');
            }

            // Replace the "thinking" message with the final response
            thinkingMessage.html(response);

            // --- ADD: Feedback / reaction UI appended after AI response ---
            const messageId = Date.now(); // simple client-side id
            const feedbackHtml = `
                <div class="mt-2 d-flex gap-2" data-msgid="${messageId}">
                    <button class="btn btn-sm btn-outline-success feedback-btn" data-type="helpful">👍 Helpful</button>
                    <button class="btn btn-sm btn-outline-danger feedback-btn" data-type="not_helpful">👎 Not helpful</button>
                    <button class="btn btn-sm btn-outline-secondary feedback-btn" data-type="too_technical">🔧 Too technical</button>
                    <button class="btn btn-sm btn-outline-primary feedback-btn" data-type="thanks">🙏 Thanks</button>
                    <button class="btn btn-sm btn-outline-warning feedback-btn" data-type="other">✉️ Send comment</button>
                </div>
            `;
            thinkingMessage.after(feedbackHtml);

            // Attach click handlers
            $(`div[data-msgid="${messageId}"] .feedback-btn`).on('click', function () {
                const type = $(this).data('type');
                if (type === 'other') {
                    const comment = prompt('Please enter additional feedback:');
                    if (!comment) return;
                    // send feedback including comment
                    sendFeedback({
                        timestamp: messageId,
                        user: currentUserFullName,
                        query: userQuery,
                        aiResponse: thinkingMessage.text().slice(0,1000),
                        feedbackType: 'other',
                        comment: comment
                    });
                    $(this).text('Sent').attr('disabled', true);
                    appendMessage('ai', `<p>Thanks for the comment — your feedback was recorded.</p>`);
                } else {
                    sendFeedback({
                        timestamp: messageId,
                        user: currentUserFullName,
                        query: userQuery,
                        aiResponse: thinkingMessage.text().slice(0,1000),
                        feedbackType: type
                    });
                    $(this).text('Sent').attr('disabled', true);
                    if (type === 'helpful') {
                        appendMessage('ai', `<p>Glad that helped! Would you like related courses or resources?</p>`);
                    } else if (type === 'thanks') {
                        appendMessage('ai', `<p>You're welcome! 😊</p>`);
                    } else {
                        appendMessage('ai', `<p>Thanks for the feedback — we'll use it to improve responses. You can also say "simplify" or "more details".</p>`);
                    }
                }
            });

            // Scroll to the bottom again to show the response
            $('#chatContainer').scrollTop($('#chatContainer')[0].scrollHeight);
        }
// ...existing code...
    </script>
</body>

</html>