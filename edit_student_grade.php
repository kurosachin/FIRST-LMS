<?php
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

$teacher_name = $_SESSION['teacher_name'];

// Include database connection
include('db_connect.php');

// Get student ID from URL
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $prelim = !empty($_POST['prelim']) ? $_POST['prelim'] : null;
    $midterm = !empty($_POST['midterm']) ? $_POST['midterm'] : null;
    $finals = !empty($_POST['finals']) ? $_POST['finals'] : null;

    // Ensure student is enrolled in the subject
    $enroll_check_sql = "SELECT * FROM enrollments WHERE student_id = ? AND subject_id = ?";
    $enroll_check_stmt = $conn->prepare($enroll_check_sql);
    $enroll_check_stmt->bind_param("ii", $student_id, $subject_id);
    $enroll_check_stmt->execute();
    $enroll_result = $enroll_check_stmt->get_result();

    if ($enroll_result->num_rows == 0) {
        // Not enrolled, so enroll now
        $enroll_insert_sql = "INSERT INTO enrollments (student_id, subject_id, teacher_id) VALUES (?, ?, ?)";
        $enroll_insert_stmt = $conn->prepare($enroll_insert_sql);
        $enroll_insert_stmt->bind_param("iii", $student_id, $subject_id, $_SESSION['teacher_id']);
        $enroll_insert_stmt->execute();
        $enroll_insert_stmt->close();
    }
    $enroll_check_stmt->close();

    // Add error logging
    error_log("Attempting to save grades - Student ID: $student_id, Subject ID: $subject_id");
    error_log("Grades - Prelim: $prelim, Midterm: $midterm, Finals: $finals");

    // Check if grade record exists
    $check_sql = "SELECT id FROM grades WHERE student_id = ? AND subject_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $student_id, $subject_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing record
        $update_sql = "UPDATE grades SET prelim = ?, midterm = ?, finals = ? WHERE student_id = ? AND subject_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ddiii", $prelim, $midterm, $finals, $student_id, $subject_id);
    } else {
        // Insert new record
        $insert_sql = "INSERT INTO grades (student_id, subject_id, prelim, midterm, finals) VALUES (?, ?, ?, ?, ?)";
        $update_stmt = $conn->prepare($insert_sql);
        $update_stmt->bind_param("iiddd", $student_id, $subject_id, $prelim, $midterm, $finals);
    }

    if ($update_stmt->execute()) {
        error_log("Grades saved successfully");
        echo "<script>alert('Grades updated successfully!'); window.location.href='gradebook.php';</script>";
        exit();
    } else {
        error_log("Error saving grades: " . $update_stmt->error);
        echo "<script>alert('Error updating grades: " . $update_stmt->error . "');</script>";
    }

    $update_stmt->close();
}

// Fetch student information and their enrolled subject
function getStudent($student_id) {
    global $conn;
    $teacher_id = $_SESSION['teacher_id'];
    $sql = "SELECT s.id, s.firstname, s.lastname, e.subject_id, sub.subject_name 
            FROM students s 
            INNER JOIN enrollments e ON s.id = e.student_id
            INNER JOIN subjects sub ON e.subject_id = sub.id
            INNER JOIN teacher_subjects ts ON sub.id = ts.subject_id
            WHERE s.id = ? AND ts.teacher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $student_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Fetch current grades
function getCurrentGrades($student_id, $subject_id) {
    global $conn;
    $sql = "SELECT prelim, midterm, finals FROM grades WHERE student_id = ? AND subject_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $student_id, $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

$student = getStudent($student_id);
$current_grades = null;

if ($student) {
    $current_grades = getCurrentGrades($student_id, $student['subject_id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student Grades</title>
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
        .form-container {
            background: #fff;
            padding: 1.5rem 1.5rem 1.5rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 400px;
            margin: 2rem auto 0 auto;
        }
        .form-container h2 {
            margin-bottom: 1rem;
            color: #2c3e50;
            font-size: 1.4rem;
            font-weight: bold;
            border-bottom: 1px solid #ecf0f1;
            padding-bottom: 0.5rem;
            text-align: center;
        }
        .student-info {
            background-color: #f8f9fa;
            padding: 0.8rem 1rem;
            border-radius: 0.4rem;
            margin-bottom: 1.2rem;
            border: 1px solid #e9ecef;
            font-size: 0.9rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .student-info strong {
            color: #34495e;
            font-weight: bold;
            margin-right: 0.3rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.4rem;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.9rem;
        }
        select, input[type="number"], input[type="text"] {
            width: 100%;
            padding: 0.5rem 0.7rem;
            border: 1px solid #bdc3c7;
            border-radius: 0.3rem;
            font-size: 0.9rem;
            color: #2c3e50;
            background-color: #fff;
            transition: all 0.2s ease;
        }
        select:focus, input[type="number"]:focus, input[type="text"]:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.1);
        }
        input[type="text"]:read-only {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.8rem;
            margin-bottom: 1.2rem;
        }
        .form-container button {
            width: 100%;
            padding: 0.6rem;
            background-color: #2c3e50;
            color: #fff;
            border: none;
            border-radius: 0.3rem;
            font-weight: bold;
            font-size: 0.9rem;
            margin-top: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .form-container button:hover {
            background-color: #34495e;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .back-btn {
            display: inline-block;
            margin-top: 1rem;
            color: #2c3e50;
            background: #f4f6f8;
            border: 1px solid #bdc3c7;
            border-radius: 0.3rem;
            padding: 0.5rem 1rem;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            text-align: center;
            width: 100%;
        }
        .back-btn:hover {
            background: #e9ecef;
            color: #222;
            border-color: #888;
            transform: translateY(-1px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
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
                margin-top: 150px;
                padding: 0.8rem;
            }
            .form-container {
                width: 95%;
                padding: 1rem;
                margin-top: 1.5rem;
            }
            .form-grid {
                grid-template-columns: 1fr;
                gap: 0.8rem;
            }
            .student-info {
                padding: 0.7rem;
                font-size: 0.85rem;
            }
            .form-container button, .back-btn {
                padding: 0.6rem;
            }
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
    <!-- Topbar -->
    <div class="topbar" id="topbar">
        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1>Edit Student Grades</h1>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    <!-- Content -->
    <div class="content" id="content">
        <div class="form-container">
            <h2>Edit Student Grades</h2>
            <?php if ($student): ?>
            <div class="student-info">
                <strong>Student Name:</strong> <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>
            </div>
            <form method="POST">
                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['id']); ?>">
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" value="<?php echo htmlspecialchars($student['subject_name']); ?>" readonly>
                    <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($student['subject_id']); ?>">
                </div>
                <div class="form-group">
                    <label for="prelim">Prelim Grade</label>
                    <input type="number" id="prelim" name="prelim" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($current_grades['prelim'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="midterm">Midterm Grade</label>
                    <input type="number" id="midterm" name="midterm" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($current_grades['midterm'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="finals">Finals Grade</label>
                    <input type="number" id="finals" name="finals" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($current_grades['finals'] ?? ''); ?>">
                </div>
                <button type="submit">Update Grades</button>
            </form>
            <?php else: ?>
            <p>Student not found.</p>
            <?php endif; ?>
            <a href="gradebook.php" class="back-btn">Back to Gradebook</a>
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