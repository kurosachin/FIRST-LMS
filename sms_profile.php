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

$sql = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
} else {
    echo "<script>alert('Student not found.');</script>";
    exit();
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SMS Profile</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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
      margin-left: 0; /* adjust for sidebar shrink */
    }

    .profile-box {
      background: #fff;
      padding: 2rem;
      border-radius: 0.5rem;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      max-width: 800px;
      margin: auto;
    }

    .profile-box h2 {
      margin-bottom: 1rem;
      color: #2c3e50;
      font-size: 1.5rem;
      border-bottom: 2px solid #ecf0f1;
      padding-bottom: 0.5rem;
    }

    .profile-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem 2rem;
    }

    .profile-grid label {
      font-weight: bold;
      color: #34495e;
    }

    .profile-grid .field {
      margin-bottom: 1rem;
    }

    .profile-grid input {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #bdc3c7;
      border-radius: 0.375rem;
      background-color: #f9f9f9;
      color: #2c3e50;
    }

    .profile-grid select {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #bdc3c7;
      border-radius: 0.375rem;
      background-color: #f9f9f9;
      color: #2c3e50;
    }

    .save-button-container {
      margin-top: 2rem;
      text-align: center;
    }

    .save-button {
      background-color: #2ecc71;
      color: white;
      padding: 0.75rem 2rem;
      border: none;
      border-radius: 0.375rem;
      font-size: 1rem;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    .save-button:hover {
      background-color: #27ae60;
    }

    .button-container {
      margin-top: 2rem;
      text-align: center;
      display: flex;
      gap: 1rem;
      justify-content: center;
    }

    .edit-button {
      background-color: #3498db;
      color: white;
      padding: 0.75rem 2rem;
      border: none;
      border-radius: 0.375rem;
      font-size: 1rem;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    .edit-button:hover {
      background-color: #2980b9;
    }

    .cancel-button {
      background-color: #e74c3c;
      color: white;
      padding: 0.75rem 2rem;
      border: none;
      border-radius: 0.375rem;
      font-size: 1rem;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    .cancel-button:hover {
      background-color: #c0392b;
    }

    .alert {
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 0.375rem;
      font-weight: bold;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    input[readonly] {
      background-color: #f9f9f9;
      border: 1px solid #dfe6e9;
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
    <h1>Profile</h1>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="content" id="main-content">
    <button class="print-btn" onclick="printContent('profile-content', 'Student Profile Report')">
        <i class="fas fa-print"></i> Print Profile
    </button>
    <div id="profile-content">
      <div class="profile-box">
        <h2>Student Profile</h2>
        <?php if (isset($_SESSION['success_message'])): ?>
          <div class="alert alert-success">
            <?php 
              echo $_SESSION['success_message'];
              unset($_SESSION['success_message']);
            ?>
          </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
          <div class="alert alert-error">
            <?php 
              echo $_SESSION['error_message'];
              unset($_SESSION['error_message']);
            ?>
          </div>
        <?php endif; ?>
        <form action="update_profile.php" method="POST" id="profileForm">
          <div class="profile-grid">
            <div class="field">
              <label>Full Name</label>
              <input type="text" name="fullname" value="<?php echo htmlspecialchars($student['firstname'] . ' ' . $student['middlename'] . ' ' . $student['lastname']); ?>" readonly>
            </div>
            <div class="field">
              <label>Date of Birth</label>
              <input type="date" name="dob" value="<?php echo htmlspecialchars($student['dob']); ?>" readonly>
            </div>
            <div class="field">
              <label>Gender</label>
              <select name="gender" disabled>
                <option value="Male" <?php echo ($student['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo ($student['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
              </select>
            </div>
            <div class="field">
              <label>Email</label>
              <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" readonly>
            </div>
            <div class="field">
              <label>Phone</label>
              <input type="tel" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" pattern="[0-9]{11}" maxlength="11" readonly oninput="validatePhone(this)">
            </div>
            <div class="field">
              <label>Address</label>
              <input type="text" name="address" value="<?php echo htmlspecialchars($student['address']); ?>" readonly>
            </div>
            <div class="field">
              <label>Grade Level</label>
              <select name="grade_level" disabled>
                <option value="1st Year" <?php echo ($student['grade_level'] == '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                <option value="2nd Year" <?php echo ($student['grade_level'] == '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                <option value="3rd Year" <?php echo ($student['grade_level'] == '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                <option value="4th Year" <?php echo ($student['grade_level'] == '4th Year') ? 'selected' : ''; ?>>4th Year</option>
              </select>
            </div>
            <div class="field">
              <label>Program</label>
              <select name="program" disabled>
                <option value="BSIT" <?php echo ($student['program'] == 'BSIT') ? 'selected' : ''; ?>>BSIT</option>
                <option value="BSHM" <?php echo ($student['program'] == 'BSHM') ? 'selected' : ''; ?>>BSHM</option>
                <option value="BSP" <?php echo ($student['program'] == 'BSP') ? 'selected' : ''; ?>>BSP</option>
                <option value="BSCRIM" <?php echo ($student['program'] == 'BSCRIM') ? 'selected' : ''; ?>>BSCRIM</option>
                <option value="BLIS" <?php echo ($student['program'] == 'BLIS') ? 'selected' : ''; ?>>BLIS</option>
              </select>
            </div>
            <div class="field">
              <label>Previous School</label>
              <input type="text" name="previous_school" value="<?php echo htmlspecialchars($student['previous_school']); ?>" readonly>
            </div>
            <div class="field">
              <label>Enrollment Date</label>
              <input type="date" name="enrollment_date" value="<?php echo htmlspecialchars($student['enrollment_date']); ?>" readonly>
            </div>
            <div class="field">
              <label>Student Type</label>
              <input type="text" name="student_type" value="<?php echo htmlspecialchars($student['student_type']); ?>" readonly>
            </div>
            <div class="field">
              <label>Parent's Name</label>
              <input type="text" name="parent_name" value="<?php echo htmlspecialchars($student['parent_name']); ?>" readonly>
            </div>
            <div class="field">
              <label>Relationship</label>
              <input type="text" name="parent_relationship" value="<?php echo htmlspecialchars($student['parent_relationship']); ?>" readonly>
            </div>
            <div class="field">
              <label>Parent's Phone</label>
              <input type="tel" name="parent_phone" value="<?php echo htmlspecialchars($student['parent_phone']); ?>" pattern="[0-9]{11}" maxlength="11" readonly oninput="validatePhone(this)">
            </div>
          </div>
          <div class="button-container">
            <button type="button" id="editButton" class="edit-button">Edit Profile</button>
            <button type="submit" id="saveButton" class="save-button" style="display: none;">Save Changes</button>
            <button type="button" id="cancelButton" class="cancel-button" style="display: none;">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const topbar = document.getElementById('topbar');
      const content = document.getElementById('main-content');

      sidebar.classList.toggle('shrink');
      topbar.classList.toggle('shrink');
      content.classList.toggle('shrink');
    }

    document.addEventListener('DOMContentLoaded', function() {
      const editButton = document.getElementById('editButton');
      const saveButton = document.getElementById('saveButton');
      const cancelButton = document.getElementById('cancelButton');
      const form = document.getElementById('profileForm');
      const inputs = form.querySelectorAll('input, select');
      let originalValues = {};

      // Store original values when edit is clicked
      function storeOriginalValues() {
        inputs.forEach(input => {
          originalValues[input.name] = input.value;
        });
      }

      // Restore original values when cancel is clicked
      function restoreOriginalValues() {
        inputs.forEach(input => {
          input.value = originalValues[input.name];
        });
      }

      editButton.addEventListener('click', function() {
        inputs.forEach(input => {
          input.removeAttribute('readonly');
          if (input.tagName === 'SELECT') {
            input.removeAttribute('disabled');
          }
        });
        editButton.style.display = 'none';
        saveButton.style.display = 'inline-block';
        cancelButton.style.display = 'inline-block';
        storeOriginalValues();
      });

      cancelButton.addEventListener('click', function() {
        inputs.forEach(input => {
          input.setAttribute('readonly', 'readonly');
          if (input.tagName === 'SELECT') {
            input.setAttribute('disabled', 'disabled');
          }
        });
        editButton.style.display = 'inline-block';
        saveButton.style.display = 'none';
        cancelButton.style.display = 'none';
        restoreOriginalValues();
      });
    });

    function validatePhone(input) {
        // Remove any non-digit characters
        input.value = input.value.replace(/\D/g, '');
        
        // Ensure exactly 11 digits
        if (input.value.length > 11) {
            input.value = input.value.slice(0, 11);
        }
    }
  </script>
</body>
</html>
