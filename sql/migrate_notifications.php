<?php
// sql/migrate_notifications.php
// CLI migration script to add the notifications table and insert initial mock seed data.

require_once __DIR__ . '/../config/db.php';

try {
    echo "Starting migration...\n";

    // 1. Create table
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NOT NULL,
        title       VARCHAR(255) NOT NULL,
        message     TEXT NOT NULL,
        type        VARCHAR(50) DEFAULT 'system',
        is_read     BOOLEAN DEFAULT FALSE,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $pdo->exec($sql);
    echo "Table 'notifications' created or already exists.\n";

    // 2. Clear any existing seeds to avoid duplicates (optional, but good for testing)
    $pdo->exec("DELETE FROM notifications WHERE message LIKE '%(Seed)%'");

    // 3. Insert mock seeds
    $seeds = [
        // Notifications for John Patient (user_id = 2)
        [
            'user_id' => 2,
            'title' => 'Appointment Confirmed',
            'message' => 'Your appointment (APT-001) with Dr. Sarah Ahmed on May 20, 2026 has been confirmed. (Seed)',
            'type' => 'appointment',
            'is_read' => 0
        ],
        [
            'user_id' => 2,
            'title' => 'New Prescription Issued',
            'message' => 'Dr. Sarah Ahmed issued prescription #RX-1048 for you. (Seed)',
            'type' => 'prescription',
            'is_read' => 0
        ],
        [
            'user_id' => 2,
            'title' => 'Test Report Ready',
            'message' => 'Your Complete Blood Count (CBC) report (RPT-001) status is Normal. (Seed)',
            'type' => 'report',
            'is_read' => 1
        ],

        // Notifications for Doctor Sarah Ahmed (user_id = 3)
        [
            'user_id' => 3,
            'title' => 'New Appointment Request',
            'message' => 'Patient John Patient has requested an appointment on May 20, 2026. (Seed)',
            'type' => 'appointment',
            'is_read' => 0
        ],

        // Notifications for Admin (user_id = 1)
        [
            'user_id' => 1,
            'title' => 'System Alert',
            'message' => 'Ambulance AMB-002 dispatched for an emergency call. (Seed)',
            'type' => 'system',
            'is_read' => 0
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) VALUES (?, ?, ?, ?, ?)");
    foreach ($seeds as $s) {
        $stmt->execute([
            $s['user_id'],
            $s['title'],
            $s['message'],
            $s['type'],
            $s['is_read']
        ]);
    }

    echo "Seed data inserted successfully.\n";
    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
