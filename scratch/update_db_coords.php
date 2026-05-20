<?php
// scratch/update_db_coords.php
require_once __DIR__ . '/../config/db.php';

$updates = [
    [
        'name' => 'City General Hospital',
        'address' => 'Dhanmondi, Dhaka',
        'lat' => 23.75270000,
        'lng' => 90.38160000
    ],
    [
        'name' => 'Apollo Medical Center',
        'address' => 'Bashundhara, Dhaka',
        'lat' => 23.80410000,
        'lng' => 90.41520000
    ],
    [
        'name' => 'LifeCare Clinic',
        'address' => 'Banani, Dhaka',
        'lat' => 23.79370000,
        'lng' => 90.40660000
    ],
    [
        'name' => 'Metro Heart Institute',
        'address' => 'Gulshan, Dhaka',
        'lat' => 23.80490000,
        'lng' => 90.42350000
    ],
    [
        'name' => 'Sunrise Pediatric Care',
        'address' => 'Lalmatia, Dhaka',
        'lat' => 23.77720000,
        'lng' => 90.37000000
    ]
];

try {
    foreach ($updates as $u) {
        $stmt = $pdo->prepare("UPDATE hospitals SET address = ?, latitude = ?, longitude = ? WHERE name = ?");
        $stmt->execute([$u['address'], $u['lat'], $u['lng'], $u['name']]);
        echo "Successfully updated '{$u['name']}' in database.\n";
    }
    echo "All coordinate updates complete.\n";
} catch (Exception $e) {
    echo "Error updating coordinates: " . $e->getMessage() . "\n";
}
?>
