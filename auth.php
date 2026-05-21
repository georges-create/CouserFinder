<?php
// auth.php - Handles User Login and Registration

ob_start(); // Start output buffering early

// === 1. SESSION MANAGEMENT AND SETUP ===
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// NOTE: Make sure 'config.php' is available and provides a valid $conn MySQLi object.
include "config.php"; 

// Initialize form data variables for form data preservation on registration error
$fullName_val = $email_val = $program_val = $year_val = $semester_val = '';
$message = "";
$message_type = 'success'; 
$active_form = 'login';


// --- Check and restore form data and messages from session ---
if (isset($_SESSION['auth_message'])) {
    $message = $_SESSION['auth_message'];
    $message_type = isset($_SESSION['auth_message_type']) ? $_SESSION['auth_message_type'] : 'success'; 
    unset($_SESSION['auth_message']); 
    unset($_SESSION['auth_message_type']);
    
    // Restore form data if present and the form was registration
    if ($message_type === 'error' && isset($_SESSION['registration_form_data'])) {
        $data = $_SESSION['registration_form_data'];
        // Use htmlspecialchars for security when outputting saved data
        $fullName_val = htmlspecialchars($data['fullName'] ?? '');
        $email_val = htmlspecialchars($data['email'] ?? '');
        $program_val = htmlspecialchars($data['program'] ?? '');
        $year_val = htmlspecialchars($data['year'] ?? '');
        $semester_val = htmlspecialchars($data['semester'] ?? '');
        unset($_SESSION['registration_form_data']); 
    }
}

// Check for a success message from a successful registration redirect
if (isset($_GET['reg_success']) && $_GET['reg_success'] == 1) {
    $message = "Registration successful! You can now log in.";
    $message_type = 'success'; 
}

// Set the current form to display for client-side JS logic
if (isset($_GET['show']) && in_array($_GET['show'], ['register', 'forgot'])) {
    $active_form = $_GET['show'];
} else if (!empty($message) && $message_type === 'error') {
    if (isset($_SESSION['last_error_form'])) {
        $active_form = $_SESSION['last_error_form'];
        unset($_SESSION['last_error_form']); 
    }
}


// === 2. HELPER FUNCTIONS ===
function sanitize_input($data)
{
    // Basic sanitization: removes whitespace, strips slashes, converts special chars to HTML entities
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); 
    return $data;
}


