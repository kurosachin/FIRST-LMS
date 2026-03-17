<?php
require_once 'db_connect.php';

// Create semesters table
$sql = "CREATE TABLE IF NOT EXISTS semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'semesters' created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Check if semesters already exist
$check_sql = "SELECT COUNT(*) as count FROM semesters WHERE name IN ('1st Semester', '2nd Semester')";
$result = $conn->query($check_sql);
$row = $result->fetch_assoc();

if ($row['count'] < 2) {
    // Insert default semesters only if they don't exist
    $sql = "INSERT IGNORE INTO semesters (name) VALUES 
        ('1st Semester'),
        ('2nd Semester')";

    if ($conn->query($sql) === TRUE) {
        echo "Default semesters inserted successfully<br>";
    } else {
        echo "Error inserting semesters: " . $conn->error . "<br>";
    }
} else {
    echo "Semesters already exist, skipping insertion<br>";
}

// Check if class_schedules table exists
$table_check = $conn->query("SHOW TABLES LIKE 'class_schedules'");
if ($table_check->num_rows > 0) {
    // Check if the column needs to be renamed
    $column_check = $conn->query("SHOW COLUMNS FROM class_schedules LIKE 'academic_term_id'");
    if ($column_check->num_rows > 0) {
        // Drop foreign key constraint first
        $fk_check = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                                 WHERE TABLE_NAME = 'class_schedules' 
                                 AND COLUMN_NAME = 'academic_term_id'
                                 AND CONSTRAINT_NAME != 'PRIMARY'
                                 AND TABLE_SCHEMA = DATABASE()");
        if ($fk = $fk_check->fetch_assoc()) {
            $conn->query("ALTER TABLE class_schedules DROP FOREIGN KEY " . $fk['CONSTRAINT_NAME']);
        }
        
        // Rename the column
        $sql = "ALTER TABLE class_schedules 
                CHANGE COLUMN academic_term_id semester_id INT";
        
        if ($conn->query($sql) === TRUE) {
            echo "Column renamed successfully<br>";
        } else {
            echo "Error renaming column: " . $conn->error . "<br>";
        }
        
        // Add new foreign key constraint
        $sql = "ALTER TABLE class_schedules 
                ADD FOREIGN KEY (semester_id) REFERENCES semesters(id)";
        
        if ($conn->query($sql) === TRUE) {
            echo "New foreign key constraint added successfully<br>";
        } else {
            echo "Error adding foreign key: " . $conn->error . "<br>";
        }
    } else {
        echo "Column already renamed, skipping modification<br>";
    }
} else {
    // Create class_schedules table if it doesn't exist
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
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
        FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'class_schedules' created successfully<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
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
    echo "Schedule enrollments table created successfully<br>";
} else {
    echo "Error creating schedule enrollments table: " . $conn->error . "<br>";
}

$conn->close();
echo "Schedule tables setup complete. <a href='admin_dashboard.php'>Go to Admin Dashboard</a>";
?> 