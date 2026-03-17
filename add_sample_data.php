<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "student_management_system";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add sample student
$sql = "INSERT INTO students (username, password, firstname, lastname, email) 
        SELECT 'student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 'john.doe@example.com'
        WHERE NOT EXISTS (SELECT 1 FROM students WHERE username = 'student1')";

if ($conn->query($sql) === TRUE) {
    echo "Sample student added if not exists<br>";
} else {
    echo "Error adding sample student: " . $conn->error . "<br>";
}

// Add sample subjects
$subjects = [
    ['MATH101', 'Mathematics 101', 3, 'First Semester', '2023-2024'],
    ['ENG101', 'English 101', 3, 'First Semester', '2023-2024'],
    ['CS101', 'Computer Science 101', 3, 'First Semester', '2023-2024']
];

foreach ($subjects as $subject) {
    $sql = "INSERT INTO subjects (subject_code, subject_name, units, semester, school_year)
            SELECT ?, ?, ?, ?, ?
            WHERE NOT EXISTS (SELECT 1 FROM subjects WHERE subject_code = ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisss", $subject[0], $subject[1], $subject[2], $subject[3], $subject[4], $subject[0]);
    
    if ($stmt->execute()) {
        echo "Subject {$subject[0]} added if not exists<br>";
    } else {
        echo "Error adding subject {$subject[0]}: " . $stmt->error . "<br>";
    }
    $stmt->close();
}

// Get the active semester
$sql = "SELECT id FROM semesters WHERE is_active = TRUE LIMIT 1";
$result = $conn->query($sql);
$semester = $result->fetch_assoc();

if ($semester) {
    // Add sample schedules
    $schedules = [
        ['MATH101', 'Monday', '08:00:00', '09:30:00', 'Room 101'],
        ['ENG101', 'Tuesday', '10:00:00', '11:30:00', 'Room 102'],
        ['CS101', 'Wednesday', '13:00:00', '14:30:00', 'Room 103']
    ];

    foreach ($schedules as $schedule) {
        // Get subject ID
        $sql = "SELECT id FROM subjects WHERE subject_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $schedule[0]);
        $stmt->execute();
        $result = $stmt->get_result();
        $subject = $result->fetch_assoc();
        $stmt->close();

        if ($subject) {
            $sql = "INSERT INTO class_schedules (subject_id, teacher_id, room, day_of_week, start_time, end_time, semester_id)
                    SELECT ?, 1, ?, ?, ?, ?, ?
                    WHERE NOT EXISTS (
                        SELECT 1 FROM class_schedules 
                        WHERE subject_id = ? AND day_of_week = ? AND start_time = ?
                    )";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssiss", 
                $subject['id'], 
                $schedule[4], 
                $schedule[1], 
                $schedule[2], 
                $schedule[3], 
                $semester['id'],
                $subject['id'],
                $schedule[1],
                $schedule[2]
            );
            
            if ($stmt->execute()) {
                echo "Schedule for {$schedule[0]} added if not exists<br>";
            } else {
                echo "Error adding schedule for {$schedule[0]}: " . $stmt->error . "<br>";
            }
            $stmt->close();
        }
    }
}

$conn->close();
echo "Sample data setup complete. <a href='login.php'>Go to Login</a>";
?> 