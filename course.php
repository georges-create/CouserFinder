<?php
// Fix Start: Using @session_start() for maximum compatibility.
@session_start();

// include config.php (Assumes $conn is established here)
include "config.php";

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: auth.php");
    exit();
}

// Get the admin's username from the session
$admin_username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : "Super Admin";

// Fetch all courses from the database
$sql = "SELECT * FROM courses ORDER BY program, year, semester, code";
$result = mysqli_query($conn, $sql);

$course_data = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $course_data[] = $row;
    }
}

// Check for and display status messages from URL parameters
$status_message = "";
$status_class = "";

if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'success_add':
            $status_message = "Course has been successfully added.";
            $status_class = "alert-success";
            break;
        case 'success_edit':
            $status_message = "Course has been successfully updated.";
            $status_class = "alert-success";
            break;
        case 'success_delete':
            $status_message = "Course has been successfully deleted.";
            $status_class = "alert-success";
            break;
        case 'error':
            // Safely output the error message if provided, otherwise a generic error.
            $status_message = "An error occurred: " . (isset($_GET['message']) ? htmlspecialchars($_GET['message']) : "Unknown error.");
            $status_class = "alert-danger";
            break;
    }
}
    
// Close connection after fetching data
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TEAU Admin - Course Management</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet" />

    <style>
/* ==========================
BASE STYLES
========================== */
html, body {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden; /* Prevent horizontal scroll globally */
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #E8F5E8;
    color: #333;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    font-size: 0.9rem;
    margin: 0;
    --header-height: 3.2rem;
}

/* Prevent scroll when sidebar open */
.overflow-hidden {
    overflow: hidden !important;
}

.teaus-primary {
    background: #4CAF50;
    color: #fff;
    transition: 0.3s ease;
}

.teaus-primary:hover {
    background: #FFEB3B;
    color: #2E7D32;
}

/* ==========================
SIDEBAR & MAIN CONTENT
========================== */
.sidebar {
    width: 220px;
    background: linear-gradient(to bottom, #2E7D32, #4CAF50);
    color: #fff;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    padding: 15px 0;
    border-radius: 0 12px 12px 0;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15);
    transform: translateX(-100%);
    transition: transform .3s ease-in-out;
    z-index: 1040;
}

/* Show sidebar on desktop */
@media (min-width: 992px) {
    .sidebar {
        transform: translateX(0);
        overflow-y: auto;
        height: 100vh;
    }
}

/* Mobile sidebar under header */
@media (max-width: 991.98px) {
    .sidebar {
        top: var(--header-height);
        height: calc(100vh - var(--header-height));
        overflow-y: auto;
    }
}

.sidebar h2 {
    text-align: center;
    font-size: 1.25rem;
    margin-bottom: 20px;
}

.sidebar a {
    display: block;
    padding: 10px 15px;
    margin: 0 8px;
    color: #e9ecef;
    text-decoration: none;
    border-radius: 6px;
    transition: 0.3s;
}

.sidebar a:hover,
.sidebar a.active {
    background: #FFEB3B;
    color: #2E7D32;
    transform: translateX(5px);
}

.sidebar.active {
    transform: translateX(0) !important;
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
HEADER (FIXED ON MOBILE)
========================== */
.mobile-fixed-header {
    height: var(--header-height);
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1100; /* NOTE: Fixed header has a high Z-Index */
    background: #2E7D32;
    border-bottom: 2px solid #4CAF50;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .5rem 1rem;
}

.mobile-fixed-header h5 {
    color: #fff;
    font-weight: 600;
    margin: 0;
    text-align: center;
    flex: 1;
}

.btn-mobile-logout {
    background-color: #2E7D32 !important;
    border: none !important;
    color: #fff !important;
    font-weight: 600;
}

.btn-mobile-logout:hover {
    background-color: #4CAF50 !important;
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
MAIN CONTENT
========================== */
.main-content {
    margin-left: 0;
    padding: 20px;
    flex: 1;
    margin-top: var(--header-height); /* Push below fixed header on mobile */
    width: 100%;
    overflow-x: hidden;
    transition: margin-left .3s ease;
}

@media (min-width: 992px) {
    .main-content {
        margin-left: 235px;
        margin-top: 0;
    }
}

/* ==========================
CARDS & TABLES
========================== */
.card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(46, 125, 50, 0.1);
    margin-bottom: 15px;
    border: none;
}

.card-header {
    background: #4CAF50;
    color: #fff;
    font-weight: 600;
    border-bottom: none;
    padding: 15px;
    border-radius: 10px 10px 0 0;
}

.card-header.h5-custom-bg {
    background: white;
    color: #333;
    font-weight: 600;
}

#coursesTable {
    width: 100% !important;
    border-collapse: collapse;
}

