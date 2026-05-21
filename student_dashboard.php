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
    $data = htmlspecialchars($data);
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
// $year and $semester are still available for header display.
$year = intval($user['year']);  
$semester = intval($user['semester']);  

/**
 * Fetch ALL courses for the program to display in the main table.
 */
$stmt_courses = $conn->prepare("SELECT id, code, name, description, year, semester  
                              FROM courses  
                              WHERE program = ?  
                              ORDER BY year ASC, semester ASC, code ASC");

$enrolledCourses = [];
$totalCourses = 0; // Initialize total courses count

if (!$stmt_courses) {
    error_log("Prepare failed: " . $conn->error);
} else {
    $stmt_courses->bind_param("s", $program);  
    $stmt_courses->execute();
    $result_courses = $stmt_courses->get_result();

    if ($result_courses->num_rows > 0) {
        $totalCourses = $result_courses->num_rows; // Update total courses count
        while ($row = $result_courses->fetch_assoc()) {
            $enrolledCourses[] = $row;
        }
    }
    $stmt_courses->close();
}

/**
 * START ANALYTICS ADDITION
 * 1. Progress Logic Fix:
 * - The 'user_course' table is missing/faulty, causing a FATAL ERROR.  
 * - Revert the completed course count to a safe placeholder of 0.
 */
$completedCoursesCount = 0; // <<<--- REVERTED TO SAFE PLACEHOLDER TO PREVENT FATAL ERROR

// Calculate Progress Percentage based on the variables now set (0 or actual count)
$progressPercent = 0;
if ($totalCourses > 0) {
    // This will calculate 0% until the correct progress logic is restored.
    $progressPercent = round(($completedCoursesCount / $totalCourses) * 100);  
}

/**
 * 2. Units for Current Semester/Year KPI (SAFE LOGIC)
 * - This query only relies on the existing 'courses' table.
 */
$currentSemesterUnitsCount = 0;
if ($conn->ping()) {
    $stmt_current_units = $conn->prepare("
        SELECT COUNT(id) as current_count
        FROM courses
        WHERE program = ? AND year = ? AND semester = ?
    ");
    
    if ($stmt_current_units) {
        $stmt_current_units->bind_param("sii", $program, $year, $semester);
        $stmt_current_units->execute();
        $result_current_units = $stmt_current_units->get_result();
        if ($row_current_units = $result_current_units->fetch_assoc()) {
            $currentSemesterUnitsCount = intval($row_current_units['current_count']);
        }
        $stmt_current_units->close();
    } else {
        error_log("Current units query failed: " . $conn->error);
    }
}
// END ANALYTICS ADDITION

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEAU Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
   <style>
/* ===============================
    RESET & BASE
    ================================= */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background-color: #E8F5E8; /* Light Green Background */
        color: #333;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        font-size: 0.85rem;
    }

    /* THEME COLORS */
    .teaus-primary {
        background-color: #4CAF50; /* Green */
        color: white;
    }

    .teaus-secondary {
        background-color: #2E7D32; /* Darker Green */
        color: white;
    }

    .teaus-yellow-accent {
        color: #FFEB3B;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    }

/* ===============================
    SIDEBAR
    ================================= */
    .sidebar {
        width: 220px;
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
        transition: transform 0.3s ease-in-out, width 0.3s ease; /* Added width transition */
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

/* ===============================
    MAIN CONTENT
    ================================= */
    .main {
        margin-left: 220px; /* Default Desktop Margin */
        padding: 15px;
        flex: 1;
        transition: margin-left 0.3s ease-in-out;
    }

/* ===============================
    HEADER - ENHANCED
    ================================= */
    .header {
        background: #ffffff;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 6px 16px rgba(46, 125, 50, 0.15), 0 0 4px rgba(46, 125, 50, 0.05);
        margin-bottom: 15px;
        border-left: 5px solid #4CAF50;
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .header h2 {
        font-size: 1.6rem;
        font-weight: 700;
        color: #2E7D32;
        margin: 0;
        margin-right: auto;
    }

    .header .user-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    /* Base styles for the info blocks (Overridden below for small screens) */
    .header p {
        margin: 0;
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.8rem;
    }

    .header .program-info {
        background-color: #E8F5E8;
        color: #2E7D32;
        border: 1px solid #C8E6C9;
    }

    .header .status-info {
        background-color: #E8F5E8;
        color: #2E7D32;
        border: 1px solid #C8E6C9;
    }

/* ===============================
    NAVBAR TOGGLE (Hamburger)
    ================================= */
    .navbar-toggle {
        display: none;
        background: none;
        border: none;
        color: #4CAF50;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 5px;
    }

/* ===============================
    OVERLAY (Mobile Sidebar)
    ================================= */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 999;
        display: none;
    }

    .sidebar-overlay.show {
        display: block;
    }

/* ===============================
    PROGRESS CARD (MODIFIED for visual & height reduction)
    ================================= */
    .progress-card {
        background: #ffffff;
        border-radius: 10px;
        padding: 15px; /* Reduced padding */
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-left: 5px solid #4CAF50;
        transition: transform 0.3s ease;
        height: 100%; /* Important for alignment in the row */
        display: flex;
        flex-direction: column;
    }

    .progress-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(46, 125, 50, 0.2);
    }

    .progress-card h3 {
        color: #2E7D32;
        margin-bottom: 10px; /* Reduced margin */
        font-size: 1.15rem; /* Reduced font size */
        border-bottom: 2px solid #4CAF50;
        padding-bottom: 3px;
    }

    .progress-summary p {
        margin: 0;
        font-size: 0.85rem; /* Reduced font size */
        color: #555;
        font-weight: 500;
    }

    .progress-card a.btn {
        margin-top: auto; /* Push button to the bottom */
        font-size: 0.8rem; /* Smaller button text */
    }

    /* Progress Bar Styling */
    .progress {
        height: 1.2rem; /* Reduced bar height */
        border-radius: 8px;
        background-color: #e9ecef; /* Light gray background */
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        margin: 10px 0 !important; /* Reduced margin around progress bar */
    }

    .progress-bar-success {
        background-color: #4CAF50; /* Green color */
        color: white;
        font-weight: bold;
        line-height: 1.2rem; /* Adjusted line-height */
        font-size: 0.75rem; /* Reduced percentage text size */
    }

    .progress-card .text-muted.small {
        font-size: 0.75rem; /* Smaller detail text */
        margin-bottom: 5px; /* Reduced margin */
    }
    
    .progress-card hr.my-3 {
        margin-top: 10px !important;
        margin-bottom: 10px !important;
    }

/* ===============================
    ANALYTICS CARD (NEW STYLING - Height reduced)
    ================================= */
    .kpi-card {
        background: #ffffff;
        border-radius: 10px;
        padding: 15px; /* Reduced padding */
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-left: 5px solid #2E7D32; /* Use a darker green for contrast */
        transition: transform 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(46, 125, 50, 0.2);
    }

    .kpi-card h4 {
        color: #4CAF50;
        margin-bottom: 5px;
        font-size: 1.15rem; /* Increased size to match progress card H3 */
        font-weight: 500;
    }

    .kpi-card .kpi-value {
        font-size: 2rem; /* Reduced large number size */
        font-weight: 700;
        color: #2E7D32;
        line-height: 1;
        margin-bottom: 5px; /* Reduced margin */
    }

    .kpi-card .kpi-detail {
        font-size: 0.8rem; /* Reduced detail text size */
        color: #555;
        margin-top: auto; /* Push detail to the bottom */
    }
    
    .kpi-card hr.my-3 {
        margin-top: 10px !important;
        margin-bottom: 10px !important;
    }
    
    .kpi-card a.btn {
        font-size: 0.8rem; /* Smaller button text */
    }

/* ===============================
    CARDS - ENHANCED (Existing)
    ================================= */
    .card {
        background: white;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 6px 16px rgba(46, 125, 50, 0.1), 0 0 4px rgba(46, 125, 50, 0.05);
        margin-bottom: 15px;
        border: 1px solid #E8F5E8;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(46, 125, 50, 0.2);
    }

    .card h3 {
        color: #4CAF50;
        margin-bottom: 10px;
        border-bottom: 2px solid #4CAF50;
        padding-bottom: 3px;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
    }

    /* Table Styling */
    table thead.table-dark {
        background-color: #2E7D32 !important;
        color: #fff !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    table thead.table-dark th {
        background-color: #2E7D32 !important;
        color: #fff !important;
        border-color: #1b5e20 !important;
    }

    /* Ensure table card takes up available height */
    .table-container {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

/* ===============================
    DATATABLES COLUMN CONTROL (Optimized Description)
    ================================= */
    .description-column {
        max-width: 250px; /* Default width */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: default;
        padding-right: 15px;
    }

/* ===============================
    DATATABLES FIXES (Retained)
    ================================= */
    .dataTables_wrapper .row:first-child,
    .dataTables_wrapper .row:last-child {
        max-width: none !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding: 0 10px;
    }

    .dataTables_wrapper .dataTables_filter {
        text-align: right;
        width: 100%;
        padding: 5px 0;
    }

    .dataTables_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 5px;
        width: 100%;
        margin: 0;
    }

    .dataTables_wrapper .dataTables_filter input {
        max-width: 150px;
        height: calc(1.5em + 0.5rem + 2px);
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }

/* ===============================
    6. FOOTER
    ================================= */
    .footer {
        background-color: #4CAF50;
        color: white;
        text-align: center;
        padding: 10px;
        margin-top: auto;
        font-size: 0.8rem;
        /* Default: Sidebar width (220px) + Main Left Padding (15px) = 235px */
        margin-left: 235px; /* FIX: Adjusted for default desktop */
        transition: margin-left 0.3s ease-in-out; /* Match .main transition */
    }

    /* FIX: Add container to center footer content on large screens */
    .footer .container {
        max-width: 1600px;
        margin-left: auto;
        margin-right: auto;
    }

    .footer .social-icons a {
        color: white;
        margin: 0 8px;
        text-decoration: none;
        font-size: 1.1rem;
        transition: color 0.3s;
    }

    .footer .social-icons a:hover {
        color: #FFEB3B;
    }


/* ===============================
    RESPONSIVE MEDIA QUERIES (REVISED)
    ================================= */

    /* FIX: Apply Max-Width to Content on Large Screens for better visual grouping */
    /* This centers the content and prevents it from spanning the whole width of a super-wide monitor. */
    .header,
    .row {
        max-width: 1600px; /* Increased max-width for super-wide screens */
        margin-left: auto;
        margin-right: auto;
    }

    /* --- NEW BREAKPOINT: Extra-Large Desktop (> 1400px) --- */
    @media (min-width: 1401px) {
        body {
            font-size: 0.95rem;
        }

        .sidebar {
            width: 280px; /* Larger Sidebar */
        }

        .main {
            margin-left: 280px; /* Corresponding margin for main content */
            padding: 20px;
        }

        /* FIX: Adjust footer margin to match the larger sidebar width + main padding (280 + 20 = 300px) */
        .footer {
            margin-left: 300px; /* Corrected to align with main content */
        }

        .dataTables_wrapper .dataTables_filter input {
            max-width: 250px;
        }

        /* Use more horizontal space in the table */
        .description-column {
            max-width: 400px; /* Increased column width for better readability */
        }
    }

    /* Large Desktop (1201px - 1400px) */
    @media (min-width: 1201px) and (max-width: 1400px) {
        .sidebar {
            width: 250px;
        }

        .main {
            margin-left: 250px;
        }

        /* FIX: Adjust footer margin to match the sidebar width + main padding (250 + 15 = 265px) */
        .footer {
            margin-left: 265px; /* Corrected to align with main content */
        }

        .dataTables_wrapper .dataTables_filter input {
            max-width: 200px;
        }

        /* Slightly increase column width */
        .description-column {
            max-width: 300px;
        }
    }


    /* Desktop (992px - 1200px) */
    @media (min-width: 992px) and (max-width: 1200px) {
        .sidebar {
            width: 200px;
        }

        .main {
            margin-left: 200px;
            padding: 12px;
        }

        /* FIX: Adjust footer margin to match the sidebar width + main padding (200 + 12 = 212px) */
        .footer {
            margin-left: 212px; /* Corrected to align with main content */
        }

        /* Keep the centering and max-width on elements *inside* .main */
        .header,
        .row {
            max-width: 1600px; /* Reapply max-width to the rows for centering */
            margin-left: auto;
            margin-right: auto;
        }

        .dataTables_wrapper .dataTables_filter input {
            max-width: 180px;
        }
    }

    /* * MOBILE/TABLET STYLING (Max-width 991px and below)
     *
     */
    @media (max-width: 991px) {
        /* HIDE DEFAULT SIDEBAR, SHOW TOGGLE, SHIFT MAIN CONTENT */
        .navbar-toggle {
            display: block;
            order: -1; /* Always put the toggle first */
        }

        .sidebar {
            transform: translateX(-100%); /* Hide sidebar by default */
            width: 250px;
        }

        .main {
            margin-left: 0; /* Remove left margin from sidebar */
            padding: 10px;
            font-size: 0.8rem;
        }

        /* FIX: Reset footer margin on mobile so it spans full width */
        .footer {
            margin-left: 0;
        }

        /* Ensure footer content is centered without max-width interference on mobile */
        .footer .container {
            max-width: none;
        }

        /* Remove max-width on mobile to use all screen space */
        .header,
        .row {
            max-width: none;
            margin-left: 0;
            margin-right: 0;
        }

        /* HEADER STYLING TO MATCH IMAGE (Hamburger + Welcome + 2 Boxes) */
        .header {
            /* Allow content to wrap, but reset main alignment to be on the same line */
            flex-direction: row;
            align-items: center; /* Align items vertically (hamburger and H2) */
            gap: 15px;
            padding: 15px;
            flex-wrap: wrap; /* Important: allows user-meta to wrap below */
        }

        .header h2 {
            /* Welcome text is no longer forced to 100% width, allowing it to be adjacent */
            width: auto;
            order: 0; /* Let H2 follow the toggle */
            text-align: left;
            font-size: 1.4rem;
            margin-right: auto; /* Push remaining content (user-meta) to the end of the line/wrap */
        }

        .header .user-meta {
            /* Make the blocks lay side-by-side on their own wrapped line */
            width: 100%;
            order: 2; /* Force to the next line after the toggle/H2 */
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            gap: 15px;
            margin-top: 10px; /* Space between H2 and blocks */
        }

        /* STYLING FOR PROGRAM/YEAR BLOCKS */
        .header .program-info,
        .header .status-info {
            flex: 1 1 45%;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px 10px;
            border-radius: 8px;
            text-align: center;
            font-size: 0.85rem;
            font-weight: 600;
            min-height: 20px;
        }

        /* Make the Current Year block solid green as per the image */
        .header .status-info {
            background-color: #4CAF50;
            color: white;
            border: 1px solid #2E7D32;
        }
        .header .status-info i {
            color: white;
        }

        /* Quick Access Card styling adjustment for smaller screens */
        .card > div.d-flex.flex-wrap.gap-3 {
            justify-content: center;
        }

        .dataTables_wrapper .dataTables_filter input {
            max-width: 120px;
        }
    }

    /* Small Mobile (<=575px - Ensures the two boxes stack on the smallest screens) */
    @media (max-width: 575px) {
        .header .user-meta {
            /* Force vertical stacking on small phones */
            flex-direction: column;
            gap: 10px;
        }

        .header .program-info,
        .header .status-info {
            flex: 1 1 100%; /* Full width when stacked */
            font-size: 0.75rem;
        }

        /* HIDE THE DESCRIPTION COLUMN (6th column) - Critical for table space */
        #myEnrolledCoursesTable th:nth-child(6),
        #myEnrolledCoursesTable td:nth-child(6) {
            display: none;
        }

        /* Further reduce the table text size if necessary */
        #myEnrolledCoursesTable {
            font-size: 0.7rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            max-width: 100px;
        }
    }
