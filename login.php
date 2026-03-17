<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "student_management_system";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    // Add error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // First check if it's a student
    $stmt = $conn->prepare("SELECT id, username, password, approval_status, firstname, lastname FROM students WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            if ($user['approval_status'] === 'approved') {
                $_SESSION['student_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['student_name'] = $user['firstname'] . ' ' . $user['lastname'];
                header("Location: dashboard.php");
                exit();
            } else {
                echo "<script>alert('Your account is not approved yet. Please wait for admin approval.');</script>";
            }
        } else {
            echo "<script>alert('Invalid password.');</script>";
        }
    } else {
        // Check if it's a teacher
        $stmt = $conn->prepare("SELECT id, username, password, firstname, lastname FROM teachers WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['teacher_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['teacher_name'] = $user['firstname'] . ' ' . $user['lastname'];
                header("Location: teacher_portal.php");
                exit();
            } else {
                echo "<script>alert('Invalid password.');</script>";
            }
        } else {
            // Check if it's an admin
            $stmt = $conn->prepare("SELECT id, username, password, firstname, lastname FROM admins WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['admin_name'] = $user['firstname'] . ' ' . $user['lastname'];
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    echo "<script>alert('Invalid password.');</script>";
                }
            } else {
                echo "<script>alert('Invalid username or password.');</script>";
            }
        }
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
         body {
    margin: 0;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
    background: #f4f6f8;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 1rem;
  }
  .container {
    background: white;
    border-radius: 0.375rem;
    padding: 2rem;
    max-width: 400px;
    width: 100%;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    text-align: center;
  }
  h2 {
    font-weight: 600;
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid black;
    padding-bottom: 0.25rem;
    width: max-content;
    margin-left: auto;
    margin-right: auto;
  }
  form {
    margin-top: 1rem;
    text-align: left;
  }
  label {
    display: block;
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
  }
  input[type="text"],
  input[type="password"] {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #6b7280;
    box-sizing: border-box;
    margin-bottom: 1rem;
  }
  input[type="text"]::placeholder,
  input[type="password"]::placeholder {
    color: #9ca3af;
  }
  input[type="text"]:focus,
  input[type="password"]:focus {
    outline: none;
    border-color: #60a5fa;
    box-shadow: 0 0 0 1px #60a5fa;
  }
  button {
    margin-top: 2rem;
      width: 100%;
      padding: 0.5rem;
      font-weight: 700;
      font-size: 0.875rem;
      color: white;
      background: #2c3e50;
      border: none;
      border-radius: 0.375rem;
      cursor: pointer;
  }
  button:hover {
    opacity: 0.9;
  }
  .login-link {
    margin-top: 1rem;
    font-size: 0.875rem;
    color: #374151;
  }
  .login-link a {
    color: #5ea9e6;
    font-weight: 600;
    text-decoration: none;
    margin-left: 0.25rem;
  }
  .login-link a:hover {
    text-decoration: underline;
  }
    </style>
</head>
<body>
    <form method="POST" action="">
        <h2>Login</h2>
        <div>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Login</button>
        <div class="login-link">
            Don't have an account? <a href="registration.php">Register here</a>
        </div>
    </form>
</body>
</html>