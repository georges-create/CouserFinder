<?php
// Include the database connection file
// NOTE: Ensure 'config.php' establishes and provides the $conn object.
include 'config.php';

// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Get and sanitize input data
    // Use trim to remove whitespace
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; 
    $program = trim($_POST['program']);
    $year = trim($_POST['year']);
    $semester = trim($_POST['semester']);
    $userType = trim($_POST['userType']);

    // Hash the password for security
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // =======================================================
    // 2. CHECK FOR DUPLICATE EMAIL 
    // =======================================================
    $check_sql = "SELECT id FROM user WHERE email = ?";
    
    // Using a prepared statement for the SELECT query
    if ($stmt = $conn->prepare($check_sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // **CHANGE:** Create a generic error message that does NOT include $email
            $error_message = urlencode("The email address provided is already registered. Please use a different email or check the existing user's details.");
            
            // Redirect with the generic error message
            header("Location: user.php?status=error&message={$error_message}");
            $stmt->close();
            mysqli_close($conn);
            exit(); // Stop execution here
        }
        $stmt->close();
    } else {
        // Handle prepare error for the check
        $error_message = urlencode("Database check error: " . $conn->error);
        header("Location: user.php?status=error&message={$error_message}");
        mysqli_close($conn);
        exit();
    }

    // =======================================================
    // 3. INSERT NEW USER (If email is unique)
    // =======================================================
    $insert_sql = "INSERT INTO user (fullName, email, password, program, year, semester, userType) VALUES (?, ?, ?, ?, ?, ?, ?)";

    // Using a prepared statement for the INSERT query
    if ($stmt = $conn->prepare($insert_sql)) {
        // 's' for string, 'i' for integer. Adjust if 'year' or 'semester' is not an integer.
        // Assuming: fullName(s), email(s), password(s), program(s), year(i), semester(i), userType(s)
        $stmt->bind_param("ssssiss", $fullName, $email, $hashedPassword, $program, $year, $semester, $userType);
        
        if ($stmt->execute()) {
            // Success! Redirect with success message
            header("Location: user.php?status=success_add");
            $stmt->close();
            mysqli_close($conn);
            exit();
        } else {
            // Insert execution failed
            $error_message = urlencode("Failed to add user: " . $stmt->error);
            header("Location: user.php?status=error&message={$error_message}");
            $stmt->close();
            mysqli_close($conn);
            exit();
        }
    } else {
        // Handle prepare error for the insert
        $error_message = urlencode("Database insert preparation error: " . $conn->error);
        header("Location: user.php?status=error&message={$error_message}");
        mysqli_close($conn);
        exit();
    }

} else {
    // If the script is accessed directly (not via POST), redirect
    header("Location: user.php");
    exit();
}
?>