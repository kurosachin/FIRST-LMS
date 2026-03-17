<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

$teacher_name = $_SESSION['teacher_name'];

// Database connection
$conn = new mysqli("localhost", "root", "", "student_management_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch dashboard items for teachers
$result = $conn->query("SELECT * FROM dashboard_items WHERE target_audience IN ('all', 'teachers') ORDER BY created_at DESC");

// Fetch assigned subjects
$teacher_id = $_SESSION['teacher_id'];
$subjects_sql = "SELECT DISTINCT s.* FROM subjects s 
                 INNER JOIN class_schedules cs ON s.id = cs.subject_id 
                 WHERE cs.teacher_id = ?";
$subjects_stmt = $conn->prepare($subjects_sql);
$subjects_stmt->bind_param("i", $teacher_id);
$subjects_stmt->execute();
$subjects_result = $subjects_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Portal</title>
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

    .assigned-subjects {
      margin: 2rem 0;
      padding: 1rem;
      background: #fff;
      border-radius: 0.5rem;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .assigned-subjects h3 {
      color: #2c3e50;
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid #ecf0f1;
    }

    .subjects-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 1rem;
    }

    .subject-card {
      background: #f8f9fa;
      padding: 1rem;
      border-radius: 0.375rem;
      border: 1px solid #e9ecef;
    }

    .subject-card h4 {
      color: #2c3e50;
      margin-bottom: 0.5rem;
    }

    .subject-card p {
      color: #6c757d;
      margin: 0.25rem 0;
      font-size: 0.9rem;
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
    <h1>Teacher Dashboard</h1>
    <a href="logout.php" class="logout-btn">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>

  <!-- Content -->
  <div class="content" id="content">
    <div class="dashboard-content">
      <h2>Welcome, <?php echo htmlspecialchars($teacher_name); ?>!</h2>
      
      <div class="assigned-subjects">
        <h3>Your Assigned Subjects</h3>
        <div class="subjects-grid">
          <?php
          if ($subjects_result->num_rows > 0) {
            while($subject = $subjects_result->fetch_assoc()) {
              echo "<div class='subject-card'>";
              echo "<h4>" . htmlspecialchars($subject['subject_code']) . "</h4>";
              echo "<p>" . htmlspecialchars($subject['subject_name']) . "</p>";
              echo "<p>Units: " . htmlspecialchars($subject['units']) . "</p>";
              echo "<p>Semester: " . htmlspecialchars($subject['semester']) . "</p>";
              echo "<p>Year Level: " . htmlspecialchars($subject['year_level']) . "</p>";
              echo "</div>";
            }
          } else {
            echo "<p>No subjects assigned yet.</p>";
          }
          ?>
        </div>
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
<?php $conn->close(); ?>
