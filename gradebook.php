<?php
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

$teacher_name = $_SESSION['teacher_name'];

// Include database connection
include('db_connect.php');

// Handle grade updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $prelim = $_POST['prelim'];
    $midterm = $_POST['midterm'];
    $finals = $_POST['finals'];

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

    // Check if grade record exists
    $check_sql = "SELECT id FROM grades WHERE student_id = ? AND subject_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $student_id, $subject_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update only non-empty fields
        $fields = [];
        $params = [];
        $types = '';
        if ($prelim !== '' && $prelim !== null) {
            $fields[] = 'prelim = ?';
            $params[] = $prelim;
            $types .= 'd';
        }
        if ($midterm !== '' && $midterm !== null) {
            $fields[] = 'midterm = ?';
            $params[] = $midterm;
            $types .= 'd';
        }
        if ($finals !== '' && $finals !== null) {
            $fields[] = 'finals = ?';
            $params[] = $finals;
            $types .= 'd';
        }
        if (count($fields) > 0) {
            $update_sql = "UPDATE grades SET " . implode(", ", $fields) . " WHERE student_id = ? AND subject_id = ?";
            $params[] = $student_id;
            $params[] = $subject_id;
            $types .= 'ii';
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param($types, ...$params);
            if ($update_stmt->execute()) {
                echo "<script>alert('Grades updated successfully!'); window.location.href='gradebook.php';</script>";
                exit();
            } else {
                echo "<script>alert('Error updating grades');</script>";
            }
            $update_stmt->close();
        } else {
            // No fields to update
            echo "<script>alert('No grades entered.'); window.location.href='gradebook.php';</script>";
            exit();
        }
    } else {
        // Insert new record (set only the fields provided, others as NULL)
        $insert_sql = "INSERT INTO grades (student_id, subject_id, prelim, midterm, finals) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $prelim_val = ($prelim !== '' && $prelim !== null) ? $prelim : null;
        $midterm_val = ($midterm !== '' && $midterm !== null) ? $midterm : null;
        $finals_val = ($finals !== '' && $finals !== null) ? $finals : null;
        $insert_stmt->bind_param("iidd d", $student_id, $subject_id, $prelim_val, $midterm_val, $finals_val);
        if ($insert_stmt->execute()) {
            echo "<script>alert('Grades updated successfully!'); window.location.href='gradebook.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error updating grades');</script>";
        }
        $insert_stmt->close();
    }
}

