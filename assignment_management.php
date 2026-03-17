<?php
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include('db_connect.php');

// Initialize variables
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = $_POST['title'];
                $description = $_POST['description'];
                $due_date = $_POST['due_date'];
                $subject_id = $_POST['subject_id'];
                
                $sql = "INSERT INTO assignments (title, description, due_date, subject_id, created_at) 
                        VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $title, $description, $due_date, $subject_id);
                
                if ($stmt->execute()) {
                    $message = "Assignment added successfully!";
                } else {
                    $error = "Error adding assignment: " . $conn->error;
                }
                break;

            case 'edit':
                $id = $_POST['id'];
                $title = $_POST['title'];
                $description = $_POST['description'];
                $due_date = $_POST['due_date'];
                $subject_id = $_POST['subject_id'];
                
                $sql = "UPDATE assignments SET title = ?, description = ?, due_date = ?, subject_id = ? 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssii", $title, $description, $due_date, $subject_id, $id);
                
                if ($stmt->execute()) {
                    $message = "Assignment updated successfully!";
                } else {
                    $error = "Error updating assignment: " . $conn->error;
                }
                break;

            case 'delete':
                $id = $_POST['id'];
                
                $sql = "DELETE FROM assignments WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $message = "Assignment deleted successfully!";
                } else {
                    $error = "Error deleting assignment: " . $conn->error;
                }
                break;

            case 'grade':
                $submission_id = $_POST['submission_id'];
                $grade = $_POST['grade'];
                // Check if grade is already set
                $check_sql = "SELECT grade FROM assignment_submissions WHERE id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $submission_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $row = $check_result->fetch_assoc();
                if ($row && $row['grade'] !== null) {
                    $error = "Grade has already been set and cannot be changed.";
                } else {
                    $sql = "UPDATE assignment_submissions SET grade = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $grade, $submission_id);
                    if ($stmt->execute()) {
                        $message = "Grade updated successfully!";
                    } else {
                        $error = "Error updating grade: " . $conn->error;
                    }
                }
                break;
        }
    }
}

