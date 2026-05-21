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

// Check if the user is an admin (optional, but good practice)
// ASSUMPTION: 'userType' is stored in the session and admin is identified by a specific value like 'admin'
if ($_SESSION['userType'] !== 'admin') {
    // Or whatever your admin user type is
    header("Location: dashboard.php"); // Redirect to non-admin dashboard
    exit();
}

// Include a function to sanitize user input
function sanitize_input($conn, $data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Initialize variables for the form
$id = $_SESSION['id'];
$fullName = '';
$email = '';
$error = '';
$success = '';

// --- 1. HANDLE FORM SUBMISSION (POST request) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and get POST data
    $newFullName = sanitize_input($conn, $_POST['fullName']);
    $newEmail = sanitize_input($conn, $_POST['email']);

    // Basic validation
    if (empty($newFullName) || empty($newEmail)) {
        $error = "Full Name and Email fields cannot be empty. 🚫";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format. 📧";
    } else {
        // Prepare an UPDATE statement
        $stmt = $conn->prepare("UPDATE user SET fullName = ?, email = ? WHERE id = ? AND userType = 'admin'");

        if (!$stmt) {
            $error = "Database preparation failed: " . $conn->error;
            error_log($error);
        } else {
            $stmt->bind_param("ssi", $newFullName, $newEmail, $id);

            if ($stmt->execute()) {
                // Update session variables immediately
                $_SESSION['fullName'] = $newFullName;
                $_SESSION['email'] = $newEmail;

                // *** FIX APPLIED HERE: Store message in session before redirect ***
                $_SESSION['message_type'] = 'success';
                $_SESSION['message_content'] = "Profile updated successfully! ✅";

                $stmt->close();
                $conn->close();
                // Redirect without the query parameter, as the message is in the session
                header("Location: admin_profile.php");
                exit();
            } else {
                // Check for duplicate email error (MySQL error code 1062)
                if ($conn->errno == 1062) {
                    $error = "The email address '$newEmail' is already in use. Please choose a different one. ";
                } else {
                    $error = "Error updating profile: " . $stmt->error;
                    error_log("Update error for ID $id: " . $stmt->error);
                }
            }
        }
    }
}

// --- 2. FETCH CURRENT USER DATA (GET or after failed POST) ---

// Fetch current user data from the database using the stored user ID
$stmt = $conn->prepare("SELECT id, fullName, userType, email FROM user WHERE id = ? AND userType = 'admin'"); // Add admin check
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    $error = "Could not fetch user data. Database error.";
} else {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    $user = null;
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Use the fetched data for the form fields, unless a POST submission failed (and $newFullName/$newEmail exist)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !empty($error)) {
            $fullName = htmlspecialchars($user['fullName']);
            $email = htmlspecialchars($user['email']);
        } else {
            // If POST failed, retain user input for convenience
            $fullName = htmlspecialchars($newFullName ?? '');
            $email = htmlspecialchars($newEmail ?? '');
        }
        $userType = htmlspecialchars($user['userType']);
        $adminId = htmlspecialchars($user['id']);
    } else {
        // If user data can't be found, log them out and redirect
        error_log("No admin found for ID: " . $id);
        session_destroy();
        header("Location: auth.php?error=user_not_found");
        exit();
    }
    $stmt->close();
}

// Close connection only if it hasn't been closed already (e.g., in a successful redirect)
if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEAU Admin Dashboard - Edit Profile</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

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
            background-color: #388E3C;
            color: white;
        }

        /* ==========================
        MOBILE HEADER
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
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            border-radius: 0 12px 12px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15);
            padding: 1rem;
            /* Default desktop padding */
            transition: transform .3s ease-in-out;
            z-index: 1040;
        }

        .sidebar h4 {
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .sidebar .nav-link {
            padding: 10px 15px;
            color: #e9ecef;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 6px;
            margin: 0;
            font-size: 0.9rem;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #FFEB3B;
            color: #2E7D32 !important;
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
            transition: margin-left .3s ease, margin-top .3s ease;
        }

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

        .form-label {
            font-weight: bold;
            color: #2E7D32;
        }

        .form-control:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
        }

        /* ==========================
        FOOTER
        ========================== */
        .footer {
            background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 10px;
            margin-top: auto;
            font-size: 0.8rem;
        }

        .footer p {
            margin: 0;
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
                /* Adjust padding to clear the header */
                padding-top: 15px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main {
                /* Remove left margin and add top margin for mobile layout */
                margin-left: 0;
                margin-top: var(--header-height);
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

            .main {
                margin-top: var(--header-height);
            }
        }



        /* Tablets & Below (lg breakpoint) */
        @media (max-width: 991.98px) {

            /* ... other styles ... */
            .main {
                /* Remove left margin and add top margin for mobile layout */
                margin-left: 0;
                margin-top: var(--header-height);
                /* <-- This is the key line */
                padding: 55px;
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
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-university fs-4 me-2"></i>
                    <h4 class="mb-0 fs-5">TEAU Admin</h4>
                </div>
                <ul class="nav flex-column">
                     <li class="nav-item mb-1">
                    <a class="nav-link " href="admin_dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a> 
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

            <main class="main flex-grow-1 mt-lg-0">
                <div class="header">
                    <h2 class="text-success">Edit Admin Profile</h2>
                    <p class="text-muted">Update your full name and email address.</p>
                </div>

                <div class="row">
                    <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                        <div class="card">
                            <h3><i class="fas fa-edit me-3"></i>Update Information</h3>

                            <?php if ($error) : ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form action="edit_admin_profile.php" method="POST">
                                <div class="mb-3">
                                    <label for="fullName" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="fullName" name="fullName" value="<?php echo $fullName; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="adminId" class="form-label">Staff/Admin ID</label>
                                    <input type="text" class="form-control" id="adminId" value="<?php echo $adminId; ?>" disabled>
                                </div>

                                <div class="mb-3">
                                    <label for="userType" class="form-label">User Role</label>
                                    <input type="text" class="form-control" id="userType" value="<?php echo $userType; ?>" disabled>
                                </div>

                                <div class="mt-4 text-center">
                                    <button type="submit" class="btn teaus-primary me-2"><i class="fas fa-save me-2"></i>Save Changes</button>
                                    <a href="admin_profile.php" class="btn btn-outline-secondary"><i class="fas fa-undo me-2"></i>Cancel</a>
                                </div>
                            </form>

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
                        // Use a slight delay to allow navigation to start
                        setTimeout(toggleSidebar, 100);
                    }
                });
            }
        });
    </script>
</body>

</html>