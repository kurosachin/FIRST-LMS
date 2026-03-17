<?php
session_start();

// Check if the user is an admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in as admin
    exit();
}

$admin_name = $_SESSION['admin_name'];

// Database connection
$conn = new mysqli("localhost", "root", "", "student_management_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_item'])) {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $target_audience = $_POST['target_audience'];
        
        $stmt = $conn->prepare("INSERT INTO dashboard_items (title, content, target_audience) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $content, $target_audience);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['edit_item'])) {
        $id = $_POST['item_id'];
        $title = $_POST['title'];
        $content = $_POST['content'];
        $target_audience = $_POST['target_audience'];
        
        $stmt = $conn->prepare("UPDATE dashboard_items SET title = ?, content = ?, target_audience = ? WHERE id = ?");
        $stmt->bind_param("sssi", $title, $content, $target_audience, $id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete_item'])) {
        $id = $_POST['item_id'];
        
        $stmt = $conn->prepare("DELETE FROM dashboard_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch all dashboard items
$result = $conn->query("SELECT * FROM dashboard_items ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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
    }

    .content.shrink {
      margin-left: auto;
    }

    .content h1 {
      font-size: 1.5rem;
      color: #2c3e50;
      margin-bottom: 1rem;
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
        padding: 1rem;
      }
    }

    .dashboard-items {
      margin-top: 2rem;
    }
    
    .dashboard-item {
      background: white;
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 0.5rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .dashboard-item h3 {
      margin: 0 0 0.5rem 0;
      color: #2c3e50;
    }
    
    .dashboard-item p {
      margin: 0 0 1rem 0;
      color: #34495e;
    }
    
    .dashboard-item .meta {
      font-size: 0.9rem;
      color: #7f8c8d;
    }
    
    .dashboard-item .actions {
      margin-top: 1rem;
    }
    
    .btn {
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 0.25rem;
      cursor: pointer;
      margin-right: 0.5rem;
    }
    
    .btn-primary { background: #3498db; color: white; }
    .btn-success { background: #2ecc71; color: white; }
    .btn-danger { background: #e74c3c; color: white; }
    
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
    }
    
    .modal-content {
      background: white;
      margin: 10% auto;
      padding: 2rem;
      width: 80%;
      max-width: 600px;
      border-radius: 0.5rem;
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
      padding: 0.5rem;
      border: 1px solid #ddd;
      border-radius: 0.25rem;
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


  <!-- Topbar -->
  <div class="topbar" id="topbar">
    <button class="toggle-btn" onclick="toggleSidebar()">
      <i class="fas fa-bars"></i>
    </button>
    <h1>Admin Dashboard</h1>
    <a href="logout.php" class="logout-btn">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>

  <!-- Content -->
  <div class="content">
    <div class="dashboard-content">
      <div class="dashboard-items">
        <h3>Dashboard Items</h3>
        <button class="btn btn-primary" onclick="showAddModal()">Add New Item</button>
        
        <?php while($item = $result->fetch_assoc()): ?>
        <div class="dashboard-item">
          <h3><?php echo htmlspecialchars($item['title']); ?></h3>
          <p><?php echo nl2br(htmlspecialchars($item['content'])); ?></p>
          <div class="meta">
            Target: <?php echo htmlspecialchars($item['target_audience']); ?> |
            Created: <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
          </div>
          <div class="actions">
            <button class="btn btn-success" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">Edit</button>
            <form method="POST" style="display: inline;">
              <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
              <button type="submit" name="delete_item" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
            </form>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>

  <!-- Add Item Modal -->
  <div id="addModal" class="modal">
    <div class="modal-content">
      <h2>Add Dashboard Item</h2>
      <form method="POST">
        <div class="form-group">
          <label for="title">Title</label>
          <input type="text" id="title" name="title" required>
        </div>
        <div class="form-group">
          <label for="content">Content</label>
          <textarea id="content" name="content" rows="4" required></textarea>
        </div>
        <div class="form-group">
          <label for="target_audience">Target Audience</label>
          <select id="target_audience" name="target_audience" required>
            <option value="all">All Users</option>
            <option value="students">Students Only</option>
            <option value="teachers">Teachers Only</option>
          </select>
        </div>
        <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
        <button type="button" class="btn btn-danger" onclick="hideModal('addModal')">Cancel</button>
      </form>
    </div>
  </div>

  <!-- Edit Item Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <h2>Edit Dashboard Item</h2>
      <form method="POST">
        <input type="hidden" id="edit_item_id" name="item_id">
        <div class="form-group">
          <label for="edit_title">Title</label>
          <input type="text" id="edit_title" name="title" required>
        </div>
        <div class="form-group">
          <label for="edit_content">Content</label>
          <textarea id="edit_content" name="content" rows="4" required></textarea>
        </div>
        <div class="form-group">
          <label for="edit_target_audience">Target Audience</label>
          <select id="edit_target_audience" name="target_audience" required>
            <option value="all">All Users</option>
            <option value="students">Students Only</option>
            <option value="teachers">Teachers Only</option>
          </select>
        </div>
        <button type="submit" name="edit_item" class="btn btn-success">Save Changes</button>
        <button type="button" class="btn btn-danger" onclick="hideModal('editModal')">Cancel</button>
      </form>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('shrink');
      document.getElementById('topbar').classList.toggle('shrink');
    }

    function showAddModal() {
      document.getElementById('addModal').style.display = 'block';
    }

    function showEditModal(item) {
      document.getElementById('edit_item_id').value = item.id;
      document.getElementById('edit_title').value = item.title;
      document.getElementById('edit_content').value = item.content;
      document.getElementById('edit_target_audience').value = item.target_audience;
      document.getElementById('editModal').style.display = 'block';
    }

    function hideModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      if (event.target.className === 'modal') {
        event.target.style.display = 'none';
      }
    }
  </script>

</body>
</html>
<?php $conn->close(); ?>
