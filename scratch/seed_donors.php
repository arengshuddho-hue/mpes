<?php
// scratch/seed_donors.php - Seeds 989 blood donor records
require_once __DIR__ . '/../config/db.php';

$firstNames = ['James','Sofia','Marcus','Aisha','Carlos','Emily','David','Priya','Michael','Sarah',
    'Ahmed','Fatima','John','Maria','Ali','Nadia','Robert','Anna','Hassan','Leila',
    'Thomas','Emma','Omar','Yasmin','Daniel','Lisa','Tariq','Amina','Chris','Nina',
    'Kevin','Tanya','Khalid','Rania','Jason','Rachel','Ibrahim','Zara','Eric','Mia',
    'Steven','Layla','Nathan','Diana','Adam','Hana','Ryan','Chloe','Yusuf','Sara',
    'Patrick','Elena','Mohammad','Dina','Andrew','Grace','Abdullah','Rina','Brian','Tina',
    'George','Mary','Bilal','Huda','Mark','Jennifer','Samir','Nour','Paul','Mona',
    'Jack','Alice','Walid','Salma','Luke','Helen','Karim','Lina','Matthew','Vera',
    'Anthony','Sophia','Hamid','Rima','Joshua','Olivia','Faisal','Sana','Alexander','Lily'];

$lastNames = ['Wilson','Patel','Brown','Raza','Rivera','Chen','Kim','Sharma','Johnson','Ahmed',
    'Khan','Hassan','Williams','Rodriguez','Ali','Islam','Taylor','Garcia','Martin','Singh',
    'Moore','Jackson','Lee','White','Harris','Clark','Lewis','Robinson','Walker','Hall',
    'Young','Allen','King','Wright','Scott','Torres','Nguyen','Hill','Flores','Green',
    'Adams','Nelson','Baker','Carter','Mitchell','Perez','Roberts','Turner','Phillips','Campbell',
    'Parker','Evans','Edwards','Collins','Stewart','Sanchez','Morris','Rogers','Reed','Cook',
    'Morgan','Bell','Murphy','Bailey','Rivera','Cooper','Richardson','Cox','Howard','Ward',
    'Brooks','James','Watson','Kelly','Sanders','Price','Bennett','Wood','Barnes','Ross',
    'Henderson','Coleman','Jenkins','Perry','Powell','Long','Patterson','Hughes','Flores','Washington'];

$bloodGroups = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
$bloodWeights = [34, 6, 9, 2, 3, 1, 38, 7]; // Approximate real-world distribution

$cities = ['Dhaka','Chittagong','Sylhet','Rajshahi','Khulna','Barishal','Mymensingh','Rangpur',
    'Comilla','Narayanganj','Gazipur','Tangail','Faridpur','Jessore','Bogura'];

$pdo->exec("DELETE FROM blood_donors WHERE id > 8"); // Keep original 8 seeds

$stmt = $pdo->prepare("INSERT INTO blood_donors 
    (name, blood_group, age, city, phone, last_donation_date, total_donations, available) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

$count = 0;
$target = 989;

// Use weighted random for blood group
function weightedRandom($groups, $weights) {
    $total = array_sum($weights);
    $rand = mt_rand(1, $total);
    $cumulative = 0;
    foreach ($groups as $i => $group) {
        $cumulative += $weights[$i];
        if ($rand <= $cumulative) return $group;
    }
    return $groups[0];
}

while ($count < $target) {
    $firstName = $firstNames[array_rand($firstNames)];
    $lastName  = $lastNames[array_rand($lastNames)];
    $name      = $firstName . ' ' . $lastName;

    $blood     = weightedRandom($bloodGroups, $bloodWeights);
    $age       = mt_rand(18, 60);
    $city      = $cities[array_rand($cities)];
    $phone     = '+880-1' . mt_rand(3,9) . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
    
    // Random last donation date in past 1-18 months
    $daysAgo   = mt_rand(30, 548);
    $lastDate  = date('Y-m-d', strtotime("-{$daysAgo} days"));
    
    $donated   = mt_rand(1, 25);
    // Available if last donation was more than 90 days ago
    $available = ($daysAgo > 90) ? 1 : 0;

    $stmt->execute([$name, $blood, $age, $city, $phone, $lastDate, $donated, $available]);
    $count++;
}

$total = $pdo->query("SELECT COUNT(*) FROM blood_donors")->fetchColumn();
$avail = $pdo->query("SELECT COUNT(*) FROM blood_donors WHERE available=1")->fetchColumn();

echo json_encode([
    'inserted' => $count,
    'total_in_db' => $total,
    'available' => $avail
]);
