<?php
// api/crud.php
// This script serves as the main controller for Create, Read, Update, Delete (CRUD) operations.
// It handles various actions such as booking appointments, issuing prescriptions, and adding users.

// Start session to verify user authentication
@session_start();

// Include database connection
require_once '../config/db.php';

// Clean any previous output (e.g. notices) to ensure pure JSON
if (ob_get_length()) ob_clean();

// Set header for JSON response
header('Content-Type: application/json');

// Authorization Check: Verify if a user is currently logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Ensure the endpoint is only accessed via POST methods
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Retrieve the specified action from POST data
$action = $_POST['action'] ?? '';

try {
    // ---------------------------------------------------------
    // 1. BOOK APPOINTMENT SECTION
    // Allows a patient to schedule an appointment with a doctor.
    // ---------------------------------------------------------
    if ($action === 'book_appointment') {
        // Extract necessary data from session and POST request
        $patient_id = $_SESSION['user_id'];
        $doctor_id = $_POST['doctor_id'] ?? null;
        $appointment_date = $_POST['date'] ?? null;
        $appointment_time = $_POST['time'] ?? null;
        $notes = $_POST['notes'] ?? '';

        // Validate required inputs
        if (!$doctor_id || !$appointment_date || !$appointment_time) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
            exit;
        }

        // Format the date and time for MySQL DATETIME column
        $datetime = date('Y-m-d H:i:s', strtotime("$appointment_date $appointment_time"));
        
        // Generate a unique serial number for the appointment
        $serial = 'APT-' . strtoupper(uniqid());

        // Prepare and execute the insertion query. 
        // Note: hospital_id is assumed null here, it could be fetched from doctor_details if needed.
        $stmt = $pdo->prepare("INSERT INTO appointments (serial_number, patient_id, doctor_id, appointment_date, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$serial, $patient_id, $doctor_id, $datetime]);

        // Create Notifications for patient and doctor
        try {
            // Fetch names
            $stmtName = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $stmtName->execute([$patient_id]);
            $patientName = $stmtName->fetchColumn();

            $stmtName->execute([$doctor_id]);
            $doctorName = $stmtName->fetchColumn();

            // Notify patient
            $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'appointment')");
            $stmtNotif->execute([
                $patient_id,
                'Appointment Requested',
                "Your appointment request ($serial) with Dr. $doctorName has been submitted and is pending confirmation."
            ]);

            // Notify doctor
            $stmtNotif->execute([
                $doctor_id,
                'New Appointment Request',
                "Patient $patientName has requested an appointment ($serial) with you for $appointment_date."
            ]);
        } catch (PDOException $eNotif) {
            // Log or ignore notification failure so it doesn't block the main database transaction
        }

        // Return success response with generated serial number
        echo json_encode(['success' => true, 'message' => 'Appointment booked successfully!', 'serial' => $serial]);
    }
    
    // ---------------------------------------------------------
    // 2. ISSUE PRESCRIPTION SECTION
    // Allows a logged-in doctor to write a prescription for a patient.
    // ---------------------------------------------------------
    elseif ($action === 'issue_prescription') {
        // Role check: Only doctors are authorized
        if ($_SESSION['role'] !== 'doctor') {
            echo json_encode(['success' => false, 'message' => 'Only doctors can issue prescriptions.']);
            exit;
        }

        // Extract required data
        $doctor_id = $_SESSION['user_id'];
        $patient_id = $_POST['patient_id'] ?? null;
        $diagnosis = $_POST['diagnosis'] ?? '';
        $medicines = $_POST['medicines'] ?? ''; // Expecting JSON string or comma-separated list
        $notes = $_POST['notes'] ?? '';

        // Validate inputs
        if (!$patient_id || !$diagnosis) {
            echo json_encode(['success' => false, 'message' => 'Patient and Diagnosis are required.']);
            exit;
        }

        // Combine fields into a single notes column. 
        // In a more normalized database, medicines might have their own linking table.
        $full_notes = "Diagnosis: $diagnosis\nMedicines: $medicines\nNotes: $notes";

        // Generate unique prescription code
        $rx_code = 'RX-' . strtoupper(substr(uniqid(), -6)) . '-' . $patient_id;

        // Insert prescription record with patient_id stored
        $stmt = $pdo->prepare("INSERT INTO prescriptions (prescription_code, doctor_id, patient_id, notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$rx_code, $doctor_id, $patient_id, $full_notes]);

        // Create Notification for the patient
        try {
            // Fetch doctor name
            $stmtName = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $stmtName->execute([$doctor_id]);
            $doctorName = $stmtName->fetchColumn();

            // Notify patient
            $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'prescription')");
            $stmtNotif->execute([
                $patient_id,
                'New Prescription Issued',
                "Dr. $doctorName has issued a new prescription for your diagnosis: $diagnosis."
            ]);
        } catch (PDOException $eNotif) {
            // Ignore notification failure
        }

        echo json_encode(['success' => true, 'message' => 'Prescription issued successfully!']);
    }

    // ---------------------------------------------------------
    // 3. ADD USER SECTION (Admin Only)
    // Allows administrators to manually register new users.
    // ---------------------------------------------------------
    elseif ($action === 'add_user') {
        // Role check: Only admins are authorized
        if ($_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Only admins can add users.']);
            exit;
        }

        // Extract new user data
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'patient';
        // Assign a default password and hash it securely
        $password = password_hash('password123', PASSWORD_DEFAULT);

        // Validate required fields
        if (!$name || !$email) {
            echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
            exit;
        }

        // Insert new user into the database
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $role]);

        echo json_encode(['success' => true, 'message' => 'User added successfully! Default password is "password123".']);
    }
    
    // ---------------------------------------------------------
    // 4. UPDATE DOCTOR STATUS (Admin Only)
    // Allows administrators to approve/reject doctor accounts.
    // ---------------------------------------------------------
    elseif ($action === 'update_doctor_status') {
        if ($_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Only admins can approve or reject doctor accounts.']);
            exit;
        }

        $user_id = $_POST['user_id'] ?? null;
        $status = $_POST['status'] ?? ''; // 'approved' or 'rejected' or 'pending'

        if (!$user_id || !in_array($status, ['approved', 'rejected', 'pending'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'doctor'");
        $stmt->execute([$status, $user_id]);

        echo json_encode(['success' => true, 'message' => 'Doctor status updated to ' . $status . ' successfully!']);
    }
    
    // ---------------------------------------------------------
    // 5. FETCH MEDICINES
    // ---------------------------------------------------------
    elseif ($action === 'get_medicines') {
        $stmt = $pdo->query("SELECT * FROM medicines ORDER BY name ASC");
        $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'medicines' => $medicines]);
    }

    // ---------------------------------------------------------
    // 6. INVALID ACTION SECTION
    // ---------------------------------------------------------
    else {
        // Fallback for unrecognized action codes
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }

} catch (PDOException $e) {
    // Catch database errors globally to prevent application crashes and output valid JSON
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
