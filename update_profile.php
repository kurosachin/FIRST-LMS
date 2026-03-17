<?php
session_start();

if (!isset($_SESSION['student_name'])) {
    header("Location: login.php");
    exit();
}

$studentName = $_SESSION['student_name'];

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "student_management_system";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get form data
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $dob = $conn->real_escape_string($_POST['dob']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $grade_level = $conn->real_escape_string($_POST['grade_level']);
    $program = $conn->real_escape_string($_POST['program']);
    $previous_school = $conn->real_escape_string($_POST['previous_school']);
    $enrollment_date = $conn->real_escape_string($_POST['enrollment_date']);
    $student_type = $conn->real_escape_string($_POST['student_type']);
    $parent_name = $conn->real_escape_string($_POST['parent_name']);
    $parent_relationship = $conn->real_escape_string($_POST['parent_relationship']);
    $parent_phone = $conn->real_escape_string($_POST['parent_phone']);

    // Split fullname into first, middle, and last name
    $name_parts = explode(' ', $fullname);
    $firstname = $name_parts[0];
    $middlename = isset($name_parts[1]) ? $name_parts[1] : '';
    $lastname = isset($name_parts[2]) ? $name_parts[2] : '';

    // Update the database
    $sql = "UPDATE students SET 
            firstname = ?, 
            middlename = ?, 
            lastname = ?, 
            dob = ?, 
            gender = ?, 
            email = ?, 
            phone = ?, 
            address = ?, 
            grade_level = ?, 
            program = ?, 
            previous_school = ?, 
            enrollment_date = ?, 
            student_type = ?, 
            parent_name = ?, 
            parent_relationship = ?, 
            parent_phone = ? 
            WHERE username = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssssss", 
        $firstname, 
        $middlename, 
        $lastname, 
        $dob, 
        $gender, 
        $email, 
        $phone, 
        $address, 
        $grade_level, 
        $program, 
        $previous_school, 
        $enrollment_date, 
        $student_type, 
        $parent_name, 
        $parent_relationship, 
        $parent_phone, 
        $studentName
    );

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating profile: " . $conn->error;
    }

    $stmt->close();
    $conn->close();

    // Redirect back to profile page
    header("Location: sms_profile.php");
    exit();
}
?> 