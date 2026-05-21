<?php
// Include the database connection and configuration
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

// Generate the programCode from the program name.
$programCode = strtoupper(implode('', array_map(function($word) {
    return $word[0];
}, array_filter(explode(' ', str_replace(array('of', 'in', 'and', 'bachelor', 'master'), '', strtolower($program)))))));
if (strlen($programCode) < 2) {
    $programCode = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $program), 0, 4));
}


$year = intval($user['year']);  
$semester = intval($user['semester']);  

/**
 * Fetch the COUNT of courses for the program to display in the card.
 */
$stmt_courses = $conn->prepare("SELECT COUNT(id) AS total_courses
                             FROM courses 
                             WHERE program = ?");

if (!$stmt_courses) {
    error_log("Prepare failed: " . $conn->error);
    $totalCourses = 0;
} else {
    $stmt_courses->bind_param("s", $program);  
    $stmt_courses->execute();
    $result_courses = $stmt_courses->get_result();

    $totalCourses = 0;
    if ($row = $result_courses->fetch_assoc()) {
        $totalCourses = $row['total_courses'];
    }

    $stmt_courses->close();
}

// --- PROGRESS CALCULATION (Example Only) ---
$totalYears = 4; // Example total length of the program
$totalSemesters = 2; // Semesters per year
$currentSemesterIndex = (($year - 1) * $totalSemesters) + $semester;
$maxSemesters = $totalYears * $totalSemesters;
$progressPercentage = min(100, round(($currentSemesterIndex / $maxSemesters) * 100));

// --- ADDED: DEMO CONTACT INFO ---
$advisorName = "Dr. Elara Vance";
$advisorEmail = "e.vance@teau.edu";
$deptHeadName = "Prof. Marcus Bell";
$deptHeadOffice = "Admin Block 301";


$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEAU Student Dashboard - Program Overview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ===============================
            1. COLOR PALETTE & VARIABLES
        ================================= */
        :root {
            --primary-green: #4CAF50; /* Green */
            --dark-green: #2E7D32; /* Darker Green */
            --light-bg: #E8F5E8; /* Light Green Background */
            --card-bg: #ffffff;
            --active-yellow: #FFEB3B;
            --sidebar-width: 220px;
            /* NEW: Define the space between sidebar and content */
            --gap-width: 15px; 
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
            /* FIX: Use flexbox for the main layout to align sidebar and content wrapper */
            display: flex; 
            font-size: 0.85rem;
            overflow-x: hidden; 
        }
        
        /* NEW: Wrapper for all content that should sit beside the fixed sidebar */
        .content-wrapper {
            flex: 1; /* Allows the wrapper to fill the remaining space (everything to the right of the sidebar) */
            display: flex;
            flex-direction: column; /* Stacks main and footer vertically */
            min-height: 100vh; /* Ensures the wrapper takes full height */
            /* FIX: Push content over by the width of the sidebar PLUS the gap on desktop */
            margin-left: calc(var(--sidebar-width) + var(--gap-width)); 
            transition: margin-left 0.3s ease-in-out;
            overflow-x: hidden;
        }

        /* --- Main Content Layout (UPDATED) --- */
        .main {
            padding: 15px;
            flex: 1; /* Allows main content to grow and push the footer down */
        }
        
        .row {
            margin: 0 auto;
        }

        /* ===============================
            3. SIDEBAR
        ================================= */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(to bottom, var(--dark-green), var(--primary-green));
            color: white;
            height: 100vh;
            padding: 15px 0;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            border-radius: 0 12px 12px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
            z-index: 1050;
            transform: translateX(0); /* Default desktop position */
            transition: transform 0.3s ease-in-out;
        }

        .sidebar h2 {
        text-align: left;
        margin-bottom: 20px;
        margin-left:20px;
        font-size: 1.25rem;
        color: #fff;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: #e9ecef;
            text-decoration: none;
            border-radius: 6px;
            margin: 0 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .sidebar a:hover {
            background-color: #388E3C;
            color: white;
        }

        .sidebar a.active-page {
            background-color: var(--active-yellow);
            color: var(--dark-green);
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* Sidebar Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            display: none;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.show {
            display: block;
        }


        /* ===============================
            4. HEADER
        ================================= */
        .header {
            background: var(--card-bg);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 6px 16px rgba(46, 125, 50, 0.15);
            margin-bottom: 20px;
            border-left: 5px solid var(--primary-green);
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .header h2 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--dark-green);
            margin: 0;
            flex-grow: 1;
        }

        .header .user-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .header p {
            margin: 0;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.85rem;
            border: 1px solid #C8E6C9;
        }

        .header .program-info,
        .header .status-info {
            background-color: #E8F5E8;
            color: var(--dark-green);
        }

        /* Hamburger Menu Toggle */
        .navbar-toggle {
            display: none; /* Hidden on desktop */
            background: none;
            border: none;
            color: var(--dark-green);
            font-size: 1.8rem;
            cursor: pointer;
            padding: 5px;
            order: -1;
        }

        /* ===============================
            5. CARD STYLING
        ================================= */
        .program-card {
            background: linear-gradient(135deg, var(--card-bg), #f0fff0); 
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(46, 125, 50, 0.15);
            border: 2px solid var(--dark-green);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 20px;
        }
        
        .program-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(46, 125, 50, 0.3);
        }

        .program-card h3 {
            font-size: 2.2rem;
            color: var(--dark-green);
            margin-bottom: 10px;
            font-weight: 800;
        }

        .program-card .program-code {
            font-size: 1.2rem;
            color: var(--primary-green);
            display: block;
            margin-top: -10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .program-card .icon-large {
            font-size: 3.5rem;
            color: var(--primary-green);
            margin-bottom: 15px;
        }

        .program-card .course-count {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
            background-color: var(--dark-green);
            display: inline-block;
            padding: 8px 20px;
            border-radius: 30px;
            margin-top: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .progress-title {
            font-weight: 600;
            color: #1b5e20;
            margin-top: 25px;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .progress {
            height: 30px;
            border-radius: 15px;
            background-color: #d1e2d1;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            background-color: var(--primary-green);
            transition: width 0.6s ease;
            font-weight: 600;
            line-height: 30px;
            border-radius: 15px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        /* --- Action Cards --- */
        .action-card {
            border: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-align: center;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: var(--card-bg);
            height: 100%;
        }

        .action-card:hover:not(.disabled) {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(46, 125, 50, 0.4);
            cursor: pointer;
        }
        
        .action-card.disabled {
            filter: grayscale(80%);
            opacity: 0.7;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .action-card.disabled:hover {
            transform: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .action-card .card-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--dark-green);
        }
        
        .action-card .coming-soon-text {
            color: #d9534f;
            font-weight: 700;
            display: block;
            font-size: 0.8rem;
            margin-top: 5px;
        }

        /* --- Contact Card --- */
        .contact-card {
            background-color: var(--card-bg);
            border: 1px solid #C8E6C9;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-top: 20px;
        }

        .contact-card h4 {
            color: var(--dark-green);
            font-weight: 600;
            margin-bottom: 15px;
            border-bottom: 2px solid var(--primary-green);
            padding-bottom: 8px;
        }

        .contact-card .list-group-item {
            border: none;
            padding: 10px 0;
            color: #555;
            font-size: 0.9rem;
            border-bottom: 1px dashed #eee;
        }
        .contact-card .list-group-item:last-child {
            border-bottom: none;
        }
        
        .contact-card .list-group-item a {
            font-size: 0.8rem;
        }
        .contact-card .list-group-item .text-muted {
            font-size: 0.8rem !important;
        }
        
        /* ===============================
            6. FOOTER STYLING
        ================================= */
        .footer {
             background-color: #4CAF50;
            color: #fff;
            text-align: center;
            font-size: 0.8rem;
            border-top: 3px solid var(--primary-green);
            margin-top: 40px; 
            width: 100%; 
        }
        
        .footer p {
            margin-bottom: 0.5rem;
        }

        .footer .social-icons a {
            color: #fff;
            font-size: 1.1rem;
            margin: 0 8px;
            transition: color 0.3s ease;
        }

        .footer .social-icons a:hover {
            color: var(--active-yellow);
        }


        /* ===============================
            7. RESPONSIVE MEDIA QUERIES
        ================================= */
        @media (max-width: 991px) {
            .header h2 {
                font-size: 1.4rem;
            }
            .program-card h3 {
                font-size: 2rem;
            }
        }

        @media (max-width: 767px) {
            /* Sidebar mobile position: hides it off-screen */
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
            }
            /* Sidebar mobile position: shows it on-screen */
            .sidebar.show {
                transform: translateX(0);
            }
            
            /* Main content adjustment for mobile */
            .content-wrapper {
                margin-left: 0; /* No offset needed on mobile */
                width: 100%;
            }

            .navbar-toggle {
                display: block; /* Show hamburger menu */
            }

            .header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding: 10px;
            }
            
            .header h2 {
                font-size: 1.2rem;
                flex-grow: 0;
                margin-right: auto;
            }
            
            .header .user-meta {
                order: 1;
                width: 100%;
                justify-content: space-between;
                margin-top: 10px;
            }
            .header p {
                flex-basis: 48%;
                text-align: center;
                padding: 4px 8px;
            }
        }
        
        @media (max-width: 575px) {
            .header h2 {
                font-size: 1rem;
            }
            .header .user-meta {
                flex-direction: column;
                gap: 5px;
            }
            .header p {
                flex-basis: 100%;
            }
            .program-card h3 {
                font-size: 1.5rem;
            }
            .progress-title {
                font-size: 1rem;
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
            <li><a href="Progress.php" class="active-page"><i class="fas fa-rocket me-2"></i>Progress</a></li>
            <li><a href="studentCourseFinder.php"><i class="fas fa-comments me-2"></i>CourseFinder</a></li>
            <li><a href="academic_calendar.php"><i class="fas fa-calendar-alt me-2"></i>Academic Calendar</a></li>
            <li><a href="profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>

        </ul>
    </nav>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="content-wrapper">
        <main class="main">
            <div class="container-fluid">
                
                <div class="header">
                    <button class="navbar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                    <h2>Program: <span class="text-success"><?php echo htmlspecialchars($program); ?></span></h2>
                    
                    <div class="user-meta">
                        <p class="program-info"><i class="fas fa-user me-1"></i> Student: <?php echo htmlspecialchars($fullName); ?></p>
                        <p class="status-info"><i class="fas fa-calendar-alt me-1"></i> Current Year: Year <?php echo htmlspecialchars($year); ?>, Sem <?php echo htmlspecialchars($semester); ?></p>
                    </div>
                </div>
        
                <div class="row">
                    <div class="col-sm-12 col-md-10 offset-md-1 col-lg-8 offset-lg-2">
                        <div class="program-card">
                            <i class="fas fa-graduation-cap icon-large"></i>
                            
                            <h3><?php echo htmlspecialchars($program); ?></h3>                       <span class="program-code">Code: <?php echo htmlspecialchars($programCode); ?></span>
                            
                            <p class="lead text-dark mt-4">
                                Your academic journey is on track! Review your status below.
                            </p>
        
                            <div class="progress-section mt-5">
                                <p class="progress-title">Academic Progress (Year <?php echo $year; ?> of <?php echo $totalYears; ?>)</p>
                                <div class="progress" role="progressbar" aria-label="Academic Progress" aria-valuenow="<?php echo $progressPercentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar" style="width: <?php echo $progressPercentage; ?>%">
                                        <?php echo $progressPercentage; ?>% Complete
                                    </div>
                                </div>
                            </div>
                            <div class="course-count mt-4">
                                <i class="fas fa-scroll me-2"></i> Total Courses in Program: <?php echo htmlspecialchars($totalCourses); ?>
                            </div>
                            
                            <p class="text-muted small mt-3">
                                Note: Progress is estimated based on current term completion.
                            </p>
                        </div>
                    </div>
                </div>
                
                <h3 class="text-center mt-4 mb-3 text-secondary" style="font-weight: 700;">
                    <i class="fas fa-layer-group me-2"></i> Quick Actions
                </h3>
                <div class="row mb-4 justify-content-center">
                    
                    <div class="col-md-4 col-sm-6 mb-3">
                        <a href="#" class="text-dark text-decoration-none">
                            <div class="action-card disabled">
                                <i class="fas fa-plus-square card-icon"></i>
                                <h5>Course Registration</h5>
                                <p class="small text-muted">Register for next semester's units.</p>
                                <span class="coming-soon-text">Coming Soon...</span>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-4 col-sm-6 mb-3">
                           <a href="#" class="text-dark text-decoration-none">
                                <div class="action-card disabled">
                                    <i class="fas fa-trophy card-icon"></i>
                                    <h5>View Final Grades</h5>
                                    <p class="small text-muted">Check your official course results.</p>
                                    <span class="coming-soon-text">Coming Soon...</span>
                                </div>
                            </a>
                    </div>
                    
                    <div class="col-md-4 col-sm-6 mb-3">
                        <a href="#" class="text-dark text-decoration-none">
                            <div class="action-card disabled">
                                <i class="fas fa-money-check-alt card-icon"></i>
                                <h5>Financial Statement</h5>
                                <p class="small text-muted">View your fee balance and payments.</p>
                                <span class="coming-soon-text">Coming Soon...</span>
                            </div>
                        </a>
                    </div>
                </div>
        
                <div class="row justify-content-center">
                    <div class="col-sm-12 col-md-10 col-lg-8">
                        <div class="contact-card">
                            <h4 class="mb-3"><i class="fas fa-phone-alt me-2"></i> Your Key Academic Contacts</h4>
                            <div class="list-group list-group-flush">
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div><i class="fas fa-user-graduate me-2 text-info"></i> Academic Advisor: <?php echo htmlspecialchars($advisorName); ?></div>
                                    <a href="mailto:<?php echo htmlspecialchars($advisorEmail); ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-envelope me-1"></i> Email</a>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div><i class="fas fa-building me-2 text-info"></i> Department Head: <?php echo htmlspecialchars($deptHeadName); ?></div>
                                    <span class="text-muted small">Office: <?php echo htmlspecialchars($deptHeadOffice); ?></span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div><i class="fas fa-id-card me-2 text-info"></i> Registrar's Office: </div>
                                    <span class="text-muted small">Ext. 4040 / registrar@teau.edu</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <footer class="footer mt-auto py-3">
            <div class="container-fluid text-center">
                <p>Created with ❤️ for TEAU. &copy; <?php echo date("Y"); ?>.</p>
                <div class="social-icons mt-2">
                    <a href="javascript:void(0)" title="Facebook"><i class="fab fa-facebook-f mx-2"></i></a>
                    <a href="javascript:void(0)" title="Twitter"><i class="fab fa-twitter mx-2"></i></a>
                    <a href="javascript:void(0)" title="Instagram"><i class="fab fa-instagram mx-2"></i></a>
                </div>
            </div>
        </footer>
    </div> <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle logic
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById("sidebar");
            const sidebarToggle = document.getElementById("sidebarToggle");
            const sidebarOverlay = document.getElementById("sidebarOverlay");

            function toggleSidebar() {
                // Use toggle for robust functionality
                sidebar.classList.toggle("show");
                sidebarOverlay.classList.toggle("show");
            }

            if (sidebarToggle && sidebar && sidebarOverlay) {
                sidebarToggle.addEventListener("click", toggleSidebar);
                sidebarOverlay.addEventListener("click", toggleSidebar);
            }
        });
    </script>
</body>

</html>