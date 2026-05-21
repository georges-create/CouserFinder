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

// Fetch courses from the 'courses' table based on user's program, year, and semester
$stmt_courses = $conn->prepare("SELECT id, code, name, description FROM courses WHERE program = ? AND year = ? AND semester = ? ORDER BY code ASC");
if (!$stmt_courses) {
    error_log("Prepare failed: " . $conn->error);
    $enrolledCourses = [];
} else {
    $stmt_courses->bind_param("sii", $program, $year, $semester);
    $stmt_courses->execute();
    $result_courses = $stmt_courses->get_result();

    $enrolledCourses = [];
    if ($result_courses->num_rows > 0) {
        while ($row = $result_courses->fetch_assoc()) {
            $enrolledCourses[] = $row;
        }
    }
    $stmt_courses->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEAU Student Dashboard - My Enrolled Courses</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet" />


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
            --sidebar-width: 220px;
            --active-yellow: #FFEB3B;
            --max-content-width: 1600px; /* New Max Width for Main Content */
            --sidebar-gap: 15px; /* Define a variable for the gap */
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
            font-size: 0.85rem;
        }

        /* --- Main Content Layout --- */
        .main {
            /* Adjust margin for sidebar width + desired gap */
            margin-left: calc(var(--sidebar-width) + var(--sidebar-gap)); 
            padding: 12px;
            flex: 1;
            transition: margin-left 0.3s ease-in-out;
        }

        /* Enforce max-width on elements *inside* .main for centering on large screens */
        .header,
        .row {
            max-width: var(--max-content-width); 
            margin-left: auto;
            margin-right: auto;
        }

        /* ===============================
        3. SIDEBAR & OVERLAY 
        ================================= */
        .sidebar {
           width: var(--sidebar-width);
           background: linear-gradient(to bottom, #2E7D32, #4CAF50);
           color: white;
           /* Reverting to 100vh to maintain full viewport height */
           height: 100vh; 
           padding: 15px 0;
           position: fixed;
           top: 0;
           left: 0;
           overflow-y: auto;
           /* Added border radius for the right side to create the gap effect */
           border-radius: 0 12px 12px 0;
           box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3); 
           z-index: 1000;
           transition: transform 0.3s ease-in-out;
           
           /* Apply padding to the right to create the visual gap */
           padding-right: var(--sidebar-gap); 
           /* Add a slightly larger right box-shadow for the gap effect */
           box-shadow: 2px 0 15px rgba(0, 0, 0, 0.4); 
        }
        
        /* Removed the media query that set bottom: 70px to fix the height issue */
        
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
            padding: 10px 15px 10px 15px;
            color: #e9ecef;
            text-decoration: none;
            border-radius: 6px;
            margin: 0 8px 0 8px; /* Standard left/right margin */
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: var(--active-yellow);
            color: var(--dark-green);
            font-weight: 600;
        }


        /* --- Footer --- */
        .footer {
             background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 0.8rem;
            margin-top: auto;
            /* Match margin to sidebar width + desired gap */
            margin-left: calc(var(--sidebar-width) + var(--sidebar-gap)); 
            transition: margin-left 0.3s ease-in-out;
            width: auto; 
        }
        
        .footer .container {
            /* Ensure footer content is also max-width and centered */
            max-width: var(--max-content-width); 
            margin-left: auto;
            margin-right: auto;
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

        /* Hamburger Menu Toggle */
        .navbar-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--dark-green);
            font-size: 1.8rem;
            cursor: pointer;
            padding: 5px;
            order: -1; 
        }


        /* ===============================
        5. CARD & TABLE STYLING
        ================================= */
        .card {
            background: var(--card-bg);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            border: none;
        }

        .card h3 {
            color: var(--dark-green);
            margin-bottom: 15px;
            border-bottom: 2px solid var(--primary-green);
            padding-bottom: 8px;
            font-size: 1.2rem;
            font-weight: 600;
        }

        table thead.table-dark th {
            background-color: var(--primary-green) !important;
            color: white !important;
            border-color: #388E3C !important;
            padding: 12px 15px;
            font-weight: 700;
            text-transform: uppercase;
        }

        table tbody tr:nth-child(even) {
            background-color: #f7fcf7;
        }


        /* ===============================
        6. RESPONSIVE MEDIA QUERIES
        ================================= */

        /* ---------------------------------------------------- */
        /* Tablet Landscape/Small Tablet (max-width: 991.98px) */
        /* ---------------------------------------------------- */
        @media (max-width: 991.98px) {
            :root {
                --sidebar-width: 250px; 
            }

            .main {
                /* Reset on mobile */
                margin-left: 0; 
                padding: 15px;
            }
            
            /* Reset footer margin on mobile so it spans full width */
            .footer {
                margin-left: 0;
            }

            /* Remove padding from sidebar on mobile view to use full width */
            .sidebar {
                padding-right: 0;
                transform: translateX(-100%);
                width: var(--sidebar-width);
                height: 100vh; /* Ensure full height on mobile */
            }

            .navbar-toggle {
                display: block;
                order: -1; 
                font-size: 1.5rem;
            }
            
            .header {
                flex-direction: row; 
                justify-content: flex-start; 
                align-items: center;
                gap: 10px;
            }
            
            .header h2 {
                font-size: 1.3rem;
                order: 0; 
                flex-grow: 1; 
                margin-right: auto;
            }
            
            .header-badges {
                order: 1; 
                width: 100%; 
                justify-content: space-between; 
                gap: 5px;
                margin-top: 5px;
            }

            .info-badge {
                flex-basis: 48%; 
                justify-content: center;
            }
        }
        
        /* ---------------------------------------------------- */
        /* Small Mobile Devices (max-width: 575.98px) */
        /* ---------------------------------------------------- */
        @media (max-width: 575.98px) {
            .header {
                padding: 15px 10px;
                gap: 8px;
            }
            
            .header h2 {
                font-size: 1.05rem;
            }
            
            .header-badges {
                flex-direction: column; 
                gap: 5px;
                width: 100%;
                align-items: center;
            }
            
            .info-badge {
                flex-basis: 100%; 
            }
        }
    </style>
