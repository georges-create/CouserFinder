<?php
// Include the database configuration file
include 'config.php';

// Check if a user ID is provided in the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare a DELETE statement to prevent SQL injection
    $sql = "DELETE FROM user WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            // Redirect back to the user page with a success message
            header("Location: user.php?status=success_delete");
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
    // If no valid ID is provided, redirect back with an error
    header("Location: user.php?status=error&message=" . urlencode("No user ID provided."));
    exit();
}
