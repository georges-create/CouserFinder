<?php
// CRITICAL: Start the session first thing, right after including config.
// Include the database connection and configuration
include "config.php";

// Start a new session or resume an existing one
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 1. SESSION MESSAGE RETRIEVAL (Must run after session_start()) ---
$message_type = '';
$message_content = '';

if (isset($_SESSION['message_content'])) {
    $message_content = $_SESSION['message_content'];
    // Default to 'info' if type isn't set, otherwise use the stored type
    $message_type = $_SESSION['message_type'] ?? 'info';

    // Clear the message immediately so it doesn't reappear on refresh
    unset($_SESSION['message_content']);
    unset($_SESSION['message_type']);
}
// ---------------------------------------------------------------------

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
$stmt = $conn->prepare("SELECT id, fullName, userType, email FROM user WHERE id = ?");
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
$adminId = $user['id']; // Renamed for clarity on the admin page
$fullName = sanitize_input($user['fullName']);
$userType = sanitize_input($user['userType']);
$email = sanitize_input($user['email']);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEAU Admin Dashboard - My Profile</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="medi.css">

    <style>
        /* ==========================
    BASE STYLES
========================== */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #E8F5E8;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            font-size: 0.85rem;
            margin: 0;
        }

        .teaus-primary {
            background-color: #4CAF50;
            color: white;
            transition: background-color 0.3s;
        }

        .teaus-primary:hover {
            background-color: #FFEB3B;
            color: #2E7D32;
        }

        .teaus-secondary {
            background-color: #2E7D32;
            color: white;
        }

        /* ==========================
    MOBILE HEADER (New for responsiveness)
========================== */
        .mobile-fixed-header {
            --header-height: 3.2rem;
            height: var(--header-height);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1100;
            background: #2E7D32;
            border-bottom: 2px solid #4CAF50;
            display: none;
            /* Hidden by default, shown on mobile via media query */
            justify-content: space-between;
            align-items: center;
            padding: .5rem 1rem;
        }

        .mobile-fixed-header h5 {
            color: #fff;
            font-weight: 600;
            margin: 0;
        }

        .navbar-toggler {
            border: none;
            background: transparent;
            color: #fff;
        }

        .navbar-toggler-icon {
            width: 1.5rem;
            height: 1.5rem;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' stroke='white' stroke-width='2' viewBox='0 0 30 30'%3E%3Cpath d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }

        /* ==========================
    SIDEBAR
========================== */
        .sidebar {
            width: 220px;
            background: linear-gradient(to bottom, #2E7D32 0%, #4CAF50 100%);
            color: white;
            height: 100vh;
            padding: 15px 0;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            border-radius: 0 12px 12px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15);
            transition: transform .3s ease-in-out;
            z-index: 1040;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.25rem;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
            padding-bottom: 20px;
            /* Space for the last item */
        }

        .sidebar a {
            display: block;
            padding: 10px 15px;
            color: #e9ecef;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 6px;
            margin: 0 8px;
            font-size: 0.9rem;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #FFEB3B;
            color: #2E7D32;
        }

        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 1030;
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }

        /* ==========================
    MAIN CONTENT
========================== */
        .main {
            margin-left: 220px;
            padding: 15px;
            flex: 1;
            transition: margin-left .3s ease;
        }

        /* Styles for Header, Card, List-group, Footer remain mostly unchanged */
        .header {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.15);
            margin-bottom: 15px;
            border-left: 4px solid #4CAF50;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.1);
            margin-bottom: 15px;
            border: 1px solid #E8F5E8;
        }

        .card h3 {
            color: #2E7D32;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 3px solid #4CAF50;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
        }

        .list-group-item {
            border: none;
            padding: 8px 0;
            background-color: transparent;
            font-size: 0.9rem;
        }

        .list-group-item strong {
            color: #2E7D32;
            min-width: 150px;
            display: inline-block;
        }

        .footer {
            background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 10px;
            margin-top: auto;
            font-size: 0.8rem;
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

        /* ==========================
    RESPONSIVE LAYOUT
========================== */

        /* Tablets & Below (lg breakpoint) */
        @media (max-width: 991.98px) {
            .mobile-fixed-header {
                display: flex;
            }

            .sidebar {
                /* Hide sidebar initially on mobile */
                transform: translateX(-100%);
                /* Sidebar positioning relative to header */
                top: var(--header-height);
                height: calc(100vh - var(--header-height));
                width: 250px;
                /* Make it slide in from the left edge */
                border-radius: 0 12px 12px 0;
                /* Push content down below the fixed header */
                padding-top: 15px;
            }

            /* Add top padding to the sidebar content to clear the fixed header */
            .sidebar ul {
                padding-top: 15px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main {
                /* Remove left margin for mobile layout */
                margin-left: 0;
                /* The margin-top class (mt-5) on the main tag handles the spacing below */
                padding: 15px;
            }
        }

        /* Phones */
        @media (max-width: 576px) {
            .mobile-fixed-header {
                --header-height: 2.8rem;
            }

            .sidebar {
                top: var(--header-height);
                height: calc(100vh - var(--header-height));
                width: 80%;
            }

            .card {
                padding: 15px;
            }

            .list-group-item strong {
                min-width: 100px;
            }
        }
    </style>
</head>

<body>
    <header class="mobile-fixed-header d-lg-none">
        <button class="navbar-toggler" id="sidebarToggle">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h5>TEAU Admin</h5>
    </header>

    <div class="d-flex flex-column flex-grow-1">
        <div class="d-flex">

            <nav id="mobileSidebar" class="sidebar d-lg-block">
                <h2><i class="fas fa-university logo-icon"></i>TEAU Admin</h2>
                <ul class="nav flex-column">

                    <li class="nav-item mb-1">
                    <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a> 
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link" href="course.php"><i class="fas fa-book me-2"></i>Course Management</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link" href="user.php"><i class="fas fa-users me-2"></i>User Management</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link" href="Analytic.php"><i class="fas fa-chart-line me-2"></i>Analytics Reports</a> 
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link active" href="admin_profile.php"><i class="fas fa-user-circle me-2"></i>My Profile</a>
                </li>
            </ul>
             <ul class="nav flex-column pt-3">
                 <li class="nav-item">
                     <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                 </li>
             </ul>
                </ul>
            </nav>
            <div class="sidebar-overlay" id="sidebarOverlay"></div>

            <main class="main flex-grow-1 mt-5 mt-lg-0">
                <div class="header">
                    <h2 class="text-success">Admin Profile</h2>
                    <p class="text-muted">Review and manage your administrative account details.</p>
                </div>

                <?php if ($message_content) : ?>
                    <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message_content); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                        <div class="card">
                            <h3><i class="fas fa-user-tie me-3"></i>Admin/Staff Details</h3>

                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Full Name:</strong> <?php echo htmlspecialchars($fullName); ?></li>
                                <li class="list-group-item"><strong>Staff/Admin ID:</strong> <?php echo htmlspecialchars($adminId); ?></li>
                                <li class="list-group-item"><strong>User Role:</strong> <span class="badge bg-danger"><?php echo htmlspecialchars($userType); ?></span></li>
                                <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></li>
                            </ul>

                            <h3 class="mt-4"><i class="fas fa-cog me-3"></i>Account Actions</h3>

                            <div class="mt-2 text-center d-grid gap-2 d-md-block">
                                <a href="edit_admin_profile.php" class="btn teaus-primary mb-2 mb-md-0"><i class="fas fa-edit me-2"></i>Edit Profile Info</a>
                                <a href="admin_change_password.php" class="btn btn-outline-danger"><i class="fas fa-key me-2"></i>Change Password</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <footer class="footer mt-auto py-3">
        <div class="container text-center">
            <p>Created with ❤️ for TEAU. &copy; 2025.</p>
            <div class="social-icons mt-2">
                <a href="#" class="text-white"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const toggler = document.getElementById('sidebarToggle');

            if (toggler && sidebar && overlay) {
                // Function to toggle the sidebar state
                const toggleSidebar = () => {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('show');
                };

                // Event listener for the hamburger button
                toggler.addEventListener('click', toggleSidebar);

                // Event listener for the overlay click (to close the sidebar)
                overlay.addEventListener('click', toggleSidebar);

                // Also close sidebar if a link is clicked on mobile
                $('#mobileSidebar a').on('click', function() {
                    if (window.innerWidth < 992) {
                        toggleSidebar();
                    }
                });
            }
        });
    </script>
</body>

</html>