</style>

</head>

<body>
    <nav class="sidebar" id="sidebar">
        <h2><i class="fas fa-university"></i> TEAU Portal</h2>
        <ul>
            <li><a href="student_dashboard.php" class="active"><i class="fas fa-home me-2"></i>Dashboard</a></li>
            <li><a href="MyUnits.php"><i class="fas fa-book-open me-2"></i>Enrolled Units</a></li>
            <li><a href="Progress.php"><i class="fas fa-rocket me-2"></i>Progress</a></li>
            <li><a href="studentCourseFinder.php"><i class="fas fa-comments me-2"></i>CourseFinder</a></li>
            <li><a href="academic_calendar.php"><i class="fas fa-calendar-alt me-2"></i>Academic Calendar</a></li>
            <li><a href="profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
        </ul>
    </nav>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main">

        <div class="header bg-white p-3 mb-3 rounded shadow-sm border-start border-success">
            <button class="navbar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h2>Welcome <span class="text-success"><?php echo htmlspecialchars($fullName); ?></span>!</h2>
            
            <div class="user-meta">
                <p class="program-info"><i class="fas fa-graduation-cap me-1"></i> Program: <?php echo htmlspecialchars($program); ?></p>
                <p class="status-info"><i class="fas fa-calendar-alt me-1"></i> Current Year: Year <?php echo htmlspecialchars($year); ?>, Sem <?php echo htmlspecialchars($semester); ?></p>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card h-100 d-flex flex-column">  
                    <h3><i class="fas fa-link me-2"></i>Quick Access</h3>
                    <div class="d-flex flex-wrap gap-3">  
                        <a href="studentCourseFinder.php" class="btn btn-outline-success"><i class="fas fa-comments me-2"></i>CourseFinder</a>
                        <a href="MyUnits.php" class="btn btn-outline-success"><i class="fas fa-clipboard-list me-1"></i> Enrolled Units</a>
                        <a href="academic_calendar.php" class="btn btn-outline-success"><i class="fas fa-calendar-alt me-1"></i> Academic Calendar</a>
                        <a href="Progress.php" class="btn btn-outline-success"><i class="fas fa-rocket me-2"></i>Progress</a>
                        <a href="profile.php" class="btn btn-outline-success"><i class="fas fa-user-edit me-1"></i> Update Profile</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            
            <div class="col-sm-12 col-md-6 mb-3 mb-md-0">
                <div class="progress-card">
                    <h3><i class="fas fa-chart-line me-2"></i>Overall Program Status</h3>
                    
                    <div class="progress-summary">
                        <p>
                            <i class="fas fa-check-circle text-success me-1"></i>
                            Units Completed: <strong><?php echo $completedCoursesCount; ?></strong> of <?php echo $totalCourses; ?>
                        </p>
                    </div>

                    <div class="progress my-3" role="progressbar" aria-label="Program Progress" aria-valuenow="<?php echo $progressPercent; ?>" aria-valuemin="0" aria-valuemax="100">
                      <div class="progress-bar progress-bar-success" style="width: <?php echo $progressPercent; ?>%">
                        <?php echo $progressPercent; ?>%
                      </div>
                    </div>

                    <p class="text-muted small">
                        <?php echo ($totalCourses > 0 && $progressPercent == 100) ? 'Congratulations, you have completed your program requirements!' : 'Continue your progress toward graduation.'; ?>
                    </p>
                    <hr class="my-3">
                    <a href="Progress.php" class="btn btn-sm btn-outline-success w-100"><i class="fas fa-arrow-right me-1"></i> View Detailed Progress</a>
                </div>
            </div>

            <div class="col-sm-12 col-md-6">
                <div class="kpi-card">
                    <h4><i class="fas fa-clipboard-list me-2"></i>Current Semester Units</h4>
                    <div class="kpi-value"><?php echo $currentSemesterUnitsCount; ?></div>
                    <p class="kpi-detail">
                        Total units scheduled for Year <?php echo htmlspecialchars($year); ?>, Sem <?php echo htmlspecialchars($semester); ?>.
                    </p>
                    <hr class="my-3">
                    <a href="MyUnits.php?year=<?php echo htmlspecialchars($year); ?>&semester=<?php echo htmlspecialchars($semester); ?>" class="btn btn-sm btn-outline-secondary w-100"><i class="fas fa-eye me-1"></i> View Unit Schedule</a>
                </div>
            </div>
            
        </div>
        <div class="row mb-3">
            <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card table-container">
                    <h3><i class="fas fa-book-open me-2"></i>Full Program Course Units: <?php echo htmlspecialchars($program); ?> (All Years)</h3>
                    <div class="table-responsive">
                        <table id="myEnrolledCoursesTable" class="table table-striped" style="width:100%">
                            <thead class="table-dark">
                                <tr>
                                    <th>id</th>
                                    <th>Year</th>  
                                    <th>Sem</th>  
                                    <th>Code</th>
                                    <th>Unit Name</th>
                                    <th class="description-column">Description</th>  
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($enrolledCourses)) {
                                    foreach ($enrolledCourses as $course) {
                                ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($course['id']); ?></td>
                                                <td><?php echo htmlspecialchars($course['year']); ?></td>
                                                <td><?php echo htmlspecialchars($course['semester']); ?></td>
                                                <td><?php echo htmlspecialchars($course['code']); ?></td>
                                                <td><?php echo htmlspecialchars($course['name']); ?></td>
                                                <td class="description-column" title="<?php echo htmlspecialchars($course['description']); ?>"><?php echo htmlspecialchars($course['description']); ?></td>  
                                            </tr>
                                <?php
                                    }
                                } else {
                                ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No courses found for this program across all years.</td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="footer mt-auto py-3">
        <div class="container text-center">
            <p>Created with ❤️ for TEAU. &copy; 2025.</p>
            <div class="social-icons mt-2">
                <a href="#" class="text-white"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                <a href="#" class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>
    
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Sidebar toggle FIX
        const sidebar = document.getElementById("sidebar");
        const sidebarToggle = document.getElementById("sidebarToggle");
        const sidebarOverlay = document.getElementById("sidebarOverlay");

        // Function to toggle sidebar visibility
        function toggleSidebar() {
            sidebar.classList.toggle("show");
            sidebarOverlay.classList.toggle("show");
        }

        // Add event listeners
        sidebarToggle.addEventListener("click", toggleSidebar);
        sidebarOverlay.addEventListener("click", toggleSidebar);

        // Initialize DataTable with static pageLength of 10
        $(document).ready(function() {
            
            // Set pageLength to 10 for all screens
            const pageLength = 10;  

            $('#myEnrolledCoursesTable').DataTable({
                "pageLength": pageLength,  
                "lengthChange": false,
                "searching": true,
                "ordering": true,
                "info": false
            });
            
        });
    </script>
</body>

</html>