<?php
// api/auth.php
// Handles login, logout, and registration (patient + doctor).
// All responses are JSON for frontend JS consumption.

session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';

// ══════════════════════════════════════════════════════
// LOGIN
// ══════════════════════════════════════════════════════
if ($action === 'login') {

    $role     = $_POST['role']     ?? '';
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name, password, role, status FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'approved') {
                if ($user['status'] === 'pending') {
                    echo json_encode(['success' => false, 'message' => 'Your account is pending admin approval. Please wait for an admin to review it.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Your account has been rejected or deactivated.']);
                }
                exit;
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            $redirect = match($role) {
                'admin'   => 'admin/dashboard.php',
                'doctor'  => 'doctor/dashboard.php',
                'patient' => 'patient/dashboard.php',
                default   => 'index.html',
            };

            echo json_encode(['success' => true, 'redirect' => $redirect, 'message' => 'Login successful!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

// ══════════════════════════════════════════════════════
// LOGOUT
// ══════════════════════════════════════════════════════
} elseif ($action === 'logout') {

    session_unset();
    session_destroy();
    echo json_encode(['success' => true, 'redirect' => 'login.php']);

// ══════════════════════════════════════════════════════
// REGISTER
// ══════════════════════════════════════════════════════
} elseif ($action === 'register') {

    $role     = $_POST['role']     ?? '';
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    // ── Common validation ──────────────────────────────
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Name, email, and password are required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }

    try {
        // Check email is not already taken
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
            exit;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $phone   = trim($_POST['phone']   ?? '');
        $address = trim($_POST['address'] ?? '');

        // ── PATIENT registration ───────────────────────
        if ($role === 'patient') {
            $blood_group     = $_POST['blood_group']   ?? null;
            $two_factor      = isset($_POST['two_factor']) && $_POST['two_factor'] === '1' ? 1 : 0;
            $profile_picture = null;

            // Handle optional profile picture upload
            if (!empty($_FILES['profile_picture']['name'])) {
                $uploadDir = '../assets/uploads/avatars/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext      = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $filename = 'patient_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadDir . $filename)) {
                    $profile_picture = 'assets/uploads/avatars/' . $filename;
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, phone, address, blood_group, profile_picture, two_factor_enabled)
                VALUES (?, ?, ?, 'patient', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $email, $hashed, $phone ?: null, $address ?: null, $blood_group ?: null, $profile_picture, $two_factor]);

            echo json_encode(['success' => true, 'message' => 'Patient account created! You can now log in.']);

        // ── DOCTOR registration ────────────────────────
        } elseif ($role === 'doctor') {
            $license    = trim($_POST['license']   ?? '');
            $specialty  = trim($_POST['specialty'] ?? '');
            $experience = intval($_POST['experience'] ?? 0);
            $hospital   = trim($_POST['hospital']  ?? '');
            $fee        = floatval($_POST['fee']    ?? 0);
            $bio        = trim($_POST['bio']        ?? '');

            if (empty($license) || empty($specialty)) {
                echo json_encode(['success' => false, 'message' => 'Medical license and specialization are required.']);
                exit;
            }

            // Check license is unique
            $licCheck = $pdo->prepare("SELECT id FROM doctor_details WHERE license_number = ?");
            $licCheck->execute([$license]);
            if ($licCheck->fetch()) {
                echo json_encode(['success' => false, 'message' => 'This license number is already registered.']);
                exit;
            }

            // Insert user first (role = doctor)
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, phone, status)
                VALUES (?, ?, ?, 'doctor', ?, 'pending')
            ");
            $stmt->execute([$name, $email, $hashed, $phone ?: null]);
            $newUserId = $pdo->lastInsertId();

            // Resolve hospital_id from name (optional)
            $hospital_id = null;
            if (!empty($hospital)) {
                $hStmt = $pdo->prepare("SELECT id FROM hospitals WHERE name LIKE ? LIMIT 1");
                $hStmt->execute(['%' . $hospital . '%']);
                $h = $hStmt->fetch();
                if ($h) $hospital_id = $h['id'];
            }

            // Insert doctor details
            $dStmt = $pdo->prepare("
                INSERT INTO doctor_details (user_id, license_number, specialist, experience_years, hospital_id, consultation_fee, bio)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $dStmt->execute([$newUserId, $license, $specialty, $experience, $hospital_id, $fee ?: null, $bio ?: null]);

            echo json_encode(['success' => true, 'message' => 'Doctor application submitted! An admin will review your account within 24–48 hours.']);

        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid role for registration.']);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

// ══════════════════════════════════════════════════════
// UNKNOWN ACTION
// ══════════════════════════════════════════════════════
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
?>