// === 3. HANDLE FORM SUBMISSIONS (POST REQUESTS) ===

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- REGISTRATION LOGIC ---
    if (isset($_POST['register'])) {
        
        // Save form data to session immediately in case of error (except password)
        $_SESSION['registration_form_data'] = [
            'fullName' => $_POST['fullName'],
            'email' => $_POST['email'],
            'program' => $_POST['program'],
            'year' => $_POST['year'],
            'semester' => $_POST['semester'],
        ];

        $fullName = sanitize_input($_POST['fullName']);
        $email = sanitize_input($_POST['email']);
        $program = sanitize_input($_POST['program']);
        $year = sanitize_input($_POST['year']);
        $semester = sanitize_input($_POST['semester']);
        $password = $_POST['password']; // Password left as is for validation/hashing
        $error_flag = false;
        
        if (!isset($_POST['terms'])) {
            $message = "You must agree to the Terms and Conditions to register.";
            $message_type = 'error';
            $error_flag = true;
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid email format.";
            $message_type = 'error';
            $error_flag = true;
        } else if (strlen($password) < 8) {
            $message = "Password must be at least 8 characters long.";
            $message_type = 'error';
            $error_flag = true;
        } 
        
        if (!$error_flag) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Use prepared statements for security
            $stmt = $conn->prepare("INSERT INTO user (fullName, email, program, year, semester, password, userType) VALUES (?, ?, ?, ?, ?, ?, 'student')");
            if ($stmt) {
                $stmt->bind_param("ssssss", $fullName, $email, $program, $year, $semester, $hashed_password);

                try {
                    if ($stmt->execute()) {
                        // Registration Success: PRG pattern with success flag
                        unset($_SESSION['registration_form_data']); // Clear saved data on success
                        header("Location: auth.php?reg_success=1");
                        exit();
                    }
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() == 1062) {
                        $message = "Email already in use, please try another email or log in.";
                    } else {
                        $message = "Database error: " . $e->getMessage();
                    }
                    $message_type = 'error';
                }
                $stmt->close();
            } else {
                $message = "Error preparing statement: " . $conn->error;
                $message_type = 'error';
            }
        }
        
        // Error occurred: Store state and message in session, then REDIRECT (PRG)
        if ($message_type === 'error') {
            $_SESSION['auth_message'] = $message;
            $_SESSION['auth_message_type'] = $message_type;
            $_SESSION['last_error_form'] = 'register'; 
            header("Location: auth.php?show=register");
            exit();
        }
    }
    
   // --- LOGIN LOGIC ---
    elseif (isset($_POST['login'])) {
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];

        // Use prepared statements for security
        $stmt = $conn->prepare("SELECT id, password, userType, fullName FROM user WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    
                    // 1. Set Session Variables
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['userType'] = $user['userType'];
                    $_SESSION['username'] = $user['fullName']; 

                    // 2. >>> LOG USER ACTIVITY HERE <<<
                    $user_id = $user['id'];
                    if ($conn) {
                        // Use a separate statement variable to avoid conflict
                        $log_stmt = $conn->prepare("INSERT INTO user_activity_log (user_id) VALUES (?)");
                        if ($log_stmt) {
                            $log_stmt->bind_param("i", $user_id);
                            $log_stmt->execute();
                            $log_stmt->close();
                        }
                    }
                    
                    // 3. Login Success: Redirect to dashboard
                    if ($_SESSION['userType'] === 'admin') {
                        header("Location: admin_dashboard.php");
                        exit();
                    } else { 
                        header("Location: student_dashboard.php");
                        exit();
                    }
                } else {
                    $message = "Invalid email or password.";
                    $message_type = 'error';
                }
            } else {
                $message = "Invalid email or password.";
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = "Error preparing statement: " . $conn->error;
            $message_type = 'error';
        }

        // Login Error occurred: Store message in session, and REDIRECT (PRG)
        if ($message_type === 'error') {
            $_SESSION['auth_message'] = $message;
            $_SESSION['auth_message_type'] = $message_type;
            $_SESSION['last_error_form'] = 'login'; 
            header("Location: auth.php?show=login");
            exit();
        }
    }
}

