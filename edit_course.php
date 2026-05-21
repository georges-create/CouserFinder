<?php
// Include the database connection (assumed to define $conn)
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and validate input values
    $id = $_POST['id'];
    $program = $_POST['program'];
    $year = $_POST['year'];
    $semester = $_POST['semester'];
    $code = $_POST['code'];
    $name = $_POST['name'];
    $description = $_POST['description'];

    // Use Prepared Statements to prevent SQL Injection (CRITICAL SECURITY FIX)
    $sql = "UPDATE courses SET 
            program=?, year=?, semester=?, code=?, name=?, description=? 
            WHERE id=?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Bind parameters: program(s), year(i), semester(i), code(s), name(s), description(s), id(i)
        if (!mysqli_stmt_bind_param($stmt, "siisssi", $program, $year, $semester, $code, $name, $description, $id)) {
             header("Location: course.php?status=error&message=" . urlencode("Binding parameters failed."));
             exit();
        }
        
        if (mysqli_stmt_execute($stmt)) {
            // SUCCESS: Redirect to course.php with success_edit status
            header("Location: course.php?status=success_edit");
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