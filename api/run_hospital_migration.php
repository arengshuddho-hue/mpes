<?php
// api/run_hospital_migration.php
require_once '../config/db.php';

try {
    // Read the main schema file
    $schema_file = '../sql/schema.sql';
    if (!file_exists($schema_file)) {
        die("Schema file not found.");
    }

    $schema_content = file_get_contents($schema_file);

    // Drop all tables in correct dependency order to prevent foreign key issues
    $drop_tables_sql = "
        SET FOREIGN_KEY_CHECKS = 0;
        DROP TABLE IF EXISTS blood_donors;
        DROP TABLE IF EXISTS test_result_values;
        DROP TABLE IF EXISTS test_reports;
        DROP TABLE IF EXISTS medicines;
        DROP TABLE IF EXISTS prescription_drugs;
        DROP TABLE IF EXISTS prescriptions;
        DROP TABLE IF EXISTS appointments;
        DROP TABLE IF EXISTS ambulances;
        DROP TABLE IF EXISTS doctor_details;
        DROP TABLE IF EXISTS users;
        DROP TABLE IF EXISTS hospitals;
        SET FOREIGN_KEY_CHECKS = 1;
    ";

    $pdo->exec($drop_tables_sql);

    // Execute SQL statement-by-statement to prevent max_allowed_packet limits
    $queries = preg_split("/;[ \t]*\r?\n/", $schema_content);
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            // Strip any comment-only lines or block comments if necessary, but PDO can handle standard comments
            $pdo->exec($query . ";");
        }
    }

    echo "<h1>Database Reset & Seeding Successful!</h1>";
    echo "<p>The entire database has been successfully recreated and seeded from schema.sql!</p>";
    echo "<p>All tables, users, doctors, and 1,500+ hospital records are fully up to date.</p>";
    echo "<p><a href='../patient/dashboard.php' style='display:inline-block;padding:12px 24px;background:#2b6cb0;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold;box-shadow:0 4px 6px rgba(0,0,0,0.1);transition:all 0.2s;'>Go to Patient Dashboard</a></p>";
    
} catch (PDOException $e) {
    die("<h1>Migration Failed</h1><p>Error: " . $e->getMessage() . "</p>");
}
?>
