<?php
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

include('db_connect.php');

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$grade = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id']);
    $grade = $_POST['grade'];

    if (empty($grade)) {
        $error = "Grade is required.";
    } else {
        $sql = "UPDATE students SET grade = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $grade, $student_id);

        if ($stmt->execute()) {
            header("Location: gradebook.php");
            exit();
        } else {
            $error = "Error updating grade.";
        }
    }
} else {
    $sql = "SELECT grade FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $grade = $row['grade'];
    } else {
        $error = "Student not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Grade</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        /* Reset default browser styles */
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

        /* Content Area Styles */
        .content {
            flex-grow: 1;
            padding: 5rem 2rem 2rem 2rem;
            transition: all 0.3s ease;
            margin-left: 0;
        }

        /* Content area when the sidebar is shrunk */
        .content.shrink {
            margin-left: auto;
        }

        .content h1 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .form-container {
            background-color: #fff;
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            margin-bottom: 1rem;
        }

        .form-container form {
            display: flex;
            flex-direction: column;
        }

        .form-container label {
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .form-container input {
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 0.375rem;
        }

        .form-container button {
            padding: 0.75rem 1.5rem;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
        }

        .form-container button:hover {
            background-color: #34495e;
        }

        .error {
            color: #e74c3c;
            margin-bottom: 1rem;
        }

        /* Responsive Styles for Smaller Screens (up to 768px width) */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: fixed;
                height: 100%;
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
    <h2><i class="fas fa-chalkboard-teacher"></i> <span>Teacher Portal</span></h2>
    <ul>
      <li class="section-title">Dashboard</li>
      <li><a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>

      <li class="section-title">Schedule</li>
      <li><a href="class_schedule.php"><i class="fas fa-calendar-alt"></i> <span>Class Schedule</span></a></li>

      <li class="section-title">Management</li>
      <li><a href="student_management.php"><i class="fas fa-users"></i> <span>Student Management</span></a></li>

      <li class="section-title">Grades</li>
      <li><a href="gradebook.php"><i class="fas fa-book"></i> <span>Gradebook</span></a></li>

      <li class="section-title">Assignment</li>
      <li><a href="assignment_management.php"><i class="fas fa-tasks"></i> <span>Assignment Management</span></a></li>
    </ul>
</div>

<div class="topbar" id="topbar">
    <button class="toggle-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <h1>Edit Grade</h1>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content" id="content">
    <div class="form-container">
        <h2>Edit Grade</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
            <label for="grade">Grade</label>
            <input type="text" id="grade" name="grade" value="<?php echo htmlspecialchars($grade); ?>" required>
            <button type="submit">Update Grade</button>
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
