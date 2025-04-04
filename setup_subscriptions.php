<?php
require_once 'db.php';

// First, make sure the referenced tables use InnoDB
$conn->query("ALTER TABLE city_reports ENGINE = InnoDB");
$conn->query("ALTER TABLE users ENGINE = InnoDB");

// Create the report_subscriptions table
$create_table_sql = "CREATE TABLE IF NOT EXISTS report_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_id (report_id),
    INDEX idx_user_id (user_id),
    CONSTRAINT fk_report_id FOREIGN KEY (report_id) REFERENCES city_reports(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_subscription (report_id, user_id)
) ENGINE=InnoDB";

if ($conn->query($create_table_sql) === TRUE) {
    echo "Report subscriptions table created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Migrate existing subscriptions
$migrate_sql = "INSERT IGNORE INTO report_subscriptions (report_id, user_id, email)
                SELECT cr.id, cr.user_id, 
                       CASE 
                           WHEN cr.contact_info IS NOT NULL AND cr.contact_info != '' 
                           THEN cr.contact_info 
                           ELSE u.email 
                       END as email
                FROM city_reports cr
                JOIN users u ON cr.user_id = u.id
                WHERE cr.notify_updates = 1";

if ($conn->query($migrate_sql) === TRUE) {
    echo "Existing subscriptions migrated successfully\n";
} else {
    echo "Error migrating subscriptions: " . $conn->error . "\n";
}

// Now update update_subscription.php to use the new table
?> 