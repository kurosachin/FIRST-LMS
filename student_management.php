<?php
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

$teacher_name = $_SESSION['teacher_name'];

// Include database connection
include('db_connect.php');

// Get filter values from GET parameters
$grade_level_filter = isset($_GET['grade_level']) ? $_GET['grade_level'] : '';
$program_filter = isset($_GET['program']) ? $_GET['program'] : '';
$subject_filter = isset($_GET['subject']) ? $_GET['subject'] : '';

// Fetch students enrolled in the teacher's classes
function getStudents($grade_level = '', $program = '', $subject = '') {
    global $conn;
    $teacher_id = $_SESSION['teacher_id'];

    // First check if teacher has any schedules
    $check_sql = "SELECT COUNT(*) as count FROM class_schedules WHERE teacher_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $teacher_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $schedule_count = $check_result->fetch_assoc()['count'];
    error_log("Number of schedules for teacher: " . $schedule_count);
    $check_stmt->close();

    // Check enrollments
    $enroll_check_sql = "SELECT COUNT(*) as count FROM enrollments WHERE teacher_id = ?";
    $enroll_check_stmt = $conn->prepare($enroll_check_sql);
    $enroll_check_stmt->bind_param("i", $teacher_id);
    $enroll_check_stmt->execute();
    $enroll_result = $enroll_check_stmt->get_result();
    $enrollment_count = $enroll_result->fetch_assoc()['count'];
    error_log("Number of enrollments for teacher: " . $enrollment_count);
    $enroll_check_stmt->close();

    // Base query
    $sql = "SELECT DISTINCT s.id, s.firstname, s.lastname, s.username, s.grade_level, s.program,
                   GROUP_CONCAT(DISTINCT sub.subject_code) as enrolled_subjects
            FROM students s
            INNER JOIN enrollments e ON s.id = e.student_id
            INNER JOIN subjects sub ON e.subject_id = sub.id
            WHERE (e.teacher_id = ? OR EXISTS (
                SELECT 1 FROM class_schedules cs 
                WHERE cs.subject_id = sub.id AND cs.teacher_id = ?
            ))";

    // Add filters if provided
    if (!empty($grade_level)) {
        $sql .= " AND s.grade_level = ?";
    }
    if (!empty($program)) {
        $sql .= " AND s.program = ?";
    }
    if (!empty($subject)) {
        $sql .= " AND sub.subject_code = ?";
    }

    $sql .= " GROUP BY s.id, s.firstname, s.lastname, s.username, s.grade_level, s.program";
    
    $stmt = $conn->prepare($sql);
    
    // Create parameter types and values array
    $param_types = "ii"; // First two parameters are always teacher_id (integer)
    $param_values = [$teacher_id, $teacher_id];
    
    // Add filter parameters
    if (!empty($grade_level)) {
        $param_types .= "i";
        $param_values[] = $grade_level;
    }
    if (!empty($program)) {
        $param_types .= "s";
        $param_values[] = $program;
    }
    if (!empty($subject)) {
        $param_types .= "s";
        $param_values[] = $subject;
    }
    
    // Bind parameters dynamically
    $stmt->bind_param($param_types, ...$param_values);
    
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $students[] = [
                'id' => $row['id'],
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'username' => $row['username'],
                'grade_level' => $row['grade_level'],
                'program' => $row['program'],
                'enrolled_subjects' => $row['enrolled_subjects']
            ];
        }
    }
    $stmt->close();
    return $students;
}

// Get unique values for filters
function getFilterOptions() {
    global $conn;
    $teacher_id = $_SESSION['teacher_id'];
    
    $options = [
        'grade_levels' => [],
        'programs' => [],
        'subjects' => []
    ];
    
    // Get unique grade levels from students enrolled in teacher's classes
    $grade_sql = "SELECT DISTINCT s.grade_level 
                  FROM students s
                  INNER JOIN enrollments e ON s.id = e.student_id
                  INNER JOIN subjects sub ON e.subject_id = sub.id
                  WHERE e.teacher_id = ? OR EXISTS (
                      SELECT 1 FROM class_schedules cs 
                      WHERE cs.subject_id = sub.id AND cs.teacher_id = ?
                  )
                  ORDER BY s.grade_level";
    $grade_stmt = $conn->prepare($grade_sql);
    $grade_stmt->bind_param("ii", $teacher_id, $teacher_id);
    $grade_stmt->execute();
    $grade_result = $grade_stmt->get_result();
    while ($row = $grade_result->fetch_assoc()) {
        if (!empty($row['grade_level'])) {
            $options['grade_levels'][] = $row['grade_level'];
        }
    }
    $grade_stmt->close();
    
    // Get unique programs from students enrolled in teacher's classes
    $program_sql = "SELECT DISTINCT s.program 
                   FROM students s
                   INNER JOIN enrollments e ON s.id = e.student_id
                   INNER JOIN subjects sub ON e.subject_id = sub.id
                   WHERE e.teacher_id = ? OR EXISTS (
                       SELECT 1 FROM class_schedules cs 
                       WHERE cs.subject_id = sub.id AND cs.teacher_id = ?
                   )
                   ORDER BY s.program";
    $program_stmt = $conn->prepare($program_sql);
    $program_stmt->bind_param("ii", $teacher_id, $teacher_id);
    $program_stmt->execute();
    $program_result = $program_stmt->get_result();
    while ($row = $program_result->fetch_assoc()) {
        if (!empty($row['program'])) {
            $options['programs'][] = $row['program'];
        }
    }
    $program_stmt->close();
    
    // Get unique subjects from teacher's classes
    $subject_sql = "SELECT DISTINCT sub.subject_code 
                   FROM subjects sub
                   INNER JOIN enrollments e ON sub.id = e.subject_id
                   WHERE e.teacher_id = ? OR EXISTS (
                       SELECT 1 FROM class_schedules cs 
                       WHERE cs.subject_id = sub.id AND cs.teacher_id = ?
                   )
                   ORDER BY sub.subject_code";
    $subject_stmt = $conn->prepare($subject_sql);
    $subject_stmt->bind_param("ii", $teacher_id, $teacher_id);
    $subject_stmt->execute();
    $subject_result = $subject_stmt->get_result();
    while ($row = $subject_result->fetch_assoc()) {
        if (!empty($row['subject_code'])) {
            $options['subjects'][] = $row['subject_code'];
        }
    }
    $subject_stmt->close();
    
    return $options;
}

