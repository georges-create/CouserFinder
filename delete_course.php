<?php
// Include the database connection (assumed to define $conn)
include "config.php";

// Check if 'id' is set and is a valid number
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    
    // Use Prepared Statement for security (CRITICAL SECURITY FIX)
    $sql = "DELETE FROM courses WHERE id=?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Bind parameter: id(i)
        if (!mysqli_stmt_bind_param($stmt, "i", $id)) {
             header("Location: course.php?status=error&message=" . urlencode("Binding parameters failed."));
             exit();
        }

        if (mysqli_stmt_execute($stmt)) {
            // SUCCESS: Redirect to course.php with success_delete status
            header("Location: course.php?status=success_delete");
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
    // Invalid ID or no ID provided
    header("Location: course.php?status=error&message=" . urlencode("Invalid or missing course ID for deletion."));
    exit();
}
mysqli_close($conn);
?>