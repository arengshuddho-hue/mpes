<?php
// api/search_doctors.php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

// Check authorization
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in first.']);
    exit;
}

// 1. Return dynamic filters if requested
if (isset($_GET['get_filters'])) {
    try {
        // Fetch distinct specialties
        $spec_stmt = $pdo->query("SELECT DISTINCT specialist FROM doctor_details WHERE specialist IS NOT NULL AND specialist != '' ORDER BY specialist ASC");
        $specialties = $spec_stmt->fetchAll(PDO::FETCH_COLUMN);

        // Fetch distinct hospitals that have doctors
        $hosp_stmt = $pdo->query("SELECT DISTINCT h.id, h.name FROM hospitals h JOIN doctor_details d ON h.id = d.hospital_id ORDER BY h.name ASC");
        $hospitals = $hosp_stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'specialties' => $specialties,
            'hospitals' => $hospitals
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching filters: ' . $e->getMessage()]);
        exit;
    }
}

// 2. Perform doctor search/filter
$search = $_GET['search'] ?? '';
$specialty = $_GET['specialty'] ?? '';
$hospital = $_GET['hospital'] ?? '';
$available = $_GET['available'] ?? '';
$sort = $_GET['sort'] ?? 'rating';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

try {
    $query = "
        SELECT 
            u.id, 
            u.name, 
            u.profile_picture as img,
            d.specialist as specialty, 
            h.name as hospital, 
            d.experience_years as experience, 
            d.rating, 
            d.total_reviews as reviews, 
            d.consultation_fee as fee, 
            d.available, 
            d.bio
        FROM doctor_details d
        JOIN users u ON d.user_id = u.id
        LEFT JOIN hospitals h ON d.hospital_id = h.id
        WHERE 1=1
    ";
    
    $params = [];

    // Search term (doctor name or specialty or bio)
    if (!empty($search)) {
        $query .= " AND (u.name LIKE ? OR d.specialist LIKE ? OR d.bio LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    // Specialty filter
    if (!empty($specialty)) {
        $query .= " AND d.specialist = ?";
        $params[] = $specialty;
    }

    // Hospital filter (checks id or name)
    if (!empty($hospital)) {
        if (is_numeric($hospital)) {
            $query .= " AND d.hospital_id = ?";
            $params[] = intval($hospital);
        } else {
            $query .= " AND h.name = ?";
            $params[] = $hospital;
        }
    }

    // Availability filter
    if (!empty($available)) {
        if ($available === 'available') {
            $query .= " AND d.available = 1";
        } elseif ($available === 'busy') {
            $query .= " AND d.available = 0";
        }
    }

    // Sorting
    if ($sort === 'rating') {
        $query .= " ORDER BY d.rating DESC, d.total_reviews DESC";
    } elseif ($sort === 'fee_asc') {
        $query .= " ORDER BY d.consultation_fee ASC";
    } elseif ($sort === 'fee_desc') {
        $query .= " ORDER BY d.consultation_fee DESC";
    } elseif ($sort === 'experience') {
        $query .= " ORDER BY d.experience_years DESC";
    }

    // Limit & Offset (paging)
    $query .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $doctors = $stmt->fetchAll();

    // Map profile pictures to a default if they don't exist
    foreach ($doctors as &$doc) {
        $doc['available'] = (bool)$doc['available'];
        $doc['rating'] = floatval($doc['rating']);
        $doc['reviews'] = intval($doc['reviews']);
        $doc['fee'] = floatval($doc['fee']);
        $doc['experience'] = intval($doc['experience']) . " years";
        if (empty($doc['img'])) {
            // Seed a consistent pravatar ID based on user ID for aesthetics
            $avatarId = ($doc['id'] % 70) + 1;
            $doc['img'] = "https://i.pravatar.cc/90?img=" . $avatarId;
        }
    }

    echo json_encode([
        'success' => true,
        'doctors' => $doctors
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Query error: ' . $e->getMessage()]);
}
?>
