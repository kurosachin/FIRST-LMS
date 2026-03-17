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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];

    // Get the teacher assigned to this subject
    $teacher_sql = "SELECT teacher_id FROM teacher_subjects WHERE subject_id = ?";
    $teacher_stmt = $conn->prepare($teacher_sql);
    $teacher_stmt->bind_param("i", $subject_id);
    $teacher_stmt->execute();
    $teacher_result = $teacher_stmt->get_result();
    $teacher = $teacher_result->fetch_assoc();

    if ($teacher) {
        $teacher_id = $teacher['teacher_id'];
    } else {
        echo "<script>alert('No teacher assigned to this subject');</script>";
        exit();
    }

    // Check if student is already enrolled in this subject
    $check_sql = "SELECT * FROM enrollments WHERE student_id = ? AND subject_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $student_id, $subject_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Student is already enrolled in this subject');</script>";
    } else {
        $sql = "INSERT INTO enrollments (student_id, subject_id, teacher_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $student_id, $subject_id, $teacher_id);

        if ($stmt->execute()) {
            echo "<script>alert('Student enrolled successfully!'); window.location.href = 'manage_students.php';</script>";
        } else {
            echo "<script>alert('Error enrolling student');</script>";
        }
        $stmt->close();
    }
    $check_stmt->close();
    $teacher_stmt->close();
}

// Get all students
$students_sql = "SELECT id, lastname, firstname FROM students";
$students_result = $conn->query($students_sql);

// Get all subjects
$subjects_sql = "SELECT s.*, t.lastname as teacher_lastname, t.firstname as teacher_firstname 
                 FROM subjects s 
                 LEFT JOIN teacher_subjects ts ON s.id = ts.subject_id 
                 LEFT JOIN teachers t ON ts.teacher_id = t.id";
$subjects_result = $conn->query($subjects_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Student</title>
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

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #34495e;
        }

        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #bdc3c7;
            border-radius: 0.375rem;
            background-color: #f9f9f9;
            color: #2c3e50;
        }

        .form-group button {
            width: 100%;
            padding: 0.75rem;
            background-color: #2c3e50;
            color: #fff;
            border: none;
            border-radius: 0.375rem;
            font-weight: bold;
            cursor: pointer;
        }

        .form-group button:hover {
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

        .subject-info {
            margin-top: 0.5rem;
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
                margin-left: 0;
            }
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
    <h1>Enroll Student</h1>
    <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<div class="content" id="content">
    <div class="form-container">
        <h2>Enroll Student in Subject</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="student_id">Select Student</label>
                <select id="student_id" name="student_id" required>
                    <option value="">Select a student</option>
                    <?php
                    while ($student = $students_result->fetch_assoc()) {
                        echo "<option value='" . $student['id'] . "'>" . $student['lastname'] . ", " . $student['firstname'] . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="subject_id">Select Subject</label>
                <select id="subject_id" name="subject_id" required>
                    <option value="">Select a subject</option>
                    <?php
                    while ($subject = $subjects_result->fetch_assoc()) {
                        $teacher_name = $subject['teacher_lastname'] ? $subject['teacher_lastname'] . ", " . $subject['teacher_firstname'] : "No teacher assigned";
                        echo "<option value='" . $subject['id'] . "'>" . $subject['subject_code'] . " - " . $subject['subject_name'] . "</option>";
                    }
                    ?>
                </select>
                <div class="subject-info">
                    <p>Teacher: <?php echo $teacher_name; ?></p>
                </div>
            </div>
            <div class="form-group">
                <button type="submit">Enroll Student</button>
            </div>
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
<?php $conn->close(); ?> 