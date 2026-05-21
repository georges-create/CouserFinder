<?php
// Include the database connection (assumed to define $conn)
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and validate input values
    $program = $_POST['program'];
    $year = $_POST['year'];
    $semester = $_POST['semester'];
    $code = $_POST['code'];
    $name = $_POST['name'];
    $description = $_POST['description'];

    // Use Prepared Statements to prevent SQL Injection (CRITICAL SECURITY FIX)
    $sql = "INSERT INTO courses (program, year, semester, code, name, description) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Bind parameters: s=string, i=integer
        // Note: I treat year and semester as string/integer based on common database practices, adjust if they are pure text in your DB.
        if (!mysqli_stmt_bind_param($stmt, "siisss", $program, $year, $semester, $code, $name, $description)) {
             header("Location: course.php?status=error&message=" . urlencode("Binding parameters failed."));
             exit();
        }

        if (mysqli_stmt_execute($stmt)) {
            // SUCCESS: Redirect to course.php with success_add status
            header("Location: course.php?status=success_add");
            exit();
        } else {
            // ERROR: Redirect with error status and database message
            header("Location: course.php?status=error&message=" . urlencode(mysqli_error($conn)));
            exit();
        }
        mysqli_stmt_close($stmt);
    } else {
        // ERROR: Failed to prepare statement
        header("Location: course.php?status=error&message=" . urlencode("Database error: Could not prepare statement."));
        exit();
    }
} else {
    // Not a POST request
    header("Location: course.php");
    exit();
}
mysqli_close($conn);
?>