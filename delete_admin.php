<?php
session_start();

// Check if the user is an admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['admin_id'])) {
    $id = $_POST['admin_id'];
    
    // Prevent deleting the last admin
    $result = $conn->query("SELECT COUNT(*) as count FROM admins");
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 1) {
        $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Admin deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting admin: " . $conn->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Cannot delete the last admin account!";
    }
}

// Redirect back to manage admins page
header("Location: manage_admins.php");
exit();
?> 