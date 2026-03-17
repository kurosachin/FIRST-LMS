<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$studentId = $_SESSION['student_id'];

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "student_management_system";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get student name
$sql = "SELECT firstname FROM students WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    // If no student found, redirect to login
    header("Location: login.php");
    exit();
}

$studentName = $student['firstname'];
$stmt->close();

// Fetch subjects
$subjects = [];

// Check if enrollments table exists
$table_check = $conn->query("SHOW TABLES LIKE 'enrollments'");
if ($table_check->num_rows > 0) {
    $sql = "SELECT s.subject_code, s.subject_name, s.semester, s.year_level as school_year 
            FROM subjects s
            JOIN enrollments e ON s.id = e.subject_id
            WHERE e.student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    
    $stmt->close();
} else {
    // Table doesn't exist yet, show a message
    echo "<div class='alert alert-warning'>The subjects system is being set up. Please check back later.</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Subjects</title>
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

    .subjects-table {
      background: #fff;
      border-radius: 0.5rem;
      padding: 1rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      overflow-x: auto;
    }

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
    <h1>Subjects</h1>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="content" id="content">
    <div class="subjects-table">
      <h2>Enrolled Subjects</h2>
      <table>
        <thead>
          <tr>
            <th>Subject Code</th>
            <th>Subject Name</th>
            <th>Semester</th>
            <th>Year Level</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($subjects) > 0): ?>
            <?php foreach ($subjects as $row): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                <td><?php echo htmlspecialchars($row['semester']); ?></td>
                <td><?php echo htmlspecialchars($row['school_year']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="4" style="text-align:center;">No subjects found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
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
