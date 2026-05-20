<?php
require_once __DIR__ . '/../config/db.php';

// 1. Delete any existing duplicate/clashing records
$pdo->exec("DELETE FROM hospitals WHERE name LIKE '%Madani Hospital%' OR name LIKE '%Dhaka Healthcare%' OR name LIKE '%Mohaimid%'");

// 2. Insert or update the 5 local hospitals with precise coordinates
$hospitals = [
    [
        'name' => 'Mohaimid Medical Center',
        'type' => 'Clinic',
        'address' => 'Madani Ave, Nurer Chala, Vatara, Dhaka-1212, Bangladesh',
        'latitude' => 23.800921,
        'longitude' => 90.429188,
        'contact_number' => '+880 1711-223344',
        'emergency_24h' => 1,
        'total_beds' => 15,
        'is_open' => 1
    ],
    [
        'name' => 'Dhaka Healthcare Systems Hospital',
        'type' => 'General',
        'address' => '08, House # 07, Road, 100 Feet Madani Ave - 02222, Dhaka-1212, Bangladesh',
        'latitude' => 23.800366,
        'longitude' => 90.426786,
        'contact_number' => '+880 2-8811223',
        'emergency_24h' => 1,
        'total_beds' => 50,
        'is_open' => 1
    ],
    [
        'name' => 'Farazy Diagonistic & Hospital Ltd.',
        'type' => 'Specialized',
        'address' => '1204, Madani Avenue, 100 Feet Road, Baridhara, Natun Bazar, Dhaka-1212, Bangladesh',
        'latitude' => 23.800049,
        'longitude' => 90.424168,
        'contact_number' => '+880 19606-316502',
        'emergency_24h' => 1,
        'total_beds' => 80,
        'is_open' => 1
    ],
    [
        'name' => 'Madani Hospital Ltd.',
        'type' => 'General',
        'address' => 'Madani Ave, Vatara, Dhaka-1212, Bangladesh',
        'latitude' => 23.801061,
        'longitude' => 90.422312,
        'contact_number' => '+880 1811-223344',
        'emergency_24h' => 1,
        'total_beds' => 30,
        'is_open' => 1
    ],
    [
        'name' => 'Upasham Health Point-Pvt Ltd.',
        'type' => 'General',
        'address' => 'H-14, R-2/B, J Block, Baridhara, Vatara, Dhaka-1212, Bangladesh',
        'latitude' => 23.795493,
        'longitude' => 90.420800,
        'contact_number' => '+880 19665-710665',
        'emergency_24h' => 1,
        'total_beds' => 40,
        'is_open' => 1
    ]
];

foreach ($hospitals as $h) {
    // Check if hospital with exact name exists
    $stmt = $pdo->prepare("SELECT id FROM hospitals WHERE name = ?");
    $stmt->execute([$h['name']]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update
        $stmtUpdate = $pdo->prepare("UPDATE hospitals SET type = ?, address = ?, latitude = ?, longitude = ?, contact_number = ?, emergency_24h = ?, total_beds = ?, is_open = ? WHERE id = ?");
        $stmtUpdate->execute([
            $h['type'], $h['address'], $h['latitude'], $h['longitude'],
            $h['contact_number'], $h['emergency_24h'], $h['total_beds'], $h['is_open'],
            $existing['id']
        ]);
        echo "Updated hospital: {$h['name']}\n";
    } else {
        // Insert
        $stmtInsert = $pdo->prepare("INSERT INTO hospitals (name, type, address, latitude, longitude, contact_number, emergency_24h, total_beds, is_open) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtInsert->execute([
            $h['name'], $h['type'], $h['address'], $h['latitude'], $h['longitude'],
            $h['contact_number'], $h['emergency_24h'], $h['total_beds'], $h['is_open']
        ]);
        echo "Inserted hospital: {$h['name']}\n";
    }
}

// Clean up any old duplicate Farazy records to prevent inaccurate coordinates from overriding the real ones
$pdo->exec("DELETE FROM hospitals WHERE name LIKE '%Farazy%' AND name != 'Farazy Diagonistic & Hospital Ltd.'");
echo "Cleaned up old Farazy duplicates.\n";
?>
