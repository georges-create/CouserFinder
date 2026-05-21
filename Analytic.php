<?php
// analytics.php - Professional System Analytics Report
// Requires config.php for database connection ($conn)

error_reporting(E_ALL);
ini_set('display_errors', 1);

include "config.php";

// Start or resume session to enforce authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check: Redirect unauthorized users
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: auth.php");
    exit();
}

// Get the admin's username for display
$admin_username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : "System Administrator";

// Initialize data structure with secure defaults
$analytics_data = [
    'kpis' => ['totalStudents' => 0, 'totalCourses' => 0, 'activePrograms' => 0, 'totalFaculty' => 0, 'totalAdmin' => 0],
    'programEnrollment' => ['labels' => [], 'counts' => []],
    'detailedEnrollment' => [],
    'userActivity' => ['active' => 0, 'dormant' => 0, 'totalUsers' => 0],
    'visitsByYear' => ['labels' => [], 'counts' => []],
    'recentActivity' => []
];
$error_message = '';

// --- DATABASE FETCHING LOGIC ---
if (isset($conn) && $conn->connect_error) {
    $error_message = 'Database Connection Failed: ' . $conn->connect_error;
} elseif (isset($conn)) {
    try {
        // 1. Fetch Core KPIs (User & Course Totals)
        $kpis = [];
        $kpis['totalStudents'] = (int)($conn->query("SELECT COUNT(id) FROM user WHERE userType='student'")->fetch_row()[0] ?? 0);
        $kpis['totalFaculty'] = (int)($conn->query("SELECT COUNT(id) FROM user WHERE userType='faculty'")->fetch_row()[0] ?? 0);
        $kpis['totalAdmin'] = (int)($conn->query("SELECT COUNT(id) FROM user WHERE userType='admin'")->fetch_row()[0] ?? 0);
        $kpis['totalCourses'] = (int)($conn->query("SELECT COUNT(id) FROM courses")->fetch_row()[0] ?? 0);
        $kpis['activePrograms'] = (int)($conn->query("SELECT COUNT(DISTINCT program) FROM user WHERE userType='student' AND program IS NOT NULL")->fetch_row()[0] ?? 0);

        // 2. Fetch Detailed Enrollment Data (by Program and Year)
        $enrollment_results = $conn->query("
            SELECT program, year, COUNT(id) AS count
            FROM user
            WHERE userType = 'student' AND program IS NOT NULL
            GROUP BY program, year
            ORDER BY program, year
        ");

        $detailedEnrollment = [];
        $programCounts = [];

        if ($enrollment_results) {
            while ($row = $enrollment_results->fetch_assoc()) {
                $program = htmlspecialchars($row['program']);
                $year = "y" . (int)$row['year']; 
                $count = (int)$row['count'];
                
                if (!isset($detailedEnrollment[$program])) {
                    $detailedEnrollment[$program] = ['program' => $program, 'y1' => 0, 'y2' => 0, 'y3' => 0, 'y4' => 0, 'total' => 0];
                    $programCounts[$program] = 0;
                }
                
                if (in_array($year, ['y1', 'y2', 'y3', 'y4'])) {
                    $detailedEnrollment[$program][$year] = $count;
                }
                $detailedEnrollment[$program]['total'] += $count;
                $programCounts[$program] += $count;
            }
        }

        $programEnrollment = [
            'labels' => array_keys($programCounts),
            'counts' => array_values($programCounts)
        ];
        
        // 3. User Activity Status (Last 30 Days)
        $totalUsers = (int)($conn->query("SELECT COUNT(id) FROM user")->fetch_row()[0] ?? 0);
        $userActivity = ['active' => 0, 'dormant' => 0, 'totalUsers' => $totalUsers];

        $active_threshold = date('Y-m-d H:i:s', strtotime('-30 days'));

        $active_users_query = $conn->query("
            SELECT COUNT(DISTINCT user_id) AS active_count
            FROM user_activity_log
            WHERE login_time >= '{$active_threshold}'
        ");
        
        $active_count = $active_users_query ? (int)($active_users_query->fetch_assoc()['active_count'] ?? 0) : 0;
        $dormant_count = $totalUsers - $active_count;
        
        $userActivity['active'] = $active_count;
        $userActivity['dormant'] = $dormant_count;

        // 4. Annual Visits Trend
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

        // 5. Recent Activity Feed
        $recent_activity_query = $conn->query("
            SELECT u.fullName AS username, u.userType, a.login_time AS action_time
            FROM user_activity_log a
            JOIN user u ON a.user_id = u.id
            ORDER BY a.login_time DESC
            LIMIT 5
        ");
        
        $recent_activity = [];
        if ($recent_activity_query) {
            while ($row = $recent_activity_query->fetch_assoc()) {
                $recent_activity[] = $row;
            }
        }
        
        // Finalize data structure
        $analytics_data = [
            'kpis' => $kpis,
            'programEnrollment' => $programEnrollment,
            'detailedEnrollment' => array_values($detailedEnrollment),
            'userActivity' => $userActivity,
            'visitsByYear' => $visitsByYear,
            'recentActivity' => $recent_activity
        ];

    } catch (Exception $e) {
        $error_message = 'Query Error: ' . $e->getMessage() . ' (Verify table names and column existence.)';
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
    <title>TEAU Admin - Analytics Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>

    <style>
    /* ==========================
    THEME & BASE STYLES
    ========================== */
    :root {
        --header-height: 3.2rem;
        --sidebar-width: 220px;
        --primary-green: #2E7D32; /* Dark Green */
        --secondary-green: #4CAF50; /* Light Green */
        --accent-yellow: #FFEB3B; /* Yellow Accent */
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

    /* Utility Classes */
    .text-primary-teal { color: var(--primary-green) !important; }
    .text-primary-green-light { color: var(--secondary-green) !important; }
    .bg-primary-teal { background-color: var(--primary-green) !important; }
    
    /* Card & KPI Styling */
    .card { background: #fff; border-radius: 10px; box-shadow: 0 4px 12px rgba(46, 125, 50, 0.1); border: none; padding: 0.2rem; margin-bottom: 15px; }
    .card-header { background: var(--secondary-green); color: #fff; font-weight: 600; padding: 0.6rem 1rem; border-radius: 10px 10px 0 0; font-size: 0.9rem; }
    .card-body { padding: 0.75rem !important; }
    .kpi-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.1rem; }
    .kpi-label { font-size: 0.8rem; margin-bottom: 0.1rem; text-transform: uppercase; }
    .border-start-custom { border-left-width: 0.4rem !important; }
    .chart-container { position: relative; height: 300px; }
    .table { font-size: 0.8rem; }
    .table thead th { background-color: var(--primary-green) !important; color: #fff !important; padding: 0.6rem 0.6rem; }
    
    /* Activity Feed Styling */
    .activity-item { padding: 8px 0; border-bottom: 1px dashed #eee; }
    .activity-item:last-child { border-bottom: none; }
    .activity-time { font-size: 0.7rem; color: #999; }

    /* ====================================
    LAYOUT STYLES
    ==================================== */
    
    .mobile-fixed-header {
        height: var(--header-height);
        position: fixed; top: 0; left: 0; width: 100%; z-index: 1100;
        background: var(--primary-green); border-bottom: 2px solid var(--secondary-green);
        display: flex; justify-content: space-between; align-items: center; padding: .5rem 1rem;
    }

    .content-wrapper { display: flex; flex-direction: column; flex-grow: 1; width: 100%; }
    .main-content { padding: 15px; flex-grow: 1; transition: margin-left .3s ease; }

    .sidebar {
        width: var(--sidebar-width);
        background: linear-gradient(to bottom, var(--primary-green), var(--secondary-green));
        color: #fff; height: 100vh; position: fixed; left: 0; padding: 15px 0;
        overflow-y: auto; border-radius: 0 12px 12px 0;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15); transition: transform .3s ease-in-out;
        z-index: 1040;
    }
    
    .sidebar .logo-full { font-size: 1.5rem; font-weight: 700; text-align: center; margin-bottom: 1rem; }
    .sidebar a { padding: 10px 15px; margin: 0 8px 5px 8px; color: #e9ecef; font-size: 0.9rem; border-radius: 8px; display: block; }
    .sidebar a:hover, .sidebar a.active { background: var(--accent-yellow); color: var(--primary-green); }

    /* RESPONSIVENESS */
    @media (max-width: 991.98px) {
        body { padding-top: var(--header-height); }
        .sidebar { top: var(--header-height); height: calc(100vh - var(--header-height)); transform: translateX(-100%); }
        .sidebar.active { transform: translateX(0) !important; }
        .main-content { margin-left: 0; }
        .footer { margin-left: 0 !important; width: 100% !important; }
        .welcome-message-container { padding: 0.5rem 1rem; margin-bottom: 1rem; background: #fff; border-bottom: 2px solid var(--secondary-green); box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05); }
    }
    
    @media (min-width: 992px) {
        body { padding-top: 0; }
        .mobile-fixed-header { display: none !important; }
        .sidebar { top: 0; height: 100vh; }
        .main-content { margin-left: var(--sidebar-width); }
        .footer { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); }
        .welcome-message-container { display: none !important; }
    }

    .sidebar-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, .5); z-index: 1030; display: none; }
    .sidebar-overlay.show { display: block; }

    .footer { 
        background: var(--primary-green); 
        color: #fff; 
        text-align: center; 
        padding: 15px 0; 
        font-size: 0.85rem; 
        margin-top: auto;
        transition: margin-left .3s ease, width .3s ease;
    }

</style>
</head>
<body id="body-main">

    <header class="mobile-fixed-header d-lg-none">
        <button class="navbar-toggler p-0 border-0" id="sidebarToggle" aria-label="Toggle navigation menu" type="button">
            <span class="navbar-toggler-icon-custom"></span> 
        </button>
        <h5 class="mb-0 text-white fw-bold">TEAU Admin</h5>
    </header>

    <div class="content-wrapper">
        <nav class="sidebar d-flex flex-column p-3" id="sidebar">
            <h2 class="logo-full"><i class="fas fa-university logo-icon me-2"></i>TEAU Admin</h2>
            <ul class="nav flex-column mt-3 flex-grow-1">
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
                    <a class="nav-link active" href="Analytic.php"><i class="fas fa-chart-line me-2"></i>Analytics Reports</a> 
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link" href="admin_profile.php"><i class="fas fa-user-circle me-2"></i>My Profile</a>
                </li>
            </ul>
             <ul class="nav flex-column pt-3">
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
                    <i class="fas fa-chart-line me-2"></i> Executive Analytics Report
                </h1>
                <p class="text-muted small d-none d-lg-block">Welcome back, <?php echo $admin_username; ?>! Comprehensive system usage and enrollment statistics.</p>


                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <strong>Database Error:</strong> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <h2 class="mb-3 text-primary-teal fs-5 mt-4"><i class="fas fa-gauge-high me-1"></i> Key Performance Indicators (KPIs)</h2>
                <div class="row g-3 mb-4"> 
                    <div class="col-6 col-md-6 col-lg-3">
                        <div class="card shadow-sm border-start-custom border-primary border-4 h-100">
                            <div class="card-body">
                                <p class="text-primary-teal kpi-label"><i class="fas fa-user-graduate me-1"></i> Total Students</p>
                                <h2 class="kpi-title text-primary-green-light"><?php echo number_format($analytics_data['kpis']['totalStudents']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-6 col-lg-3">
                        <div class="card shadow-sm border-start-custom border-success border-4 h-100">
                            <div class="card-body">
                                <p class="text-primary-teal kpi-label"><i class="fas fa-book-open me-1"></i> Active Courses</p>
                                <h2 class="kpi-title text-primary-green-light"><?php echo number_format($analytics_data['kpis']['totalCourses']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-6 col-lg-3">
                        <div class="card shadow-sm border-start-custom border-warning border-4 h-100">
                            <div class="card-body">
                                <p class="text-primary-teal kpi-label"><i class="fas fa-sitemap me-1"></i> Active Programs</p>
                                <h2 class="kpi-title text-primary-green-light"><?php echo number_format($analytics_data['kpis']['activePrograms']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-6 col-lg-3">
                        <div class="card shadow-sm border-start-custom border-info border-4 h-100">
                            <div class="card-body">
                                <p class="text-primary-teal kpi-label"><i class="fas fa-users-cog me-1"></i> Total Staff</p>
                                <h2 class="kpi-title text-primary-green-light"><?php echo number_format($analytics_data['kpis']['totalFaculty'] + $analytics_data['kpis']['totalAdmin']); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h2 class="mb-3 text-primary-teal fs-5"><i class="fas fa-chart-area me-1"></i> Enrollment & Platform Usage Overview</h2>
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header"><i class="fas fa-pie-chart me-2"></i> Program Enrollment Breakdown</div>
                            <div class="card-body chart-container">
                                <canvas id="programEnrollmentChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header"><i class="fas fa-signal me-2"></i> Annual Platform Visits Trend</div>
                            <div class="card-body chart-container">
                                <canvas id="visitsByYearChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-8 col-lg-8">
                        <div class="card shadow-sm h-100">
                            <div class="card-header"><i class="fas fa-chart-bar me-2"></i> Enrollment by Academic Year</div>
                            <div class="card-body chart-container">
                                <canvas id="enrollmentYearSemesterChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-lg-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-primary-teal"><i class="fas fa-bell me-2"></i> User Activity Status</div>
                            <div class="card-body">
                                <h3 class="user-activity-total text-center text-primary-teal mb-1"><?php echo number_format($analytics_data['userActivity']['totalUsers']); ?></h3>
                                <p class="text-center text-muted small mb-3">Total Registered Users</p>
                                <div class="d-flex justify-content-around user-activity-status">
                                    <div class="text-center">
                                        <h4 class="text-success mb-0"><?php echo number_format($analytics_data['userActivity']['active']); ?></h4>
                                        <small class="text-muted">Active (Logged in Past 30 Days)</small>
                                    </div>
                                    <div class="text-center">
                                        <h4 class="text-danger mb-0"><?php echo number_format($analytics_data['userActivity']['dormant']); ?></h4>
                                        <small class="text-muted">Dormant (Inactive)</small>
                                    </div>
                                </div>
                                <hr class="mt-3 mb-2">
                                <p class="small fw-bold text-primary-teal mb-1"><i class="fas fa-history me-1"></i> Recent Logins</p>
                                <?php if (!empty($analytics_data['recentActivity'])): ?>
                                    <?php foreach ($analytics_data['recentActivity'] as $activity): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="mb-0 small text-dark">
                                                <strong><?php echo htmlspecialchars($activity['username']); ?></strong> (<?php echo ucfirst($activity['userType']); ?>)
                                            </p>
                                            <span class="activity-time"><?php echo date('M d, H:i', strtotime($activity['action_time'])); ?></span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-center text-muted small py-1 mb-0">No recent activity logged.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <h2 class="mb-3 text-primary-teal fs-5"><i class="fas fa-table me-1"></i> Detailed Student Distribution Table</h2>
                <div class="row g-3 mb-3">
                    <div class="col-lg-12">
                        <div class="card shadow-sm h-100">
                            <div class="card-header">Student Enrollment by Program and Academic Year</div>
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
                                                'Information Technology' => '#4CAF50',
                                                'Computer Science' => '#2E7D32',
                                                'Business Administration' => '#FFEB3B',
                                                'Education' => '#FFC107',
                                                'Nursing' => '#00BCD4',
                                                'Default' => '#9E9E9E'
                                            ];
                                            
                                            if (empty($analytics_data['detailedEnrollment'])): ?>
                                                <tr><td colspan="6" class="text-center text-muted">No student data or an error occurred.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($analytics_data['detailedEnrollment'] as $row): 
                                                    $color = $program_colors[$row['program']] ?? $program_colors['Default'];
                                                ?>
                                                <tr>
                                                    <td><span style="display:inline-block; width:8px; height:8px; background-color:<?php echo htmlspecialchars($color); ?>; margin-right:8px; border-radius:50%;"></span><?php echo htmlspecialchars($row['program']); ?></td>
                                                    <td><strong><?php echo number_format(htmlspecialchars($row['total'])); ?></strong></td>
                                                    <td><?php echo number_format(htmlspecialchars($row['y1'])); ?></td>
                                                    <td><?php echo number_format(htmlspecialchars($row['y2'])); ?></td>
                                                    <td><?php echo number_format(htmlspecialchars($row['y3'])); ?></td>
                                                    <td><?php echo number_format(htmlspecialchars($row['y4'])); ?></td>
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
            <p class="m-0">Created with <span style="color: var(--accent-yellow);">❤️</span> for TEAU. &copy; <?php echo date('Y'); ?>.</p>
            <div class="social-icons mt-2">
                <a href="#" class="text-white"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                <a href="#" class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

 

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
                        label: 'Total Platform Logins',
                        data: data.visitsByYear.counts,
                        backgroundColor: 'rgba(76, 175, 80, 0.2)',
                        borderColor: PROGRAM_COLORS['Information Technology'],
                        pointBackgroundColor: PROGRAM_COLORS['Computer Science'],
                        borderWidth: 2,
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
                    plugins: { legend: { display: true, position: 'top' } }
                }
            });
        }

        // --- Sidebar Toggle for Mobile ---
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const body = document.getElementById('body-main');
            
            // Function to toggle sidebar state
            const toggleSidebar = () => {
                const isActive = sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('show', isActive);
                // Prevent scrolling the main page while the sidebar is open on mobile
                body.classList.toggle('overflow-hidden', isActive && window.innerWidth < 992);
            };

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }
            
            // Close sidebar when clicking the overlay
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', toggleSidebar);
            }
            
            // Fix: Add icon to the toggle button (it was missing)
            const toggleIconSpan = sidebarToggle.querySelector('.navbar-toggler-icon-custom');
            if (toggleIconSpan) {
                toggleIconSpan.innerHTML = '<i class="fas fa-bars text-white fs-4"></i>';
            }


            // Render all charts on load
            if (ANALYTICS_DATA.programEnrollment.counts && ANALYTICS_DATA.programEnrollment.counts.length > 0) {
                renderProgramEnrollmentChart(ANALYTICS_DATA);
            }
            if (ANALYTICS_DATA.detailedEnrollment && ANALYTICS_DATA.detailedEnrollment.length > 0) {
                renderEnrollmentYearChart(ANALYTICS_DATA);
            }
            if (ANALYTICS_DATA.visitsByYear.counts && ANALYTICS_DATA.visitsByYear.counts.length > 0) {
                renderVisitsByYearChart(ANALYTICS_DATA);
            }
        });

    </script>
</body>
</html>
