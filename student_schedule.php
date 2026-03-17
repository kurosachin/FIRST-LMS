<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['student_id']) || !isset($_SESSION['student_name'])) {
    header('Location: login.php');
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// Get student's enrolled schedules
$sql = "SELECT cs.*, s.subject_name, t.firstname as teacher_firstname, 
        t.lastname as teacher_lastname, sem.name as term_name 
        FROM class_schedules cs 
        JOIN subjects s ON cs.subject_id = s.id 
        JOIN teachers t ON cs.teacher_id = t.id 
        JOIN semesters sem ON cs.semester_id = sem.id 
        JOIN schedule_enrollments se ON cs.id = se.schedule_id 
        WHERE se.student_id = ? AND se.status = 'enrolled'
        ORDER BY FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), 
        cs.start_time";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$schedules = $stmt->get_result();

// Get available schedules for enrollment
$sql = "SELECT cs.*, s.subject_name, t.firstname as teacher_firstname, 
        t.lastname as teacher_lastname, sem.name as term_name 
        FROM class_schedules cs 
        JOIN subjects s ON cs.subject_id = s.id 
        JOIN teachers t ON cs.teacher_id = t.id 
        JOIN semesters sem ON cs.semester_id = sem.id 
        WHERE sem.is_active = 1 
        AND NOT EXISTS (
            SELECT 1 FROM schedule_enrollments se 
            WHERE se.schedule_id = cs.id 
            AND se.student_id = ?
        )
        ORDER BY FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), 
        cs.start_time";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$available_schedules = $stmt->get_result();

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enroll') {
    $schedule_id = $_POST['schedule_id'];
    
    // Check for schedule conflicts
    $check_sql = "SELECT cs.* FROM class_schedules cs 
                 JOIN schedule_enrollments se ON cs.id = se.schedule_id 
                 WHERE se.student_id = ? AND se.status = 'enrolled'
                 AND cs.day_of_week = (
                     SELECT day_of_week FROM class_schedules WHERE id = ?
                 )
                 AND ((cs.start_time <= (
                     SELECT end_time FROM class_schedules WHERE id = ?
                 ) AND cs.end_time > (
                     SELECT start_time FROM class_schedules WHERE id = ?
                 ))
                 OR (cs.start_time < (
                     SELECT end_time FROM class_schedules WHERE id = ?
                 ) AND cs.end_time >= (
                     SELECT start_time FROM class_schedules WHERE id = ?
                 ))
                 OR (cs.start_time >= (
                     SELECT start_time FROM class_schedules WHERE id = ?
                 ) AND cs.end_time <= (
                     SELECT end_time FROM class_schedules WHERE id = ?
                 )))";
    
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iiiiiiii", $student_id, $schedule_id, $schedule_id, $schedule_id, $schedule_id, $schedule_id, $schedule_id, $schedule_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "Schedule conflict detected! You cannot enroll in overlapping classes.";
    } else {
        $sql = "INSERT INTO schedule_enrollments (schedule_id, student_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $schedule_id, $student_id);
        
        if ($stmt->execute()) {
            $success = "Successfully enrolled in the class!";
            // Refresh the page to show updated schedules
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error = "Error enrolling in class: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Schedule</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="js/print.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            display: flex;
            min-height: 100vh;
            background-color: #f4f6f8;
        }

        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: #ecf0f1;
            padding-top: 1rem;
            flex-shrink: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .sidebar.shrink {
            width: 70px;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .sidebar.shrink h2 span {
            display: none;
        }

        .sidebar ul {
            list-style: none;
            padding: 0 1rem;
        }

        .sidebar li {
            padding: 0.8rem;
            transition: background-color 0.2s;
        }

        .sidebar li:hover {
            background-color: #34495e;
        }

        .sidebar .section-title {
            font-weight: bold;
            font-size: 0.75rem;
            margin-top: 1.5rem;
            margin-bottom: 0.3rem;
            color: #bdc3c7;
            text-transform: uppercase;
        }

        .sidebar.shrink .section-title,
        .sidebar.shrink li span {
            display: none;
        }

        .sidebar li a {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topbar {
            position: fixed;
            left: 250px;
            right: 0;
            top: 0;
            background-color: #fff;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 999;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .topbar.shrink {
            left: 70px;
        }

        .toggle-btn {
            font-size: 1.2rem;
            cursor: pointer;
            background: none;
            border: none;
            color: #2c3e50;
        }

        .logout-btn {
            margin-left: auto;
            text-decoration: none;
            color: #e74c3c;
            font-weight: bold;
        }

        .content {
            flex-grow: 1;
            padding: 5rem 2rem 2rem 2rem;
            transition: all 0.3s ease;
            margin-left: 0;
        }

        .content.shrink {
            margin-left: auto;
        }

        .schedule-container {
            background: #fff;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1rem;
            margin: 1rem 0;
        }

        .schedule-item {
            background: #f8f9fa;
            border: 1px solid #eee;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .schedule-item h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .time-slot {
            font-size: 0.9em;
            color: #666;
            margin: 0.5rem 0;
        }

        .teacher-name {
            font-style: italic;
            color: #666;
            margin: 0.5rem 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        th, td {
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            background: #2c3e50;
            color: white;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: fixed;
                height: auto;
                z-index: 1000;
            }

            .topbar {
                left: 0;
            }

            .content {
                margin-top: 200px;
            }

            .schedule-grid {
                grid-template-columns: 1fr;
            }
        }

        .print-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 10px 0;
        }
        .print-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <h2><i class="fas fa-graduation-cap"></i> <span>Student Portal</span></h2>
        <ul>
            <li class="section-title">Student Dashboard</li>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="sms_profile.php"><i class="fas fa-id-card"></i> <span>SMS Profile</span></a></li>
            <li><a href="semestral_grade.php"><i class="fas fa-chart-bar"></i> <span>Semestral Grade</span></a></li>

            <li class="section-title">Enrollment</li>
            <li><a href="enrollment.php"><i class="fas fa-edit"></i> <span>Enrollment</span></a></li>

            <li class="section-title">Schedule</li>
            <li><a href="student_schedule.php"><i class="fas fa-calendar-alt"></i> <span>My Schedule</span></a></li>

            <li class="section-title">Academic</li>
            <li><a href="subjects.php"><i class="fas fa-book"></i> <span>Subjects</span></a></li>
            <li><a href="student_assignments.php"><i class="fas fa-tasks"></i> <span>My Assignments</span></a></li>

            <li class="section-title">Wallet & Payment</li>
            <li><a href="account_statement.php"><i class="fas fa-wallet"></i> <span>Account Statement / Balance</span></a></li>
        </ul>
    </div>

    <div class="topbar" id="topbar">
        <button class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h1>Student Schedule</h1>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content" id="content">
        <button class="print-btn" onclick="printContent('schedule-content', 'Student Schedule Report')">
            <i class="fas fa-print"></i> Print Schedule
        </button>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div id="schedule-content">
            <div class="schedule-container">
                <h2>Current Schedule</h2>
                <div class="schedule-grid">
                    <?php
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    foreach ($days as $day) {
                        echo "<div class='schedule-item'>";
                        echo "<h3>$day</h3>";
                        
                        // Filter schedules for this day
                        $schedules->data_seek(0);
                        $has_schedule = false;
                        
                        while ($schedule = $schedules->fetch_assoc()) {
                            if ($schedule['day_of_week'] === $day) {
                                $has_schedule = true;
                                echo "<div class='schedule-item'>";
                                echo "<strong>" . htmlspecialchars($schedule['subject_name']) . "</strong><br>";
                                echo "<span class='time-slot'>" . date('h:i A', strtotime($schedule['start_time'])) . " - " . 
                                     date('h:i A', strtotime($schedule['end_time'])) . "</span><br>";
                                echo "Room: " . htmlspecialchars($schedule['room']) . "<br>";
                                echo "<span class='teacher-name'>Teacher: " . htmlspecialchars($schedule['teacher_firstname'] . " " . 
                                     $schedule['teacher_lastname']) . "</span><br>";
                                echo "Term: " . htmlspecialchars($schedule['term_name']);
                                echo "</div>";
                            }
                        }
                        
                        if (!$has_schedule) {
                            echo "<p>No classes scheduled</p>";
                        }
                        
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('shrink');
            document.getElementById('topbar').classList.toggle('shrink');
            document.getElementById('content').classList.toggle('shrink');
        }
    </script>
</body>
</html> 