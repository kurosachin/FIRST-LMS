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

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Get filter values
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '';
$year_level_filter = isset($_GET['year_level']) ? $_GET['year_level'] : '';

// Build the query
$sql = "SELECT s.*, sem.name as semester_name 
        FROM subjects s 
        LEFT JOIN semesters sem ON s.semester = sem.id 
        WHERE 1=1";

if (!empty($semester_filter)) {
    $sql .= " AND s.semester = " . intval($semester_filter);
}
if (!empty($year_level_filter)) {
    $sql .= " AND s.year_level = '" . $conn->real_escape_string($year_level_filter) . "'";
}

$sql .= " ORDER BY s.subject_name";

// Fetch subjects with filters
$result = $conn->query($sql);

// Get all semesters for the filter dropdown
$semesters_result = $conn->query("SELECT * FROM semesters ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Subjects</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
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
    <h1>Manage Subjects</h1>
    <a href="logout.php" class="logout-btn">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>

<div class="content" id="content">
  <div class="manage-table">
    <h2>Manage Subjects</h2>

    <a href="add_subject.php" class="add-btn">
      <i class="fas fa-plus"></i> Add New Subject
    </a>
    <h2>Subject List</h2>

    <form method="GET" class="filter-form" style="display: flex; gap: 10px; align-items: center; margin-bottom: 20px;">
      <div>
        <label for="semester" style="margin-right: 5px;">Semester:</label>
        <select name="semester" id="semester" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
          <option value="">All Semesters</option>
          <?php while ($semester = $semesters_result->fetch_assoc()): ?>
            <option value="<?php echo $semester['id']; ?>" <?php echo $semester_filter == $semester['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($semester['name']); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div>
        <label for="year_level" style="margin-right: 5px;">Year Level:</label>
        <select name="year_level" id="year_level" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
          <option value="">All Year Levels</option>
          <option value="1st Year" <?php echo $year_level_filter === '1st Year' ? 'selected' : ''; ?>>1st Year</option>
          <option value="2nd Year" <?php echo $year_level_filter === '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
          <option value="3rd Year" <?php echo $year_level_filter === '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
          <option value="4th Year" <?php echo $year_level_filter === '4th Year' ? 'selected' : ''; ?>>4th Year</option>
        </select>
      </div>
      <button type="submit" style="padding: 5px 15px; background-color: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer;">
        <i class="fas fa-filter"></i> Filter
      </button>
      <?php if (!empty($semester_filter) || !empty($year_level_filter)): ?>
        <a href="manage_subjects.php" style="padding: 5px 15px; background-color: #e74c3c; color: white; border: none; border-radius: 4px; text-decoration: none;">
          <i class="fas fa-times"></i> Clear Filters
        </a>
      <?php endif; ?>
    </form>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Subject Code</th>
          <th>Subject Name</th>
          <th>Program</th>
          <th>Units</th>
          <th>Semester</th>
          <th>School Year</th>
          <th>Year Level</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= htmlspecialchars($row['subject_code']) ?></td>
            <td><?= htmlspecialchars($row['subject_name']) ?></td>
            <td><?= isset($row['program']) ? htmlspecialchars($row['program']) : 'Not Set' ?></td>
            <td><?= isset($row['units']) ? htmlspecialchars($row['units']) : 'Not Set' ?></td>
            <td><?= htmlspecialchars($row['semester_name']) ?></td>
            <td><?= htmlspecialchars($row['school_year']) ?></td>
            <td><?= htmlspecialchars($row['year_level']) ?></td>
            <td class="actions">
              <a class="action-link" href="edit_subject.php?id=<?= $row['id'] ?>">Edit</a>
              <a class="action-link" href="manage_subjects.php?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure?');">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
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

<?php $conn->close(); ?>
