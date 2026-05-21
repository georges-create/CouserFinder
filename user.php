<?php
// Start session for status redirects and authentication check
@session_start();

// Include the database configuration file
// NOTE: $conn is assumed to be established here.
include 'config.php';

// Check if the user is logged in (Authentication/Authorization)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: auth.php");
    exit();
}

// Get the admin's username from the session (Display only)
$admin_username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : "Super Admin";


// Fetch all users from the database for display
$sql = "SELECT id, fullName, email, program, year, semester, userType FROM user";
$result = $conn->query($sql);

// Check for and display status messages from URL parameters
$status_message = "";
$status_class = "";

if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'success_add': 
            $status_message = "User has been successfully added.";
            $status_class = "alert-success";
            break;
        case 'success_edit':
            $status_message = "User has been successfully updated.";
            $status_class = "alert-success";
            break;
        case 'success_delete':
            $status_message = "User has been successfully deleted.";
            $status_class = "alert-success";
            break;
        case 'error':
            $status_message = "An error occurred: " . (isset($_GET['message']) ? htmlspecialchars($_GET['message']) : "Unknown error.");
            $status_class = "alert-danger";
            break;
    }
}
    
// NOTE: We close the connection at the end of the page body.
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TEAU Admin - User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
   <style>
    /* ===============================
    COLOR VARIABLES & BASE STYLES
    ================================= */
    :root {
        --teaus-green-dark: #2e7d32; /* Deep Green */
        --teaus-green-light: #4caf50; /* Primary Green */
        --teaus-yellow-accent: #ffeb3b; /* Accent Yellow */
        --teaus-text-secondary: #c8e6c9; /* Light green for secondary text */
        --teaus-link-active-bg: #ffc107; /* Darker yellow for active background */
        --header-height: 3.2rem;
        --sidebar-width: 240px; /* Slightly wider sidebar for better content spacing */
    }

    body {
        background-color: #e8f5e8;
        font-family: "Segoe UI", sans-serif;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        font-size: 0.9rem; /* Slightly larger base font */
    }

    /* Custom Button/Accent Styles (Keeping original good styles) */
    .teaus-primary {
        background: var(--teaus-green-light);
        color: #fff;
        transition: 0.3s ease;
        border-color: var(--teaus-green-light);
    }

    .teaus-primary:hover {
        background: var(--teaus-yellow-accent);
        color: var(--teaus-green-dark);
        border-color: var(--teaus-yellow-accent);
    }

    .text-primary {
        color: var(--teaus-green-light) !important;
    }
               h2 {
            font-size: 18px;
        }


    /* ====================================
    SIDEBAR & HEADER STYLES
    ==================================== */

    .mobile-fixed-header {
        height: var(--header-height);
        position: fixed; top: 0; left: 0; width: 100%; z-index: 1100;
        background: var(--teaus-green-dark); border-bottom: 2px solid var(--teaus-green-light);
        display: flex; justify-content: space-between; align-items: center; padding: .5rem 1rem;
    }

    /* Fix for White Hamburger Menu Icon */
    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23fff' viewBox='0 0 30 30'%3E%3Cpath stroke='rgba%28255,255,255,1%29' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E") !important;
    }

    .sidebar {
        width: var(--sidebar-width);
        /* Enhanced Gradient */
        background: linear-gradient(180deg, var(--teaus-green-dark) 0%, #388e3c 80%, var(--teaus-green-light) 100%);
        color: white;
        height: 100vh;
        position: fixed;
        left: 0;
        padding: 0; /* Remove top/bottom padding to control header/footer areas */
        border-radius: 0 12px 12px 0;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.25); /* Stronger shadow for depth */
        transform: translateX(-100%);
        transition: transform .3s ease-in-out, box-shadow .3s ease;
        z-index: 1040;
        display: flex;
        flex-direction: column;
        justify-content: space-between; /* Use space-between for bottom logout */
    }

    /* New: Sidebar Header/Branding Area */
    .sidebar-header {
        padding: 15px 15px 10px 15px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 10px;
    }

    .sidebar-header h2 {
        font-size: 1.35rem;
        margin: 0;
        letter-spacing: 1px;
    }

    /* New: Sidebar User Info */
    .user-info {
        padding: 5px 15px 15px 15px;
        text-align: center;
        font-size: 0.8rem;
        color: var(--teaus-text-secondary);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 15px;
    }

    .user-info .fw-bold {
        font-size: 0.9rem;
        color: white; /* Name should stand out */
        display: block;
    }

    /* Sidebar Link Styles */
    .sidebar .nav-item {
        margin: 0 10px 5px 10px;
    }

    .sidebar .nav-link {
        color: white;
        padding: 10px 15px;
        transition: 0.3s;
        border-radius: 6px; /* Slightly more rounded corners */
        font-size: 0.95rem;
    }

    /* Hover and Active state FIX */
    .sidebar .nav-link:hover {
        background: rgba(255, 255, 255, 0.1) !important; /* Slight white background on hover */
        transform: translateX(3px); /* Subtle slide-out effect */
    }

    /* Active state is primary focus */
    .sidebar .nav-link.active {
        background: var(--teaus-yellow-accent) !important;
        color: var(--teaus-green-dark) !important;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Ensure icons also change color on hover/active */
    .sidebar .nav-link:hover i {
        color: var(--teaus-yellow-accent) !important; /* Yellow icon on hover */
    }
    .sidebar .nav-link.active i {
        color: var(--teaus-green-dark) !important;
    }

    /* New: Logout section styling (desktop only) */
    .sidebar-footer-links {
        padding: 15px 0 15px 0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    .sidebar-footer-links .nav-link:hover {
        background: #d32f2f !important; /* Red background on logout hover */
        transform: none;
    }

    /* RESPONSIVE MEDIA QUERIES (Keep the good logic) */
    @media (max-width: 991.98px) {
        body { padding-top: var(--header-height); }
        .sidebar {
            top: var(--header-height);
            height: calc(100vh - var(--header-height));
            transform: translateX(-100%);
            border-radius: 0 0 0 0; /* Remove border-radius on mobile for full-height slide-out */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5); /* Stronger shadow when open */
        }
        .sidebar.active { transform: translateX(0) !important; }
        .main-content { margin-left: 0; margin-top: 0; }
        .sidebar-header { display: none !important; } /* Hide large branding on mobile to save space */
    }

    @media (min-width: 992px) {
        body { padding-top: 0; }
        .mobile-fixed-header { display: none !important; }
        .sidebar {
            top: 0;
            height: 100vh;
            transform: translateX(0); /* Always visible on desktop */
        }
        .main-content {
            margin-left: calc(var(--sidebar-width) + 25px);
            margin-top: 0;
        }
        .footer {
            margin-left: calc(var(--sidebar-width) + 25px);
        }
        .user-info { display: block; } /* Show user info on desktop */
    }

    .sidebar-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, .5); z-index: 1030; display: none; }
    .sidebar-overlay.show { display: block; }

    /* ===============================
    MAIN CONTENT & FOOTER (Keep rest of existing styles)
    ================================= */
    .main-content {
        padding: 20px;
        flex: 1;
        width: 100%;
        overflow-x: hidden;
        transition: margin-left .3s ease;
    }
    /* ... rest of your existing content and footer styles ... */
    .card {
        border: none;
        box-shadow: 0 4px 6px rgba(46, 125, 50, 0.15);
        border-radius: 8px;
    }
    
    .card-header {
        background: var(--teaus-green-light);
        color: #fff;
        font-weight: 600;
        border-bottom: none;
        padding: 15px;
        border-radius: 8px 8px 0 0;
    }

    #usersTable {
        width: 100% !important;
        font-size: 0.85rem;
        table-layout: auto;
    }

    #usersTable thead th {
        background-color: var(--teaus-green-dark);
        color: white;
        font-size: 0.8rem;
        white-space: nowrap;
    }

    .action-buttons {
        white-space: nowrap;
    }

    .footer {
        background-color: var(--teaus-green-light);
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
        color: var(--teaus-yellow-accent);
    }

    /* ===============================
    MODALS & RESPONSIVE TABLE
    ================================= */
    @media (max-width: 991.98px) {
        .modal.fade .modal-dialog {
            margin-top: calc(var(--header-height) + 1rem);
        }
    }
    
    .modal-header {
        background-color: var(--teaus-green-light);
        color: white;
        padding: 15px;
        border-bottom: none;
    }
    
    .modal-header .btn-close-white {
        filter: brightness(0) invert(1);
    }
    
    #deleteUserModal .modal-header {
        background-color: #dc3545 !important;
    }


    @media (max-width: 992px) {
        /* Hide Program, Year, Semester columns on medium screens */
        #usersTable th:nth-child(4), #usersTable td:nth-child(4),
        #usersTable th:nth-child(5), #usersTable td:nth-child(5),
        #usersTable th:nth-child(6), #usersTable td:nth-child(6) {
            display: none !important;
        }
    }
    /* Add this block inside your existing <style> tag */
