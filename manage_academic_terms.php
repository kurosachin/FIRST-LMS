<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_term':
                $name = $_POST['name'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                // If setting this term as active, deactivate all other terms
                if ($is_active) {
                    $sql = "UPDATE academic_terms SET is_active = 0";
                    $conn->query($sql);
                }

                $sql = "INSERT INTO academic_terms (name, start_date, end_date, is_active) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $name, $start_date, $end_date, $is_active);
                
                if ($stmt->execute()) {
                    $success = "Academic term added successfully!";
                } else {
                    $error = "Error adding academic term: " . $conn->error;
                }
                break;

            case 'edit_term':
                $id = $_POST['id'];
                $name = $_POST['name'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                // If setting this term as active, deactivate all other terms
                if ($is_active) {
                    $sql = "UPDATE academic_terms SET is_active = 0 WHERE id != ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                }

                $sql = "UPDATE academic_terms SET name = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssii", $name, $start_date, $end_date, $is_active, $id);
                
                if ($stmt->execute()) {
                    $success = "Academic term updated successfully!";
                } else {
                    $error = "Error updating academic term: " . $conn->error;
                }
                break;

            case 'delete_term':
                $id = $_POST['id'];
                
                // Check if term has any schedules
                $check_sql = "SELECT COUNT(*) as count FROM class_schedules WHERE academic_term_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $row = $result->fetch_assoc();

                if ($row['count'] > 0) {
                    $error = "Cannot delete term because it has associated schedules. Please delete the schedules first.";
                } else {
                    $sql = "DELETE FROM academic_terms WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $success = "Academic term deleted successfully!";
                    } else {
                        $error = "Error deleting academic term: " . $conn->error;
                    }
                }
                break;
        }
    }
}

// Get all academic terms
$sql = "SELECT * FROM academic_terms ORDER BY start_date DESC";
$terms = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Academic Terms</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .term-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        .term-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        .term-inactive {
            background-color: #f5f5f5;
            color: #757575;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Academic Terms</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Add New Term Form -->
        <div class="card">
            <h2>Add New Academic Term</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_term">
                
                <div class="form-group">
                    <label for="name">Term Name:</label>
                    <input type="text" name="name" required placeholder="e.g., Fall 2024">
                </div>

                <div class="form-group">
                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date" required>
                </div>

                <div class="form-group">
                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" required>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active">
                        Set as Active Term
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Add Term</button>
            </form>
        </div>

        <!-- List of Terms -->
        <div class="card">
            <h2>Academic Terms</h2>
            <?php if ($terms->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Term Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($term = $terms->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($term['name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($term['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($term['end_date'])); ?></td>
                                    <td>
                                        <span class="term-status <?php echo $term['is_active'] ? 'term-active' : 'term-inactive'; ?>">
                                            <?php echo $term['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm" 
                                                onclick="editTerm(<?php echo htmlspecialchars(json_encode($term)); ?>)">
                                            Edit
                                        </button>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_term">
                                            <input type="hidden" name="id" value="<?php echo $term['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to delete this term?')">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No academic terms found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Term Modal -->
    <div id="editTermModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Academic Term</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_term">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_name">Term Name:</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>

                <div class="form-group">
                    <label for="edit_start_date">Start Date:</label>
                    <input type="date" name="start_date" id="edit_start_date" required>
                </div>

                <div class="form-group">
                    <label for="edit_end_date">End Date:</label>
                    <input type="date" name="end_date" id="edit_end_date" required>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" id="edit_is_active">
                        Set as Active Term
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Update Term</button>
            </form>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById('editTermModal');
        const span = document.getElementsByClassName('close')[0];

        function editTerm(term) {
            document.getElementById('edit_id').value = term.id;
            document.getElementById('edit_name').value = term.name;
            document.getElementById('edit_start_date').value = term.start_date;
            document.getElementById('edit_end_date').value = term.end_date;
            document.getElementById('edit_is_active').checked = term.is_active == 1;
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html> 