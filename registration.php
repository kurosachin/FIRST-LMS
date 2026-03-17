<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Registration Form</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
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
    form {
      background: white;
      border-radius: 0.375rem;
      padding: 2rem;
      max-width: 700px;
      width: 100%;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      overflow-y: auto;
      max-height: 90vh;
    }
    h2, h3 {
      font-weight: 600;
      border-bottom: 1px solid #ddd;
      padding-bottom: 0.25rem;
    }
    h2 {
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
    }
    h3 {
      font-size: 1.125rem;
      margin-top: 2rem;
      margin-bottom: 1rem;
    }
    .grid-container-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem 2rem;
    }
    label {
      font-weight: 600;
      font-size: 0.875rem;
      display: block;
      margin-bottom: 0.25rem;
    }
    input, select {
      width: 100%;
      padding: 0.5rem 0.75rem;
      font-size: 0.875rem;
      border: 1px solid #d1d5db;
      border-radius: 0.25rem;
      background-color: #fff;
      color: #1f2937;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    input:focus, select:focus {
      border-color: #60a5fa;
      box-shadow: 0 0 0 1px #60a5fa;
      outline: none;
    }
    select {
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 0.75rem center;
      background-size: 1rem;
      padding-right: 2.5rem;
    }
    fieldset {
      border: none;
      margin: 0;
      padding: 0;
    }
    .gender-options {
      display: flex;
      gap: 2rem;
      font-weight: 400;
      font-size: 0.875rem;
    }
    .gender-options label {
      display: flex;
      align-items: center;
      gap: 0.5rem;
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
      opacity: 0.95;
    }
    .login-link {
      margin-top: 1rem;
      text-align: center;
    }
    .login-link a {
      color: #5ea9e6;
      text-decoration: none;
    }
    .login-link a:hover {
      text-decoration: underline;
    }
    @media (max-width: 640px) {
      .grid-container-2 {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "student_management_system";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $requiredFields = ['lastname', 'firstname', 'dob', 'gender', 'email', 'phone', 'address', 'grade_level', 'program', 'enrollment_date', 'student_type', 'parent_name', 'parent_relationship', 'parent_phone', 'username', 'password', 'confirmpassword'];

    $errors = [];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst($field) . " is required.";
        }
    }

    if (!empty($errors)) {
        echo "<script>alert('Error: " . implode("\\n", $errors) . "');</script>";
    } else {
        // Proceed with validation and database insertion
        $lastname = htmlspecialchars($_POST['lastname']);
        $firstname = htmlspecialchars($_POST['firstname']);
        $middlename = htmlspecialchars($_POST['middlename']);
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $email = htmlspecialchars($_POST['email']);
        $phone = htmlspecialchars($_POST['phone']);
        $address = htmlspecialchars($_POST['address']);
        $grade_level = htmlspecialchars($_POST['grade_level']);
        $program = htmlspecialchars($_POST['program']);
        $previous_school = htmlspecialchars($_POST['previous_school']);
        $enrollment_date = $_POST['enrollment_date'];
        $student_type = $_POST['student_type'];
        $parent_name = htmlspecialchars($_POST['parent_name']);
        $parent_relationship = htmlspecialchars($_POST['parent_relationship']);
        $parent_phone = htmlspecialchars($_POST['parent_phone']);
        $username = htmlspecialchars($_POST['username']);
        $password = $_POST['password'];
        $confirmpassword = $_POST['confirmpassword'];

        if ($password !== $confirmpassword) {
            echo "<script>alert('Error: Passwords do not match.');</script>";
        } else {
            // Check for duplicate username
            $check_username = $conn->prepare("SELECT id FROM students WHERE username = ?");
            $check_username->bind_param("s", $username);
            $check_username->execute();
            $check_username->store_result();
            
            if ($check_username->num_rows > 0) {
                echo "<script>alert('Error: Username already exists. Please choose a different username.');</script>";
            } else {
                // Check for duplicate email
                $check_email = $conn->prepare("SELECT id FROM students WHERE email = ?");
                $check_email->bind_param("s", $email);
                $check_email->execute();
                $check_email->store_result();
                
                if ($check_email->num_rows > 0) {
                    echo "<script>alert('Error: Email already exists. Please use a different email address.');</script>";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Add grade column if it doesn't exist
                    $alter_table = "ALTER TABLE students ADD COLUMN IF NOT EXISTS grade VARCHAR(10)";
                    $conn->query($alter_table);

                    $sql = "INSERT INTO students (
                        lastname, firstname, middlename, dob, gender,
                        email, phone, address,
                        grade_level, program, previous_school, enrollment_date, student_type,
                        parent_name, parent_relationship, parent_phone,
                        username, password
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssssssssssssssss",
                        $lastname, $firstname, $middlename, $dob, $gender,
                        $email, $phone, $address,
                        $grade_level, $program, $previous_school, $enrollment_date, $student_type,
                        $parent_name, $parent_relationship, $parent_phone,
                        $username, $hashed_password
                    );

                    if ($stmt->execute()) {
                        echo "<script>alert('Registration successful!'); window.location.href = 'login.php';</script>";
                    } else {
                        echo "<script>alert('Error: Could not save data.');</script>";
                    }

                    $stmt->close();
                }
                $check_email->close();
            }
            $check_username->close();
        }
    }
    $conn->close();
}
?>

