<?php
// Database credentials
$servername = "sql210.infinityfree.com";
$username = "if0_40088286";     
$password = "ioXFxksni1ZsLz";         
$dbname = "if0_40088286_db_teau";

// Create a new MySQLi object connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection and stop the script if it fails
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>