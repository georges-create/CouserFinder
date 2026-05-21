<?php
// Set the content type header immediately to ensure the client knows to expect JSON.
header('Content-Type: application/json');

// --- 1. Database Connection ---
// Include the database configuration variables.
// This file must define: $servername, $username, $password, $dbname
include "config.php";

// Establish a dedicated connection for this API request.
// Using a new connection prevents issues if other scripts close the main connection.
$conn = new mysqli($servername, $username, $password, $dbname);

// Check for database connection errors.
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// --- 2. Get and Sanitize Input Parameters ---
// Sanitize and validate input parameters from the URL query string.
$program = isset($_GET['program']) ? trim($_GET['program']) : null;
$year = isset($_GET['year']) ? intval($_GET['year']) : null;
$semester = isset($_GET['semester']) ? intval($_GET['semester']) : null;
$courseCode = isset($_GET['code']) ? strtoupper(trim($_GET['code'])) : null;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : null;

// --- 3. Build Dynamic SQL Query and Bind Parameters ---
// FIX: Add 'program', 'year', and 'semester' to the SELECT list.
$sql = "SELECT id, code, name, description, program, year, semester FROM courses WHERE 1=1";
$params = [];
$types = "";

// Course Code search takes highest priority if provided.
if ($courseCode) {
    $sql .= " AND code LIKE ?";
    $params[] = "%" . $courseCode . "%";
    $types .= "s";
}
// Keyword search is the next priority.
else if ($keyword) {
    // FIX: Include program, year, and semester in results for keyword searches too
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%" . $keyword . "%";
    $params[] = "%" . $keyword . "%";
    $types .= "ss";
}
// Default search by Program, Year, and Semester.
else {
    // This section is implicitly searching by PYS, so no additional columns needed
    if ($program) {
        $sql .= " AND program = ?";
        $params[] = $program;
        $types .= "s";
    }

    // Only add year if it's a positive number (0 or null would search all years)
    if ($year > 0) {
        $sql .= " AND year = ?";
        $params[] = $year;
        $types .= "i";
    }

    // Only add semester if it's a positive number (0 or null would search all semesters)
    if ($semester > 0) {
        $sql .= " AND semester = ?";
        $params[] = $semester;
        $types .= "i";
    }
}

// --- 4. Prepare and Execute the Statement ---
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "SQL statement preparation failed: " . $conn->error]);
    $conn->close();
    exit();
}

// Use a cleaner way to bind parameters using the splat operator (...),
// which is a standard feature in PHP 5.6 and newer.
if (!empty($params)) {
    // Note: The @ operator is used to suppress the error if the function is called with too few arguments,
    // though modern PHP practice is to ensure $types and $params match.
    $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
    http_response_code(500);
    // In a production environment, you might log this error and return a generic message.
    echo json_encode(["error" => "SQL statement execution failed: " . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit();
}

$result = $stmt->get_result();
$courses = [];

// Fetch all results into an array.
while ($row = $result->fetch_assoc()) {
    // FIX: Convert year and semester to integers before sending to JSON
    // The front-end JavaScript expects these to be numbers (or null/0)
    $row['year'] = intval($row['year']);
    $row['semester'] = intval($row['semester']);
    $courses[] = $row;
}

// --- 5. Clean Up and Respond ---
$stmt->close();
$conn->close();

// Return the results as a JSON array.
// An empty array is returned if no courses are found, which is a standard API response.
echo json_encode($courses);
?>