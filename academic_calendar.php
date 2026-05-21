<?php
// academic_calendar.php

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

// Function to sanitize user input
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fetch user data from the database using the stored user ID in the session
$id = $_SESSION['id'];
$user = null;

if (isset($conn) && $conn->ping()) {
    $stmt = $conn->prepare("SELECT fullName, userType, program, year, semester FROM user WHERE id = ?");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
    } else {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
        } else {
            error_log("No user found for ID: " . $id);
            session_destroy();
            header("Location: auth.php?error=user_not_found");
            exit();
        }
        $stmt->close();
    }
} else {
    error_log("DB Connection Failed during user fetch.");
    $user = ['fullName' => 'Guest', 'userType' => 'Student', 'program' => 'N/A', 'year' => 0, 'semester' => 0];
}

// Set variables for the dynamic parts of the page
$fullName = sanitize_input($user['fullName']);
$userType = sanitize_input($user['userType']);
$program = sanitize_input($user['program']);
$year = intval($user['year']);
$semester = intval($user['semester']);

// Concatenate Term for Header Button
$currentTerm = "Y $year, Sem $semester";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEAU Portal - Academic Calendar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />
    
    <style>
        /* ===============================
            1. THEME COLORS & BASE
        ================================= */
        :root {
            --teau-dark-green: #2E7D32;
            --teau-primary: #4CAF50;
            --teau-light-green: #E8F5E8;
            --teau-yellow: #FFEB3B;
            /* 👇 NEW VARIABLE FOR THE GAP */
            --sidebar-gap: 15px; 
            --sidebar-width-base: 220px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
             font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--teau-light-green);
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            font-size: 0.85rem;
        }

        /* Status Buttons (Program/Term) */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 8px;
            background-color: var(--teau-light-green); 
            color: var(--teau-dark-green);
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        /* Styling the "Current Term" badge to be green */
        .status-badge-term {
            background-color: var(--teau-primary);
            color: white;
        }
        
        /* ===============================
            2. SIDEBAR & MAIN LAYOUT
        ================================= */
        .sidebar {
           /* Use base width here for initial setting */
           width: var(--sidebar-width-base); 
           background: linear-gradient(to bottom, var(--teau-dark-green), var(--teau-primary));
           color: white;
           height: 100vh; /* Keeps it full height */
           padding: 15px 0;
           position: fixed;
           top: 0;
           left: 0;
           overflow-y: auto;
           z-index: 1000;
           transition: transform 0.3s ease-in-out;
           /* 👇 VISUAL GAP EFFECT */
           border-radius: 0 12px 12px 0; 
           border-right: var(--sidebar-gap) solid transparent; /* Creates the transparent gap space */
           box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3); /* Base shadow */
        }

       .sidebar h2 {
        text-align: left;
        margin-bottom: 20px;
        margin-left:20px;
        font-size: 1.25rem;
        color: #fff;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
    }

        .sidebar ul { list-style: none; padding: 0; }
        
        .sidebar a {
            display: flex; 
            align-items: center;
            padding: 10px 15px;
            color: #e9ecef;
            text-decoration: none;
            margin: 0 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: var(--teau-yellow);
            color: var(--teau-dark-green);
            font-weight: 600;
            border-radius: 6px;
        }
        
        .main {
            /* 👇 Calculates margin: sidebar width + gap */
            margin-left: calc(var(--sidebar-width-base) + var(--sidebar-gap));
            padding: 15px;
            flex: 1;
            transition: margin-left 0.3s ease-in-out;
            min-width: 300px;
        }
        
        /* Dashboard-style Header */
        .header {
            background: #ffffff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 6px 16px rgba(46, 125, 50, 0.15), 0 0 4px rgba(46, 125, 50, 0.05);
            margin-bottom: 15px;
            border-left: 5px solid var(--teau-primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        /* New Wrapper for Hamburger and Title */
        .header-title-group {
            display: flex;
            align-items: center;
        }
        
        .welcome-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--teau-dark-green);
        }

        .header-meta {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .navbar-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--teau-dark-green);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
            order: 0; 
        }
        
        /* ===============================
            3. CONTENT SPECIFIC STYLING
        ================================= */
        
        /* Calendar Main Title Styling */
        .page-title {
            color: var(--teau-dark-green); 
            border-bottom: 2px solid var(--teau-light-green); 
            padding-bottom: 10px;
        }
        
        #calendar {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .fc .fc-daygrid-event {
            white-space: normal;
            padding: 1px 1px;
            height: auto; 
            line-height: 1.3;
            font-size: 0.8rem;
            min-height: 18px;
            margin-bottom: 2px;
            color: white; 
            font-weight: 500;
        }

        /* Calendar Button Styling */
        .fc .fc-button-primary {
            background-color: var(--teau-primary);
            border-color: var(--teau-primary);
        }

        .fc .fc-button-primary:hover {
            background-color: var(--teau-dark-green);
            border-color: var(--teau-dark-green);
        }
        
        .footer {
            background-color: #4CAF50;
            color: #ffffff;
            border-top: 1px solid var(--teau-primary);
            /* 👇 Calculates margin: sidebar width + gap */
            margin-left: calc(var(--sidebar-width-base) + var(--sidebar-gap)); 
            transition: margin-left 0.3s ease-in-out;
        }

        .footer a {
            color: white; 
        }
        
        /* ===============================
            4. RESPONSIVE MEDIA QUERIES
        ================================= */
        @media (min-width: 1201px) {
            .sidebar { 
                width: 250px; 
                border-right-width: var(--sidebar-gap); /* Apply the gap here too */
            }
            .main { 
                margin-left: calc(250px + var(--sidebar-gap)); 
                padding: 20px; 
            }
            .footer { 
                margin-left: calc(250px + var(--sidebar-gap)) !important; 
            }
        }
        
        @media (min-width: 768px) and (max-width: 1200px) {
            .sidebar { 
                width: 200px; 
                border-right-width: var(--sidebar-gap); /* Apply the gap here too */
            }
            .main { 
                margin-left: calc(200px + var(--sidebar-gap)); 
            }
            .footer { 
                margin-left: calc(200px + var(--sidebar-gap)) !important; 
            }
        }

        @media (max-width: 767px) {
            /* On mobile, remove the gap and reset margins */
            .sidebar { 
                transform: translateX(-100%); 
                width: 250px; 
                border-right-width: 0;
            }
            .sidebar.show { transform: translateX(0); }
            .main { margin-left: 0; padding: 10px; }
            .footer { margin-left: 0 !important; }
            
            /* ... (rest of mobile styles) ... */
            .navbar-toggle { display: block; color: var(--teau-dark-green); margin-right: 10px; }
            .header { flex-direction: column; align-items: center; justify-content: center; }
            .header-title-group { flex-basis: 100%; width: 100%; justify-content: center; margin-bottom: 10px; order: -1; }
            .welcome-title { font-size: 1.1rem; }
            .header-meta { width: 100%; justify-content: space-around; }
            .fc .fc-toolbar { flex-direction: column; align-items: center; }
        }
    </style>
