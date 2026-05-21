<?php
// fetch_events.php

// Include the database connection and configuration
include "config.php";

// Set the content type to JSON
header('Content-Type: application/json');

// 1. Validate Database Connection
if (!isset($conn) || $conn->connect_error) {
    error_log("DB Connection Failed in fetch_events.php: " . ($conn->connect_error ?? 'Connection object not set.'));
    echo json_encode([]);
    exit();
}

// 2. Validate Request Method and Parameter
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['program'])) {
    error_log("Invalid request or 'program' parameter missing in fetch_events.php.");
    $conn->close();
    echo json_encode([]);
    exit();
}

$program = $_POST['program'];
$events = [];

// SQL Query: Filter by ALL programs OR the user's specific program
$sql = "SELECT id, title, start_date AS start, end_date AS end, description, color 
        FROM academic_events 
        WHERE program_filter = 'ALL' OR program_filter = ?";

$stmt = $conn->prepare($sql);

// 3. Check for Prepared Statement Failure
if ($stmt === false) {
    error_log("SQL Prepare Failed: " . $conn->error);
    $conn->close();
    echo json_encode([]);
    exit();
}

// 4. Bind Parameter and Execute
$stmt->bind_param("s", $program);

if ($stmt->execute()) {
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $event = [
            'id'    => $row['id'],
            'title' => $row['title'],
            'start' => $row['start'],
            'extendedProps' => [
                'description' => $row['description']
            ],
            'allDay' => true // CRITICAL: Forces event to display in the month grid
        ];

        // Only include the 'end' date if it's not NULL
        if (!empty($row['end'])) {
            $event['end'] = $row['end'];
        }

        // Set color if specified, otherwise FullCalendar uses its default
        if (!empty($row['color'])) {
            $event['color'] = $row['color'];
        }

        $events[] = $event;
    }

    $stmt->close();
} else {
    // 5. Check for Execution Error
    error_log("SQL Execution Failed: " . $stmt->error);
}

$conn->close();

// Output the final events array as JSON
echo json_encode($events);
?>