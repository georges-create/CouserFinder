<?php
include "config.php";

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

/**
 * Function to sanitize input
 * Wrapped in if (!function_exists) to prevent redeclaration errors if included elsewhere.
 */
if (!function_exists('sanitize_input')) {
    function sanitize_input($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

// Handle Form Submission (POST Request)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Basic validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "All fields are required.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($newPassword) < 8) {
        $error = "New password must be at least 8 characters long.";
    } else {
        // 1. Verify Current Password
        if (isset($conn) && $conn->ping()) {
            $stmt = $conn->prepare("SELECT password FROM user WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
        } else {
            $error = "Database connection lost. Please try again.";
            $user = null;
        }

        if ($user && password_verify($currentPassword, $user['password'])) {
            // 2. Hash and Update New Password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Re-establish connection if it was closed
            if (!isset($conn) || !$conn->ping()) {
                include "config.php";
            }
            
            $stmt_update = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
            if ($stmt_update === false) {
                $error = "Database error: " . $conn->error;
            } else {
                $stmt_update->bind_param("si", $hashedPassword, $id);

                if ($stmt_update->execute()) {
                    $success = "Password updated successfully! You will be logged out to re-login.";
                    session_destroy(); // Force logout for security
                    header("Refresh: 3; url=auth.php"); // Redirect to login after 3 seconds
                } else {
                    $error = "Error updating password: " . $stmt_update->error;
                }
                $stmt_update->close();
            }
        } else {
            $error = "The current password you entered is incorrect.";
        }
    }
}

// Close connection at the end
if (isset($conn) && $conn->ping()) {
    $conn->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEAU Student Dashboard - Change Password</title>

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

    /* THEME COLORS & BUTTONS */
    .teaus-primary {
        background-color: var(--primary-green);
        color: white;
        border-color: var(--primary-green);
        transition: all 0.3s;
    }

    .teaus-primary:hover {
        background-color: var(--dark-green);
        border-color: var(--dark-green);
        color: white;
    }
    
    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
        transition: all 0.3s;
    }
    
    .btn-secondary:hover {
        background-color: #5a6268;
        border-color: #545b62;
    }
    
    /* Update Password button style */
    .btn-update-password {
        background-color: var(--primary-green);
        color: white;
        border: 2px solid var(--primary-green);
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-update-password:hover {
        background-color: var(--dark-green);
        border-color: var(--dark-green);
        color: white;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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

    /* FORM STYLING */
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
    
    /* Alert Styling */
    .alert {
        font-size: 0.9rem;
        border-radius: 6px;
        padding: 10px 15px;
        margin-bottom: 15px;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border-color: #c3e6cb;
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
    3. MAIN CONTENT & HEADER
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
    4. CARD STYLING (Form Content)
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
    
    /* ===============================
    5. PASSWORD TOGGLE
    ================================= */
    .password-container {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        top: 50%;
        right: 15px;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
        padding: 5px;
        font-size: 1.1rem;
        transition: color 0.2s;
        z-index: 10;
    }

    .password-toggle:hover {
        color: var(--dark-green);
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

    /* ---------------------------------- */
    /* TABLET & LARGE SCREENS (>= 768px) */
    /* ---------------------------------- */
    @media (min-width: 768px) {
         .sidebar {
            /* Ensure sidebar is visible and fixed on tablet/desktop */
            transform: translateX(0);
            width: var(--sidebar-width-sm); /* Default to smaller for tablet */
        }
        .main {
            margin-left: calc(var(--sidebar-width-sm) + 15px);
        }
        .footer {
            margin-left: calc(var(--sidebar-width-sm) + 15px);
        }
        .navbar-toggle {
            /* *** HIDE HAMBURGER MENU ON TABLET/DESKTOP *** */
            display: none !important; 
        }
        
        /* Adjustments for larger desktop (Min-width: 992px) */
        @media (min-width: 992px) {
            .sidebar {
                width: var(--sidebar-width-lg); /* 250px */
            }
            .main {
                margin-left: calc(var(--sidebar-width-lg) + 20px); 
            }
            .footer {
                margin-left: calc(var(--sidebar-width-lg) + 20px); 
            }
        }
    }
    
    /* ---------------------------------------------------- */
    /* SMALL SCREENS (Mobile Portrait/Tablet: <= 767.98px)  */
    /* ---------------------------------------------------- */
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
        .main, .footer {
            margin-left: 0;
            padding: 10px;
        }
        
        /* --- HEADER FIX for Mobile --- */
        .header {
            /* Changed to row layout to place button and title side-by-side */
            min-height: 50px; 
            padding: 10px 15px;
            flex-direction: row; 
            justify-content: flex-start; 
            align-items: center;
            gap: 10px; /* Space between button and title */
        }

        .navbar-toggle {
            /* SHOW HAMBURGER MENU ON MOBILE */
            display: flex; /* Changed from block to flex to center the bars */
            order: -1; 
            margin: 0; /* Remove top/bottom margin */
            /* Ensure the old Font Awesome icon is not visible if present */
            font-size: 0; 
        }

        .header h2 {
            /* This is the key change: tells the title to grow and push the subtitle away */
            flex-grow: 1; 
            text-align: left; /* Align title to the left, next to the button */
            font-size: 1.3rem;
            width: auto; /* Allow flex to control width */
        }

        .header p {
            /* Keep subtitle hidden */
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
    
    /* ------------------------------------------------ */
    /* EXTRA SMALL SCREENS (Mobile Small: <= 480px) */
    /* ------------------------------------------------ */
    @media (max-width: 480px) {
        .sidebar {
            width: 220px;
        }
        .main {
            padding: 8px;
        }
        .card {
            padding: 12px;
        }
        .form-control {
            font-size: 0.85rem;
            padding: 8px 10px;
        }
        .form-label {
            font-size: 0.9rem;
        }
        .header h2 {
            font-size: 1.2rem;
        }
        .password-toggle {
            font-size: 1rem;
            right: 10px;
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
            <h2 class="text-success">Change Password</h2>
            <p class="text-muted">For your security, please enter your current password to set a new one.</p>
        </div>

        <div class="row">
            <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                <div class="card">
                    <h3><i class="fas fa-key me-2"></i>Password Update</h3>

                    <?php if ($error) : ?>
                        <div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success) : ?>
                        <div class="alert alert-success" role="alert"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="change_password.php">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <i class="fas fa-eye password-toggle" data-target="current_password"></i>
                            </div>
                        </div>
                        <hr class="my-4">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <i class="fas fa-eye password-toggle" data-target="new_password"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <i class="fas fa-eye password-toggle" data-target="confirm_password"></i>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between">
                            <button type="submit" class="btn btn-update-password"><i class="fas fa-key me-2"></i>Update Password</button>
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
                    // Set margin-left based on sidebar width + a gap (20px)
                    mainContent.style.marginLeft = `${sidebarWidth + 20}px`;
                    mainFooter.style.marginLeft = `${sidebarWidth + 20}px`;
                } else {
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

            // ===============================
            // PASSWORD TOGGLE FUNCTIONALITY
            // ===============================
            document.querySelectorAll('.password-toggle').forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const passwordInput = document.getElementById(targetId);

                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        this.classList.remove('fa-eye');
                        this.classList.add('fa-eye-slash'); // Change icon to 'hide'
                    } else {
                        passwordInput.type = 'password';
                        this.classList.remove('fa-eye-slash');
                        this.classList.add('fa-eye'); // Change icon back to 'show'
                    }
                });
            });
        });
    </script>
</body>

</html>