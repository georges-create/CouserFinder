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

// Function to sanitize input
// NOTE: mysqli_real_escape_string is not used here as passwords are not SQL strings but passed to password_hash
if (!function_exists('sanitize_input')) {
    function sanitize_input($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

// Handle Form Submission (POST Request)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize, but don't use mysqli_real_escape_string as we are dealing with plain text passwords for validation/hashing
    $currentPassword = sanitize_input($_POST['current_password']);
    $newPassword = sanitize_input($_POST['new_password']);
    $confirmPassword = sanitize_input($_POST['confirm_password']);

    // Basic validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "All fields are required. 🚫";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "New password and confirmation do not match. ❌";
    } elseif (strlen($newPassword) < 8) {
        $error = "New password must be at least 8 characters long. ⚠️";
    } else {
        // 1. Verify Current Password
        // Check if $conn is still open (might be closed on redirect in other files)
        if (!isset($conn) || !$conn->ping()) {
            include "config.php"; // Re-include if connection was closed
        }

        $stmt = $conn->prepare("SELECT password FROM user WHERE id = ?");

        if ($stmt === false) {
            $error = "Database preparation failed: " . $conn->error;
        } else {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($currentPassword, $user['password'])) {
                // 2. Hash and Update New Password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                $stmt_update = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
                if ($stmt_update === false) {
                    $error = "Database error: " . $conn->error;
                } else {
                    $stmt_update->bind_param("si", $hashedPassword, $id);

                    if ($stmt_update->execute()) {
                        // FIX: Use session to store message for redirect
                        $_SESSION['message_type'] = 'success';
                        $_SESSION['message_content'] = "Password updated successfully! You will be logged out to re-login. ✅";

                        $stmt_update->close();
                        // Close connection before redirect
                        if (isset($conn) && $conn->ping()) {
                            $conn->close();
                        }

                        session_destroy(); // Force logout for security
                        // Redirect immediately to prevent form resubmission and display message via session on auth.php
                        header("Location: auth.php");
                        exit();
                    } else {
                        $error = "Error updating password: " . $stmt_update->error;
                    }
                    $stmt_update->close();
                }
            } else {
                $error = "The current password you entered is incorrect. 🛑";
            }
        }
    }
}
// Close connection if it's still open
if (isset($conn) && $conn->ping()) {
    $conn->close();
}

// Check if a success message was set from a previous successful update that wasn't redirected
if (!empty($_SESSION['message_type']) && $_SESSION['message_type'] === 'success') {
    $success = $_SESSION['message_content'];
    unset($_SESSION['message_type']);
    unset($_SESSION['message_content']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEAU Admin Dashboard - Change Password</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ==========================
        BASE STYLES
        ========================== */
        * {
            box-sizing: border-box;
        }

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
            transition: transform .3s ease-in-out;
            z-index: 1040;
        }

        .sidebar h2 {
            text-align: left;
            margin-bottom: 20px;
            font-size: 1.25rem;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            padding: 0 8px;
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
            transition: all 0.3s ease;
            border-radius: 6px;
            margin: 0 8px;
            font-size: 0.9rem;
        }

        .sidebar a:hover,
        .sidebar a.active {
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
        PASSWORD TOGGLE STYLES (NEW)
        ========================== */
        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            /* Vertically center */
            right: 10px;
            /* Offset from the right */
            transform: translateY(-50%);
            /* Fine-tune centering */
            cursor: pointer;
            color: #6c757d;
            padding: 5px;
            font-size: 1.1rem;
            transition: color 0.2s;
            z-index: 10;
        }

        .password-toggle:hover {
            color: #4CAF50;
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
                transform: translateX(-100%);
                top: var(--header-height);
                height: calc(100vh - var(--header-height));
                width: 250px;
                padding-top: 15px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main {
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
    </style>
</head>

<body>
    <header class="mobile-fixed-header d-lg-none">
        <button class="navbar-toggler" id="sidebarToggle">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h5>TEAU Portal</h5>
    </header>

    <div class="d-flex flex-column flex-grow-1">
        <div class="d-flex">
            <nav id="mobileSidebar" class="sidebar d-lg-block">
                <h2><i class="fas fa-university logo-icon"></i>TEAU Admin</h2>
                <ul>
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


            <main class="main flex-grow-1 mt-lg-0 mt-5">
                <div class="header">
                    <h2 class="text-danger">Change Password</h2>
                    <p class="text-muted">For your security, please enter your current password to set a new one.</p>
                </div>

                <div class="row">
                    <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                        <div class="card">
                            <h3><i class="fas fa-key me-3"></i>Password Update</h3>

                            <?php if ($error) : ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            <?php if ($success) : ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($success); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="change_password.php">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <div class="password-container">
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <i class="fas fa-eye password-toggle" data-target="current_password"></i>
                                    </div>
                                </div>
                                <hr>
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
                                    <button type="submit" class="btn teaus-primary"><i class="fas fa-key me-2"></i>Update Password</button>
                                    <a href="admin_profile.php" class="btn btn-outline-secondary"><i class="fas fa-times me-2"></i>Cancel</a>
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
            // Sidebar Toggle Logic (already existing)
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const toggler = document.getElementById('sidebarToggle');

            if (toggler && sidebar && overlay) {
                const toggleSidebar = () => {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('show');
                };

                toggler.addEventListener('click', toggleSidebar);
                overlay.addEventListener('click', toggleSidebar);

                $('#mobileSidebar a').on('click', function() {
                    if (window.innerWidth < 992) {
                        setTimeout(toggleSidebar, 100);
                    }
                });
            }

            // ===============================
            // PASSWORD TOGGLE FUNCTIONALITY (NEW)
            // ===============================
            document.querySelectorAll('.password-toggle').forEach(toggle => {
                toggle.addEventListener('click', function() {
                    // Get the ID of the target input field from the data-target attribute
                    const targetId = this.getAttribute('data-target');
                    const passwordInput = document.getElementById(targetId);

                    // Toggle the input type between 'password' and 'text'
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