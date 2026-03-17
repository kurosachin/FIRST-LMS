<?php
require_once 'db_connect.php';

// Remove duplicate entries keeping only the ones with the lowest IDs
$sql = "DELETE s1 FROM semesters s1
        INNER JOIN semesters s2
        WHERE s1.name = s2.name AND s1.id > s2.id";

if ($conn->query($sql) === TRUE) {
    echo "Duplicate semesters removed successfully<br>";
} else {
    echo "Error removing duplicates: " . $conn->error . "<br>";
}

// Show remaining entries
$sql = "SELECT * FROM semesters ORDER BY id";
$result = $conn->query($sql);

echo "<h3>Current Semesters:</h3>";
echo "<ul>";
while ($row = $result->fetch_assoc()) {
    echo "<li>ID: " . $row['id'] . " - Name: " . $row['name'] . "</li>";
}
echo "</ul>";

$conn->close();
?> 