#coursesTable thead th {
    background-color: #4CAF50 !important;
    color: #fff !important;
}

/* Force table container to never overflow screen */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* ==========================
MODAL Z-INDEX FIX
========================== */
/* Fix: Modals and their backdrops must have a higher z-index than the fixed header (z-index: 1100). */
.modal-backdrop {
    z-index: 1190 !important;
}
.modal {
    z-index: 1200 !important;
}

/* ==========================
FOOTER
========================== */
.footer {
    background: #4CAF50;
    color: #fff;
    text-align: center;
    padding: 10px;
    margin-top: auto;
}

@media (min-width: 992px) {
    .footer {
        margin-left: 235px;
    }
}

/* ==========================
RESPONSIVE QUERIES (FIXED)
========================== */

/* Tablets & small desktops (991.98px and down) - Keep ID, Unit Name, Actions visible */
@media (max-width: 991.98px) {
    .main-content { padding: 15px; }

    /* Hide Program(2), Year(3), Sem(4), Code(5), Description(7) */
    #coursesTable thead th:nth-child(2),
    #coursesTable tbody td:nth-child(2),
    #coursesTable thead th:nth-child(3),
    #coursesTable tbody td:nth-child(3),
    #coursesTable thead th:nth-child(4),
    #coursesTable tbody td:nth-child(4),
    #coursesTable thead th:nth-child(5),
    #coursesTable tbody td:nth-child(5),
    #coursesTable thead th:nth-child(7),
    #coursesTable tbody td:nth-child(7) {
        display: none !important;
    }
    
    /* FIX: Make action buttons side-by-side on tablets and small phones */
    .action-buttons {
        display: flex !important; /* Use flexbox */
        flex-direction: row !important; /* Arrange horizontally */
        justify-content: center; /* Center them in the small cell */
        gap: 5px !important; /* Add space between buttons */
    }
    
    .action-buttons button {
        /* Reset button size for tablet/larger phone view */
        width: auto !important;
        height: auto !important;
        padding: 0.25rem 0.4rem !important;
        font-size: 0.8rem !important;
    }
}


