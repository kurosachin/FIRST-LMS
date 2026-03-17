<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$studentId = $_SESSION['student_id'];
$studentName = $_SESSION['student_name'];

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "student_management_system";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get student information
$sql = "SELECT id, firstname, lastname FROM students WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

// Handle enrollment action
if (isset($_POST['enroll'])) {
    $subjectId = $_POST['subject_id'];

    // Check if student is already enrolled in this subject
    $checkSql = "SELECT id FROM enrollments WHERE student_id = ? AND subject_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $studentId, $subjectId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo "<script>alert('You are already enrolled in this subject.');</script>";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert into enrollments table
            $sql = "INSERT INTO enrollments (student_id, subject_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $studentId, $subjectId);
            $stmt->execute();
            
            // Get all active schedules for this subject with teacher student counts
            $scheduleSql = "SELECT cs.id, cs.day_of_week, cs.start_time, cs.end_time, cs.teacher_id,
                           (SELECT COUNT(*) FROM schedule_enrollments se 
                            WHERE se.schedule_id = cs.id AND se.status = 'enrolled') as student_count
                           FROM class_schedules cs 
                           JOIN semesters sem ON cs.semester_id = sem.id 
                           WHERE cs.subject_id = ? AND sem.is_active = 1
                           ORDER BY student_count ASC";
            $scheduleStmt = $conn->prepare($scheduleSql);
            $scheduleStmt->bind_param("i", $subjectId);
            $scheduleStmt->execute();
            $scheduleResult = $scheduleStmt->get_result();
            
            if ($scheduleResult->num_rows > 0) {
                // Get the schedule with the least number of students
                $schedule = $scheduleResult->fetch_assoc();
                
                // Check for schedule conflicts
                $checkConflictSql = "SELECT cs.* FROM class_schedules cs 
                                   JOIN schedule_enrollments se ON cs.id = se.schedule_id 
                                   WHERE se.student_id = ? AND se.status = 'enrolled'
                                   AND cs.day_of_week = ?
                                   AND ((cs.start_time <= ? AND cs.end_time > ?) 
                                   OR (cs.start_time < ? AND cs.end_time >= ?) 
                                   OR (cs.start_time >= ? AND cs.end_time <= ?))";
                
                $checkConflictStmt = $conn->prepare($checkConflictSql);
                $checkConflictStmt->bind_param("isssssss", 
                    $studentId, 
                    $schedule['day_of_week'],
                    $schedule['end_time'],
                    $schedule['start_time'],
                    $schedule['end_time'],
                    $schedule['start_time'],
                    $schedule['start_time'],
                    $schedule['end_time']
                );
                $checkConflictStmt->execute();
                $conflictResult = $checkConflictStmt->get_result();
                
                if ($conflictResult->num_rows > 0) {
                    throw new Exception("Schedule conflict detected! You cannot enroll in overlapping classes.");
                }
                
                // Assign student to the schedule
                $assignSql = "INSERT INTO schedule_enrollments (schedule_id, student_id, status) VALUES (?, ?, 'enrolled')";
                $assignStmt = $conn->prepare($assignSql);
                $assignStmt->bind_param("ii", $schedule['id'], $studentId);
                $assignStmt->execute();
            }

            // Initialize grades for the student in this subject
            $gradeSql = "INSERT INTO grades (student_id, subject_id, prelim, midterm, finals) VALUES (?, ?, NULL, NULL, NULL)";
            $gradeStmt = $conn->prepare($gradeSql);
            $gradeStmt->bind_param("ii", $studentId, $subjectId);
            $gradeStmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to the subjects page upon successful enrollment
            header("Location: subjects.php");
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo "<script>alert('Error enrolling in the subject: " . $e->getMessage() . "');</script>";
        }
        
        $stmt->close();
        if (isset($scheduleStmt)) $scheduleStmt->close();
        if (isset($checkConflictStmt)) $checkConflictStmt->close();
        if (isset($assignStmt)) $assignStmt->close();
        if (isset($gradeStmt)) $gradeStmt->close();
    }
    $checkStmt->close();
}

// Fetch available subjects
$subjects = [];
$sql = "SELECT * FROM subjects";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

