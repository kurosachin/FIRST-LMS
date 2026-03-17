<?php
session_start();
$conn = new mysqli("localhost", "root", "", "student_management_system");

$id = $_GET['id'];

// First delete related enrollment records
$conn->query("DELETE FROM enrollments WHERE student_id = $id");

// Then delete the student
$conn->query("DELETE FROM students WHERE id = $id");

header("Location: manage_students.php");
exit();
?>