/* Phones (575.98px and down) - Keep ID, Unit Name, Actions */
@media (max-width: 575.98px) {
    body { font-size: 0.8rem; }

    .main-content { padding: 10px; }

    /* Action buttons: side-by-side (inherited from above, but reinforce for smallest sizes) */
    .action-buttons {
        display: flex !important;
        flex-direction: row !important; 
        justify-content: center;
        gap: 3px !important; /* Tighter gap on very small screens */
    }

    .action-buttons button {
        /* Keep buttons small and square for tiny screens */
        width: 26px !important;
        height: 26px !important;
        padding: 0 !important;
        font-size: 0.7rem !important;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Column Visibility Control (same as before) */
    /* Hide: Program (2), Year (3), Sem (4), Code (5) */
    #coursesTable thead th:nth-child(2),
    #coursesTable tbody td:nth-child(2),
    #coursesTable thead th:nth-child(3),
    #coursesTable tbody td:nth-child(3),
    #coursesTable thead th:nth-child(4),
    #coursesTable tbody td:nth-child(4),
    #coursesTable thead th:nth-child(5),
    #coursesTable tbody td:nth-child(5) {
        display: none !important;
    }

    /* Hide: Description (7) */
    #coursesTable thead th:nth-child(7),
    #coursesTable tbody td:nth-child(7) {
        display: none !important;
    }

    /* DataTables overflow fix */
    .dataTables_wrapper {
        width: 100% !important;
        overflow-x: auto !important;
    }

    table.dataTable {
        width: 100% !important;
        min-width: 300px; 
    }
}
/* Tiny phones (450px and down) */
@media (max-width: 450px) {
    #coursesTable th, #coursesTable td {
        text-align: center;
        padding: 0.3rem !important;
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
        
        <a href="logout.php" class="btn btn-sm ms-2 btn-mobile-logout">
            <i class="fas fa-sign-out-alt me-1"></i>Logout
        </a>
    </header>

    <div class="d-flex">
        <nav class="sidebar d-flex flex-column p-3" id="sidebar">
            <h2 class="d-none d-lg-block"><i class="fas fa-university logo-icon mx-1"></i>TEAU Admin</h2>

            <ul class="nav flex-column mt-3 flex-grow-1">

                <li class="nav-item mb-1">
                    <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a> 
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link active" href="course.php"><i class="fas fa-book me-2"></i>Course Management</a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link" href="user.php"><i class="fas fa-users me-2"></i>User Management</a>
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

                <div class="row mb-4 header-row d-flex justify-content-between align-items-center">

                    <div class="col-lg-6 col-12 order-lg-1 order-2 header-title-container">
                        <h1 class="h4 mb-0" style="color: #2E7D32;">
                            <i class="fas fa-cogs me-2"></i>Course Management
                        </h1>
                    </div>

                    <div class="col-lg-6 col-12 text-lg-end order-lg-2 order-1 header-welcome-container">
                        <h3 class="mb-0" style="color: #2E7D32; font-size: 1.1rem;">
                            Welcome back, <span class="fw-bold" style="color: #4CAF50;"><?= $admin_username ?></span>
                        </h3>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <p class="text-muted">Use the table below to manage all course units, and click the button to add new entries.</p>
                        <button class="btn teaus-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                            <i class="fas fa-plus me-2"></i>Add New Course
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card p-0">
                            <h5 class="card-header h5-custom-bg text-success">
                                <i class="fas fa-table me-2"></i>Courses and Units List
                            </h5>
                            <div class="card-body p-3">
                                <div class="table-responsive">
                                    <table id="coursesTable" class="table table-striped" style="width: 100%">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Program</th>
                                                <th>Year</th>
                                                <th>Sem</th>
                                                <th>Code</th>
                                                <th>Unit Name</th>
                                                <th>Description</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Use the collected $course_data array for displaying the table
                                            if (!empty($course_data)) {
                                                foreach ($course_data as $row) {
                                                    echo "<tr>";
                                                    // FIX: Display the actual database ID 
                                                    echo "<td>" . htmlspecialchars($row['id']) . "</td>"; 
                                                    echo "<td>" . htmlspecialchars($row['program']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['year']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['code']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                                    echo '<td class="action-buttons">';
                                                    // Edit Button with custom style
                                                    echo '<button class="btn btn-sm" style="background-color: transparent; border-color: #4CAF50; color: #4CAF50;" data-bs-toggle="modal" data-bs-target="#editCourseModal" onclick="editCourse(' .
                                                         $row['id'] . ', \'' . htmlspecialchars(addslashes($row['program'])) . '\', \'' . htmlspecialchars(addslashes($row['year'])) . '\', \'' . htmlspecialchars(addslashes($row['semester'])) . '\', \'' .
                                                         htmlspecialchars(addslashes($row['code'])) . '\', \'' . htmlspecialchars(addslashes($row['name'])) . '\', \'' .
                                                         htmlspecialchars(addslashes($row['description'])) . '\')">';
                                                    echo '<i class="fas fa-edit"></i></button>';
                                                    // Delete Button
                                                    echo '<button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteCourseModal" data-course-id="' . $row['id'] . '">';
                                                    echo '<i class="fas fa-trash"></i></button>';
                                                    echo '</td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="8">No courses found.</td></tr>';
                                            }
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

    <footer class="footer py-3 mt-auto">
        <div class="container text-center">
            <p class="m-0">Created with <span style="color: #FFEB3B;">❤️</span> for TEAU. &copy; 2025.</p>
            <div class="social-icons mt-2">
                <a href="#" class="text-white"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <div class="modal fade" id="addCourseModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form action="add_course.php" method="POST">
                    <div class="modal-header" style="background-color: #4CAF50; color: white; border-radius: 10px 10px 0 0;">
                        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Course</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Program</label>
                            <select class="form-select form-select-sm" name="program" required>
                                <option value="">Select program</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Business Administration">Business Administration</option>
                                <option value="Nursing">Nursing</option>
                                <option value="Education">Education</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Year</label>
                            <input type="number" class="form-control form-control-sm" name="year" min="1" max="4" required />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Semester</label>
                            <input type="number" class="form-control form-control-sm" name="semester" min="1" max="2" required />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Unit Code</label>
                            <input type="text" class="form-control form-control-sm" name="code" required />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Unit Name</label>
                            <input type="text" class="form-control form-control-sm" name="name" required />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Description</label>
                            <textarea class="form-control form-control-sm" name="description" rows="2" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn teaus-primary btn-sm">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCourseModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form action="edit_course.php" method="POST">
                    <div class="modal-header" style="background-color: #4CAF50; color: white; border-radius: 10px 10px 0 0;">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Course</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editCourseId" name="id" />
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
                            <input type="number" class="form-control form-control-sm" id="editYear" name="year" min="1" max="4" required />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Semester</label>
                            <input type="number" class="form-control form-control-sm" id="editSemester" name="semester" min="1" max="2" required />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Unit Code</label>
                            <input type="text" class="form-control form-control-sm" id="editCode" name="code" required />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Unit Name</label>
                            <input type="text" class="form-control form-control-sm" id="editName" name="name" required />
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Description</label>
                            <textarea class="form-control form-control-sm" id="editDescription" name="description" rows="2" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn teaus-primary btn-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteCourseModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this course?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <a id="deleteCourseLink" href="delete_course.php" class="btn btn-danger btn-sm">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {

        // Initialize DataTables
        $("#coursesTable").DataTable({
            responsive: true,
            "pageLength": 10,
            // CRITICAL FIX: Disable Auto-Width to prevent forced horizontal scroll
            "autoWidth": false, 
            "scrollX": false,
            // END CRITICAL FIX
            "language": {
                "search": "Filter Courses:",
                "lengthMenu": "Show _MENU_ courses",
                "zeroRecords": "No matching courses found",
                "info": "Showing _START_ to _END_ of _TOTAL_ courses",
                "infoEmpty": "Showing 0 to 0 of 0 courses",
                "infoFiltered": "(filtered from _MAX_ total courses)"
            }
        });

        // Event listener for the delete modal opening
        $('#deleteCourseModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var courseId = button.data('course-id');
            var modal = $(this);
            // Sets the href of the Delete button to the delete script
            modal.find('#deleteCourseLink').attr('href', 'delete_course.php?id=' + courseId);
        });

        // Sidebar Toggle for mobile
        $('#sidebarToggle, #sidebarOverlay').on('click', function() {
            $('#sidebar').toggleClass('active');
            $('#sidebarOverlay').toggleClass('show');
            
            // FIX: Toggle overflow-hidden on the body for mobile view (stops vertical scroll)
            if ($(window).width() < 992) {
                $('body').toggleClass('overflow-hidden', $('#sidebar').hasClass('active'));
            }
        });
        
        // Ensure sidebar starts closed on small screens
        if ($(window).width() < 992) {
            $('#sidebar').removeClass('active');
            $('#sidebarOverlay').removeClass('show');
            $('body').removeClass('overflow-hidden');
        } else {
            $('#sidebar').addClass('active'); // Keep it visible on large screens
        }
    });

    // Global function to populate the Edit Course Modal
    function editCourse(id, program, year, semester, code, name, description) {
        document.getElementById('editCourseId').value = id;
        document.getElementById('editProgram').value = program;
        document.getElementById('editYear').value = year;
        document.getElementById('editSemester').value = semester;
        document.getElementById('editCode').value = code;
        document.getElementById('editName').value = name;
        document.getElementById('editDescription').value = description;
    }
    </script>
</body>

</html>