$filter_options = getFilterOptions();
$students = getStudents($grade_level_filter, $program_filter, $subject_filter);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="js/print.js"></script>
    <style>
        /* Reset default browser styles */
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

        .sidebar li a {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
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
            transition: left 0.3s ease;
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
            font-size: 1rem;
        }

        /* Content Area Styles */
        .content {
            flex-grow: 1;
            padding: 5rem 2rem 2rem 2rem;
            transition: all 0.3s ease;
        }

        /* Content area when the sidebar is shrunk */
        .content.shrink {
            margin-left: auto;
        }

        .content h1 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        /* Student Table Styles */
        .manage-table {
            background: #fff;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        h2 { margin-bottom: 1rem; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
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

        tr:nth-child(even) { background-color: #f9fafb; }

        .action-link {
            color: #2980b9;
            text-decoration: none;
            margin-right: 10px;
        }

        .action-link:hover { text-decoration: underline; }

        .btn-edit {
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            margin-right: 5px;
        }

        .btn-edit:hover {
            background-color: #34495e;
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-delete:hover {
            background-color: #c0392b;
        }

        /* Responsive Styles for Smaller Screens (up to 768px width) */
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
                padding: 1rem;
            }
        }

        table.students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table.students-table th, table.students-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table.students-table th {
            background-color: #2c3e50;
            color: #fff;
            font-weight: bold;
        }
        table.students-table tr {
            background-color: #fff;
        }
        table.students-table tr:hover {
            background-color: #f5f5f5;
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

<!-- Topbar -->
<div class="topbar" id="topbar">
    <button class="toggle-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <h1>Student Management</h1>
    <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<!-- Content -->
<div class="content" id="content">
    <button class="print-btn" onclick="printContent('student-management-content', 'Student Management Report')">
        <i class="fas fa-print"></i> Print Report
    </button>
    <div id="student-management-content">
        <div class="manage-table">
            <h2>My Students</h2>
            <div class="filters">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label for="grade_level">Grade Level:</label>
                        <select name="grade_level" id="grade_level">
                            <option value="">All Grades</option>
                            <?php foreach ($filter_options['grade_levels'] as $grade): ?>
                                <option value="<?php echo $grade; ?>" <?php echo $grade_level_filter == $grade ? 'selected' : ''; ?>>
                                    <?php echo $grade; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="program">Program:</label>
                        <select name="program" id="program">
                            <option value="">All Programs</option>
                            <?php foreach ($filter_options['programs'] as $program): ?>
                                <option value="<?php echo $program; ?>" <?php echo $program_filter == $program ? 'selected' : ''; ?>>
                                    <?php echo $program; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="subject">Enrolled Subject:</label>
                        <select name="subject" id="subject">
                            <option value="">All Subjects</option>
                            <?php foreach ($filter_options['subjects'] as $subject): ?>
                                <option value="<?php echo $subject; ?>" <?php echo $subject_filter == $subject ? 'selected' : ''; ?>>
                                    <?php echo $subject; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="filter-btn">Apply Filters</button>
                    <a href="student_management.php" class="reset-btn">Reset</a>
                </form>
            </div>
            <table class="students-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Grade Level</th>
                        <th>Program</th>
                        <th>Enrolled Subjects</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['id']); ?></td>
                        <td><?php echo htmlspecialchars($student['lastname'] . ', ' . $student['firstname']); ?></td>
                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                        <td><?php echo htmlspecialchars($student['grade_level']); ?></td>
                        <td><?php echo htmlspecialchars($student['program']); ?></td>
                        <td><?php echo htmlspecialchars($student['enrolled_subjects']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('shrink');
        document.getElementById('topbar').classList.toggle('shrink');
        document.getElementById('content').classList.toggle('shrink');
    }

    function deleteStudent(studentId) {
        if (confirm('Are you sure you want to delete this student?')) {
            window.location.href = 'delete_student.php?id=' + studentId;
        }
    }
</script>

</body>
</html>

<?php $conn->close(); ?>
