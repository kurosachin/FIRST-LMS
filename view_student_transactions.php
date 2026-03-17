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

if (!isset($_GET['id'])) {
    header("Location: manage_student_accounts.php");
    exit();
}

$student_id = $_GET['id'];

// Fetch student details
$stmt = $conn->prepare("SELECT firstname, lastname FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

// Fetch transactions
$pendingTransactions = [];
$paidTransactions = [];
$totalBalance = 0;

$stmt = $conn->prepare("SELECT id, date, description, amount, status FROM wallet_transactions WHERE student_id = ? ORDER BY date DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $status = $row['status'] ?? 'pending';
    if ($status === 'pending') {
        $pendingTransactions[] = $row;
        $totalBalance += $row['amount'];
    } else {
        $paidTransactions[] = $row;
    }
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student Transactions</title>
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

        .transactions-table {
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

        .balance {
            font-weight: bold;
            text-align: right;
            margin-top: 1rem;
            font-size: 1.2rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
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

        /* Print-specific styles */
        @media print {
            /* Hide elements not needed in print */
            .sidebar, .topbar, .btn, .alert {
                display: none !important;
            }

            /* Reset content margins and padding */
            .content {
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Print-specific container */
            .transactions-table {
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            /* Add page header */
            .transactions-table::before {
                content: "Student Transaction Statement";
                display: block;
                font-size: 24px;
                font-weight: bold;
                text-align: center;
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 2px solid #000;
            }

            /* Add student info header */
            .transactions-table::after {
                content: "Student: " attr(data-student-name);
                display: block;
                font-size: 16px;
                margin-bottom: 20px;
            }

            /* Add date printed */
            .transactions-table::after {
                content: "Date Printed: " attr(data-print-date);
                display: block;
                font-size: 12px;
                margin-top: 20px;
                text-align: right;
            }

            /* Ensure tables break properly */
            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            /* Improve table appearance */
            th {
                background-color: #f0f0f0 !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            td, th {
                border: 1px solid #ddd !important;
            }

            /* Ensure text is black */
            body {
                color: #000 !important;
                background: #fff !important;
            }

            /* Add page numbers */
            @page {
                margin: 2cm;
            }

            @page :first {
                margin-top: 3cm;
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

        <li class="section-title">Settings</li>
        <li><a href="settings.php"><i class="fas fa-cogs"></i> <span>System Settings</span></a></li>
    </ul>
</div>

<div class="topbar" id="topbar">
    <button class="toggle-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <h1>Student Transactions</h1>
    <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<div class="content" id="content">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 1rem; margin-bottom: 1rem; border-radius: 0.25rem;">
            <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 1rem; margin-bottom: 1rem; border-radius: 0.25rem;">
            <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <div class="transactions-table" 
         data-student-name="<?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>"
         data-print-date="<?php echo date('Y-m-d H:i:s'); ?>">
        <h2>Transaction History for <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></h2>
        
        <h3 style="margin-top: 1rem; color: #e74c3c;">Pending Transactions</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pendingTransactions) > 0): ?>
                    <?php foreach ($pendingTransactions as $transaction): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['date']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            <td><?php echo number_format($transaction['amount'], 2); ?></td>
                            <td style="color: #e74c3c;"><?php echo htmlspecialchars($transaction['status'] ?? 'pending'); ?></td>
                            <td>
                                <form action="update_transaction_status.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                    <button type="submit" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">Mark as Paid</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No pending transactions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h3 style="margin-top: 2rem; color: #27ae60;">Paid Transactions</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($paidTransactions) > 0): ?>
                    <?php foreach ($paidTransactions as $transaction): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['date']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            <td><?php echo number_format($transaction['amount'], 2); ?></td>
                            <td style="color: #27ae60;"><?php echo htmlspecialchars($transaction['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">No paid transactions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="balance">Current Balance (Pending): <?php echo number_format($totalBalance, 2); ?></div>
        <div style="margin-top: 1rem;">
            <a href="manage_student_accounts.php" class="btn btn-secondary">Back to Accounts</a>
            <button class="btn btn-primary" onclick="printStatement()">Print Statement</button>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('shrink');
        document.getElementById('topbar').classList.toggle('shrink');
        document.getElementById('content').classList.toggle('shrink');
    }

    function printStatement() {
        // Update the print date before printing
        const transactionsTable = document.querySelector('.transactions-table');
        transactionsTable.setAttribute('data-print-date', new Date().toLocaleString());
        
        // Print the document
        window.print();
    }
</script>

</body>
</html>

<?php $conn->close(); ?> 