<form method="POST" action="" novalidate>
  <h2>Student Registration</h2>

  <h3>Basic Information</h3>
  <div class="grid-container-2">
    <div><label>Last Name</label><input name="lastname" type="text" required></div>
    <div><label>First Name</label><input name="firstname" type="text" required></div>
    <div><label>Middle Name</label><input name="middlename" type="text"></div>
    <div><label>Date of Birth</label><input name="dob" type="date" required></div>
  </div>
  <fieldset>
    <legend>Gender</legend>
    <div class="gender-options">
      <label><input type="radio" name="gender" value="Male" required> Male</label>
      <label><input type="radio" name="gender" value="Female"> Female</label>
      <label><input type="radio" name="gender" value="Other"> Other</label>
    </div>
  </fieldset>

  <h3>Contact Information</h3>
  <div class="grid-container-2">
    <div><label>Email</label><input name="email" type="email" required></div>
    <div><label>Phone</label><input name="phone" type="tel" pattern="[0-9]{11}" maxlength="11" required oninput="validatePhone(this)"></div>
    <div style="grid-column: span 2;"><label>Address</label><input name="address" type="text" required></div>
  </div>

  <h3>Academic Information</h3>
  <div class="grid-container-2">
    <div>
      <label>Grade Level</label>
      <select name="grade_level" required>
        <option value="">Select Grade Level</option>
        <option value="1st Year">1st Year</option>
        <option value="2nd Year">2nd Year</option>
        <option value="3rd Year">3rd Year</option>
        <option value="4th Year">4th Year</option>
      </select>
    </div>
    <div>
      <label>Program</label>
      <select name="program" required>
        <option value="">Select Program</option>
        <option value="BSIT">BSIT</option>
        <option value="BSHM">BSHM</option>
        <option value="BSP">BSP</option>
        <option value="BSCRIM">BSCRIM</option>
        <option value="BLIS">BLIS</option>
      </select>
    </div>
    <div><label>Previous School(optional)</label><input name="previous_school" type="text"></div>
    <div><label>Enrollment Date</label><input name="enrollment_date" type="date" required></div>
    <div><label>Student Type</label>
      <select name="student_type" required>
        <option value="" disabled selected>Select type</option>
        <option value="New">New</option>
        <option value="Returning">Returning</option>
        <option value="Transfer">Transfer</option>
      </select>
    </div>
  </div>

  <h3>Parent/Guardian Information</h3>
  <div class="grid-container-2">
    <div><label>Parent/Guardian Name</label><input name="parent_name" type="text" required></div>
    <div><label>Relationship</label><input name="parent_relationship" type="text" required></div>
    <div><label>Parent's Phone</label><input name="parent_phone" type="tel" pattern="[0-9]{11}" maxlength="11" required oninput="validatePhone(this)"></div>
  </div>

  <h3>Login Credentials</h3>
  <div>
    <label>Username</label><input name="username" type="text" required>
    <label>Password</label><input name="password" type="password" required minlength="6">
    <label>Confirm Password</label><input name="confirmpassword" type="password" required minlength="6">
  </div>

  <button type="submit">Register</button>

  <div class="login-link">
    Already have an account? <a href="login.php">Log in here</a>
  </div>
</form>
<script>
function validatePhone(input) {
    // Remove any non-digit characters
    input.value = input.value.replace(/\D/g, '');
    
    // Ensure exactly 11 digits
    if (input.value.length > 11) {
        input.value = input.value.slice(0, 11);
    }
}
</script>
</body>
</html>
