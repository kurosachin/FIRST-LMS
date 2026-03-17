<?php
session_start();

if (!isset($_SESSION['teacher_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
include('db_connect.php');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['studentId']) || !isset($data['grades'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    exit();
}

$studentId = $data['studentId'];
$grades = $data['grades'];

// Validate grades
foreach ($grades as $grade) {
    if (!is_numeric($grade) || $grade < 0 || $grade > 100) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid grade value']);
        exit();
    }
}

// Update grades in database
$sql = "UPDATE students SET 
        prelim = ?, 
        midterm = ?, 
        finals = ? 
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("dddi", 
    $grades['prelim'],
    $grades['midterm'],
    $grades['finals'],
    $studentId
);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$conn->close();
?> 