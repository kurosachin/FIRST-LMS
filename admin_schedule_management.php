<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_name'])) {
    header('Location: login.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_schedule':
                $subject_id = $_POST['subject_id'];
                $teacher_id = $_POST['teacher_id'];
                $room = $_POST['room'];
                $day_of_week = $_POST['day_of_week'];
                $start_time = $_POST['start_time'];
                $end_time = $_POST['end_time'];
                $semester_id = $_POST['semester_id'];

                // Check for schedule conflicts
                $check_sql = "SELECT * FROM class_schedules 
                            WHERE teacher_id = ? 
                            AND day_of_week = ? 
                            AND ((start_time <= ? AND end_time > ?) 
                            OR (start_time < ? AND end_time >= ?) 
                            OR (start_time >= ? AND end_time <= ?))";
                
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("isssssss", $teacher_id, $day_of_week, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time);
                $check_stmt->execute();
                $result = $check_stmt->get_result();

                if ($result->num_rows > 0) {
                    $error = "Schedule conflict detected! Please choose a different time slot.";
                } else {
                    $sql = "INSERT INTO class_schedules (subject_id, teacher_id, room, day_of_week, start_time, end_time, semester_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iissssi", $subject_id, $teacher_id, $room, $day_of_week, $start_time, $end_time, $semester_id);
                    
                    if ($stmt->execute()) {
                        $success = "Schedule added successfully!";
                        // Get the new schedule's ID
                        $new_schedule_id = $stmt->insert_id;
                        // Find students enrolled in this subject but not in any schedule for it
                        $student_sql = "SELECT e.student_id FROM enrollments e
                            LEFT JOIN schedule_enrollments se ON e.student_id = se.student_id
                                AND se.schedule_id IN (SELECT id FROM class_schedules WHERE subject_id = ?)
                            WHERE e.subject_id = ? AND se.id IS NULL";
                        $student_stmt = $conn->prepare($student_sql);
                        $student_stmt->bind_param("ii", $subject_id, $subject_id);
                        $student_stmt->execute();
                        $student_result = $student_stmt->get_result();
                        while ($row = $student_result->fetch_assoc()) {
                            $assign_stmt = $conn->prepare("INSERT INTO schedule_enrollments (schedule_id, student_id, status) VALUES (?, ?, 'enrolled')");
                            $assign_stmt->bind_param("ii", $new_schedule_id, $row['student_id']);
                            $assign_stmt->execute();
                            $assign_stmt->close();
                        }
                        $student_stmt->close();
                    } else {
                        $error = "Error adding schedule: " . $conn->error;
                    }
                }
                break;

            case 'delete_schedule':
                $schedule_id = $_POST['schedule_id'];
                $sql = "DELETE FROM class_schedules WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $schedule_id);
                
                if ($stmt->execute()) {
                    $success = "Schedule deleted successfully!";
                } else {
                    $error = "Error deleting schedule: " . $conn->error;
                }
                break;
        }
    }
}

// Get all schedules
$day_filter = isset($_GET['day']) ? $_GET['day'] : '';
$room_filter = isset($_GET['room']) ? $_GET['room'] : '';
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '';

$sql = "SELECT cs.*, s.subject_name, t.firstname as teacher_firstname, 
        t.lastname as teacher_lastname, sem.name as semester_name 
        FROM class_schedules cs 
        JOIN subjects s ON cs.subject_id = s.id 
        JOIN teachers t ON cs.teacher_id = t.id 
        JOIN semesters sem ON cs.semester_id = sem.id 
        WHERE 1=1";

if (!empty($day_filter)) {
    $sql .= " AND cs.day_of_week = '" . $conn->real_escape_string($day_filter) . "'";
}
if (!empty($room_filter)) {
    $sql .= " AND cs.room = '" . $conn->real_escape_string($room_filter) . "'";
}
if (!empty($semester_filter)) {
    $sql .= " AND cs.semester_id = " . intval($semester_filter);
}

$sql .= " ORDER BY FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), 
        cs.start_time";

$schedules = $conn->query($sql);
if (!$schedules) {
    die("Error fetching schedules: " . $conn->error);
}

// Get all subjects
$sql = "SELECT * FROM subjects";
$subjects = $conn->query($sql);
if (!$subjects || $subjects->num_rows == 0) {
    die("Error: No subjects found in database. Please add subjects first.");
}

// Get all teachers
$sql = "SELECT * FROM teachers";
$teachers = $conn->query($sql);
if (!$teachers || $teachers->num_rows == 0) {
    die("Error: No teachers found in database. Please add teachers first.");
}

// Get active semesters
$sql = "SELECT * FROM semesters WHERE is_active = 1";
$semesters = $conn->query($sql);
if (!$semesters || $semesters->num_rows == 0) {
    die("Error: No active semesters found. Please add an active semester first.");
}

