<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'];

$conn = new mysqli("localhost", "root", "", "student_management_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get filter values from GET parameters
$program_filter = isset($_GET['program']) ? $_GET['program'] : '';
$grade_filter = isset($_GET['grade']) ? $_GET['grade'] : '';

// Build the SQL query with filters
$sql = "SELECT id, lastname, firstname, email, phone, username, program, grade_level FROM teachers WHERE 1=1";
if (!empty($program_filter)) {
    $sql .= " AND program = '" . $conn->real_escape_string($program_filter) . "'";
}
if (!empty($grade_filter)) {
    $sql .= " AND grade_level = '" . $conn->real_escape_string($grade_filter) . "'";
}
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Teachers</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
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

    .manage-table {
      background: #fff;
      border-radius: 0.5rem;
      padding: 1rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      overflow-x: auto;
    }

    h2 {
      margin-bottom: 1rem;
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

    tr:nth-child(even) {
      background-color: #f9fafb;
    }

    .add-btn {
      display: inline-block;
      margin-bottom: 1rem;
      background-color: #2c3e50;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 0.375rem;
      text-decoration: none;
      font-weight: bold;
    }

    .add-btn:hover {
      background-color: #34495e;
    }

    a.action-link {
      color: #2980b9;
      text-decoration: none;
      margin-right: 10px;
    }

    a.action-link:hover {
      text-decoration: underline;
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
  <button class="toggle-btn" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
  </button>
  <h1>Manage Teachers</h1>
  <a href="logout.php" class="logout-btn">
    <i class="fas fa-sign-out-alt"></i> Logout
  </a>
</div>

<div class="content" id="content">
  <button class="print-btn" onclick="printContent('teachers-content', 'Teachers Management Report')">
    <i class="fas fa-print"></i> Print Report
  </button>
  <div id="teachers-content">
    <div class="manage-table">
      <h2>Teacher List</h2>
      <div class="action-buttons">
        <a href="add_teacher.php" class="add-btn">
          <i class="fas fa-plus"></i> Add New Teacher
        </a>
        <a href="admin_schedule_management.php" class="add-btn" style="background-color: #27ae60;">
          <i class="fas fa-book"></i> Assign Schedule
        </a>
      </div>

      <!-- Filter Form -->
      <form method="GET" class="filter-form" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
        <div>
          <label for="program" style="margin-right: 5px;">Program:</label>
          <select name="program" id="program" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
            <option value="">All Programs</option>
            <option value="BSIT" <?php echo $program_filter === 'BSIT' ? 'selected' : ''; ?>>BSIT</option>
            <option value="BSHM" <?php echo $program_filter === 'BSHM' ? 'selected' : ''; ?>>BSHM</option>
            <option value="BSP" <?php echo $program_filter === 'BSP' ? 'selected' : ''; ?>>BSP</option>
            <option value="BSCRIM" <?php echo $program_filter === 'BSCRIM' ? 'selected' : ''; ?>>BSCRIM</option>
            <option value="BLIS" <?php echo $program_filter === 'BLIS' ? 'selected' : ''; ?>>BLIS</option>
          </select>
        </div>
        <div>
          <label for="grade" style="margin-right: 5px;">Grade Level:</label>
          <select name="grade" id="grade" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
            <option value="">All Grades</option>
            <option value="1st Year" <?php echo $grade_filter === '1st Year' ? 'selected' : ''; ?>>1st Year</option>
            <option value="2nd Year" <?php echo $grade_filter === '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
            <option value="3rd Year" <?php echo $grade_filter === '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
            <option value="4th Year" <?php echo $grade_filter === '4th Year' ? 'selected' : ''; ?>>4th Year</option>
          </select>
        </div>
        <button type="submit" style="padding: 5px 15px; background-color: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer;">
          <i class="fas fa-filter"></i> Filter
        </button>
        <?php if (!empty($program_filter) || !empty($grade_filter)): ?>
          <a href="manage_teachers.php" style="padding: 5px 15px; background-color: #e74c3c; color: white; border: none; border-radius: 4px; text-decoration: none;">
            <i class="fas fa-times"></i> Clear Filters
          </a>
        <?php endif; ?>
      </form>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Last Name</th>
            <th>First Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Username</th>
            <th>Program</th>
            <th>Grade Level</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              echo "<tr>";
              echo "<td>" . $row["id"] . "</td>";
              echo "<td>" . $row["lastname"] . "</td>";
              echo "<td>" . $row["firstname"] . "</td>";
              echo "<td>" . $row["email"] . "</td>";
              echo "<td>" . $row["phone"] . "</td>";
              echo "<td>" . $row["username"] . "</td>";
              echo "<td>" . $row["program"] . "</td>";
              echo "<td>" . $row["grade_level"] . "</td>";
              echo "<td>
                      <a href='edit_teacher.php?id=" . $row["id"] . "' class='action-link'>Edit</a>
                      <a href='delete_teacher.php?id=" . $row["id"] . "' class='action-link' onclick='return confirm(\"Are you sure you want to delete this teacher?\")'>Delete</a>
                    </td>";
              echo "</tr>";
            }
          } else {
            echo "<tr><td colspan='10'>No teachers found</td></tr>";
          }
          ?>
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