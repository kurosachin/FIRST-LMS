<?php
$conn = new mysqli("localhost", "root", "", "student_management_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['id'])) {
    header("Location: manage_subjects.php");
    exit();
}

$id = $_GET['id'];
$subject = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_code = $_POST['subject_code'];
    $subject_name = $_POST['subject_name'];
    $program = $_POST['program'];
    $units = $_POST['units'];
    $semester = $_POST['semester'];
    $school_year = $_POST['school_year'];
    $year_level = $_POST['year_level'];

    $stmt = $conn->prepare("UPDATE subjects SET subject_code=?, subject_name=?, program=?, units=?, semester=?, school_year=?, year_level=? WHERE id=?");
    $stmt->bind_param("sssssssi", $subject_code, $subject_name, $program, $units, $semester, $school_year, $year_level, $id);
    $stmt->execute();

    header("Location: manage_subjects.php");
    exit();
} else {
    $stmt = $conn->prepare("SELECT * FROM subjects WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subject = $result->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Subject</title>
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

    .form-container {
      background: #fff;
      padding: 2rem;
      border-radius: 0.5rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      max-width: 600px;
      margin: auto;
      margin-bottom: 2rem;
    }

    .form-container h2 {
      margin-bottom: 1rem;
      color: #2c3e50;
      font-size: 1.5rem;
      border-bottom: 2px solid #ecf0f1;
      padding-bottom: 0.5rem;
    }

    .form-container .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem 2rem;
    }

    .form-container label {
      font-weight: bold;
      color: #34495e;
    }

    .form-container input,
    .form-container select {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #bdc3c7;
      border-radius: 0.375rem;
      background-color: #f9f9f9;
      color: #2c3e50;
    }

    .form-container button {
      width: 100%;
      padding: 0.75rem;
      background-color: #2c3e50;
      color: #fff;
      border: none;
      border-radius: 0.375rem;
      font-weight: bold;
      margin-top: 1rem;
      cursor: pointer;
    }

    .form-container button:hover {
      background-color: #34495e;
    }

    .back-link {
      display: inline-block;
      margin-top: 1rem;
      color: #2c3e50;
      text-decoration: none;
      font-weight: bold;
    }

    .back-link:hover {
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

      <li class="section-title">Teachers</li>
      <li><a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> <span>Manage Teachers</span></a></li>
      <li><a href="add_teacher.php"><i class="fas fa-user-plus"></i> <span>Add Teacher</span></a></li>

      <li class="section-title">Students</li>
      <li><a href="manage_students.php"><i class="fas fa-users"></i> <span>Manage Students</span></a></li>
      <li><a href="add_student.php"><i class="fas fa-user-plus"></i> <span>Add Student</span></a></li>
      <li><a href="manage_student_accounts.php"><i class="fas fa-wallet"></i> <span>Student Accounts</span></a></li>

      <li class="section-title">Subjects</li>
      <li><a href="manage_subjects.php"><i class="fas fa-book"></i> <span>Manage Subjects</span></a></li>
      <li><a href="add_subject.php"><i class="fas fa-plus-circle"></i> <span>Add Subject</span></a></li>

      <li class="section-title">Admins</li>
      <li><a href="manage_admins.php"><i class="fas fa-user-shield"></i> <span>Manage Admin</span></a></li>
      <li><a href="add_admin.php"><i class="fas fa-user-plus"></i> <span>Add Admin</span></a></li>
    </ul>
  </div>

<div class="topbar" id="topbar">
  <button class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
  <strong>Edit Subject</strong>
  <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content" id="content">
  <div class="form-container">
    <h2>Edit Subject</h2>
    <form method="post">
      <div class="form-grid">
        <div class="field">
          <label for="subject_code">Subject Code</label>
          <input type="text" id="subject_code" name="subject_code" value="<?= htmlspecialchars($subject['subject_code']) ?>" required>
        </div>
        <div class="field">
          <label for="subject_name">Subject Name</label>
          <input type="text" id="subject_name" name="subject_name" value="<?= htmlspecialchars($subject['subject_name']) ?>" required>
        </div>
        <div class="field">
          <label for="program">Program</label>
          <select id="program" name="program" required>
            <option value="">Select Program</option>
            <option value="ICT" <?= (isset($subject['program']) && $subject['program'] == 'ICT') ? 'selected' : '' ?>>ICT</option>
            <option value="HUMSS" <?= (isset($subject['program']) && $subject['program'] == 'HUMSS') ? 'selected' : '' ?>>HUMSS</option>
            <option value="STEM" <?= (isset($subject['program']) && $subject['program'] == 'STEM') ? 'selected' : '' ?>>STEM</option>
            <option value="ABM" <?= (isset($subject['program']) && $subject['program'] == 'ABM') ? 'selected' : '' ?>>ABM</option>
            <option value="GAS" <?= (isset($subject['program']) && $subject['program'] == 'GAS') ? 'selected' : '' ?>>GAS</option>
          </select>
        </div>
        <div class="field">
          <label for="units">Units</label>
          <input type="number" id="units" name="units" min="1" value="<?= isset($subject['units']) ? htmlspecialchars($subject['units']) : '' ?>" required>
        </div>
        <div class="field">
          <label for="semester">Semester</label>
          <select id="semester" name="semester" required>
            <option value="">Select Semester</option>
            <option value="1st Semester" <?= ($subject['semester'] == '1st Semester') ? 'selected' : '' ?>>1st Semester</option>
            <option value="2nd Semester" <?= ($subject['semester'] == '2nd Semester') ? 'selected' : '' ?>>2nd Semester</option>
          </select>
        </div>
        <div class="field">
          <label for="school_year">School Year</label>
          <input type="text" id="school_year" name="school_year" value="<?= htmlspecialchars($subject['school_year']) ?>" required>
        </div>
        <div class="field">
          <label for="year_level">Year Level</label>
          <select id="year_level" name="year_level" required>
            <option value="">Select Year Level</option>
            <option value="1st Year" <?= ($subject['year_level'] == '1st Year') ? 'selected' : '' ?>>1st Year</option>
            <option value="2nd Year" <?= ($subject['year_level'] == '2nd Year') ? 'selected' : '' ?>>2nd Year</option>
            <option value="3rd Year" <?= ($subject['year_level'] == '3rd Year') ? 'selected' : '' ?>>3rd Year</option>
            <option value="4th Year" <?= ($subject['year_level'] == '4th Year') ? 'selected' : '' ?>>4th Year</option>
          </select>
        </div>
      </div>
      <button type="submit">Update Subject</button>
    </form>
    <a href="manage_subjects.php" class="back-link">← Back to Manage Subjects</a>
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
