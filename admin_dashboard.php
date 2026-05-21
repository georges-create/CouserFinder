<?php
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

// Get the admin's username from the session
$admin_username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : "Super Admin";

// Initialize data array with default values, RECENT ACTIVITY REMOVED
$analytics_data = [
    'kpis' => ['totalStudents' => 0, 'totalCourses' => 0, 'activePrograms' => 0, 'totalFaculty' => 0, 'totalAdmin' => 0],
    'programEnrollment' => ['labels' => [], 'counts' => []],
    'detailedEnrollment' => [],
    'userActivity' => ['active' => 0, 'dormant' => 0, 'totalUsers' => 0],
    'visitsByYear' => ['labels' => [], 'counts' => []],
];
$error_message = '';

// Assuming $conn is correctly set up in config.php
if (isset($conn) && $conn->connect_error) {
    $error_message = 'Database Connection Failed: ' . $conn->connect_error;
} elseif (isset($conn)) {
    try {
        // --- 1. Fetch Core KPIs ---
        $kpis = [];
        $kpis['totalStudents'] = $conn->query("SELECT COUNT(id) FROM user WHERE userType='student'")->fetch_row()[0] ?? 0;
        $kpis['totalFaculty'] = $conn->query("SELECT COUNT(id) FROM user WHERE userType='faculty'")->fetch_row()[0] ?? 0;
        $kpis['totalAdmin'] = $conn->query("SELECT COUNT(id) FROM user WHERE userType='admin'")->fetch_row()[0] ?? 0;
        $kpis['totalCourses'] = $conn->query("SELECT COUNT(id) FROM courses")->fetch_row()[0] ?? 0;
        $kpis['activePrograms'] = $conn->query("SELECT COUNT(DISTINCT program) FROM user WHERE userType='student'")->fetch_row()[0] ?? 0;

        // --- 2. Fetch Detailed Enrollment Data for Charts and Table ---
        $enrollment_results = $conn->query("
            SELECT program, year, COUNT(id) AS count
            FROM user
            WHERE userType = 'student'
            GROUP BY program, year
            ORDER BY program, year
        ");

        $detailedEnrollment = [];
        $programCounts = [];

        if ($enrollment_results) {
            while ($row = $enrollment_results->fetch_assoc()) {
                $program = $row['program'];
                $year = "y" . $row['year'];
                $count = (int)$row['count'];
                
                if (!isset($detailedEnrollment[$program])) {
                    $detailedEnrollment[$program] = ['program' => $program, 'y1' => 0, 'y2' => 0, 'y3' => 0, 'y4' => 0, 'total' => 0];
                    $programCounts[$program] = 0;
                }
                
                $detailedEnrollment[$program][$year] = $count;
                $detailedEnrollment[$program]['total'] += $count;
                $programCounts[$program] += $count;
            }
        }

        $programEnrollment = [
            'labels' => array_keys($programCounts),
            'counts' => array_values($programCounts)
        ];
        
        // --- USER ANALYTICS LOGIC ---
        $totalUsers = $conn->query("SELECT COUNT(id) FROM user")->fetch_row()[0] ?? 0;
        $userActivity = ['active' => 0, 'dormant' => 0, 'totalUsers' => (int)$totalUsers];

        $active_threshold = date('Y-m-d H:i:s', strtotime('-30 days'));

        // *** FIX 1: Uses login_time
        $active_users_query = $conn->query("
            SELECT COUNT(DISTINCT user_id) AS active_count
            FROM user_activity_log
            WHERE login_time >= '{$active_threshold}'
        ");
        
        $active_count = $active_users_query ? ($active_users_query->fetch_assoc()['active_count'] ?? 0) : 0;
        $dormant_count = $totalUsers - $active_count;
        
        $userActivity['active'] = (int)$active_count;
        $userActivity['dormant'] = (int)$dormant_count;

        // *** FIX 2: Uses login_time
        $visits_by_year_results = $conn->query("
            SELECT YEAR(login_time) AS visit_year, COUNT(id) AS visit_count
            FROM user_activity_log
            GROUP BY visit_year
            ORDER BY visit_year
        ");

        $visitsByYear = ['labels' => [], 'counts' => []];
        if ($visits_by_year_results) {
            while ($row = $visits_by_year_results->fetch_assoc()) {
                $visitsByYear['labels'][] = $row['visit_year'];
                $visitsByYear['counts'][] = (int)$row['visit_count'];
            }
        }
        
        // Finalize data structure
        $analytics_data = [
            'kpis' => $kpis,
            'programEnrollment' => $programEnrollment,
            'detailedEnrollment' => array_values($detailedEnrollment),
            'userActivity' => $userActivity,
            'visitsByYear' => $visitsByYear,
        ];

    } catch (Exception $e) {
        $error_message = 'Query Error: ' . $e->getMessage() . ' (Check your **user_activity_log** table structure!)';
    }
    
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
// =========================================================
// END OF PHP DATABASE LOGIC
// =========================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEAU Admin - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>

    <style>
    /* ==========================
    BASE STYLES & THEME
    ========================== */
    :root {
        --header-height: 3.2rem;
        --sidebar-width: 210px; 
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
    }

    /* Primary Colors */
    .text-primary-teal { color: #2E7D32 !important; }
    .text-primary-green { color: #4CAF50 !important; }
    
    /* Card/KPI Styling */
    .card { background: #fff; border-radius: 10px; box-shadow: 0 4px 12px rgba(46, 125, 50, 0.1); border: none; padding: 0.2rem; margin-bottom: 15px; }
    .card-header { background: #4CAF50; color: #fff; font-weight: 600; padding: 0.5rem 1rem; border-radius: 10px 10px 0 0; font-size: 0.9rem; }
    .card-body { padding: 0.75rem !important; }
    .kpi-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.1rem; }
    .kpi-label { font-size: 0.75rem; margin-bottom: 0.1rem; }
    .border-start-custom { border-left-width: 0.3rem !important; }
    .chart-container { position: relative; height: 250px; }
    .table { font-size: 0.8rem; }
    .table thead th { background-color: #2E7D32 !important; color: #fff !important; padding: 0.5rem 0.5rem; }
    
    /* Quick Actions/Activity Feed Styling - Global reset for mobile to prevent interference */
    .quick-action-btn { transition: all 0.3s ease; margin-right: 0 !important; } 
    .quick-action-btn:hover { background-color: #E8F5E8; transform: translateY(-2px); }
    
    /* ====================================
    SIDEBAR & HEADER STYLES
    ==================================== */
    
    .mobile-fixed-header {
        height: var(--header-height);
        position: fixed; top: 0; left: 0; width: 100%; z-index: 1100;
        background: #2E7D32; border-bottom: 2px solid #4CAF50;
        display: flex; justify-content: space-between; align-items: center; padding: .5rem 1rem;
    }
    
    .navbar-toggler-icon-custom {
        display: inline-block; width: 1.5em; height: 1.5em; vertical-align: middle;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='white' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        background-repeat: no-repeat; background-position: center; background-size: 100%;
    }

    /* Style for the new mobile logout button (Icon and Text) */
    .mobile-logout-btn {
        color: #fff;
        text-decoration: none;
        padding: 0.25rem 0.5rem; 
        border-radius: 5px;
        font-size: 0.9rem;
        transition: background-color 0.3s;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    .mobile-logout-btn:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }


    .content-wrapper { display: flex; flex-direction: column; flex-grow: 1; width: 100%; }
    .main-content { padding: 15px; flex-grow: 1; transition: margin-left .3s ease; }

    .sidebar {
        width: var(--sidebar-width);
        background: linear-gradient(to bottom, #2E7D32, #4CAF50);
        color: #fff; 
        height: 100vh; 
        position: fixed; 
        left: 0; 
        overflow-y: hidden; 
        border-radius: 0 12px 12px 0;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15); 
        transition: transform .3s ease-in-out;
        z-index: 1040;
        display: flex; 
        flex-direction: column;
    }

    /* Scrollable container for main navigation links - uses flex-grow */
    .sidebar-nav-scroll {
        flex-grow: 1; 
        overflow-y: auto; 
    }
    
    /* Class for TEAU Admin title - hide on small screens */
    .sidebar .logo-full { 
        font-size: 1.5rem; 
        font-weight: 700; 
        text-align: center; 
        margin-bottom: 1rem; 
        padding: 15px 15px 0 15px; 
    }
    .sidebar a { padding: 10px 15px; margin: 0 8px 5px 8px; color: #e9ecef; font-size: 0.9rem; border-radius: 8px; display: block; }
    .sidebar a:hover, .sidebar a.active { background: #FFEB3B; color: #2E7D32; }

    /* RESPONSIVE MEDIA QUERIES */
    @media (max-width: 991.98px) {
        body { padding-top: var(--header-height); }
        .sidebar { 
            top: var(--header-height); 
            height: calc(100vh - var(--header-height)); 
            transform: translateX(-100%); 
        }
        .sidebar.active { transform: translateX(0) !important; }
        .main-content { margin-left: 0; }
        .welcome-message-container { padding: 0.5rem 1rem; margin-bottom: 1rem; background: #fff; border-bottom: 2px solid #4CAF50; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05); }
        
        /* New: Adjust sidebar link spacing for better density on small screens */
        .sidebar-nav-scroll .nav-item {
            margin-bottom: 0 !important;
        }
    }
    
    @media (min-width: 992px) {
        body { padding-top: 0; }
        .mobile-fixed-header { display: none !important; }
        .sidebar { 
            top: 0; 
            height: 100vh; 
            margin-right: 10px; 
            width: var(--sidebar-width); 
        }
        .main-content { 
            margin-left: calc(var(--sidebar-width) + 10px); 
            padding-right: 20px !important; 
        }
        .welcome-message-container { display: none !important; }
        
        /* Quick Actions Spacing */
        .quick-action-btn {
            margin-right: 15px !important; 
        }
        .row.g-2.text-center > div:last-child .quick-action-btn {
            margin-right: 0 !important; 
        }
        
        /* Footer Spacing */
        .footer {
            margin-left: calc(var(--sidebar-width) + 10px); 
        }
        .footer .container {
            max-width: calc(100% - var(--sidebar-width) - 50px); 
            margin-right: 0;
            margin-left: auto;
        }
    }

    .sidebar-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, .5); z-index: 1030; display: none; }
    .sidebar-overlay.show { display: block; }
    .body.overflow-hidden { overflow: hidden; }

    .footer { 
        background: #2E7D32; color: #fff; text-align: center; padding: 15px 0; font-size: 0.85rem; 
    }
    
    .footer .social-icons a { color: #fff; margin: 0 8px; font-size: 1.1rem; transition: color 0.3s; }
    .footer .social-icons a:hover { color: #FFEB3B; }

</style>
</head>
<body id="body-main">

    <header class="mobile-fixed-header d-lg-none">
        <button class="navbar-toggler p-0 border-0" id="sidebarToggle" aria-label="Toggle navigation menu" type="button">
            <span class="navbar-toggler-icon-custom"></span> 
        </button>
        <h5 class="mb-0 text-white fw-bold">TEAU Admin</h5>
        <a href="logout.php" class="mobile-logout-btn">
            <i class="fas fa-sign-out-alt me-1"></i> Logout
        </a>
    </header>

    <div class="content-wrapper">
        <nav class="sidebar" id="sidebar">
            <h2 class="logo-full d-none d-lg-block"><i class="fas fa-university logo-icon me-2"></i>TEAU Admin</h2>
            
            <div class="sidebar-nav-scroll">
                <ul class="nav flex-column mt-3">
                    <li class="nav-item mb-1">
                        <a class="nav-link active" href="admin_dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a> 
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
                        <a class="nav-link" href="admin_profile.php"><i class="fas fa-user-circle me-2"></i>My Profile</a>
                    </li>
                </ul>
            </div>
            
             <ul class="nav flex-column pt-3 pb-3 d-none d-lg-block">
                 <li class="nav-item">
                     <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                 </li>
             </ul>
        </nav>
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <main class="main-content">
            <div class="container-fluid p-lg-4 p-2"> 
                
                <div class="welcome-message-container d-lg-none text-end">
                    <p class="mb-0 text-primary-teal small fw-semibold">
                        Welcome, <?php echo $admin_username; ?>!
                    </p>
                </div>

                <h1 class="mb-3 text-primary-teal fs-4">
                    <i class="fas fa-tachometer-alt me-2"></i> TEAU Admin Dashboard
                </h1>
                <p class="text-muted small d-none d-lg-block">Welcome back, <?php echo $admin_username; ?>! Here's a summary of the system status.</p>


                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <strong>Database Error:</strong> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="row g-2 mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-primary-teal"><i class="fas fa-bolt me-2"></i> Quick Actions</div>
                            <div class="card-body">
                                <div class="row g-2 text-center">
                                    <div class="col-6 col-md-3">
                                        <a href="user.php?new=true" class="btn btn-outline-success w-100 quick-action-btn border-2 py-3">
                                            <i class="fas fa-user-plus fa-lg mb-1 d-block"></i> <span class="small">Add User</span>
                                        </a>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <a href="course.php?new=true" class="btn btn-outline-success w-100 quick-action-btn border-2 py-3">
                                            <i class="fas fa-folder-plus fa-lg mb-1 d-block"></i> <span class="small">New Course</span>
                                        </a>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <a href="admin_profile.php" class="btn btn-outline-success w-100 quick-action-btn border-2 py-3">
                                            <i class="fas fa-user-circle me-2"></i> <span class="small">Profile</span>
                                        </a>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <a href="Analytic.php" class="btn btn-outline-success w-100 quick-action-btn border-2 py-3">
                                            <i class="fas fa-chart-line me-2"></i> <span class="small">Analytics</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <h2 class="mb-3 text-primary-teal fs-5"><i class="fas fa-chart-area me-1"></i> Key Performance Indicators (KPIs)</h2>
                <div class="row g-2 mb-4"> 
                    <div class="col-6 col-md-6 col-lg-3"><div class="card shadow-sm border-start-custom border-primary border-4 h-100"><div class="card-body"><p class="text-primary-teal kpi-label"><i class="fas fa-users me-1"></i> Students</p><h2 class="kpi-title text-primary-green"><?php echo $analytics_data['kpis']['totalStudents']; ?></h2></div></div></div>
                    <div class="col-6 col-md-6 col-lg-3"><div class="card shadow-sm border-start-custom border-success border-4 h-100"><div class="card-body"><p class="text-primary-teal kpi-label"><i class="fas fa-book-open me-1"></i> Courses</p><h2 class="kpi-title text-primary-green"><?php echo $analytics_data['kpis']['totalCourses']; ?></h2></div></div></div>
                    <div class="col-6 col-md-6 col-lg-3"><div class="card shadow-sm border-start-custom border-warning border-4 h-100"><div class="card-body"><p class="text-primary-teal kpi-label"><i class="fas fa-graduation-cap me-1"></i> Programs</p><h2 class="kpi-title text-primary-green"><?php echo $analytics_data['kpis']['activePrograms']; ?></h2></div></div></div>
                    <div class="col-6 col-md-6 col-lg-3"><div class="card shadow-sm border-start-custom border-info border-4 h-100"><div class="card-body"><p class="text-primary-teal kpi-label"><i class="fas fa-chalkboard-teacher me-1"></i> Staff</p><h2 class="kpi-title text-primary-green"><?php echo $analytics_data['kpis']['totalFaculty'] + $analytics_data['kpis']['totalAdmin']; ?></h2></div></div></div>
                </div>
                
                <h2 class="mb-3 text-primary-teal fs-5"><i class="fas fa-chart-pie me-1"></i> Enrollment & Usage Charts</h2>
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-4 col-lg-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header"><i class="fas fa-signal me-2"></i> User Status (Past 30 Days)</div>
                            <div class="card-body">
                                <h3 class="user-activity-total text-center text-primary-green mb-1"><?php echo $analytics_data['userActivity']['totalUsers']; ?></h3>
                                <p class="text-center text-muted small mb-3">Total Registered Users</p>
                                <div class="d-flex justify-content-around user-activity-status">
                                    <div class="text-center">
                                        <h4 class="text-success mb-0"><?php echo $analytics_data['userActivity']['active']; ?></h4>
                                        <small class="text-muted">Active (Logged In)</small>
                                    </div>
                                    <div class="text-center">
                                        <h4 class="text-danger mb-0"><?php echo $analytics_data['userActivity']['dormant']; ?></h4>
                                        <small class="text-muted">Dormant (Inactive)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-8 col-lg-8">
                        <div class="card shadow-sm h-100">
                            <div class="card-header"><i class="fas fa-chart-area me-2"></i> Annual Platform Visits</div>
                            <div class="card-body chart-container">
                                <canvas id="visitsByYearChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-5 col-lg-5">
                        <div class="card shadow-sm h-100">
                            <div class="card-header"><i class="fas fa-pie-chart me-2"></i> Program Enrollment Breakdown</div>
                            <div class="card-body chart-container">
                                <canvas id="programEnrollmentChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-7 col-lg-7">
                        <div class="card shadow-sm h-100">
                            <div class="card-header"><i class="fas fa-chart-bar me-2"></i> Enrollment by Academic Year</div>
                            <div class="card-body chart-container">
                                <canvas id="enrollmentYearSemesterChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-lg-12">
                        <div class="card shadow-sm h-100">
                            <div class="card-header"><i class="fas fa-table me-2"></i> Detailed Student Distribution</div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover mb-0" id="studentDistributionTable">
                                        <thead>
                                            <tr>
                                                <th>Program</th>
                                                <th>Total</th>
                                                <th>Year 1</th>
                                                <th>Year 2</th>
                                                <th>Year 3</th>
                                                <th>Year 4</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Define program colors for chart consistency
                                            $program_colors = [
                                                'Information Technology' => '#4CAF50', // Green
                                                'Computer Science' => '#2E7D32', // Dark Green
                                                'Business Administration' => '#FFEB3B', // Yellow Accent
                                                'Education' => '#FFC107', // Amber
                                                'Nursing' => '#00BCD4', // Cyan
                                                'Default' => '#9E9E9E' // Grey
                                            ];
                                            
                                            if (empty($analytics_data['detailedEnrollment'])): ?>
                                                <tr><td colspan="6" class="text-center text-muted">No student data or an error occurred.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($analytics_data['detailedEnrollment'] as $row): 
                                                    $color = $program_colors[$row['program']] ?? $program_colors['Default'];
                                                ?>
                                                <tr>
                                                    <td><span style="display:inline-block; width:8px; height:8px; background-color:<?php echo htmlspecialchars($color); ?>; margin-right:8px; border-radius:50%;"></span><?php echo htmlspecialchars($row['program']); ?></td>
                                                    <td><strong><?php echo htmlspecialchars($row['total']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($row['y1']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['y2']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['y3']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['y4']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
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
                <a href="#" class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Inject PHP data directly into JavaScript
        const ANALYTICS_DATA = <?php echo json_encode($analytics_data); ?>;
        
        const PROGRAM_COLORS = {
            'Information Technology': '#4CAF50',
            'Computer Science': '#2E7D32',
            'Business Administration': '#FFEB3B',
            'Education': '#FFC107',
            'Nursing': '#00BCD4',
            'Default': '#9E9E9E'
        };
        
        // --- CHART RENDERING FUNCTIONS ---

        function renderProgramEnrollmentChart(data) {
            const ctx = document.getElementById('programEnrollmentChart').getContext('2d');
            if (window.programChart) { window.programChart.destroy(); }
            
            const labels = data.programEnrollment.labels;
            const backgroundColors = labels.map(label => PROGRAM_COLORS[label] || PROGRAM_COLORS['Default']);

            window.programChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data.programEnrollment.counts,
                        backgroundColor: backgroundColors,
                        borderColor: '#fff', 
                        borderWidth: 2,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { 
                        legend: { 
                            position: 'right', 
                            labels: { 
                                color: '#333', 
                                boxWidth: 10,
                                font: { size: 10 } 
                            } 
                        }, 
                        title: { display: false } 
                    }
                }
            });
        }
        function renderEnrollmentYearChart(data) {
            const ctx = document.getElementById('enrollmentYearSemesterChart').getContext('2d');
            if (window.yearChart) { window.yearChart.destroy(); }

            const years = [1, 2, 3, 4];
            const enrollmentByYear = years.map(year => 
                data.detailedEnrollment.reduce((sum, p) => sum + (p['y' + year] || 0), 0)
            );

            window.yearChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: years.map(y => `Year ${y}`),
                    datasets: [{
                        label: 'Total Students',
                        data: enrollmentByYear,
                        backgroundColor: PROGRAM_COLORS['Computer Science'],
                        borderColor: PROGRAM_COLORS['Computer Science'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            suggestedMax: Math.max(...enrollmentByYear) > 0 ? Math.max(...enrollmentByYear) + 2 : 10, // Ensure a max for empty data
                            ticks: { precision: 0, color: '#333', font: { size: 10 } },
                            grid: { color: '#E0E0E0' }
                        },
                        x: {
                            ticks: { color: '#333', font: { size: 10 } },
                            grid: { color: '#E0E0E0' }
                        }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }
        
        function renderVisitsByYearChart(data) {
            const ctx = document.getElementById('visitsByYearChart').getContext('2d');
            if (window.visitsChart) { window.visitsChart.destroy(); }
            
            window.visitsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.visitsByYear.labels,
                    datasets: [{
                        label: 'Total Platform Visits (Logins)',
                        data: data.visitsByYear.counts,
                        backgroundColor: 'rgba(76, 175, 80, 0.2)',
                        borderColor: PROGRAM_COLORS['Information Technology'],
                        borderWidth: 2,
                        pointRadius: 5,
                        pointBackgroundColor: PROGRAM_COLORS['Information Technology'],
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: '#333', font: { size: 10 } },
                            grid: { color: '#E0E0E0' }
                        },
                        x: {
                            ticks: { color: '#333', font: { size: 10 } },
                            grid: { color: '#E0E0E0' }
                        }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }

        // --- GENERAL SETUP & EVENT HANDLERS ---
        document.addEventListener('DOMContentLoaded', function() {
            // Render all charts on load
            renderProgramEnrollmentChart(ANALYTICS_DATA);
            renderEnrollmentYearChart(ANALYTICS_DATA);
            renderVisitsByYearChart(ANALYTICS_DATA);
            
            // Sidebar Toggle Logic for Mobile
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const bodyMain = document.getElementById('body-main');

            function toggleSidebar() {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('show');
                bodyMain.classList.toggle('overflow-hidden'); // Prevent scroll on body when sidebar is open
            }

            sidebarToggle.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', toggleSidebar);

            // Close sidebar on link click in mobile view
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 992) {
                        toggleSidebar();
                    }
                });
            });
            
            // Optional: Re-render charts on window resize to fix display issues sometimes experienced with Chart.js and containers
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    renderProgramEnrollmentChart(ANALYTICS_DATA);
                    renderEnrollmentYearChart(ANALYTICS_DATA);
                    renderVisitsByYearChart(ANALYTICS_DATA);
                }, 250);
            });
        });
    </script>
</body>
</html>