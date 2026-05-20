<?php
// api/blood_donors.php
// Returns and manages blood donor data from the database as JSON.
// Supports filtering by blood_group, availability, city, and a search query.
// Also returns aggregate stats (total, available count) and supports registration.

@session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

try {
    if ($action === 'register') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $blood_group = trim($_POST['blood_group'] ?? '');
        $age = filter_var($_POST['age'] ?? '', FILTER_VALIDATE_INT);
        $city = trim($_POST['city'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $last_donation_date = trim($_POST['last_donation_date'] ?? '');
        $total_donations = filter_var($_POST['total_donations'] ?? '0', FILTER_VALIDATE_INT);

        if (!$name || !$blood_group || $age === false || !$city || !$phone) {
            echo json_encode(['success' => false, 'message' => 'All fields (Name, Blood Group, Age, City, Phone) are required and must be valid.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO blood_donors (name, blood_group, age, city, phone, last_donation_date, total_donations, available) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([
            $name,
            $blood_group,
            $age,
            $city,
            $phone,
            !empty($last_donation_date) ? $last_donation_date : null,
            $total_donations !== false ? $total_donations : 0
        ]);

        echo json_encode(['success' => true, 'message' => 'Registration successful!']);
        exit;
    }

    if ($action === 'stats') {
        // ── Aggregate stats ──────────────────────────────────────────
        $total     = $pdo->query("SELECT COUNT(*) FROM blood_donors")->fetchColumn();
        $available = $pdo->query("SELECT COUNT(*) FROM blood_donors WHERE available = 1")->fetchColumn();
        $cities    = $pdo->query("SELECT DISTINCT city FROM blood_donors WHERE city IS NOT NULL AND city != '' ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);
        $groups    = $pdo->query("SELECT DISTINCT blood_group FROM blood_donors ORDER BY blood_group")->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'success'   => true,
            'total'     => (int)$total,
            'available' => (int)$available,
            'cities'    => $cities,
            'groups'    => $groups,
        ]);
        exit;
    }

    // ── List / search donors ─────────────────────────────────────────
    $blood_group = $_GET['blood_group'] ?? '';
    $availability = $_GET['availability'] ?? '';
    $city         = $_GET['city']         ?? '';
    $search       = $_GET['search']       ?? '';

    $where  = [];
    $params = [];

    if ($blood_group && $blood_group !== 'All') {
        $where[]  = 'blood_group = ?';
        $params[] = $blood_group;
    }
    if ($availability === 'available') {
        $where[] = 'available = 1';
    } elseif ($availability === 'unavailable') {
        $where[] = 'available = 0';
    }
    if ($city) {
        $where[]  = 'city = ?';
        $params[] = $city;
    }
    if ($search) {
        $where[]  = '(name LIKE ? OR city LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql = "SELECT id, name, blood_group, age, city, phone, 
                   DATE_FORMAT(last_donation_date, '%b %Y') AS last_donated,
                   total_donations, available
            FROM blood_donors";

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY available DESC, name ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cast types for clean JSON
    foreach ($donors as &$d) {
        $d['available']        = (bool)$d['available'];
        $d['total_donations']  = (int)$d['total_donations'];
        $d['age']              = (int)$d['age'];
    }

    echo json_encode([
        'success' => true,
        'count'   => count($donors),
        'donors'  => $donors,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
