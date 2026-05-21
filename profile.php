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

// Check if user is an Admin, and redirect them if they are
if (isset($_SESSION['userType']) && $_SESSION['userType'] === 'Admin') {
    header("Location: adminProfile.php");
    exit();
}

// Include a function to sanitize user input
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Fetch user data from the database using the stored user ID in the session
$id = $_SESSION['id'];

// CRITICAL FIX: Fetch student-specific fields (program, year, semester)
$stmt = $conn->prepare("SELECT id, fullName, userType, program, year, semester, email FROM user WHERE id = ?");
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
$studentId = $user['id'];
$fullName = sanitize_input($user['fullName']);
$userType = sanitize_input($user['userType']);
$program = sanitize_input($user['program']);
$year = sanitize_input($user['year']);
$semester = sanitize_input($user['semester']);
$email = sanitize_input($user['email']);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEAU Student Portal - My Profile</title>

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
            --sidebar-width: 250px;
            --active-yellow: #FFEB3B;
            --text-dark: #333;
        }

        /* Helper classes matching the theme */
        .teaus-primary { background-color: var(--primary-green); color: white; }
        .teaus-secondary { background-color: var(--dark-green); color: white; }
        .teaus-yellow-accent { color: var(--active-yellow); text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3); }

        /* ===============================
        2. BASE & UTILITIES
        ================================= */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            font-size: 0.9rem;
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
        
        .navbar-toggle {
            /* Default state: hidden on desktop */
            display: none; 
            background: none;
            border: none;
            color: var(--primary-green);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
        }


        /* ===============================
        4. MAIN CONTENT & HEADER
        ================================= */
        .main {
            margin-left: var(--sidebar-width);
            padding: 15px;
            flex: 1;
            transition: margin-left 0.3s ease-in-out;
            min-width: 300px;
            width: auto;
        }
        
        .header {
            background: var(--card-bg);
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: flex-start;
            border-left: 4px solid var(--primary-green);
            position: relative; 
        }

        .header h2 {
            font-size: 1.5rem;
            color: var(--dark-green);
            margin: 0;
            font-weight: 600;
        }
        
        /* For desktop, push the subtitle to the far right */
        .header p {
            margin: 0;
            font-size: 0.85rem;
            color: #6c757d;
            margin-left: auto; /* Push to the right on desktop */
        }
        
        .header .spacer { 
            display: none; 
        }

        /* ===============================
        5. CARD STYLING (Profile Content)
        ================================= */
        .card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.1);
            border: 1px solid #e0e0e0;
        }

        .card h3 {
            color: var(--dark-green);
            margin-bottom: 12px;
            border-bottom: 2px solid var(--primary-green);
            padding-bottom: 5px;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .list-group-flush .list-group-item {
            border: none;
            padding: 8px 0;
            border-bottom: 1px dashed #e0e0e0;
            background-color: transparent;
            font-size: 0.9rem;
        }
        
        .list-group-flush .list-group-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .list-group-item strong {
            width: 120px;
            display: inline-block;
            color: var(--dark-green);
            font-weight: 600;
        }
        
        .badge.bg-success {
            background-color: var(--primary-green) !important;
            padding: 5px 10px;
            font-weight: 400;
        }
        
        .btn {
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        .btn.teaus-primary {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
            color: white;
            transition: all 0.3s;
        }
        
        .btn.teaus-primary:hover {
            background-color: var(--dark-green);
            border-color: var(--dark-green);
            color: white;
        }
        
        .btn.btn-outline-danger {
            transition: all 0.3s;
        }

        /* ===============================
        6. FOOTER
        ================================= */
        .footer {
            background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 8px 10px;
            margin-top: auto;
            font-size: 0.75rem;
            transition: margin-left 0.3s ease-in-out;
        }

        /* ===============================
        7. RESPONSIVE MEDIA QUERIES
        ================================= */
        
        /* Mobile (max-width: 991.98px) */
        @media (max-width: 991.98px) {
            .main {
                padding: 10px;
                margin-left: 0;
            }

            .sidebar {
                transform: translateX(-100%); 
                width: 280px;
            }

            .sidebar.show {
                transform: translateX(0); 
            }
            
            /* FIX 1: Make navbar-toggle part of the flex flow, not absolute */
            .navbar-toggle {
                display: block; 
                position: static; 
                transform: none;
                order: -3; /* Force it to the start of the flex container */
                margin-right: 10px; /* Space between the button and the title */
                width: auto;
            }
            
            .footer {
                margin-left: 0;
            }

            /* FIX 2: Reset header to flex row for side-by-side layout on small screens */
            .header {
                min-height: auto; 
                padding: 10px 15px;
                flex-direction: row; /* Back to row layout */
                justify-content: flex-start; /* Align content to the left */
                align-items: center; 
                gap: 5px; /* Reduce gap */
            }
            
            .header h2 {
                /* Allow the heading to grow to push the paragraph away */
                flex-grow: 1; 
                text-align: left;
                font-size: 1.2rem;
                margin-bottom: 0; 
            }

            /* FIX 3: Push the subtitle paragraph to the far right */
            .header p {
                display: block;
                margin-left: auto; 
                white-space: nowrap;
                font-size: 0.75rem;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
        }
        
        /* Tablet (min-width: 992px) - Desktop */
        @media (min-width: 992px) {
            .navbar-toggle {
                display: none; 
            }
            /* Ensure desktop header p is pushed right */
            .header p {
                margin-left: auto;
            }
            .header h2 {
                flex-grow: 0;
            }
        }

        /* Small Mobile Adjustments (<= 575.98px) */
        @media (max-width: 575.98px) {
            .card {
                padding: 15px;
            }
            .card h3 {
                font-size: 1.1rem;
            }
            .list-group-item strong {
                display: block;
                width: 100%;
                margin-bottom: 5px;
            }
            .list-group-item {
                font-size: 0.8rem;
                padding: 6px 0;
            }
            .btn {
                width: 100%;
                margin-bottom: 8px;
            }
            .mt-4.text-center {
                display: flex;
                flex-direction: column;
            }
            /* FIX 4: On smallest screens, hide the subtitle paragraph completely */
            .header p {
                display: none;
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
            <li><a href="studentCourseFinder.php"><i class="fas fa-comments me-2"></i>CourseFinder</a></li>
            <li><a href="academic_calendar.php"><i class="fas fa-calendar-alt me-2"></i>Academic Calendar</a></li>
            <li><a href="profile.php"  class="active"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
        </ul>
    </nav>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main">
        <div class="header">
            <button class="navbar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            
            <h2 class="text-success">Student Profile</h2>
            
            <p class="text-muted">Review and manage your personal and academic account details.</p>
        </div>

        <div class="row">
            <div class="col-12 col-lg-8 offset-lg-2">
                <div class="card">
                    <h3><i class="fas fa-user-graduate me-3"></i>Student Details</h3>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Full Name:</strong> <?php echo htmlspecialchars($fullName); ?></li>
                        <li class="list-group-item"><strong>Student ID:</strong> <?php echo htmlspecialchars($studentId); ?></li>
                        <li class="list-group-item"><strong>User Role:</strong> <span class="badge bg-success"><?php echo htmlspecialchars($userType); ?></span></li>
                        <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></li>
                    </ul>

                    <h3 class="mt-4"><i class="fas fa-school me-3"></i>Academic Information</h3>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Program/Course:</strong> <?php echo htmlspecialchars($program); ?></li>
                        <li class="list-group-item"><strong>Current Year:</strong> <?php echo htmlspecialchars($year); ?></li>
                        <li class="list-group-item"><strong>Current Semester:</strong> <?php echo htmlspecialchars($semester); ?></li>
                    </ul>

                    <h3 class="mt-4"><i class="fas fa-cog me-3"></i>Account Actions</h3>

                    <div class="mt-4 text-center">
                        <a href="edit_profile.php" class="btn teaus-primary mx-2"><i class="fas fa-edit me-2"></i>Edit Profile Info</a>
                        <a href="change_password.php" class="btn btn-outline-danger mx-2"><i class="fas fa-key me-2"></i>Change Password</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    

    <footer class="footer mt-auto py-3" id="mainFooter">
        <div class="container text-center">
            <p>Created with <i class="fas fa-heart text-danger"></i> for TEAU. &copy; <?php echo date("Y"); ?>.</p>
            <div class="social-icons mt-2">
                <a href="#" class="text-white"><i class="fab fa-facebook-f mx-2"></i></a>
                <a href="#" class="text-white"><i class="fab fa-twitter mx-2"></i></a>
                <a href="#" class="text-white"><i class="fab fa-instagram mx-2"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /**
         * Sidebar Toggle Logic & Responsive Layout Adjustments
         */
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById("sidebar");
            const sidebarToggle = document.getElementById("sidebarToggle");
            const sidebarOverlay = document.getElementById("sidebarOverlay");
            const mainContent = document.querySelector('.main');
            const mainFooter = document.getElementById('mainFooter');
            const sidebarWidth = '250px'; // Match CSS variable

            function updateLayout() {
                // Only adjust for desktop
                if (window.innerWidth >= 992) {
                    mainContent.style.marginLeft = sidebarWidth;
                    mainFooter.style.marginLeft = sidebarWidth;
                } else {
                    mainContent.style.marginLeft = '0';
                    mainFooter.style.marginLeft = '0';
                }
            }

            function toggleSidebar() {
                sidebar.classList.toggle("show");
                sidebarOverlay.classList.toggle("show");
            }

            if (sidebarToggle && sidebar && sidebarOverlay) {
                sidebarToggle.addEventListener("click", toggleSidebar);
                sidebarOverlay.addEventListener("click", toggleSidebar);
                window.addEventListener("resize", updateLayout);
            }
            
            // Initial layout update
            updateLayout();
        });
    </script>
</body>

</html>