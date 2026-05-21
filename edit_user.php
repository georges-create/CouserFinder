<?php
// Include the database configuration file
include 'config.php';

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $id = trim($_POST['id']);
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $program = trim($_POST['program']);
    $year = $_POST['year'];
    $semester = $_POST['semester'];
    $userType = trim($_POST['userType']);

    // Check if the ID is a valid integer
    if (!is_numeric($id)) {
        header("Location: user.php?status=error&message=" . urlencode("Invalid user ID."));
        exit();
    }

    // Prepare an UPDATE statement to prevent SQL injection
    $sql = "UPDATE user SET fullName = ?, email = ?, program = ?, year = ?, semester = ?, userType = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssssssi", $fullName, $email, $program, $year, $semester, $userType, $id);

        if ($stmt->execute()) {
            // Redirect back to the user page with a success message
            header("Location: user.php?status=success_edit");
            exit();
        } else {
            // Redirect back with an error message
            header("Location: user.php?status=error&message=" . urlencode($stmt->error));
            exit();
        }
        $stmt->close();
    } else {
        // Redirect back with a statement preparation error
        header("Location: user.php?status=error&message=" . urlencode($conn->error));
        exit();
    }
} else {
    // If not a POST request, redirect back
    header("Location: user.php");
    exit();
}
?>