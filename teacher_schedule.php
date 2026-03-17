<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['teacher_id']) || !isset($_SESSION['teacher_name'])) {
    header('Location: login.php');
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];

// Get filter values from GET parameters
$day_filter = isset($_GET['day']) ? $_GET['day'] : '';
$room_filter = isset($_GET['room']) ? $_GET['room'] : '';
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '';

// Get teacher's schedules with filters
$sql = "SELECT cs.*, s.subject_name, sem.name as semester_name 
        FROM class_schedules cs 
        JOIN subjects s ON cs.subject_id = s.id 
        JOIN semesters sem ON cs.semester_id = sem.id 
        WHERE cs.teacher_id = ?";

// Add filters if provided
if (!empty($day_filter)) {
    $sql .= " AND cs.day_of_week = ?";
}
if (!empty($room_filter)) {
    $sql .= " AND cs.room = ?";
}
if (!empty($semester_filter)) {
    $sql .= " AND sem.id = ?";
}

$sql .= " ORDER BY FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), 
        cs.start_time";

$stmt = $conn->prepare($sql);

// Create parameter types and values array
$param_types = "i"; // First parameter is always teacher_id (integer)
$param_values = [$teacher_id];

// Add filter parameters
if (!empty($day_filter)) {
    $param_types .= "s";
    $param_values[] = $day_filter;
}
if (!empty($room_filter)) {
    $param_types .= "s";
    $param_values[] = $room_filter;
}
if (!empty($semester_filter)) {
    $param_types .= "i";
    $param_values[] = $semester_filter;
}

// Bind parameters dynamically
$stmt->bind_param($param_types, ...$param_values);
$stmt->execute();
$schedules = $stmt->get_result();

// Get unique values for filters
function getFilterOptions() {
    global $conn;
    $teacher_id = $_SESSION['teacher_id'];
    
    $options = [
        'days' => [],
        'rooms' => [],
        'semesters' => []
    ];
    
    // Get unique days
    $day_sql = "SELECT DISTINCT day_of_week 
                FROM class_schedules 
                WHERE teacher_id = ? 
                ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
    $day_stmt = $conn->prepare($day_sql);
    $day_stmt->bind_param("i", $teacher_id);
    $day_stmt->execute();
    $day_result = $day_stmt->get_result();
    while ($row = $day_result->fetch_assoc()) {
        $options['days'][] = $row['day_of_week'];
    }
    $day_stmt->close();
    
    // Get unique rooms
    $room_sql = "SELECT DISTINCT room 
                FROM class_schedules 
                WHERE teacher_id = ? 
                ORDER BY room";
    $room_stmt = $conn->prepare($room_sql);
    $room_stmt->bind_param("i", $teacher_id);
    $room_stmt->execute();
    $room_result = $room_stmt->get_result();
    while ($row = $room_result->fetch_assoc()) {
        $options['rooms'][] = $row['room'];
    }
    $room_stmt->close();
    
    // Get unique semesters
    $semester_sql = "SELECT DISTINCT sem.id, sem.name 
                    FROM semesters sem
                    JOIN class_schedules cs ON sem.id = cs.semester_id
                    WHERE cs.teacher_id = ?
                    ORDER BY sem.name";
    $semester_stmt = $conn->prepare($semester_sql);
    $semester_stmt->bind_param("i", $teacher_id);
    $semester_stmt->execute();
    $semester_result = $semester_stmt->get_result();
    while ($row = $semester_result->fetch_assoc()) {
        $options['semesters'][] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }
    $semester_stmt->close();
    
    return $options;
}

