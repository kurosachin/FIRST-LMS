<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'student_management_system');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    // Get and sanitize input
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $grade_level = trim($_POST['grade']); // Updated variable name
    $program = trim($_POST['program']);
    $email = trim($_POST['email']); // Add email field

    // Check if username already exists in any table
    $check_username = $conn->prepare("SELECT username FROM (
        SELECT username FROM teachers
        UNION
        SELECT username FROM students
        UNION
        SELECT username FROM admins
    ) AS all_users WHERE username = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    $result = $check_username->get_result();
    
    if ($result->num_rows > 0) {
        echo "<script>alert('Error: Username already exists in the system.'); window.history.back();</script>";
        exit();
    }
    $check_username->close();

    // Check if email already exists in any table
    $check_email = $conn->prepare("SELECT email FROM (
        SELECT email FROM teachers
        UNION
        SELECT email FROM students
        UNION
        SELECT email FROM admins
    ) AS all_users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result = $check_email->get_result();
    
    if ($result->num_rows > 0) {
        echo "<script>alert('Error: Email already exists in the system.'); window.history.back();</script>";
        exit();
    }
    $check_email->close();

    // Prepare SQL and bind parameters
    $stmt = $conn->prepare("INSERT INTO students (firstname, lastname, username, password, grade_level, program, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $firstname, $lastname, $username, $password, $grade_level, $program, $email);

    // Execute and check
    if ($stmt->execute()) {
        echo "<script>alert('Student added successfully!'); window.location.href = 'manage_students.php';</script>";
    } else {
        echo "<script>alert('Failed to add student. Please try again.');</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Add Student</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    /* Basic Reset */
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
      justify-content: flex-start;
    }

    .sidebar.shrink li {
      justify-content: center;
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
      font-size: 1rem;
    }

    .content {
      flex-grow: 1;
      padding: 5rem 2rem 2rem 2rem;
      transition: all 0.3s ease;
      margin-left: 0;
    }

    .content.shrink {
      margin-left: auto; /* adjust for sidebar shrink */
    }

    .form-container {
      background: #fff;
      padding: 2rem;
      border-radius: 0.5rem;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      max-width: 800px;
      margin: auto;
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
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-container input:focus,
    .form-container select:focus {
      border-color: #3498db;
      box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.13);
      outline: none;
    }
    .form-container select {
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 0.75rem center;
      background-size: 1rem;
      padding-right: 2.5rem;
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
  
<!-- Topbar -->
<div class="topbar" id="topbar">
  <button class="toggle-btn" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
  </button>
  <h1>Add Student</h1>
  <a href="logout.php" class="logout-btn">
    <i class="fas fa-sign-out-alt"></i> Logout
  </a>
</div>

<!-- Main Content -->
<div class="content" id="content">
  <div class="form-container">
    <h2>Add New Student</h2>
    <form method="POST" action="">
      <div class="form-grid">
        <div>
          <label for="firstname">First Name</label>
          <input type="text" id="firstname" name="firstname" required />
        </div>
        <div>
          <label for="lastname">Last Name</label>
          <input type="text" id="lastname" name="lastname" required />
        </div>
        <div>
          <label for="username">Username</label>
          <input type="text" id="username" name="username" required />
        </div>
        <div>
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required />
        </div>
        <div class="form-group">
          <label for="grade">Grade Level</label>
          <select id="grade" name="grade" required>
            <option value="">Select Grade Level</option>
            <option value="1st Year">1st Year</option>
            <option value="2nd Year">2nd Year</option>
            <option value="3rd Year">3rd Year</option>
            <option value="4th Year">4th Year</option>
          </select>
        </div>
        <div>
          <label for="program">Program</label>
          <select id="program" name="program" required>
            <option value="">Select Program</option>
            <option value="BSIT">BSIT</option>
            <option value="BSHM">BSHM</option>
            <option value="BSP">BSP</option>
            <option value="BSCRIM">BSCRIM</option>
            <option value="BLIS">BLIS</option>
          </select>
        </div>
        <div>
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required />
        </div>
      </div>
      <button type="submit">Add Student</button>
      <a href="manage_students.php" class="back-link">← Back to Manage Students</a>
    </form>
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

//add student