// Function to get all assignments
function getAssignments() {
    global $conn;
    
    // Check if the assignments table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'assignments'");
    if ($tableCheck->num_rows == 0) {
        // Table doesn't exist, redirect to create_tables.php
        header("Location: create_tables.php");
        exit();
    }
    
    $teacher_id = $_SESSION['teacher_id'];
    
    $sql = "SELECT a.*, s.subject_name,
            (SELECT COUNT(*) FROM assignment_submissions asub 
             JOIN enrollments e ON asub.student_id = e.student_id 
             WHERE asub.assignment_id = a.id AND asub.status = 'completed' 
             AND e.subject_id = a.subject_id) as completed_count,
            (SELECT COUNT(*) FROM enrollments e 
             JOIN class_schedules cs ON e.subject_id = cs.subject_id 
             WHERE cs.teacher_id = ? AND e.subject_id = a.subject_id) as total_students
            FROM assignments a 
            JOIN subjects s ON a.subject_id = s.id 
            JOIN class_schedules cs ON s.id = cs.subject_id
            WHERE cs.teacher_id = ?
            ORDER BY a.due_date ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $teacher_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        // If there's an error with the query, return an empty array
        return [];
    }
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get all subjects
function getSubjects() {
    global $conn;
    $teacher_id = $_SESSION['teacher_id'];
    
    $sql = "SELECT DISTINCT s.* 
            FROM subjects s
            JOIN class_schedules cs ON s.id = cs.subject_id
            WHERE cs.teacher_id = ?
            ORDER BY s.subject_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get assignment submissions
function getAssignmentSubmissions($assignment_id) {
    global $conn;
    $sql = "SELECT s.id, s.firstname, s.lastname, asub.status, asub.submitted_at, asub.file_path, asub.grade, asub.id as submission_id
            FROM enrollments e
            JOIN students s ON s.id = e.student_id
            LEFT JOIN assignment_submissions asub ON asub.student_id = s.id AND asub.assignment_id = ?
            WHERE e.subject_id = (SELECT subject_id FROM assignments WHERE id = ?)
            ORDER BY s.lastname, s.firstname";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $assignment_id, $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get assignments and subjects
$assignments = getAssignments();
$subjects = getSubjects();

// Get submissions for a specific assignment if requested
$submissions = [];
if (isset($_GET['view_submissions'])) {
    $submissions = getAssignmentSubmissions($_GET['view_submissions']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assignment Management</title>
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

        .content {
            flex-grow: 1;
            padding: 5rem 2rem 2rem 2rem;
            transition: all 0.3s ease;
            margin-left: 0;
        }

        .content.shrink {
            margin-left: auto;
        }

        .assignment-table {
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

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .btn-success {
            background-color: #2ecc71;
            color: white;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            width: 50%;
            border-radius: 8px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
        }

        .alert {
            padding: 10px;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

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

            .modal-content {
                width: 90%;
            }
        }

        .completion-text {
            font-size: 0.9rem;
            color: #2c3e50;
            font-weight: 500;
        }

        /* Remove the completion bar styles */
        .completion-bar,
        .completion-bar .progress,
        .completion-bar .completion-text {
            display: none;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
            text-align: center;
            min-width: 80px;
        }

        .status-pending {
            background-color: #f1c40f;
            color: #fff;
        }

        .status-completed {
            background-color: #2ecc71;
            color: #fff;
        }

        .submissions-table {
            background-color: #fff;
            border-radius: 4px;
            padding: 1rem;
            margin-top: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .submissions-table h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .submissions-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .submissions-table th {
            background: #2c3e50;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: 500;
        }

        .submissions-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .submissions-table tr:hover {
            background-color: #f8f9fa;
        }

        .btn-info {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-info:hover {
            background-color: #2980b9;
        }

        .btn-info i {
            margin-right: 4px;
        }

        .text-muted {
            color: #6c757d;
            font-style: italic;
        }

        .btn-info {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .btn-info i {
            margin-right: 4px;
        }
    </style>
</head>
<body>
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

    <div class="topbar" id="topbar">
        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1>Assignments</h1>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content" id="content">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="assignment-table">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2>Assignments</h2>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add New Assignment
                </button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Subject</th>
                        <th>Due Date</th>
                        <th>Completion</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></td>
                        <td>
                            <span class="completion-text">
                                <?php echo $assignment['completed_count']; ?>/<?php echo $assignment['total_students']; ?> completed
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-primary" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($assignment)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-info" onclick="viewSubmissions(<?php echo $assignment['id']; ?>)">
                                <i class="fas fa-users"></i> View Submissions
                            </button>
                            <button class="btn btn-danger" onclick="confirmDelete(<?php echo $assignment['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (isset($_GET['view_submissions'])): ?>
        <div class="submissions-table">
            <h2>Assignment Submissions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Status</th>
                        <th>Submitted At</th>
                        <th>Submission</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($submission['lastname'] . ', ' . $submission['firstname']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $submission['status']; ?>">
                                <?php echo ucfirst($submission['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $submission['submitted_at'] ? date('M d, Y H:i', strtotime($submission['submitted_at'])) : 'Not submitted'; ?>
                        </td>
                        <td>
                            <?php if ($submission['status'] === 'completed' && !empty($submission['file_path'])): ?>
                                <?php 
                                $file_path = $submission['file_path'];
                                $absolute_path = $_SERVER['DOCUMENT_ROOT'] . '/SMS_2/' . $file_path;
                                if (file_exists($absolute_path)): 
                                ?>
                                    <a href="<?php echo htmlspecialchars($file_path); ?>" class="btn btn-info" target="_blank">
                                        <i class="fas fa-download"></i> View Submission
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">File not found</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">No file submitted</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($submission['status'] === 'completed'): ?>
                                <?php if (isset($submission['grade']) && $submission['grade'] !== null): ?>
                                    <span style="color:#2ecc71; font-weight:bold;"> <?php echo $submission['grade']; ?> </span>
                                <?php else: ?>
                                    <form method="POST" style="display:inline-flex; align-items:center; gap:5px;">
                                        <input type="hidden" name="action" value="grade">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['submission_id']; ?>">
                                        <select name="grade" required>
                                            <option value="">--</option>
                                            <?php for ($g = 1; $g <= 10; $g++): ?>
                                                <option value="<?php echo $g; ?>" <?php if (isset($submission['grade']) && $submission['grade'] == $g) echo 'selected'; ?>><?php echo $g; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <button type="submit" class="btn btn-success" style="padding:2px 8px; font-size:0.9em;">Save</button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add Assignment Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addModal')">&times;</span>
            <h2>Add New Assignment</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="subject_id">Subject</label>
                    <select id="subject_id" name="subject_id" required>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>">
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input type="date" id="due_date" name="due_date" required>
                </div>
                <button type="submit" class="btn btn-success">Add Assignment</button>
            </form>
        </div>
    </div>

    <!-- Edit Assignment Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2>Edit Assignment</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label for="edit_title">Title</label>
                    <input type="text" id="edit_title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_subject_id">Subject</label>
                    <select id="edit_subject_id" name="subject_id" required>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>">
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_due_date">Due Date</label>
                    <input type="date" id="edit_due_date" name="due_date" required>
                </div>
                <button type="submit" class="btn btn-success">Update Assignment</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete this assignment?</p>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <button type="submit" class="btn btn-danger">Delete</button>
                <button type="button" class="btn btn-primary" onclick="closeModal('deleteModal')">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const topbar = document.getElementById('topbar');
            const content = document.getElementById('content');
            
            sidebar.classList.toggle('shrink');
            topbar.classList.toggle('shrink');
            content.classList.toggle('shrink');
        }

        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function openEditModal(assignment) {
            document.getElementById('edit_id').value = assignment.id;
            document.getElementById('edit_title').value = assignment.title;
            document.getElementById('edit_description').value = assignment.description;
            document.getElementById('edit_subject_id').value = assignment.subject_id;
            document.getElementById('edit_due_date').value = assignment.due_date;
            document.getElementById('editModal').style.display = 'block';
        }

        function confirmDelete(id) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }

        function viewSubmissions(assignmentId) {
            window.location.href = 'assignment_management.php?view_submissions=' + assignmentId;
        }
    </script>
</body>
</html>
