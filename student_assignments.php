<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include('db_connect.php');

// Handle assignment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $assignment_id = $_POST['assignment_id'];
        $student_id = $_SESSION['student_id'];
        $status = $_POST['status'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $due_date = $_POST['due_date'];
        
        // First verify that the student is enrolled in the subject
        $verify_sql = "SELECT 1 FROM enrollments e 
                      JOIN assignments a ON e.subject_id = a.subject_id 
                      WHERE e.student_id = ? AND a.id = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("ii", $student_id, $assignment_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows > 0) {
            // Update assignment details
            $update_sql = "UPDATE assignments SET title = ?, description = ?, due_date = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssi", $title, $description, $due_date, $assignment_id);
            
            if ($update_stmt->execute()) {
                // Student is enrolled, proceed with submission
                $check_sql = "SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ii", $assignment_id, $student_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Update existing submission
                    $sql = "UPDATE assignment_submissions SET status = ?, submitted_at = NOW() WHERE assignment_id = ? AND student_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sii", $status, $assignment_id, $student_id);
                } else {
                    // Create new submission
                    $sql = "INSERT INTO assignment_submissions (assignment_id, student_id, status) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iis", $assignment_id, $student_id, $status);
                }
                
                if ($stmt->execute()) {
                    $message = "Assignment updated successfully!";
                } else {
                    $error = "Error updating assignment status: " . $conn->error;
                }
            } else {
                $error = "Error updating assignment details: " . $conn->error;
            }
        } else {
            $error = "You are not enrolled in this subject.";
        }
    }
    if ($_POST['action'] === 'upload') {
        $assignment_id = $_POST['assignment_id'];
        $student_id = $_SESSION['student_id'];
        
        // First verify that the student is enrolled in the subject
        $verify_sql = "SELECT 1 FROM enrollments e 
                      JOIN assignments a ON e.subject_id = a.subject_id 
                      WHERE e.student_id = ? AND a.id = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("ii", $student_id, $assignment_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows > 0) {
            // Check if file was uploaded
            if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['assignment_file'];
                $allowed_types = ['pdf', 'doc', 'docx', 'txt'];
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_ext, $allowed_types)) {
                    $error = "Invalid file type. Only PDF, DOC, DOCX, and TXT files are allowed.";
                    return;
                }
                
                // Create uploads directory if it doesn't exist
                $upload_dir = 'uploads/assignments/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $filename = uniqid() . '_' . $file['name'];
                $filepath = $upload_dir . $filename;
                $absolute_path = $_SERVER['DOCUMENT_ROOT'] . '/SMS_2/' . $filepath;
                
                if (move_uploaded_file($file['tmp_name'], $absolute_path)) {
                    // Check if submission already exists
                    $check_sql = "SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param("ii", $assignment_id, $student_id);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    
                    // Check if file_path column exists
                    $column_check = "SHOW COLUMNS FROM assignment_submissions LIKE 'file_path'";
                    $column_result = $conn->query($column_check);
                    $has_file_path = $column_result->num_rows > 0;
                    
                    if ($result->num_rows > 0) {
                        // Update existing submission
                        if ($has_file_path) {
                            $sql = "UPDATE assignment_submissions SET status = 'completed', file_path = ?, submitted_at = NOW() WHERE assignment_id = ? AND student_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("sii", $filepath, $assignment_id, $student_id);
                        } else {
                            $sql = "UPDATE assignment_submissions SET status = 'completed', submitted_at = NOW() WHERE assignment_id = ? AND student_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("ii", $assignment_id, $student_id);
                        }
                    } else {
                        // Create new submission
                        if ($has_file_path) {
                            $sql = "INSERT INTO assignment_submissions (assignment_id, student_id, status, file_path) VALUES (?, ?, 'completed', ?)";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("iis", $assignment_id, $student_id, $filepath);
                        } else {
                            $sql = "INSERT INTO assignment_submissions (assignment_id, student_id, status) VALUES (?, ?, 'completed')";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("ii", $assignment_id, $student_id);
                        }
                    }
                    
                    if ($stmt->execute()) {
                        $message = "Assignment submitted successfully!";
                    } else {
                        $error = "Error submitting assignment: " . $conn->error;
                        // Delete uploaded file if database update fails
                        unlink($absolute_path);
                    }
                } else {
                    $error = "Error uploading file.";
                }
            } else {
                $error = "Please select a file to upload.";
            }
        } else {
            $error = "You are not enrolled in this subject.";
        }
    }
}

