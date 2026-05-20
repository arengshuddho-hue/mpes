<?php
require_once __DIR__ . '/../config/db.php';

$names = ['Madani Hospital', 'Farazy', 'Dhaka Healthcare', 'Mohaimid', 'Upasham'];

foreach ($names as $name) {
    echo "Searching for '$name':\n";
    $stmt = $pdo->prepare("SELECT id, name, latitude, longitude, address FROM hospitals WHERE name LIKE ?");
    $stmt->execute(["%$name%"]);
    $results = $stmt->fetchAll();
    if (empty($results)) {
        echo "  -> NOT FOUND!\n";
    } else {
        foreach ($results as $row) {
            echo "  -> ID: {$row['id']} | Name: {$row['name']} | Lat: {$row['latitude']} | Lng: {$row['longitude']} | Address: {$row['address']}\n";
        }
    }
    echo "\n";
}
?>