$filter_options = getFilterOptions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Schedule</title>
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
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .sidebar a {
            color: #ecf0f1;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
        }

        .sidebar i {
            width: 20px;
            text-align: center;
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

        @media (max-width: 768px) {
            .schedule-table {
                display: block;
                overflow-x: auto;
            }
        }

        .filters {
            background: #fff;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .filter-form {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-weight: bold;
            color: #2c3e50;
        }

        .filter-group select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 0.25rem;
            min-width: 150px;
        }

        .filter-btn, .reset-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            font-weight: bold;
        }

        .filter-btn {
            background-color: #2c3e50;
            color: white;
        }

        .reset-btn {
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            display: inline-block;
        }

        .filter-btn:hover {
            background-color: #34495e;
        }

        .reset-btn:hover {
            background-color: #c0392b;
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
 <!-- Sidebar -->
 <div class="sidebar" id="sidebar">
    <h2><i class="fas fa-chalkboard-teacher"></i> <span>Teacher Portal</span></h2>
    <ul>
      <li class="section-title">Dashboard</li> 
      <li><a href="teacher_portal.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>

      <li class="section-title">Management</li>
      <li><a href="student_management.php"><i class="fas fa-users"></i> <span>Student Management</span></a></li>

      <li class="section-title">Grades</li>
      <li><a href="gradebook.php"><i class="fas fa-book"></i> <span>Gradebook</span></a></li>

      <li class="section-title">Assignment</li>
      <li><a href="assignment_management.php"><i class="fas fa-tasks"></i> <span>Assignment Management</span></a></li>

      <li class="section-title">Schedule</li>
      <li><a href="teacher_schedule.php"><i class="fas fa-calendar-alt"></i> <span>Schedule Management</span></a></li>
    </ul>
  </div>

    <div class="topbar" id="topbar">
        <button class="toggle-btn" id="toggleSidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h3>My Schedule</h3>
    </div>

    <div class="content" id="content">
        <button class="print-btn" onclick="printContent('schedule-content', 'Teacher Schedule Report')">
            <i class="fas fa-print"></i> Print Schedule
        </button>
        <div id="schedule-content">
            <div class="container">
                <h1>My Schedule</h1>

                <!-- Display Current Schedule -->
                <div class="card">
                    <h2>Current Schedule</h2>

                    <!-- Filters -->
                    <div class="filters">
                        <form method="GET" class="filter-form">
                            <div class="filter-group">
                                <label for="day">Day:</label>
                                <select name="day" id="day">
                                    <option value="">All Days</option>
                                    <?php foreach ($filter_options['days'] as $day): ?>
                                        <option value="<?php echo $day; ?>" <?php echo $day_filter == $day ? 'selected' : ''; ?>>
                                            <?php echo $day; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="room">Room:</label>
                                <select name="room" id="room">
                                    <option value="">All Rooms</option>
                                    <?php foreach ($filter_options['rooms'] as $room): ?>
                                        <option value="<?php echo $room; ?>" <?php echo $room_filter == $room ? 'selected' : ''; ?>>
                                            <?php echo $room; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="semester">Semester:</label>
                                <select name="semester" id="semester">
                                    <option value="">All Semesters</option>
                                    <?php foreach ($filter_options['semesters'] as $semester): ?>
                                        <option value="<?php echo $semester['id']; ?>" <?php echo $semester_filter == $semester['id'] ? 'selected' : ''; ?>>
                                            <?php echo $semester['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="filter-btn">Apply Filters</button>
                            <a href="teacher_schedule.php" class="reset-btn">Reset</a>
                        </form>
                    </div>

                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Subject</th>
                                <th>Time</th>
                                <th>Room</th>
                                <th>Semester</th>
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
                                        echo "<td>" . date('h:i A', strtotime($schedule['start_time'])) . " - " . 
                                             date('h:i A', strtotime($schedule['end_time'])) . "</td>";
                                        echo "<td>" . htmlspecialchars($schedule['room']) . "</td>";
                                        echo "<td>" . htmlspecialchars($schedule['semester_name']) . "</td>";
                                        echo "</tr>";
                                    }
                                }
                                
                                if (!$has_schedule) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($day) . "</td>";
                                    echo "<td colspan='4' style='text-align: center; color: #7f8c8d;'>No classes scheduled</td>";
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
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