// Fetch enrolled subjects
$enrolledSubjects = [];
$sql = "SELECT s.subject_code, s.subject_name, s.units
        FROM subjects s
        JOIN enrollments e ON s.id = e.subject_id
        WHERE e.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $enrolledSubjects[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Enrollment</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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

    /* Sidebar Styles */
    .sidebar {
      width: 250px;
      background-color: #2c3e50;
      color: #ecf0f1;
      padding-top: 1rem;
      flex-shrink: 0;
      overflow: hidden;
      transition: all 0.3s ease;
      position: fixed;
      height: 100vh;
      z-index: 1000;
    }

    /* Sidebar when shrunk */
    .sidebar.shrink {
      width: 70px;
    }

    .sidebar h2 {
      text-align: center;
      margin-bottom: 1rem;
      font-size: 1.2rem;
      padding: 0 1rem;
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
      border-radius: 4px;
      margin-bottom: 0.2rem;
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
      padding-left: 0.5rem;
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

    /* Topbar Styles */
    .topbar {
      position: fixed;
      left: 250px;
      right: 0;
      top: 0;
      background-color: #fff;
      padding: 1rem 2rem;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      z-index: 999;
      display: flex;
      align-items: center;
      gap: 1rem;
      transition: all 0.3s ease;
    }

    /* Topbar when the sidebar is shrunk */
    .topbar.shrink {
      left: 70px;
    }

    .toggle-btn {
      font-size: 1.2rem;
      cursor: pointer;
      background: none;
      border: none;
      color: #2c3e50;
      padding: 0.5rem;
      border-radius: 4px;
      transition: background-color 0.2s;
    }

    .toggle-btn:hover {
      background-color: #f4f6f8;
    }

    .logout-btn {
      margin-left: auto;
      text-decoration: none;
      color: #e74c3c;
      font-weight: bold;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      transition: background-color 0.2s;
    }

    .logout-btn:hover {
      background-color: #fde8e8;
    }

    /* Content Area Styles */
    .content {
      flex-grow: 1;
      padding: 5rem 2rem 2rem 2rem;
      transition: all 0.3s ease;
      margin-left: 250px;
    }

    /* Content area when the sidebar is shrunk */
    .content.shrink {
      margin-left: 70px;
    }

    /* Enrollment Table Styles */
    .enroll-table {
      background-color: #fff;
      border-radius: 0.5rem;
      padding: 1.5rem;
      margin-bottom: 40px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      overflow-x: auto;
    }

    .enroll-table h2 {
      color: #2c3e50;
      margin-bottom: 1.5rem;
      font-size: 1.5rem;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 1rem;
    }

    th,
    td {
      padding: 1rem;
      border-bottom: 1px solid #eee;
      text-align: left;
    }

    th {
      background: #2c3e50;
      color: white;
      font-weight: 600;
    }

    tr:nth-child(even) {
      background-color: #f9fafb;
    }

    tr:hover {
      background-color: #f4f6f8;
    }

    /* Enroll Button Style */
    .btn-enroll {
      background-color: #3498db;
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.2s;
      font-weight: 500;
    }

    .btn-enroll:hover {
      background-color: #2980b9;
    }

    /* Responsive Styles */
    @media (max-width: 1024px) {
      .content {
        padding: 5rem 1rem 1rem 1rem;
      }
      
      .enroll-table {
        padding: 1rem;
      }
      
      th, td {
        padding: 0.75rem;
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }
      
      .sidebar.active {
        transform: translateX(0);
      }
      
      .topbar {
        left: 0;
      }
      
      .content {
        margin-left: 0;
        padding-top: 4rem;
      }
      
      .content.shrink {
        margin-left: 0;
      }
      
      .enroll-table {
        margin: 0 -1rem;
        border-radius: 0;
      }
      
      table {
        font-size: 0.9rem;
      }
      
      th, td {
        padding: 0.5rem;
      }
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
    <button class="toggle-btn" onclick="toggleSidebar()">
      <i class="fas fa-bars"></i>
    </button>
    <h1>Enrollment</h1>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content" id="content">
  <div id="enrollment-content">
    <div class="enroll-table">
      <h2>Available Subjects</h2>
      <table>
        <thead>
          <tr>
            <th>Subject Code</th>
            <th>Subject Name</th>
            <th>Units</th>
            <th>Semester</th>
            <th>School Year</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($subjects as $subject): ?>
          <tr>
            <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
            <td><?php echo htmlspecialchars($subject['units']); ?></td>
            <td><?php echo htmlspecialchars($subject['semester']); ?></td>
            <td><?php echo htmlspecialchars($subject['school_year']); ?></td>
            <td>
              <form action="enrollment.php" method="POST">
                  <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                  <button type="submit" name="enroll" class="btn-enroll">Enroll</button>
              </form>
            </td>
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
</script>

</body>
</html>