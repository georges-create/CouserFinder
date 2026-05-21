<?php


include "config.php"; // Database connection

$message = "";
$error = "";
$token_valid = false;
$token = "";

// Helper function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['token'])) {
    $token = sanitize_input($_GET['token']);
    $current_time = date("Y-m-d H:i:s");

    // Validate token and expiry
    $stmt = $conn->prepare("SELECT id FROM user WHERE reset_token = ? AND token_expiry > ?");
    $stmt->bind_param("ss", $token, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token_valid = true;
    } else {
        $error = "The password reset link is invalid or has expired. Please request a new one.";
    }
    $stmt->close();
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $token = sanitize_input($_POST['token']);
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
        $token_valid = true;
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
        $token_valid = true;
    } else {
        $current_time = date("Y-m-d H:i:s");

        $check_stmt = $conn->prepare("SELECT id FROM user WHERE reset_token = ? AND token_expiry > ?");
        $check_stmt->bind_param("ss", $token, $current_time);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            $error = "The password reset link is invalid or has expired. Please request a new one.";
            $token_valid = false;
        } else {
            $user_data = $check_result->fetch_assoc();
            $userId = $user_data['id'];
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update_stmt = $conn->prepare("UPDATE user SET password = ?, reset_token = NULL, token_expiry = NULL WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $userId);

            if ($update_stmt->execute()) {
                $message = "Your password has been successfully reset! You can now log in.";
                $token_valid = false;
                header("Refresh: 3; url=auth.php");
            } else {
                $error = "Failed to update password. Database error.";
                $token_valid = true;
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }
} else {
    $error = "Access denied. Please use the link provided in your email.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Reset Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(to right, #2E7D32, #4CAF50);
        display: flex;
        flex-direction: column; /* Allows stacking title and form */
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 10px;
    }
    .form-container {
        background-color: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        width: 100%;
        max-width: 400px;
    }
    .form-container h2 {
        text-align: center;
        color: #2E7D32;
        margin-bottom: 1.5rem;
        font-weight: 600;
        font-size: 1.6rem;
    }
    .btn-primary {
        background-color: #4CAF50;
        border-color: #4CAF50;
        transition: background-color 0.3s, transform 0.2s;
    }
    .btn-primary:hover {
        background-color: #388e3c;
        border-color: #388e3c;
        transform: translateY(-2px);
    }
    .message-box-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        text-align: center;
        font-weight: bold;
    }
    .message-box-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        text-align: center;
        font-weight: bold;
    }
    .text-link {
        color: #4CAF50;
        text-decoration: none;
        transition: color 0.2s;
    }
    .text-link:hover {
        color: #FFEB3B;
        text-decoration: underline;
    }
    .teaus-yellow-accent {
        color: #FFEB3B;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    }

    /* MAIN TITLE STYLES */
    .main-title {
        color: white;
        text-align: center;
        margin-bottom: 20px;
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1.2;
        text-shadow:
            0 0 5px rgba(255, 255, 255, 0.5),
            0 4px 6px rgba(0, 0, 0, 0.6);
        max-width: 400px;
        padding: 0 10px;
    }

    /* Password container for eye icon */
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
        z-index: 10;
    }

    /* Responsive adjustments */
    @media (max-width: 575.98px) {
        .main-title {
            font-size: 1.25rem;
            margin-bottom: 15px;
        }
        .form-container {
            padding: 20px;
            margin-top: 10px;
            margin-bottom: 10px;
        }
    }

    @media (min-width: 768px) {
        .main-title {
            font-size: 1.75rem;
            margin-bottom: 30px;
            max-width: 500px;
        }
        .form-container {
            max-width: 450px;
        }
    }
</style>
</head>
<body>
<div class="main-title">
    TEAU CourseFinder: <span class="teaus-yellow-accent">Secure</span> Password Reset
</div>
<div class="form-container">
    <?php if (!empty($message)) : ?>
        <div class="message-box-success">
            <?php echo $message; ?>
            <p class="mt-2">Redirecting to login...</p>
        </div>
    <?php endif; ?>

    <?php if (!empty($error) && !$token_valid) : ?>
        <div class="message-box-error">
            <?php echo $error; ?>
            <p class="mt-2"><a href="auth.php" class="text-link">Go back to Login</a></p>
        </div>
    <?php endif; ?>

    <?php if ($token_valid && empty($message)) : ?>
        <h2><i class="fas fa-lock me-2"></i>Reset Your Password</h2>
        
        <?php if (!empty($error) && $token_valid) : ?>
            <div class="message-box-error mb-3">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="reset_password.php" method="post" novalidate>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <div class="password-container">
                    <input type="password" class="form-control" id="password" name="password" required minlength="8" placeholder="Enter new password">
                    <span class="password-toggle" onclick="togglePasswordVisibility('password', this)">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <div class="password-container">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Confirm new password">
                    <span class="password-toggle" onclick="togglePasswordVisibility('confirm_password', this)">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary" name="reset_password">Set New Password</button>
            </div>
        </form>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
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
</script>
</body>
</html>