</head>

<body>
    <nav class="sidebar" id="sidebar">
        <h2><i class="fas fa-university"></i> TEAU Portal</h2>
        
        <div class="sidebar-menu-wrapper">
            <ul>
                <li><a href="student_dashboard.php"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                <li><a href="MyUnits.php" class="active"><i class="fas fa-book-open me-2"></i>Enrolled Units</a></li>
                <li><a href="Progress.php"><i class="fas fa-rocket me-2"></i>Progress</a></li>
                <li><a href="studentCourseFinder.php"><i class="fas fa-comments me-2"></i>CourseFinder</a></li>
                <li><a href="academic_calendar.php"><i class="fas fa-calendar-alt me-2"></i>Academic Calendar</a></li>
                <li><a href="profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </nav>
    <div class="sidebar-overlay" id="sidebarOverlay"></div> 

    <main class="main">

        <div class="header">
            <button class="navbar-toggle mx-3" id="sidebarToggle"><i class="fas fa-bars"></i></button>

            <h2>Welcome <?php echo htmlspecialchars($fullName); ?>!</h2>

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
                <div class="card table-container">
                    <h3><i class="fas fa-list-alt me-2"></i>Enrolled  Units (Year <?php echo htmlspecialchars($year); ?> Sem<?php echo htmlspecialchars($semester); ?>)</h3>
                    <div class="table-responsive">
                        <table id="myEnrolledCoursesTable" class="table table-striped" style="width:100%">
                            <thead class="table-dark">
                                <tr>
                                    <th data-priority="4">ID</th>
                                    <th data-priority="1">Code</th>
                                    <th data-priority="1">Unit Name</th>
                                    <th data-priority="2">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!empty($enrolledCourses)) {
                                    foreach ($enrolledCourses as $course) {
                                ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($course['id']); ?></td>
                                                <td><?php echo htmlspecialchars($course['code']); ?></td>
                                                <td><?php echo htmlspecialchars($course['name']); ?></td>
                                                <td><?php echo htmlspecialchars($course['description']); ?></td>
                                            </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <i class="fas fa-exclamation-circle me-2"></i> No courses found for your current program and intake.
                                        </td>
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

    <footer class="footer py-3">
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
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        /**
         * Sidebar Toggle Logic
         */
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById("sidebar");
            const sidebarToggle = document.getElementById("sidebarToggle");
            const sidebarOverlay = document.getElementById("sidebarOverlay");

            function toggleSidebar() {
                // Toggles the 'show' class on both elements for slide-in/out and overlay visibility
                sidebar.classList.toggle("show");
                sidebarOverlay.classList.toggle("show");
            }

            if (sidebarToggle && sidebar && sidebarOverlay) {
                // Event listeners to open/close the sidebar
                sidebarToggle.addEventListener("click", toggleSidebar);
                sidebarOverlay.addEventListener("click", toggleSidebar);
            }

            /**
             * Initialize DataTable for the enrolled courses table
             */
            $('#myEnrolledCoursesTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthChange: true,
                searching: true,
                ordering: true,
                info: true,
                language: {
                    search: "Filter Courses:",
                    lengthMenu: "Show _MENU_ courses per page",
                    zeroRecords: "No matching courses found",
                    info: "Showing _START_ to _END_ of _TOTAL_ courses",
                    infoEmpty: "Showing 0 to 0 of 0 courses",
                    infoFiltered: "(filtered from _MAX_ total courses)"
                }
            });
        });
    </script>
</body>

</html>