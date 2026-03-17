<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'];

$conn = new mysqli("localhost", "root", "", "student_management_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle adding new transaction
if (isset($_POST['add_transaction'])) {
    $student_id = $_POST['student_id'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $date = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO wallet_transactions (student_id, date, description, amount) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issd", $student_id, $date, $description, $amount);
    
    if ($stmt->execute()) {
        echo "<script>alert('Transaction added successfully!');</script>";
    } else {
        echo "<script>alert('Error adding transaction.');</script>";
    }
    $stmt->close();
}

// Fetch all students with their current balance
$sql = "SELECT s.id, s.firstname, s.lastname, 
        COALESCE(SUM(wt.amount), 0) as balance 
        FROM students s 
        LEFT JOIN wallet_transactions wt ON s.id = wt.student_id 
        GROUP BY s.id, s.firstname, s.lastname";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Student Accounts</title>
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

        .accounts-table {
            background: #fff;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
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

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
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
            background-color: #fff;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 0.375rem;
        }

        .close {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
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

<div class="topbar" id="topbar">
    <button class="toggle-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <h1>Manage Student Accounts</h1>
    <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<div class="content" id="content">
    <div class="accounts-table">
        <h2>Student Accounts</h2>
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Current Balance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                        <td><?php echo number_format($row['balance'], 2); ?></td>
                        <td>
                            <button class="btn btn-primary" onclick="openTransactionModal(<?php echo $row['id']; ?>)">
                                Add Transaction
                            </button>
                            <a href="view_student_transactions.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">
                                View Transactions
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Transaction Modal -->
<div id="transactionModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeTransactionModal()">&times;</span>
        <h2>Add Transaction</h2>
        <form method="POST" action="">
            <input type="hidden" name="student_id" id="student_id">
            <div class="form-group">
                <label for="description">Description</label>
                <input type="text" id="description" name="description" required>
            </div>
            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" step="0.01" required>
            </div>
            <button type="submit" name="add_transaction" class="btn btn-primary">Add Transaction</button>
        </form>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('shrink');
        document.getElementById('topbar').classList.toggle('shrink');
        document.getElementById('content').classList.toggle('shrink');
    }

    function openTransactionModal(studentId) {
        document.getElementById('transactionModal').style.display = 'block';
        document.getElementById('student_id').value = studentId;
    }

    function closeTransactionModal() {
        document.getElementById('transactionModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('transactionModal')) {
            closeTransactionModal();
        }
    }
</script>

</body>
</html>

<?php $conn->close(); ?> 