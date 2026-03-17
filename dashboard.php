<?php
session_start();

if (!isset($_SESSION['student_id']) || !isset($_SESSION['student_name'])) {
    header("Location: login.php");
    exit();
}

$studentName = $_SESSION['student_name'];
$student_id = $_SESSION['student_id'];

// Database connection
$conn = new mysqli("localhost", "root", "", "student_management_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch dashboard items for students
$result = $conn->query("SELECT * FROM dashboard_items WHERE target_audience IN ('all', 'students') ORDER BY created_at DESC");

// Fetch recent assignments
$assignments_sql = "SELECT a.*, s.subject_name, 
                    COALESCE(asub.status, 'pending') as submission_status
                    FROM assignments a 
                    JOIN subjects s ON a.subject_id = s.id 
                    JOIN enrollments e ON s.id = e.subject_id
                    LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ?
                    WHERE e.student_id = ?
                    ORDER BY a.due_date ASC
                    LIMIT 5";
$assignments_stmt = $conn->prepare($assignments_sql);
$assignments_stmt->bind_param("ii", $student_id, $student_id);
$assignments_stmt->execute();
$assignments_result = $assignments_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Dashboard</title>
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

    .content {
      flex-grow: 1;
      padding: 5rem 2rem 2rem 2rem;
      transition: all 0.3s ease;
    }

    .content.shrink {
      margin-left: auto;
    }

    .content h1 {
      font-size: 1.5rem;
      color: #2c3e50;
      margin-bottom: 1rem;
    }

    .dashboard-items {
      margin-top: 2rem;
    }
    
    .dashboard-item {
      background: white;
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 0.5rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .dashboard-item h3 {
      margin: 0 0 0.5rem 0;
      color: #2c3e50;
    }
    
    .dashboard-item p {
      margin: 0 0 1rem 0;
      color: #34495e;
    }
    
    .dashboard-item .meta {
      font-size: 0.9rem;
      color: #7f8c8d;
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
        padding: 1rem;
      }
    }

    .recent-assignments {
        margin-bottom: 2rem;
    }

    .assignment-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .assignment-card {
        background: white;
        padding: 1rem;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .assignment-card h3 {
        margin: 0 0 0.5rem 0;
        color: #2c3e50;
    }

    .assignment-card .subject {
        color: #7f8c8d;
        font-size: 0.9rem;
        margin: 0.25rem 0;
    }

    .assignment-card .due-date {
        color: #e74c3c;
        font-size: 0.9rem;
        margin: 0.25rem 0;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: bold;
        margin-top: 0.5rem;
    }

    .status-pending {
        background-color: #f1c40f;
        color: #fff;
    }

    .status-completed {
        background-color: #2ecc71;
        color: #fff;
    }

    .view-all {
        display: inline-block;
        margin-top: 1rem;
        color: #3498db;
        text-decoration: none;
        font-weight: bold;
    }

    .view-all:hover {
        text-decoration: underline;
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
    <h1>Dashboard</h1>
    <a href="logout.php" class="logout-btn">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>

  <div class="content" id="main-content">
    <h1>Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($studentName); ?>!</p>
    
    <div class="recent-assignments">
        <h2>Recent Assignments</h2>
        <?php if ($assignments_result->num_rows > 0): ?>
            <div class="assignment-grid">
                <?php while($assignment = $assignments_result->fetch_assoc()): ?>
                    <div class="assignment-card">
                        <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                        <p class="subject"><?php echo htmlspecialchars($assignment['subject_name']); ?></p>
                        <p class="due-date">Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></p>
                        <span class="status-badge status-<?php echo $assignment['submission_status']; ?>">
                            <?php echo ucfirst($assignment['submission_status']); ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            </div>
            <a href="student_assignments.php" class="view-all">View All Assignments</a>
        <?php else: ?>
            <p>No assignments due at the moment.</p>
        <?php endif; ?>
    </div>
    
    <div class="dashboard-items">
      <?php while($item = $result->fetch_assoc()): ?>
      <div class="dashboard-item">
        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
        <p><?php echo nl2br(htmlspecialchars($item['content'])); ?></p>
        <div class="meta">
          Posted: <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('shrink');
      document.getElementById('topbar').classList.toggle('shrink');
    }
  </script>

</body>
</html>
