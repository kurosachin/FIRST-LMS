<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "student_management_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all enrollments with student and subject details
$sql = "SELECT e.*, s.lastname as student_lastname, s.firstname as student_firstname, 
        sub.subject_code, sub.subject_name, t.lastname as teacher_lastname, t.firstname as teacher_firstname
        FROM enrollments e
        INNER JOIN students s ON e.student_id = s.id
        INNER JOIN subjects sub ON e.subject_id = sub.id
        LEFT JOIN teachers t ON e.teacher_id = t.id
        ORDER BY s.lastname, s.firstname";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Enrollments</title>
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

        .sidebar a {
            color: #ecf0f1;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar a:hover {
            color: #3498db;
        }

        .topbar {
            background-color: #fff;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            z-index: 100;
            transition: all 0.3s ease;
        }

        .topbar.shrink {
            left: 70px;
        }

        .toggle-btn {
            background: none;
            border: none;
            color: #2c3e50;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .logout-btn {
            color: #e74c3c;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .content {
            flex: 1;
            padding: 2rem;
            margin-top: 60px;
            margin-left: 250px;
            transition: all 0.3s ease;
        }

        .content.shrink {
            margin-left: 70px;
        }

        .enrollments-container {
            background: #fff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .enrollments-container h2 {
            margin-bottom: 1rem;
            color: #2c3e50;
            font-size: 1.5rem;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 0.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .action-buttons {
            margin-bottom: 1rem;
        }

        .add-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: #2c3e50;
            color: #fff;
            text-decoration: none;
            border-radius: 0.375rem;
            font-weight: bold;
        }

        .add-btn:hover {
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
                margin-left: 0;
            }

            table {
                display: block;
                overflow-x: auto;
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
    </ul>
</div>

<div class="topbar" id="topbar">
    <button class="toggle-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <h1>View Enrollments</h1>
    <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<div class="content" id="content">
    <button class="print-btn" onclick="printContent('enrollments-content', 'Enrollments Report')">
        <i class="fas fa-print"></i> Print Enrollments
    </button>
    <div id="enrollments-content">
        <div class="enrollments-container">
            <h2>Student Enrollments</h2>
            <div class="action-buttons">
                <a href="enroll_student.php" class="add-btn">
                    <i class="fas fa-user-graduate"></i> Enroll New Student
                </a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Teacher</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['student_lastname'] . ", " . $row['student_firstname'] . "</td>";
                            echo "<td>" . $row['subject_code'] . "</td>";
                            echo "<td>" . $row['subject_name'] . "</td>";
                            echo "<td>" . ($row['teacher_lastname'] ? $row['teacher_lastname'] . ", " . $row['teacher_firstname'] : "No teacher assigned") . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>No enrollments found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <a href="manage_students.php" class="back-link">← Back to Manage Students</a>
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