// Function to get student's assignments
function getStudentAssignments() {
    global $conn;
    $student_id = $_SESSION['student_id'];
    
    // First check if file_path column exists
    $check_column = "SHOW COLUMNS FROM assignment_submissions LIKE 'file_path'";
    $result = $conn->query($check_column);
    $has_file_path = $result->num_rows > 0;
    
    $sql = "SELECT a.*, s.subject_name, 
            COALESCE(asub.status, 'pending') as submission_status,
            asub.submitted_at" . 
            ($has_file_path ? ", asub.file_path" : "") . ",
            asub.grade
            FROM assignments a 
            JOIN subjects s ON a.subject_id = s.id 
            JOIN enrollments e ON s.id = e.subject_id
            LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ?
            WHERE e.student_id = ?
            ORDER BY a.due_date ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $student_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

$assignments = getStudentAssignments();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Assignments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
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
            background: #fff;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
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

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-pending {
            background-color: #f1c40f;
            color: #fff;
        }

        .status-completed {
            background-color: #2ecc71;
            color: #fff;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-success {
            background-color: #2ecc71;
            color: white;
        }

        .btn-success:hover {
            background-color: #27ae60;
        }

        .btn-warning {
            background-color: #f1c40f;
            color: white;
        }

        .btn-warning:hover {
            background-color: #f39c12;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            overflow-y: auto;
            padding: 20px;
        }

        .modal-content {
            background-color: #fff;
            margin: 20px auto;
            padding: 2rem;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 600px;
            position: relative;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            position: sticky;
            top: 0;
            background-color: #fff;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group:last-child {
            margin-bottom: 0;
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
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 0.25rem;
            background-color: #f8f9fa;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .form-group select {
            cursor: pointer;
        }

        .form-group input[readonly],
        .form-group textarea[readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }

        .modal-content button[type="submit"] {
            margin-top: 1rem;
            width: 100%;
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

        .btn-info {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-left: 10px;
            transition: background-color 0.2s;
        }
        .btn-info:hover {
            background-color: #2980b9;
            color: #fff;
            text-decoration: none;
        }
        .btn-info i {
            margin-right: 4px;
        }

        /* File Upload Styles */
        .file-upload-container {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .file-upload-wrapper {
            position: relative;
            display: inline-block;
        }

        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-button {
            display: inline-block;
            padding: 8px 16px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #333;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .file-upload-button:hover {
            background-color: #e9ecef;
        }

        .file-name {
            font-size: 14px;
            color: #666;
            margin-left: 10px;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-upload-button i {
            margin-right: 5px;
        }

        .submission-actions {
            display: flex;
            align-items: center;
            gap: 10px;
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
        <h1>My Assignments</h1>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content" id="content">
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div id="assignments-content">
            <div class="assignment-table">
                <h2>My Assignments</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Description</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Grade</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['description']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $assignment['submission_status']; ?>">
                                    <?php echo ucfirst($assignment['submission_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (isset($assignment['grade']) && $assignment['grade'] !== null): ?>
                                    <span style="color:#2ecc71; font-weight:bold;"> <?php echo $assignment['grade']; ?> </span>
                                <?php else: ?>
                                    <span class="text-muted">Not graded</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($assignment['submission_status'] === 'pending'): ?>
                                    <form method="POST" enctype="multipart/form-data" style="display: inline;">
                                        <input type="hidden" name="action" value="upload">
                                        <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                        <div class="file-upload-container">
                                            <div class="file-upload-wrapper">
                                                <input type="file" name="assignment_file" id="file-<?php echo $assignment['id']; ?>" class="file-upload-input" accept=".pdf,.doc,.docx,.txt" required>
                                                <label for="file-<?php echo $assignment['id']; ?>" class="file-upload-button">
                                                    <i class="fas fa-file-upload"></i> Choose File
                                                </label>
                                            </div>
                                            <span class="file-name" id="file-name-<?php echo $assignment['id']; ?>">No file chosen</span>
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-upload"></i> Submit
                                        </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="submission-actions">
                                        <span class="status-badge status-completed">
                                            <i class="fas fa-check"></i> Submitted
                                        </span>
                                        <?php if (isset($assignment['file_path']) && $assignment['file_path']): ?>
                                            <a href="<?php echo htmlspecialchars($assignment['file_path']); ?>" class="btn-info" target="_blank">
                                                <i class="fas fa-download"></i> View Submission
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('shrink');
            document.getElementById('topbar').classList.toggle('shrink');
            document.getElementById('content').classList.toggle('shrink');
        }

        // File upload name display
        document.querySelectorAll('.file-upload-input').forEach(input => {
            input.addEventListener('change', function() {
                const fileName = this.files[0]?.name || 'No file chosen';
                const fileDisplay = document.getElementById('file-name-' + this.id.split('-')[1]);
                if (fileDisplay) {
                    fileDisplay.textContent = fileName;
                }
            });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html> 