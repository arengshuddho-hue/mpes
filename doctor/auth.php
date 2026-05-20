<?php
// doctor/auth.php
// This script manages authentication specific to the doctor and admin portals.
// It intercepts login POST requests, verifies credentials (currently mocked), 
// sets session variables, and redirects to the appropriate dashboard.

session_start(); // Initialize session to store user state
require_once 'config/db.php'; // Include database connection

// Proceed only if the request method is POST (form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- PATIENT LOGIN ---
    if (isset($_POST['login_patient'])) {
        $email = trim($_POST['patient_email']);
        $password = trim($_POST['patient_password']);
        
        // MOCK AUTHENTICATION FOR NOW
        // In a real scenario, we would verify against the DB:
        // $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'patient'");
        // $stmt->execute([$email]);
        // $user = $stmt->fetch();
        // if($user && password_verify($password, $user['password'])) { ... }
        
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'patient';
        $_SESSION['name'] = 'Demo Patient';
        header("Location: patient/dashboard.php");
        exit;
    }

    // --- DOCTOR LOGIN ---
    if (isset($_POST['login_doctor'])) {
        $email = trim($_POST['doctor_email']);
        $password = trim($_POST['doctor_password']);
        $license_no = trim($_POST['license_no']);
        
        // MOCK AUTHENTICATION FOR NOW
        $_SESSION['user_id'] = 2;
        $_SESSION['role'] = 'doctor';
        $_SESSION['name'] = 'Dr. Demo Specialist';
        header("Location: doctor/dashboard.php");
        exit;
    }

    // --- ADMIN LOGIN ---
    if (isset($_POST['login_admin'])) {
        $email = trim($_POST['admin_email']);
        $password = trim($_POST['admin_password']);
        
        // MOCK AUTHENTICATION FOR NOW
        $_SESSION['user_id'] = 3;
        $_SESSION['role'] = 'admin';
        $_SESSION['name'] = 'Super Admin';
        header("Location: admin/dashboard.php");
        exit;
    }
}

// If accessed directly or failed, redirect back to login
header("Location: login.php");
exit;
?>