// Fetch students and their grades
function getStudents($selected_subject = null) {
    global $conn;
    $teacher_id = $_SESSION['teacher_id'];
    
    // First check if teacher has any schedules
    $check_sql = "SELECT COUNT(*) as count FROM class_schedules WHERE teacher_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $teacher_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $schedule_count = $check_result->fetch_assoc()['count'];
    error_log("Number of schedules for teacher: " . $schedule_count);
    $check_stmt->close();

    // Check enrollments
    $enroll_check_sql = "SELECT COUNT(*) as count FROM enrollments WHERE teacher_id = ?";
    $enroll_check_stmt = $conn->prepare($enroll_check_sql);
    $enroll_check_stmt->bind_param("i", $teacher_id);
    $enroll_check_stmt->execute();
    $enroll_result = $enroll_check_stmt->get_result();
    $enrollment_count = $enroll_result->fetch_assoc()['count'];
    error_log("Number of enrollments for teacher: " . $enrollment_count);
    $enroll_check_stmt->close();

    // Query to fetch students enrolled in teacher's subjects using both class_schedules and enrollments
    $sql = "SELECT DISTINCT s.id, s.firstname, s.lastname, s.username, s.grade_level, s.program,
                   GROUP_CONCAT(DISTINCT sub.subject_code) as enrolled_subjects,
                   g.prelim, g.midterm, g.finals, g.subject_id, sub.id as subject_id, sub.subject_name
            FROM students s
            INNER JOIN enrollments e ON s.id = e.student_id
            INNER JOIN subjects sub ON e.subject_id = sub.id
            LEFT JOIN grades g ON s.id = g.student_id AND sub.id = g.subject_id
            WHERE (e.teacher_id = ? OR EXISTS (
                SELECT 1 FROM class_schedules cs 
                WHERE cs.subject_id = sub.id AND cs.teacher_id = ?
            ))";
    
    if ($selected_subject !== null) {
        $sql .= " AND sub.id = ?";
    }
    
    $sql .= " GROUP BY s.id, g.prelim, g.midterm, g.finals, g.subject_id, sub.id, sub.subject_name";
    
    // Debug output
    error_log("Teacher ID: " . $teacher_id);
    error_log("SQL Query: " . $sql);
    
    $stmt = $conn->prepare($sql);
    if ($selected_subject !== null) {
        $stmt->bind_param("iii", $teacher_id, $teacher_id, $selected_subject);
    } else {
        $stmt->bind_param("ii", $teacher_id, $teacher_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Debug output
    error_log("Number of students found: " . $result->num_rows);

    $students = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
    return $students;
}

// Fetch subjects
function getSubjects() {
    global $conn;
    $teacher_id = $_SESSION['teacher_id'];
    $sql = "SELECT DISTINCT s.id, s.subject_code, s.subject_name 
            FROM subjects s
            INNER JOIN class_schedules cs ON s.id = cs.subject_id
            WHERE cs.teacher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subjects = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
    }
    return $subjects;
}

$selected_subject = isset($_GET['subject']) ? intval($_GET['subject']) : null;
$students = getStudents($selected_subject);
$subjects = getSubjects();
$edit_student = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    foreach ($students as $s) {
        if ($s['id'] == $edit_id) {
            $edit_student = $s;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gradebook</title>
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

        /* Student Table Styles */
        .student-table {
            background-color: #fff;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 40px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
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

        .grade-display {
            display: inline-block;
            min-width: 60px;
            padding: 4px 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
            text-align: center;
        }

        .btn-edit {
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-edit:hover {
            background-color: #34495e;
        }

        /* Card-style Edit Form */
        .form-container { background: #fff; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 2px 6px rgba(0,0,0,0.1); max-width: 600px; margin: 3rem auto 0 auto; }
        .form-container h2 { margin-bottom: 1rem; color: #2c3e50; font-size: 1.5rem; border-bottom: 2px solid #ecf0f1; padding-bottom: 0.5rem; }
        .student-info { background-color: #f8f9fa; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem; }
        .student-info p { margin: 0.5rem 0; color: #2c3e50; }
        .student-info strong { color: #34495e; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem 2rem; }
        .form-group label { font-weight: bold; color: #34495e; }
        .form-group input { width: 100%; padding: 0.5rem; border: 1px solid #bdc3c7; border-radius: 0.375rem; background-color: #f9f9f9; color: #2c3e50; }
        .form-container button { width: 100%; padding: 0.75rem; background-color: #2c3e50; color: #fff; border: none; border-radius: 0.375rem; font-weight: bold; margin-top: 1rem; cursor: pointer; }
        .form-container button:hover { background-color: #34495e; }

        /* Responsive Styles for Smaller Screens (up to 768px width) */
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

            .form-container { width: 95%; }
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            position: relative;
        }
        .close {
            color: #aaa;
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: #e74c3c;
            text-decoration: none;
            cursor: pointer;
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

      <li class="section-title">Management</li>
      <li><a href="student_management.php"><i class="fas fa-users"></i> <span>Student Management</span></a></li>

      <li class="section-title">Grades</li>
      <li><a href="gradebook.php"><i class="fas fa-book"></i> <span>Gradebook</span></a></li>

      <li class="section-title">Assignment</li>
      <li><a href="assignment_management.php"><i class="fas fa-tasks"></i> <span>Assignment Management</span></a></li>
   
      <li class="section-title">Schedule</li>
      <li><a href="teacher_schedule.php"><i class="fas fa-calendar-alt"></i> <span>Schedule Management</span></a></li>
    </ul>
</div>

<!-- Topbar -->
<div class="topbar" id="topbar">
    <button class="toggle-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <h1>Gradebook</h1>
    <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<!-- Content -->
<div class="content" id="content">
    <div id="gradebook-content">
        <div class="student-table">
            <h2>Student Grades</h2>
            <div style="margin-bottom: 1rem;">
                <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                    <label for="subject_filter" style="font-weight: bold;">Filter by Subject:</label>
                    <select name="subject" id="subject_filter" style="padding: 0.5rem; border: 1px solid #bdc3c7; border-radius: 0.375rem; min-width: 200px;">
                        <option value="">All Subjects</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>" <?php echo ($selected_subject == $subject['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" style="padding: 0.5rem 1rem; background-color: #2c3e50; color: white; border: none; border-radius: 0.375rem; cursor: pointer;">Filter</button>
                </form>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Subject</th>
                        <th>Prelim</th>
                        <th>Midterm</th>
                        <th>Finals</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></td>
                        <td>
                            <?php 
                            $subject_name = 'N/A';
                            foreach ($subjects as $subject) {
                                if ($subject['id'] == $student['subject_id']) {
                                    $subject_name = $subject['subject_name'];
                                    break;
                                }
                            }
                            echo htmlspecialchars($subject_name);
                            ?>
                        </td>
                        <td><?php echo ($student['prelim'] !== null && $student['prelim'] !== '') ? htmlspecialchars($student['prelim']) : 'N/A'; ?></td>
                        <td><?php echo ($student['midterm'] !== null && $student['midterm'] !== '') ? htmlspecialchars($student['midterm']) : 'N/A'; ?></td>
                        <td><?php echo ($student['finals'] !== null && $student['finals'] !== '') ? htmlspecialchars($student['finals']) : 'N/A'; ?></td>
                        <td>
                            <a class="btn-edit" href="gradebook.php?edit_id=<?php echo $student['id']; ?>&subject_id=<?php echo $student['subject_id']; ?>">Assign Grades</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal for Assign Grades -->
        <div id="assignGradesModal" class="modal" style="display: <?php echo ($edit_student && isset($_GET['subject_id'])) ? 'block' : 'none'; ?>;">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <?php if ($edit_student && isset($_GET['subject_id'])): ?>
                <?php
                    $subject_id = intval($_GET['subject_id']);
                    $subject_name = '';
                    foreach ($subjects as $subject) {
                        if ($subject['id'] == $subject_id) {
                            $subject_name = $subject['subject_name'];
                            break;
                        }
                    }
                ?>
                <div class="form-container" style="box-shadow:none;max-width:100%;margin:0;">
                    <h2>Assign Grades</h2>
                    <div class="student-info">
                        <p><strong>Student Name:</strong> <?php echo htmlspecialchars($edit_student['firstname'] . ' ' . $edit_student['lastname']); ?></p>
                        <p><strong>Subject:</strong> <?php echo htmlspecialchars($subject_name); ?></p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($edit_student['id']); ?>">
                        <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="prelim">Prelim Grade</label>
                                <input type="number" id="prelim" name="prelim" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($edit_student['prelim'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="midterm">Midterm Grade</label>
                                <input type="number" id="midterm" name="midterm" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($edit_student['midterm'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="finals">Finals Grade</label>
                                <input type="number" id="finals" name="finals" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($edit_student['finals'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="final_grade">Final Grade</label>
                                <input type="text" id="final_grade" name="final_grade" value="<?php 
                                    $grades = [
                                        $edit_student['prelim'],
                                        $edit_student['midterm'],
                                        $edit_student['finals']
                                    ];
                                    $valid_grades = array_filter($grades, function($g) { return $g !== null && $g !== ''; });
                                    echo count($valid_grades) ? number_format(array_sum($valid_grades)/count($valid_grades), 2) : 'N/A';
                                ?>" readonly>
                            </div>
                        </div>
                        <button type="submit">Update Grades</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('shrink');
        document.getElementById('topbar').classList.toggle('shrink');
        document.getElementById('content').classList.toggle('shrink');
    }

    function updateGrade(studentId, gradeType, value) {
        // Get the subject ID from the first enrolled subject
        const subjectId = document.querySelector('input[name="subject_id"]').value;
        
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'gradebook.php';
        
        // Add student ID
        const studentIdInput = document.createElement('input');
        studentIdInput.type = 'hidden';
        studentIdInput.name = 'student_id';
        studentIdInput.value = studentId;
        form.appendChild(studentIdInput);
        
        // Add subject ID
        const subjectIdInput = document.createElement('input');
        subjectIdInput.type = 'hidden';
        subjectIdInput.name = 'subject_id';
        subjectIdInput.value = subjectId;
        form.appendChild(subjectIdInput);
        
        // Add grade type and value
        const gradeInput = document.createElement('input');
        gradeInput.type = 'hidden';
        gradeInput.name = gradeType;
        gradeInput.value = value;
        form.appendChild(gradeInput);
        
        // Add other grade types with empty values
        const otherGrades = ['prelim', 'midterm', 'finals'].filter(g => g !== gradeType);
        otherGrades.forEach(grade => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = grade;
            input.value = '';
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }

    function closeModal() {
        window.location.href = 'gradebook.php';
    }

    // If modal should be open, scroll to top
    <?php if ($edit_student && isset($_GET['subject_id'])): ?>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('assignGradesModal').style.display = 'block';
        window.scrollTo(0, 0);
    });
    <?php endif; ?>
</script>

</body>
</html>

<?php $conn->close(); ?>
