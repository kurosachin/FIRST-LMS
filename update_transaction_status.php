<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_student_accounts.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "student_management_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$transaction_id = $_POST['transaction_id'];
$student_id = $_POST['student_id'];

// Start transaction
$conn->begin_transaction();

try {
    // Get the transaction amount
    $stmt = $conn->prepare("SELECT amount FROM wallet_transactions WHERE id = ? AND student_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $transaction_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    $stmt->close();

    if (!$transaction) {
        throw new Exception("Transaction not found or already paid");
    }

    $amount = $transaction['amount'];

    // Update the transaction status
    $stmt = $conn->prepare("UPDATE wallet_transactions SET status = 'paid' WHERE id = ? AND student_id = ?");
    $stmt->bind_param("ii", $transaction_id, $student_id);
    $stmt->execute();
    $stmt->close();

    // Add a new transaction to subtract the amount
    $stmt = $conn->prepare("INSERT INTO wallet_transactions (student_id, description, amount, status) VALUES (?, ?, ?, 'paid')");
    $description = "Payment for transaction #" . $transaction_id;
    $negative_amount = -$amount;
    $stmt->bind_param("isd", $student_id, $description, $negative_amount);
    $stmt->execute();
    $stmt->close();

    // Commit the transaction
    $conn->commit();
    $_SESSION['success_message'] = "Transaction marked as paid successfully and balance updated.";
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    $_SESSION['error_message'] = "Failed to update transaction status: " . $e->getMessage();
}

$conn->close();

header("Location: view_student_transactions.php?id=" . $student_id);
exit();
?> 