// === 4. CLEANUP ===
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEAU CourseFinder - Login & Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
    /* 1. Global Reset & Body Styles */
    * {
        box-sizing: border-box;
    }
    
    html { width: 100%; overflow-x: hidden; }
    
    body {
        width: 100%; 
        overflow-x: hidden; 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(to right, #2E7D32, #4CAF50); /* Green Gradient */
        color: #333;
        display: flex;
        flex-direction: column;
        justify-content: flex-start; 
        align-items: center;
        min-height: 100dvh;
        font-size: 0.9rem;
        padding: 0; 
        margin: 0; 
        overflow-y: auto; 
    }
    
    /* 2. Title & Accent Styles */
    .main-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: white;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.4);
        margin-top: 20px;
    }
    
    .sub-title {
        font-size: 1.1rem;
        color: #FFEB3B; 
        font-weight: 400;
        margin-bottom: 20px;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
    }
    
    .teaus-yellow-accent {
        color: #FFEB3B;
    }

    /* 3. Button Styles */
    .btn-primary {
        background-color: #2E7D32; 
        border-color: #2E7D32;
        font-weight: 600;
        padding: 0.6rem 1rem;
        transition: background-color 0.2s, transform 0.1s;
    }

    .btn-primary:hover {
        background-color: #4CAF50; 
        border-color: #4CAF50;
        transform: translateY(-1px);
    }
    
    /* 4. Link Styles */
    .text-link {
        color: #2E7D32; 
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s, text-decoration 0.2s;
    }

    .text-link:hover {
        color: #1B5E20; 
        text-decoration: underline;
    }

    /* 5. Message Box Styles */
    .message-box {
        padding: 10px 15px;
        margin-bottom: 1rem; 
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 600;
        line-height: 1.4;
        border: 1px solid transparent;
    }

    .success-box {
        background-color: #e6ffed; 
        color: #1a6e34; 
        border-color: #a3e0b0;
    }

    .error-box {
        background-color: #fff0f0; 
        color: #d13030; 
        border-color: #ffb3b3;
    }
    
    /* 6. Layout and Structure */
    .auth-page-content {
        display: flex;
        justify-content: center;
        align-items: stretch; 
        width: 95%; 
        max-width: 400px; 
        margin-top: 20px;
        margin-bottom: 20px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        overflow: hidden; 
        height: auto; 
    }
    
    .feature-panel {
        display: none; 
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 20px;
        background: rgba(255, 255, 255, 0.15); 
        color: white;
        border-right: 1px solid rgba(255, 255, 255, 0.2);
        flex-basis: 50%; 
    }

    .form-container {
        background-color: white;
        padding: 20px;
        width: 100%;
        max-height: 90vh; 
        overflow-y: auto;
        display: flex; 
        flex-direction: column;
    }

    .form-container h2 {
        text-align: center;
        color: #2E7D32;
        margin-bottom: 1.5rem;
        font-weight: 600;
        font-size: 1.6rem;
    }
    
    .form-container .form-label {
        font-size: 0.85rem;
        margin-bottom: 0.25rem; 
        color: #555;
        font-weight: 500;
    }

    .form-container .form-control {
        font-size: 0.9rem;
        padding: 0.5rem 0.75rem;
    }
    
    /* Password Toggle Icon Styling */
    .password-container {
        position: relative;
    }
    
    .password-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #aaa;
    }
    
    /* Form Switching Logic */
    .form-wrapper {
        position: relative; 
        flex-grow: 1; 
        display: flex; 
    }
    
    .auth-form {
        position: absolute; 
        top: 0;
        left: 0;
        width: 100%;
        transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out; 
        opacity: 0;
        transform: translateX(100%);
        pointer-events: none; 
        display: block; 
        flex-shrink: 0; 
    }
    
    .auth-form.form-initial-visible,
    .auth-form.form-visible {
        opacity: 1;
        transform: translateX(0);
        position: static; 
        pointer-events: auto;
        display: block;
    }

    .auth-form.hidden-form {
        display: none; 
    }

    /* Register Form Compactness */
    #registerForm h2 { margin-bottom: 1rem; }
    #registerForm .mb-3 { margin-bottom: 0.5rem !important; }
    #registerForm .form-label { font-size: 0.8rem; margin-bottom: 0.1rem; }
    #registerForm .form-control { font-size: 0.85rem; padding: 0.3rem 0.6rem; }
    #registerForm select.form-control { height: calc(1.5em + 0.6rem + 2px); padding-top: 0.3rem; padding-bottom: 0.3rem; }
    #registerForm .form-check { margin-top: 0.5rem; margin-bottom: 1rem !important; }
    
    /* 7. Footer Styles */
    .footer {
        background-color: #2E7D32; 
        color: white;
        text-align: center;
        padding: 10px;
        width: 100%;
        margin-top: auto; 
        max-width: 100%; 
    }

    .footer p { margin: 0; font-size: 0.8rem; }
    
    .footer .social-icons a { color: white; margin: 0 8px; font-size: 1.1rem; transition: color 0.2s; }
    .footer .social-icons a:hover { color: #FFEB3B; }

    /* 8. Media Queries for Desktop Layout */
    @media (max-width: 991.98px) {
        .form-container {
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        .auth-page-content { box-shadow: none; }
    }
    
    @media (min-width: 992px) {
        .auth-page-content { max-width: 900px; height: auto; }
        .form-container { flex-basis: 50%; border-radius: 0 12px 12px 0; }
        .feature-panel { display: flex; flex-basis: 50%; border-radius: 12px 0 0 12px; }
    }
</style>
</head>

<body>
    <div class="main-title">
        TEAU <span class="teaus-yellow-accent">CourseFinder</span>
    </div>
    <div class="sub-title">
        A Unified, AI-Powered Course Discovery Platform
    </div>

    <div class="auth-page-content">
        <div class="feature-panel">
            <i class="fas fa-brain icon-large"></i>
            <h3>AI-Powered Course Matching</h3>
            <p>Our intelligent system analyzes your academic profile to provide the most relevant course recommendations for your program and goals.</p>
            <p style="font-size: 0.75rem; opacity: 0.8;">(Future feature spotlight area)</p>
        </div>
        
        <div class="form-container">
            <?php 
                // Display dynamic success/error message box
                if (!empty($message)) : 
                    $alert_class = ($message_type === 'error') ? 'error-box' : 'success-box';
            ?>
                <div class="message-box <?php echo $alert_class; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-wrapper">
                <div id="loginForm" class="auth-form <?php echo ($active_form === 'login') ? 'form-initial-visible' : 'hidden-form'; ?>">
                    <h2><i class="fas fa-sign-in-alt me-2"></i>TEAU <span class="teaus-yellow-accent">Login</span></h2>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="mb-3">
                            <label for="loginEmail" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="loginEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="loginPassword" class="form-label">Password</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="loginPassword" name="password" required minlength="8">
                                <span class="password-toggle" onclick="togglePasswordVisibility('loginPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3 text-end">
                            <a href="#" class="text-link" style="font-size: 0.8rem;" onclick="showForgotPassword()">Forgot Password?</a>
                        </div>
                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary" name="login">Log In</button>
                        </div>
                        <div class="text-center">
                            <p>Don't have an account? <a href="#" class="text-link" onclick="showRegister()">Register here</a></p>
                        </div>
                    </form>
                </div>
                
                <div id="forgotPasswordForm" class="auth-form <?php echo ($active_form === 'forgot') ? 'form-initial-visible' : 'hidden-form'; ?>">
                    <h2><i class="fas fa-lock me-2"></i>Forgot <span class="teaus-yellow-accent">Password</span></h2>
                    <p>Enter your email address to receive a password reset link. (Requires separate email configuration)</p>
                    <form action="forgot_password.php" method="post">
                        <div class="mb-3">
                            <label for="forgotEmail" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="forgotEmail" name="email" required>
                        </div>
                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary" name="forgot_password">Send Reset Link</button>
                        </div>
                        <div class="text-center">
                            <p><a href="#" class="text-link" onclick="showLogin()">Back to Login</a></p>
                        </div>
                    </form>
                </div>
                
                <div id="registerForm" class="auth-form <?php echo ($active_form === 'register') ? 'form-initial-visible' : 'hidden-form'; ?>">
                    <h2><i class="fas fa-user-plus me-2"></i>TEAU <span class="teaus-yellow-accent">Register</span></h2>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="mb-3">
                            <label for="registerName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="registerName" name="fullName" value="<?php echo $fullName_val; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerEmail" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="registerEmail" name="email" value="<?php echo $email_val; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="program" class="form-label">Program of Study</label>
                            <select class="form-control" id="program" name="program" required>
                                <option value="">Select your program</option>
                                <option value="Information Technology" <?php if ($program_val == 'Information Technology') echo 'selected'; ?>>Information Technology</option>
                                <option value="Computer Science" <?php if ($program_val == 'Computer Science') echo 'selected'; ?>>Computer Science</option>
                                <option value="Business Administration" <?php if ($program_val == 'Business Administration') echo 'selected'; ?>>Business Administration</option>
                                <option value="Nursing" <?php if ($program_val == 'Nursing') echo 'selected'; ?>>Nursing</option>
                                <option value="Education" <?php if ($program_val == 'Education') echo 'selected'; ?>>Education</option>
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label for="year" class="form-label">Year of Study</label>
                                <select class="form-control" id="year" name="year" required>
                                    <option value="">Select year</option>
                                    <option value="1" <?php if ($year_val == '1') echo 'selected'; ?>>1st Year</option>
                                    <option value="2" <?php if ($year_val == '2') echo 'selected'; ?>>2nd Year</option>
                                    <option value="3" <?php if ($year_val == '3') echo 'selected'; ?>>3rd Year</option>
                                    <option value="4" <?php if ($year_val == '4') echo 'selected'; ?>>4th Year</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="semester" class="form-label">Semester</label>
                            <select class="form-control" id="semester" name="semester" required>
                                <option value="">Select semester</option>
                                <option value="1" <?php if ($semester_val == '1') echo 'selected'; ?>>Semester 1</option>
                                <option value="2" <?php if ($semester_val == '2') echo 'selected'; ?>>Semester 2</option>
                            </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="registerPassword" class="form-label">Password (Min. 8 chars)</label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="registerPassword" name="password" required minlength="8">
                                <span class="password-toggle" onclick="togglePasswordVisibility('registerPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="termsCheck" name="terms" onchange="toggleRegisterButton(this)">
                            <label class="form-check-label" for="termsCheck" style="font-size: 0.8rem;">
                                I agree to the <a href="#" class="text-link" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                            </label>
                        </div>
                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary" name="register" id="registerButton" disabled>Register</button>
                        </div>
                        <div class="text-center">
                            <p>Already have an account? <a href="#" class="text-link" onclick="showLogin()">Log in here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">TEAU Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Acceptance of Terms</h6>
                    <p>By accessing and using the TEAU CourseFinder platform, you accept and agree to be bound by the terms and provisions of this agreement. If you do not agree to abide by the above, please do not use this service.</p>
                    <h6>2. User Responsibility</h6>
                    <p>You are responsible for maintaining the confidentiality of your account and password and for restricting access to your computer. You agree to accept responsibility for all activities that occur under your account or password.</p>
                    <h6>3. Data Usage</h6>
                    <p>All data submitted, including academic details, will be used solely for the purpose of course matching, academic planning, and internal research to improve the platform's AI recommendations. Your personal data will not be shared with third parties without explicit consent, except as required by law.</p>
                    <h6>4. Intellectual Property</h6>
                    <p>The content, organization, graphics, design, compilation, and other matters related to the Site are protected under applicable copyrights, trademarks, and other proprietary rights.</p>
                    <h6>5. Limitation of Liability</h6>
                    <p>TEAU CourseFinder is provided "as is." We make no warranties, expressed or implied, and hereby disclaim all other warranties. We shall not be liable for any damages arising out of the use or inability to use the platform.</p>
                    <h6>6. Changes to Terms</h6>
                    <p>We reserve the right to revise these Terms and Conditions at any time. Your continued use of the platform after any changes indicates your acceptance of the new terms.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <div class="container text-center">
            <p>Created with ❤️ for TEAU. &copy; 2025.</p>
            <div class="social-icons mt-2">
                <a href="#" class="text-white"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const forgotPasswordForm = document.getElementById('forgotPasswordForm');
        const allForms = [loginForm, registerForm, forgotPasswordForm];
        
        function resetForms() {
            allForms.forEach(form => {
                form.classList.remove('form-visible', 'form-initial-visible');
                form.style.position = 'absolute'; 
                form.style.opacity = 0;
                form.style.transform = 'translateX(100%)';
                form.style.pointerEvents = 'none';
                form.classList.add('hidden-form');
            });
        }

        function showForm(formElement) {
            resetForms();
            
            formElement.style.position = 'static';
            formElement.classList.remove('hidden-form');
            
            // Force reflow/repaint to ensure the transition starts from the correct position
            void formElement.offsetWidth;
            
            formElement.classList.add('form-visible');
            formElement.style.opacity = 1;
            formElement.style.transform = 'translateX(0)';
            formElement.style.pointerEvents = 'auto';
        }

        function showLogin() {
            showForm(loginForm);
            history.pushState(null, '', 'auth.php?show=login'); 
        }

        function showRegister() {
            showForm(registerForm);
            history.pushState(null, '', 'auth.php?show=register');
        }

        function showForgotPassword() {
            showForm(forgotPasswordForm);
            history.pushState(null, '', 'auth.php?show=forgot');
        }


        function togglePasswordVisibility(fieldId, iconElement) {
            const passwordField = document.getElementById(fieldId);
            const icon = iconElement.querySelector('i');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function toggleRegisterButton(checkbox) {
            document.getElementById('registerButton').disabled = !checkbox.checked;
        }
    </script>
</body>

</html>