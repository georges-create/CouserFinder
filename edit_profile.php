<?php
// Include the database connection and configuration
// NOTE: Make sure config.php initializes the $conn object.
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

$id = $_SESSION['id'];
$error = $success = "";
$user = []; // Initialize user data array

// 1. Function to sanitize input
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// 2. Handle Form Submission (POST Request)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = sanitize_input($_POST['fullName']);
    $program = sanitize_input($_POST['program']);
    // Validate and sanitize integers
    $year = filter_var($_POST['year'], FILTER_VALIDATE_INT);
    $semester = filter_var($_POST['semester'], FILTER_VALIDATE_INT);
    $email = sanitize_input($_POST['email']);

    // Basic validation
    if (empty($fullName) || empty($email) || empty($program) || $year === false || $semester === false || $year < 1 || $semester < 1) {
        $error = "All fields are required and must be valid numbers.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Prepare an UPDATE statement
        $stmt = $conn->prepare("UPDATE user SET fullName = ?, email = ?, program = ?, year = ?, semester = ? WHERE id = ?");

        if ($stmt === false) {
            $error = "Database error: " . $conn->error;
        } else {
            // Correct bind_param types: s(string), s(string), s(string), i(int), i(int), i(int)
            $stmt->bind_param("sssiii", $fullName, $email, $program, $year, $semester, $id);

            if ($stmt->execute()) {
                $success = "Profile updated successfully! Redirecting to profile...";
                // Update session variables if necessary (e.g., fullName)
                $_SESSION['fullName'] = $fullName;
                // Since the update was successful, update $user array for immediate display
                $user = [
                    'fullName' => $fullName,
                    'email' => $email,
                    'program' => $program,
                    'year' => $year,
                    'semester' => $semester
                ];
                header("Refresh: 2; url=profile.php"); // Redirect after 2 seconds
            } else {
                $error = "Error updating profile: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// 3. Fetch Current User Data (GET Request or pre-fill form after POST failure)
// Only fetch if $user array is empty (i.e., not updated from a successful POST)
if (empty($user)) {
    if (isset($conn) && $conn->ping()) {
        $stmt = $conn->prepare("SELECT fullName, userType, program, year, semester, email FROM user WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $fetched_user = $result->fetch_assoc();
        $stmt->close();
    } else {
        // Handle case where connection might be lost before fetch
        session_destroy();
        header("Location: auth.php?error=db_lost");
        exit();
    }

    if (!$fetched_user) {
        session_destroy();
        header("Location: auth.php?error=user_not_found");
        exit();
    }
    // Set $user to the fetched data
    $user = $fetched_user;
}

// Close connection at the end of the PHP script
if (isset($conn)) $conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEAU Student Dashboard - Edit Profile</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ===============================
        1. RESET & BASE
        ================================= */
        :root {
            --primary-green: #4CAF50;
            --dark-green: #2E7D32;
            --light-bg: #E8F5E8;
            --card-bg: #ffffff;
            --sidebar-width-lg: 250px;
            --sidebar-width-sm: 200px;
            --active-yellow: #FFEB3B;
            --text-dark: #333;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            font-size: 0.9rem;
        }

        /* THEME COLORS */
        .teaus-primary {
            background-color: var(--primary-green);
            color: white;
            border-color: var(--primary-green);
        }

        .teaus-secondary {
            background-color: var(--dark-green);
            color: white;
        }
        
        .teaus-primary:hover {
            background-color: var(--dark-green);
            border-color: var(--dark-green);
            color: white;
        }
        
        /* New style for secondary button to match theme */
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }

        /* ===============================
        1.5. CUSTOM HAMBURGER MENU STYLING (The Three Green Lines)
        ================================= */
        .navbar-toggle {
            /* Set button size to wrap the icon nicely */
            width: 40px; 
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: none;
            background: none;
            cursor: pointer;
            outline: none;
        }

        .hamburger-icon {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 25px; /* Width of the icon */
            height: 20px; /* Total height of the three bars */
        }

        .bar {
            display: block;
            height: 3px; /* Thickness of each line */
            width: 100%;
            background-color: var(--primary-green); /* The green color */
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        /* ===============================
        2. FORM STYLING IMPROVEMENTS
        ================================= */
        .form-control {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 10px 15px;
            font-size: 0.9rem;
            transition: all 0.3s;
            box-shadow: none;
        }

        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-green);
            margin-bottom: 5px;
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

        /* ===============================
        4. MAIN CONTENT & HEADER
        ================================= */
        .main {
            margin-left: var(--sidebar-width-lg);
            padding: 15px 20px;
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
            margin-bottom: 20px;
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

        .header p {
            margin: 0;
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        /* ===============================
        5. CARD STYLING (Form Content)
        ================================= */
        .card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.15);
            border: 1px solid #e0e0e0;
        }

        .card h3 {
            color: var(--dark-green);
            margin-bottom: 20px;
            border-bottom: 2px solid var(--primary-green);
            padding-bottom: 8px;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        /* Alert Styling */
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
            font-size: 0.9rem;
            border-radius: 6px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
            font-size: 0.9rem;
            border-radius: 6px;
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
            font-size: 0.8rem;
            transition: margin-left 0.3s ease-in-out;
        }

        /* ===============================
        7. RESPONSIVE MEDIA QUERIES
        ================================= */

        /* *** FIX APPLIED HERE: Hides hamburger menu on tablet/desktop (768px and up) *** */
        @media (min-width: 768px) {
            .sidebar {
                /* Ensure sidebar is visible and fixed on tablet/desktop */
                transform: translateX(0);
                width: var(--sidebar-width-sm); /* Keep it narrower for tablet */
            }
            .main {
                /* Push content to the right of the sidebar */
                margin-left: calc(var(--sidebar-width-sm) + 10px);
            }
            .navbar-toggle {
                /* HIDE HAMBURGER MENU */
                display: none;
            }
            
            /* Adjustments for larger desktop (Min-width: 992px) */
            @media (min-width: 992px) {
                .sidebar {
                    width: var(--sidebar-width-lg);
                }
                .main {
                    margin-left: calc(var(--sidebar-width-lg) + 15px);
                }
            }
            
            .header {
                justify-content: flex-start;
            }
            .header h2 {
                flex-grow: 0;
            }
            .header p {
                margin-left: auto; /* Push subtitle to the right on large screens */
            }
        }
        
        /* Mobile Screens (Max-width: 767.98px) */
        @media (max-width: 767.98px) {
            body {
                font-size: 0.85rem;
            }
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main {
                margin-left: 0;
                padding: 10px;
            }
            
            /* NEW FLEXBOX LAYOUT FOR HEADER */
            .header {
                min-height: 50px; 
                padding: 10px 15px;
                flex-direction: row; 
                justify-content: flex-start; 
                align-items: center;
                gap: 10px; 
            }

            .navbar-toggle {
                /* SHOW HAMBURGER MENU */
                display: block;
                order: -1; 
                margin: 0;
                padding: 0;
            }

            /* Ensure the old Font Awesome icon is not visible if present */
            .navbar-toggle i.fas {
                display: none !important; 
            }

            .header h2 {
                flex-grow: 1; 
                text-align: left; 
                font-size: 1.3rem;
                margin: 0;
                width: auto; 
            }

            .header p {
                /* Hide the subtitle completely on small screens to save space */
                display: none; 
            }
            
            .card {
                padding: 15px;
            }
            
            /* Full width buttons on mobile */
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 10px;
            }
            .d-flex.justify-content-between .btn {
                width: 100%;
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
            <li><a href="profile.php" class="active"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
        </ul>
    </nav>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main flex-grow-1">
        <div class="header">
            <button class="navbar-toggle" id="sidebarToggle">
                <div class="hamburger-icon">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </button>
            <h2 class="text-success">Edit Profile</h2>
            <p class="text-muted">Update your personal and academic details below.</p>
        </div>

        <div class="row">
            <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                <div class="card">
                    <h3><i class="fas fa-pen-to-square me-2"></i>Update Information</h3>

                    <?php if ($error) : ?>
                        <div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success) : ?>
                        <div class="alert alert-success" role="alert"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="edit_profile.php">
                        <div class="mb-3">
                            <label for="fullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="fullName" name="fullName" value="<?php echo htmlspecialchars($user['fullName'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <label for="program" class="form-label">Program/Course</label>
                            <input type="text" class="form-control" id="program" name="program" value="<?php echo htmlspecialchars($user['program'] ?? ''); ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="year" class="form-label">Current Year</label>
                                <input type="number" class="form-control" id="year" name="year" value="<?php echo htmlspecialchars($user['year'] ?? 1); ?>" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="semester" class="form-label">Current Semester</label>
                                <input type="number" class="form-control" id="semester" name="semester" value="<?php echo htmlspecialchars($user['semester'] ?? 1); ?>" min="1" required>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between">
                            <button type="submit" class="btn teaus-primary"><i class="fas fa-save me-2"></i>Save Changes</button>
                            <a href="profile.php" class="btn btn-secondary"><i class="fas fa-times me-2"></i>Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer mt-auto py-3">
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
            const mainFooter = document.querySelector('.footer');

            function updateLayout() {
                // Determine sidebar width based on screen size for desktop
                let sidebarWidth = 0;
                if (window.innerWidth >= 992) {
                    sidebarWidth = 250;
                } else if (window.innerWidth >= 768) {
                    sidebarWidth = 200;
                }
                
                // Only adjust for desktop/tablet (768px and up)
                if (window.innerWidth >= 768) {
                    // Set margins based on the determined sidebar width
                    const currentSidebarWidth = (window.innerWidth >= 992) ? 250 : 200;
                    mainContent.style.marginLeft = `${currentSidebarWidth + 15}px`;
                    mainFooter.style.marginLeft = `${currentSidebarWidth + 15}px`;
                } else {
                    // Reset margins for mobile view
                    mainContent.style.marginLeft = '0';
                    mainFooter.style.marginLeft = '0';
                }
            }

            function toggleSidebar() {
                // Only allow sidebar toggle on screens <= 767.98px
                if (window.innerWidth <= 767.98) {
                    sidebar.classList.toggle("show");
                    sidebarOverlay.classList.toggle("show");
                }
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