@media (max-width: 575.98px) {
    /* Targets the icon within the mobile logout button */
    .btn-mobile-logout .fa-sign-out-alt {
        /* Example: Reduces the icon size from the default 1rem */
        font-size: 0.8rem;
    }
    
    /* Example: Reduces the button padding slightly */
    .btn-mobile-logout {
        padding: 0.3rem !important; /* Adjust padding if needed */
    }
}
    @media (max-width: 575.98px) {
        .table-responsive {
            overflow-x: auto;
        }
        #usersTable {
            font-size: 0.75rem !important;
        }
        /* Hide Full Name column on small screens */
        #usersTable th:nth-child(2),
        #usersTable td:nth-child(2) {
            display: none !important;
        }
        
        #usersTable td:nth-child(3) { /* Email column */
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: row;
            gap: 3px;
        }
        
        .action-buttons .btn {
            padding: 0.2rem 0.4rem !important;
            font-size: 0.65rem !important;
        }
    }
</style>
</head>

<body>

    <header class="mobile-fixed-header d-lg-none">
        <button class="navbar-toggler" id="sidebarToggle" aria-label="Toggle navigation menu" type="button">
            <span class="navbar-toggler-icon"></span> 
        </button>
        <h5 class="mb-0 text-white fw-bold mx-auto">TEAU Admin</h5>
        
        <a href="logout.php" class="btn btn-sm ms-2 btn-mobile-logout text-white" style="background: transparent !important; border: none;">
            <i class="fas fa-sign-out-alt me-1">logout</i>
        </a>
    </header>

    <div class="d-flex">
        <nav class="sidebar d-flex flex-column p-3" id="sidebar">
            <h2 class="d-none d-lg-block text-white"><i class="fas fa-university logo-icon mx-1"></i>TEAU Admin</h2>

            <ul class="nav flex-column mt-3 flex-grow-1">
                <li class="nav-item mb-1">
                    <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a> 
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link" href="course.php"><i class="fas fa-book me-2"></i>Course Management</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link active" href="user.php"><i class="fas fa-users me-2"></i>User Management</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link" href="Analytic.php"><i class="fas fa-chart-line me-2"></i>Analytics Reports</a> 
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link" href="admin_profile.php"><i class="fas fa-user-circle me-2"></i>My Profile</a>
                </li>
            </ul>

            <ul class="nav flex-column pt-3 mt-auto d-none d-lg-block">
                <li class="nav-item">
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <main class="main-content flex-grow-1">
            <div class="container-fluid">
                <?php if (!empty($status_message)): ?>
                    <div class="alert <?= $status_class ?> alert-dismissible fade show" role="alert">
                        <?= $status_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <div class="row mb-3">
                    <div class="col-12">
                         <div class="row mb-4 header-row d-flex justify-content-between align-items-center">

                            <div class="col-lg-6 col-12 order-lg-1 order-2 header-title-container">
                                <h1 class="h4 mb-0" style="color: var(--teaus-green-dark);">
                                    <i class="fas fa-user-friends me-2"></i>User Management
                                </h1>
                            </div>

                            <div class="col-lg-6 col-12 text-lg-end order-lg-2 order-1 header-welcome-container">
                                <h3 class="mb-0" style="color: var(--teaus-green-dark); font-size: 1.1rem;">
                                    Welcome back, <span class="fw-bold text-primary"><?= $admin_username ?></span>
                                </h3>
                            </div>
                        </div>

                        <p class="text-muted">
                            Add, edit, or delete user accounts for TEAU programs.
                        </p>
                        <button class="btn teaus-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-user-plus me-2"></i>Add New User
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card p-0">
                            <h5 class="card-header">
                                <i class="fas fa-table me-2"></i>Users List
                            </h5>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="usersTable" class="table table-striped" style="width: 100%">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Full Name</th>
                                                <th>Email address</th>
                                                <th>Program</th>
                                                <th>Year</th>
                                                <th>Semester</th>
                                                <th>Role</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($result->num_rows > 0) {
                                                $displayId = 1; // Initialize the counter for display
                                                while ($row = $result->fetch_assoc()) {
                                                    // Escape data for both HTML display and JS function calls
                                                    $js_data = [
                                                        'id' => htmlspecialchars($row['id'], ENT_QUOTES),
                                                        'fullName' => htmlspecialchars($row['fullName'], ENT_QUOTES),
                                                        'email' => htmlspecialchars($row['email'], ENT_QUOTES),
                                                        'program' => htmlspecialchars($row['program'], ENT_QUOTES),
                                                        'year' => htmlspecialchars($row['year'], ENT_QUOTES),
                                                        'semester' => htmlspecialchars($row['semester'], ENT_QUOTES),
                                                        'userType' => htmlspecialchars($row['userType'], ENT_QUOTES)
                                                    ];
                                                    
                                                    echo "<tr>";
                                                    echo "<td>" . $displayId . "</td>"; // Display the sequential counter
                                                    echo "<td>" . htmlspecialchars($row['fullName']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['program']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['year']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['userType']) . "</td>";
                                                    echo '<td class="action-buttons">';
                                                    
                                                    // Edit Button - passing escaped data to JavaScript
                                                    echo '<button class="btn btn-sm" style="background-color: transparent; border-color: var(--teaus-green-light); color: var(--teaus-green-light);" data-bs-toggle="modal" data-bs-target="#editUserModal" onclick="editUser(\'' . 
                                                         $js_data['id'] . '\', \'' . $js_data['fullName'] . '\', \'' . $js_data['email'] . '\', \'' . $js_data['program'] . '\', \'' . $js_data['year'] . '\', \'' . $js_data['semester'] . '\', \'' . $js_data['userType'] . '\')">';
                                                    echo '<i class="fas fa-edit"></i></button>';
                                                    
                                                    // Delete Button
                                                    echo '<button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal" data-user-id="' . $js_data['id'] . '">';
                                                    echo '<i class="fas fa-trash"></i></button>';
                                                    echo '</td>';
                                                    echo "</tr>";
                                                    $displayId++; // Increment the counter
                                                }
                                            } else {
                                                echo '<tr><td colspan="8">No users found.</td></tr>';
                                            }
                                            $conn->close();
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <footer class="footer mt-auto py-3">
        <div class="container text-center">
            <p>Created with <span style="color: var(--teaus-yellow-accent);">❤️</span> for TEAU. &copy; 2025.</p>
            <div class="social-icons mt-2">
                <a href="#" class="text-white"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form action="add_user.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-user-plus me-2"></i>Add User
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control form-control-sm" name="fullName" required />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control form-control-sm" name="email" required />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control form-control-sm" name="password" required />
                        </div>
                        <div class="mb-2">
                            <label for="program" class="form-label">Program of Study</label>
                            <select class="form-select form-select-sm" id="program" name="program" required>
                                <option value="">Select your program</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Business Administration">Business Administration</option>
                                <option value="Nursing">Nursing</option>
                                <option value="Education">Education</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-control form-control-sm" name="year" min="1" max="4" />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Semester</label>
                            <input type="number" class="form-control form-control-sm" name="semester" min="1" max="2" />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Role</label>
                            <select class="form-select form-select-sm" name="userType" required>
                                <option value="">Select</option>
                                <option value="admin">Admin</option>
                                <option value="student">Student</option>
                                <option value="faculty">Faculty</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                            Close
                        </button>
                        <button type="submit" class="btn teaus-primary btn-sm">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form action="edit_user.php" method="POST"> 
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Edit User
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editUserId" name="id" />
                        <div class="mb-2">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control form-control-sm" id="editFullName" name="fullName" required />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control form-control-sm" id="editEmail" name="email" required />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Program</label>
                            <select class="form-select form-select-sm" id="editProgram" name="program" required>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Business Administration">Business Administration</option>
                                <option value="Nursing">Nursing</option>
                                <option value="Education">Education</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-control form-control-sm" id="editYear" name="year" min="1" max="4" />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Semester</label>
                            <input type="number" class="form-control form-control-sm" id="editSemester" name="semester" min="1" max="2" />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Role</label>
                            <select class="form-select form-select-sm" id="editUserType" name="userType" required>
                                <option value="admin">Admin</option>
                                <option value="student">Student</option>
                                <option value="faculty">Faculty</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-danger">New Password (Leave blank to keep old)</label>
                            <input type="password" class="form-control form-control-sm" name="password" placeholder="******" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                            Close
                        </button>
                        <button type="submit" class="btn teaus-primary btn-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white"> 
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this user? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <a id="deleteUserLink" href="#" class="btn btn-danger btn-sm">Delete</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable with responsive setting
            $("#usersTable").DataTable({
                responsive: true,
                "pageLength": 10,
                "autoWidth": false,
                "scrollX": false,
                "language": {
                    "search": "Filter Users:",
                    "lengthMenu": "Show _MENU_ users",
                    "zeroRecords": "No matching users found",
                    "info": "Showing _START_ to _END_ of _TOTAL_ users",
                    "infoEmpty": "Showing 0 to 0 of 0 users",
                    "infoFiltered": "(filtered from _MAX_ total users)"
                }
            });

            // Handle delete button click to set the modal link
            $('#deleteUserModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var userId = button.data('user-id');
                var deleteLink = document.getElementById('deleteUserLink');
                deleteLink.href = 'delete_user.php?id=' + userId;
            });
            
            // Sidebar Toggle for mobile
            $('#sidebarToggle, #sidebarOverlay').on('click', function() {
                $('#sidebar').toggleClass('active');
                
                // Toggle overlay visibility
                $('#sidebarOverlay').toggleClass('show'); 
                
                if ($(window).width() < 992) {
                    $('body').toggleClass('overflow-hidden', $('#sidebar').hasClass('active'));
                }
            });
        
            // Control sidebar state on initial load/resize
            $(window).on('load resize', function() {
                if ($(window).width() >= 992) {
                    $('#sidebar').addClass('active'); // Keep active on desktop
                    $('#sidebarOverlay').removeClass('show');
                    $('body').removeClass('overflow-hidden');
                } else {
                    $('#sidebar').removeClass('active'); // Hide on mobile by default
                    // Note: Toggle logic above handles showing/hiding on mobile click
                }
            });
        });

        // Function to populate the edit modal with user data
        function editUser(id, fullName, email, program, year, semester, userType) {
            document.getElementById("editUserId").value = id;
            document.getElementById("editFullName").value = fullName;
            document.getElementById("editEmail").value = email;
            document.getElementById("editProgram").value = program;
            document.getElementById("editYear").value = year;
            document.getElementById("editSemester").value = semester;
            document.getElementById("editUserType").value = userType;
        }
    </script>
</body>

</html>
