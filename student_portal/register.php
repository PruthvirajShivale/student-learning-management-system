<?php require 'config.php'; ?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Register</title></head>
<body>
<h2>Student Registration</h2>
<form action="register_process.php" method="post">
  <label>Name</label><br><input type="text" name="name" required><br>
  <label>Roll No</label><br><input type="text" name="roll_no" required><br>
  <label>College / University</label><br><input type="text" name="college" required><br>
  <label>Email</label><br><input type="email" name="email" required><br>
  <label>Contact No</label><br><input type="text" name="contact" required><br>
  <label>Parent Contact</label><br><input type="text" name="parent_contact"><br>
  <label>Password</label><br><input type="password" name="password" required><br>
  <label>Confirm Password</label><br><input type="password" name="confirm_password" required><br><br>
  <button type="submit">Register</button>
</form>
<p>Already registered? <a href="login.php">Login here</a></p>
</body>
</html>