</head>

<body>
    <nav class="sidebar" id="sidebar">
        <h2><i class="fas fa-university"></i> TEAU Portal</h2>
        <ul>
            <li><a href="student_dashboard.php" class="mb-1"><i class="fas fa-home me-2"></i>Dashboard</a></li>
            <li><a href="MyUnits.php" class="mb-1"><i class="fas fa-book-open me-2"></i>Enrolled Units</a></li>
            <li><a href="Progress.php" class="mb-1"><i class="fas fa-rocket me-2"></i>Progress</a></li>
            <li><a href="studentCourseFinder.php" class="mb-1"><i class="fas fa-comments me-2"></i>CourseFinder</a></li>
            <li><a href="academic_calendar.php" class="active mb-1"><i class="fas fa-calendar-alt me-2"></i>Academic Calendar</a></li>
            <li><a href="profile.php" class="mb-1"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
            <li><a href="logout.php" class="mb-1"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
        </ul>
    </nav>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main">
        <div class="header">
            <div class="header-title-group">
                <button class="navbar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
                
                <span class="welcome-title">Welcome <?php echo htmlspecialchars($fullName); ?>!</span>
            </div>
            
            <div class="header-meta">
                <div class="status-badge">
                    <i class="fas fa-graduation-cap me-1"></i> Program: <?php echo htmlspecialchars($program); ?>
                </div>
                <div class="status-badge status-badge-term">
                    <i class="fas fa-calendar-alt me-1"></i> Current Year: <?php echo htmlspecialchars($currentTerm); ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card p-3 shadow-sm border-0">
                    <h3 class="mb-4 text-center page-title">
                        <i class="fas fa-calendar-alt me-2"></i> Academic Calendar
                    </h3>
                    
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
        
        <div class="event-tooltip" id="eventTooltip"></div>
    </main>
    
    <footer class="footer mt-auto py-3">
        <div class="container text-center">
            <p>Created with ❤️ for TEAU. &copy; <?php echo date("Y"); ?>.</p>
            <div class="social-icons mt-2">
                <a href="#"><i class="fab fa-facebook-f mx-2"></i></a>
                <a href="#"><i class="fab fa-twitter mx-2"></i></a>
                <a href="#"><i class="fab fa-instagram mx-2"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar Toggle Logic
            const sidebar = document.getElementById("sidebar");
            const sidebarToggle = document.getElementById("sidebarToggle");
            const mainContent = document.querySelector('.main');
            const mainFooter = document.querySelector('.footer');

            function toggleSidebar() {
                sidebar.classList.toggle("show");
            }

            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener("click", toggleSidebar);
            }

            // Function to dynamically update margin based on screen size and sidebar width
            function updateLayoutMargin() {
                const isMobile = window.innerWidth < 768;
                const sidebarGap = 15; // Matches --sidebar-gap in CSS

                if (!isMobile) {
                    let sidebarWidth;
                    if (window.innerWidth > 1200) sidebarWidth = 250;
                    else if (window.innerWidth >= 768) sidebarWidth = 200;
                    else sidebarWidth = 220; // Base width fallback

                    const requiredMargin = sidebarWidth + sidebarGap;
                    
                    // Apply calculated margins to main content and footer
                    mainContent.style.marginLeft = `${requiredMargin}px`;
                    mainFooter.style.marginLeft = `${requiredMargin}px`;
                    
                    // You might need to adjust the sidebar's CSS width in the JS as well
                    // if you rely on the JS updateLayoutMargin, but here we let CSS media queries handle the width.
                } else {
                    // Mobile view: reset margins
                    mainContent.style.marginLeft = '0';
                    mainFooter.style.marginLeft = '0';
                }
            }

            // Run on load and on resize
            window.addEventListener('resize', updateLayoutMargin);
            updateLayoutMargin();
            
            
            // FullCalendar Initialization
            const calendarEl = document.getElementById('calendar');
            const userProgram = '<?php echo $program; ?>'; 

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                events: {
                    url: 'fetch_events.php', 
                    method: 'POST',
                    extraParams: {
                        program: userProgram 
                    },
                    failure: function() {
                        alert('There was an error while fetching academic events!');
                    }
                },
                
                // Event Tooltip interaction logic (using jQuery for simplicity here)
                eventDidMount: function(info) {
                    const tooltip = document.getElementById('eventTooltip');
                    
                    $(info.el).on('mouseenter', function(e) {
                        const event = info.event;
                        let content = `<strong>${event.title}</strong>`;
                        if (event.extendedProps.description) {
                            content += `<p style="margin-top:5px; margin-bottom:0;">${event.extendedProps.description}</p>`;
                        } else {
                            content += `<p style="margin-top:5px; margin-bottom:0;">No detailed description.</p>`;
                        }
                        
                        tooltip.innerHTML = content;
                        tooltip.style.display = 'block';
                        
                        // Position the tooltip
                        tooltip.style.left = e.pageX + 15 + 'px';
                        tooltip.style.top = e.pageY + 15 + 'px';
                        
                    }).on('mouseleave', function() {
                        tooltip.style.display = 'none';
                    });
                },
                
                navLinks: true, 
                editable: false, 
                selectable: true,
                dayMaxEvents: true, 
                contentHeight: 'auto'
            });

            calendar.render();
        });
    </script>
</body>

</html>