// Get unique rooms for filter
$rooms_sql = "SELECT DISTINCT room FROM class_schedules ORDER BY room";
$rooms_result = $conn->query($rooms_sql);
$rooms = [];
if ($rooms_result) {
    while ($row = $rooms_result->fetch_assoc()) {
        $rooms[] = $row['room'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Schedule Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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
            top: 0;
            left: 250px;
            right: 0;
            height: 60px;
            background-color: white;
            display: flex;
            align-items: center;
            padding: 0 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1000;
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

        .content {
            flex-grow: 1;
            padding: 5rem 2rem 2rem 2rem;
            transition: all 0.3s ease;
            margin-left: 0;
        }

        .content.shrink {
            margin-left: auto;
        }

        .card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 0.375rem;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            background-color: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .schedule-table th {
            background-color: #2c3e50;
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: bold;
        }

        .schedule-table td {
            padding: 1rem;
            border-bottom: 1px solid #ecf0f1;
        }

        .schedule-table tr:last-child td {
            border-bottom: none;
        }

        .schedule-table tr:hover {
            background-color: #f8f9fa;
        }

        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            background-color: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .form-table th {
            background-color: #2c3e50;
            color: white;
            padding: 1rem;
            text-align: center;
            font-weight: bold;
            white-space: nowrap;
        }

        .form-table td {
            padding: 1rem;
            border-bottom: 1px solid #ecf0f1;
            text-align: center;
        }

        .form-table tr:last-child td {
            border-bottom: none;
        }

        .form-table select,
        .form-table input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 0.375rem;
            font-size: 1rem;
            background-color: #f8f9fa;
            min-width: 120px;
        }

        .form-table select:focus,
        .form-table input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .form-actions {
            margin-top: 1.5rem;
            text-align: right;
        }

        .form-actions .btn {
            min-width: 120px;
        }

        @media (max-width: 1200px) {
            .form-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <h2><i class="fas fa-graduation-cap"></i> <span>Admin Portal</span></h2>
    <ul>
      <li class="section-title">Dashboard</li>
      <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
      
      <li class="section-title">Notifications</li>
      <li><a href="notification.php"><i class="fas fa-bell"></i> <span>View Notifications</span></a></li>

      <li class="section-title">Teachers</li>
      <li><a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> <span>Manage Teachers</span></a></li>
      <li><a href="add_teacher.php"><i class="fas fa-user-plus"></i> <span>Add Teacher</span></a></li>

      <li class="section-title">Students</li>
      <li><a href="manage_students.php"><i class="fas fa-users"></i> <span>Manage Students</span></a></li>
      <li><a href="add_student.php"><i class="fas fa-user-plus"></i> <span>Add Student</span></a></li>
      <li><a href="approve_students.php"><i class="fas fa-check-circle"></i> <span>Approve Students</span></a></li>
      <li><a href="manage_student_accounts.php"><i class="fas fa-wallet"></i> <span>Student Accounts</span></a></li>

      <li class="section-title">Schedule</li>
      <li><a href="admin_schedule_management.php"><i class="fas fa-calendar-check"></i> <span>Schedule Management</span></a></li>

      <li class="section-title">Subjects</li>
      <li><a href="manage_subjects.php"><i class="fas fa-book"></i> <span>Manage Subjects</span></a></li>
      <li><a href="add_subject.php"><i class="fas fa-plus-circle"></i> <span>Add Subject</span></a></li>

      <li class="section-title">Admins</li>
      <li><a href="manage_admins.php"><i class="fas fa-user-shield"></i> <span>Manage Admin</span></a></li>
      <li><a href="add_admin.php"><i class="fas fa-user-plus"></i> <span>Add Admin</span></a></li>
    </ul>
  </div>

    <div class="topbar" id="topbar">
        <button class="toggle-btn" id="toggleSidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h3>Schedule Management</h3>
    </div>

    <div class="content" id="content">
        <div class="container">
            <h1>Schedule Management</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Add Schedule Button -->
            <button onclick="showAddScheduleModal()" style="margin-bottom: 20px; padding: 10px 20px; background-color: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer;">
                <i class="fas fa-plus"></i> Add New Schedule
            </button>

            <!-- Add New Schedule Form -->
            <!-- Note: Schedules added here are used for student enrollment. If no schedules exist for a subject, students cannot enroll. -->
            <div class="card">
                <h2>Add New Schedule</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_schedule">
                    
                    <table class="form-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Semester</th>
                                <th>Day</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select name="subject_id" required>
                                        <?php while ($subject = $subjects->fetch_assoc()): ?>
                                            <option value="<?php echo $subject['id']; ?>">
                                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="teacher_id" required>
                                        <?php while ($teacher = $teachers->fetch_assoc()): ?>
                                            <option value="<?php echo $teacher['id']; ?>">
                                                <?php echo htmlspecialchars($teacher['firstname'] . ' ' . $teacher['lastname']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="semester_id" required>
                                        <?php while ($semester = $semesters->fetch_assoc()): ?>
                                            <option value="<?php echo $semester['id']; ?>">
                                                <?php echo htmlspecialchars($semester['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="day_of_week" required>
                                        <option value="Monday">Monday</option>
                                        <option value="Tuesday">Tuesday</option>
                                        <option value="Wednesday">Wednesday</option>
                                        <option value="Thursday">Thursday</option>
                                        <option value="Friday">Friday</option>
                                        <option value="Saturday">Saturday</option>
                                        <option value="Sunday">Sunday</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="time" name="start_time" required>
                                </td>
                                <td>
                                    <input type="time" name="end_time" required>
                                </td>
                                <td>
                                    <input type="text" name="room" required>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Add Schedule</button>
                    </div>
                </form>
            </div>

            <!-- Display All Schedules -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>All Schedules</h2>
                    <form method="GET" class="filter-form" style="display: flex; gap: 10px; align-items: center;">
                        <div>
                            <label for="day" style="margin-right: 5px;">Day:</label>
                            <select name="day" id="day" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
                                <option value="">All Days</option>
                                <option value="Monday" <?php echo $day_filter === 'Monday' ? 'selected' : ''; ?>>Monday</option>
                                <option value="Tuesday" <?php echo $day_filter === 'Tuesday' ? 'selected' : ''; ?>>Tuesday</option>
                                <option value="Wednesday" <?php echo $day_filter === 'Wednesday' ? 'selected' : ''; ?>>Wednesday</option>
                                <option value="Thursday" <?php echo $day_filter === 'Thursday' ? 'selected' : ''; ?>>Thursday</option>
                                <option value="Friday" <?php echo $day_filter === 'Friday' ? 'selected' : ''; ?>>Friday</option>
                                <option value="Saturday" <?php echo $day_filter === 'Saturday' ? 'selected' : ''; ?>>Saturday</option>
                                <option value="Sunday" <?php echo $day_filter === 'Sunday' ? 'selected' : ''; ?>>Sunday</option>
                            </select>
                        </div>
                        <div>
                            <label for="room" style="margin-right: 5px;">Room:</label>
                            <select name="room" id="room" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
                                <option value="">All Rooms</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo htmlspecialchars($room); ?>" <?php echo $room_filter === $room ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($room); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="semester" style="margin-right: 5px;">Semester:</label>
                            <select name="semester" id="semester" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
                                <option value="">All Semesters</option>
                                <?php 
                                // Reset the semesters result set
                                $semesters->data_seek(0);
                                while ($semester = $semesters->fetch_assoc()): ?>
                                    <option value="<?php echo $semester['id']; ?>" <?php echo $semester_filter == $semester['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($semester['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" style="padding: 5px 15px; background-color: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <?php if (!empty($day_filter) || !empty($room_filter) || !empty($semester_filter)): ?>
                            <a href="admin_schedule_management.php" style="padding: 5px 15px; background-color: #e74c3c; color: white; border: none; border-radius: 4px; text-decoration: none;">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Time</th>
                            <th>Room</th>
                            <th>Semester</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        foreach ($days as $day) {
                            $schedules->data_seek(0);
                            $has_schedule = false;
                            
                            while ($schedule = $schedules->fetch_assoc()) {
                                if ($schedule['day_of_week'] === $day) {
                                    $has_schedule = true;
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($day) . "</td>";
                                    echo "<td>" . htmlspecialchars($schedule['subject_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($schedule['teacher_firstname'] . ' ' . $schedule['teacher_lastname']) . "</td>";
                                    echo "<td>" . date('h:i A', strtotime($schedule['start_time'])) . " - " . 
                                         date('h:i A', strtotime($schedule['end_time'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($schedule['room']) . "</td>";
                                    echo "<td>" . htmlspecialchars($schedule['semester_name']) . "</td>";
                                    echo "<td>";
                                    echo "<form method='POST' action='' style='display: inline;'>";
                                    echo "<input type='hidden' name='action' value='delete_schedule'>";
                                    echo "<input type='hidden' name='schedule_id' value='" . $schedule['id'] . "'>";
                                    echo "<button type='submit' class='btn btn-danger'>Delete</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            }
                            
                            if (!$has_schedule) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($day) . "</td>";
                                echo "<td colspan='6' style='text-align: center; color: #7f8c8d;'>No classes scheduled</td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            const topbar = document.getElementById('topbar');
            
            sidebar.classList.toggle('shrink');
            content.classList.toggle('shrink');
            topbar.classList.toggle('shrink');
        });
    </script>
</body>
</html> 