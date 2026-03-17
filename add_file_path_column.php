<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "student_management_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add file_path column if it doesn't exist
$check_column = "SHOW COLUMNS FROM assignment_submissions LIKE 'file_path'";
$result = $conn->query($check_column);

if ($result->num_rows == 0) {
    $alter_sql = "ALTER TABLE assignment_submissions ADD COLUMN file_path VARCHAR(255) DEFAULT NULL AFTER status";
    if ($conn->query($alter_sql) === TRUE) {
        echo "Successfully added file_path column to assignment_submissions table";
    } else {
        echo "Error adding file_path column: " . $conn->error;
    }
} else {
    echo "file_path column already exists in assignment_submissions table";
}

$conn->close();
?> 