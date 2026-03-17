<?php
// Include database connection
include('db_connect.php');

// Read and execute SQL file
$sql = file_get_contents('sql_file/update_grades_structure.sql');

if ($conn->multi_query($sql)) {
    echo "Database structure updated successfully!";
} else {
    echo "Error updating database structure: " . $conn->error;
}

$conn->close();
?> 