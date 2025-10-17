<?php
session_start();
require __DIR__ . '/config_mysqli.php';
require __DIR__ . '/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf'])) {
  $_SESSION['flash'] = 'Invalid request.';
  header('Location: register.php');
  exit;
}

$fullname = trim($_POST['fullname']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirm = $_POST['confirm_password'];

// --- ตรวจสอบค่าที่กรอกเข้ามา ---
if ($password !== $confirm) {
  $_SESSION['flash'] = 'Passwords do not match.';
  header('Location: register.php');
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $_SESSION['flash'] = 'Invalid email address.';
  header('Location: register.php');
  exit;
}

// --- ตรวจสอบว่า email ซ้ำหรือยัง ---
$check = $mysqli->prepare('SELECT id FROM users WHERE email = ?');
$check->bind_param('s', $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
  $_SESSION['flash'] = 'Email already registered.';
  $check->close();
  header('Location: register.php');
  exit;
}
$check->close();

// --- สร้างรหัสผ่านแบบ hash ---
$hash = password_hash($password, PASSWORD_DEFAULT);

// --- เพิ่มเข้า database ---
$stmt = $mysqli->prepare('INSERT INTO users (email, display_name, password_hash) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $email, $fullname, $hash);

if ($stmt->execute()) {
  $_SESSION['flash'] = 'Registration successful! You can now log in.';
  header('Location: login.php');
} else {
  $_SESSION['flash'] = 'Database error: ' . $mysqli->error;
  header('Location: register.php');
}

$stmt->close();
$mysqli->close();
