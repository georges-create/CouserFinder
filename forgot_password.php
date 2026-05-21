<?php
    
ini_set('display_errors', 1);
error_reporting(E_ALL);
// forgot_password.php - Handles Forgot Password Requests

ob_start(); // Start output buffering early to prevent "Headers already sent" errors

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
include "config.php"; // Assumes this sets up $conn

// Load PHPMailer classes
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Send email function using PHPMailer and SMTP
function sendEmail($toEmail, $toName, $subject, $htmlContent) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'georgejuma147@gmail.com'; // Replace with your SMTP username
        $mail->Password = 'dpvbsmxomcgmeqwj';    // !! IMPORTANT: Use an App Password if using Gmail 2FA !!
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('georgejuma147@gmail.com', 'TEAU CourseFinder'); // Replace with your "From" address and name
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlContent;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail error: " . $mail->ErrorInfo);
        return false;
    }
}

$message = ""; // Used for the security-conscious success/unknown message
$error = "";   // Used for actual system errors (DB failure, Emailer failure)

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['forgot_password'])) {
    $email = sanitize_input($_POST['email']);

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, fullName FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Set the default message for security (same whether user exists or not)
    $message = "A password reset link has been sent to your email address (if an account exists).";

    if ($result->num_rows === 0) {
        // User not found. We continue with the default vague success message.
    } else {
        $user = $result->fetch_assoc();
        $userId = $user['id'];
        $userName = $user['fullName'];

        // Generate token and expiry
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", time() + 3600); // 1 hour expiry

        // Store token
        $update_stmt = $conn->prepare("UPDATE user SET reset_token = ?, token_expiry = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $token, $expires, $userId);

        if ($update_stmt->execute()) {
            $reset_link = "http://georges.gt.tc/reset_password.php?token=" . $token; // Replace with your domain

            $htmlContent = "
                <p>Hello $userName,</p>
                <p>We received a request to reset the password for your TEAU CourseFinder account.</p>
                <p>Please click the following link to reset your password:</p>
                <p><a href='$reset_link'>Reset My Password</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you did not request a password reset, please ignore this email.</p>
                <p>Thank you,<br>TEAU CourseFinder Support</p>
            ";

            if (sendEmail($email, $userName, 'Password Reset Request for TEAU CourseFinder', $htmlContent)) {
                // Email sent successfully. $message is already set to the vague success message.
                
                // --- ADMIN NOTIFICATION CODE START (Optional: Notify Admin) ---
                $adminEmail = 'georgejuma147@gmail.com'; 
                $adminName = 'Admin';
                $notificationSubject = 'ALERT: Password Reset Link Sent';
                $notificationContent = "
                    <p>A password reset link was successfully sent for the following user:</p>
                    <ul>
                        <li>Email: **$email**</li>
                        <li>Full Name: $userName</li>
                    </ul>
                    <p>Token: $token</p>
                    <p>Expiry: $expires</p>
                ";
                sendEmail($adminEmail, $adminName, $notificationSubject, $notificationContent);
                // --- ADMIN NOTIFICATION CODE END ---

            } else {
                // PHPMailer failure
                $error = "The password reset link could not be sent due to a system error. Contact support.";
                $message = ""; // Clear vague message, prioritize the system error
            }
        } else {
            // DB failure
            $error = "Database error while preparing reset. Please contact support.";
            $message = ""; // Clear vague message, prioritize the system error
        }
        $update_stmt->close();
    }
    $stmt->close();
}

// --- FINAL REDIRECTION LOGIC (MODIFIED) ---

// Determine the final session message and type
if (!empty($error)) {
    // This handles actual system errors (DB failure, Emailer failed to connect/send)
    $final_message = $error;
    $message_type = 'error';
} else if (!empty($message)) {
    // This handles the security-conscious "vague success" message
    $final_message = $message;
    $message_type = 'success'; // Treat the successful attempt as a "success" type for UI styling
} else {
    // Fallback
    $final_message = "An unknown error occurred. Please try again.";
    $message_type = 'error';
}

// Store the message and type in session variables as requested
$_SESSION['auth_message'] = $final_message;
$_SESSION['auth_message_type'] = $message_type;

// Redirect back to the login page, telling the JS to show the correct form
header("Location: auth.php?show=forgot"); 
exit();

// Close the database connection
if (isset($conn)) {
    $conn->close();
}

// Clean output buffer and send headers
ob_end_flush();
?>
