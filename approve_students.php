<?php
session_start();

// Check if the user is an admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in as admin
    exit();
}

$admin_name = $_SESSION['admin_name'];

// Database connection
$conn = new mysqli("localhost", "root", "", "student_management_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve'])) {
        $id = $_POST['student_id'];
        $stmt = $conn->prepare("UPDATE students SET approval_status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['reject'])) {
        $id = $_POST['student_id'];
        $stmt = $conn->prepare("UPDATE students SET approval_status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch all pending student registrations
$result = $conn->query("SELECT * FROM students WHERE approval_status = 'pending' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Approve Students</title>
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

    .dashboard-content {
      padding: 2rem;
      background: white;
      border-radius: 0.375rem;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .dashboard-items {
      margin-top: 2rem;
    }

    .student-item {
      background: white;
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 0.5rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .student-item h3 {
      margin: 0 0 0.5rem 0;
      color: #2c3e50;
    }

    .student-item p {
      margin: 0 0 1rem 0;
      color: #34495e;
    }

    .student-item .actions {
      margin-top: 1rem;
    }

    .btn {
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 0.25rem;
      cursor: pointer;
      margin-right: 0.5rem;
    }

    .btn-success { background: #2ecc71; color: white; }
    .btn-danger { background: #e74c3c; color: white; }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <h2><i class="fas fa-graduation-cap"></i> <span>Admin Portal</span></h2>
    <ul>
      <li class="section-title">Dashboard</li>
      <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>

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

      <li class="section-title">Notifications</li>
      <li><a href="notification.php"><i class="fas fa-bell"></i> <span>View Notifications</span></a></li>
    </ul>
  </div>

  <!-- Topbar -->
  <div class="topbar" id="topbar">
    <button class="toggle-btn" onclick="toggleSidebar()">
      <i class="fas fa-bars"></i>
    </button>
    <h1>Approve Students</h1>
    <a href="logout.php" class="logout-btn">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>

  <!-- Content -->
  <div class="content">
    <div class="dashboard-content">
      <div class="dashboard-items">
        <h3>Pending Student Registrations</h3>

        <?php while($student = $result->fetch_assoc()): ?>
        <div class="student-item">
          <h3><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></h3>
          <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
          <p><strong>Program:</strong> <?php echo htmlspecialchars($student['program']); ?></p>
          <div class="actions">
            <form method="POST" style="display: inline;">
              <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
              <button type="submit" name="approve" class="btn btn-success" onclick="return confirm('Are you sure you want to approve this student?')">Approve</button>
              <button type="submit" name="reject" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this student?')">Reject</button>
            </form>
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
    }
  </script>

</body>
</html>
<?php $conn->close(); ?>