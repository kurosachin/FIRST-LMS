<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "student_management_system";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create students table
$sql = "CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'students' created successfully<br>";
} else {
    echo "Error creating students table: " . $conn->error . "<br>";
}

// Create subjects table
$sql = "CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) UNIQUE NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    units INT NOT NULL,
    semester VARCHAR(20) NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'subjects' created successfully<br>";
} else {
    echo "Error creating subjects table: " . $conn->error . "<br>";
}

// Create class_schedules table
$sql = "CREATE TABLE IF NOT EXISTS class_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    room VARCHAR(50) NOT NULL,
    day_of_week VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    semester_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'class_schedules' created successfully<br>";
} else {
    echo "Error creating class_schedules table: " . $conn->error . "<br>";
}

// Create schedule_enrollments table
$sql = "CREATE TABLE IF NOT EXISTS schedule_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('enrolled', 'dropped') DEFAULT 'enrolled',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES class_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (schedule_id, student_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'schedule_enrollments' created successfully<br>";
} else {
    echo "Error creating schedule_enrollments table: " . $conn->error . "<br>";
}

// Create semesters table
$sql = "CREATE TABLE IF NOT EXISTS semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'semesters' created successfully<br>";
} else {
    echo "Error creating semesters table: " . $conn->error . "<br>";
}

// Insert default semester if not exists
$sql = "INSERT INTO semesters (name, is_active) 
        SELECT 'First Semester 2023-2024', TRUE 
        WHERE NOT EXISTS (SELECT 1 FROM semesters WHERE is_active = TRUE)";

if ($conn->query($sql) === TRUE) {
    echo "Default semester added if not exists<br>";
} else {
    echo "Error adding default semester: " . $conn->error . "<br>";
}

$conn->close();
echo "Database setup complete. <a href='login.php'>Go to Login</a>";
?> 