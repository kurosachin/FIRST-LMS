<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// Include database connection
include('db_connect.php');

// Fetch student's grades
function getStudentGrades($student_id) {
    global $conn;
    $sql = "SELECT 
                sub.subject_name, 
                sub.subject_code, 
                g.prelim, 
                g.midterm, 
                g.finals,
                CASE 
                    WHEN g.prelim IS NOT NULL AND g.midterm IS NOT NULL AND g.finals IS NOT NULL 
                    THEN (g.prelim + g.midterm + g.finals) / 3
                    ELSE NULL
                END as final_grade
            FROM enrollments e
            INNER JOIN subjects sub ON e.subject_id = sub.id
            LEFT JOIN grades g ON g.student_id = e.student_id AND g.subject_id = e.subject_id
            WHERE e.student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $grades = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }
    }
    return $grades;
}

$student_grades = getStudentGrades($student_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semestral Grade</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <script src="js/print.js"></script>
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

        .student-info {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .student-info h2 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .student-info p {
            color: #7f8c8d;
            margin-bottom: 0.25rem;
        }

        /* Responsive Styles for Smaller Screens (up to 768px width) */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: fixed;
                height: auto;
                z-index: 1000;
                transform: translateY(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateY(0);
            }

            .topbar {
                left: 0;
                width: 100%;
            }

            .content {
                margin-top: 80px;
                padding: 1rem;
                width: 100%;
            }

            .student-table {
                margin-top: 1rem;
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

<!-- Topbar -->
<div class="topbar" id="topbar">
    <button class="toggle-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <h1>Semestral Grades</h1>
    <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<!-- Content -->
<div class="content" id="content">
    <button class="print-btn" onclick="printContent('semestral-content', 'Semestral Grade Report')">
        <i class="fas fa-print"></i> Print Semestral Grade
    </button>
    <div id="semestral-content">
        <div class="student-info">
            <h2>Student Information</h2>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($student_name); ?></p>
            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student_id); ?></p>
        </div>

        <div class="student-table">
            <h1>Grade Report</h1>
            <table>
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Prelim</th>
                        <th>Midterm</th>
                        <th>Finals</th>
                        <th>Final Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($student_grades)): ?>
                        <?php foreach ($student_grades as $grade): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($grade['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                            <td>
                                <span class="grade-display">
                                    <?php echo $grade['prelim'] ? htmlspecialchars($grade['prelim']) : 'N/A'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="grade-display">
                                    <?php echo $grade['midterm'] ? htmlspecialchars($grade['midterm']) : 'N/A'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="grade-display">
                                    <?php echo $grade['finals'] ? htmlspecialchars($grade['finals']) : 'N/A'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="grade-display">
                                    <?php echo $grade['final_grade'] ? number_format($grade['final_grade'], 2) : 'N/A'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No grades available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const topbar = document.getElementById('topbar');
        const content = document.getElementById('content');
        
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('active');
        } else {
            sidebar.classList.toggle('shrink');
            topbar.classList.toggle('shrink');
            content.classList.toggle('shrink');
        }
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.querySelector('.toggle-btn');
        
        if (window.innerWidth <= 768 && 
            !sidebar.contains(event.target) && 
            !toggleBtn.contains(event.target) && 
            sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }
    });
</script>

</body>
</html>

<?php $conn->close(); ?>