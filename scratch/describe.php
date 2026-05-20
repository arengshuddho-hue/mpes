<?php
// scratch/describe.php
require_once __DIR__ . '/../config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE hospitals");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
