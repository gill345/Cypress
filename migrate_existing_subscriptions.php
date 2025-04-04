<?php
require_once 'db.php';

// Get all reports with notify_updates=1
$query = "SELECT cr.id, cr.user_id, cr.contact_info, u.email 
          FROM city_reports cr
          JOIN users u ON cr.user_id = u.id
          WHERE cr.notify_updates = 1";

$result = $conn->query($query);

if ($result) {
    $migrated = 0;
    $failed = 0;

    while ($row = $result->fetch_assoc()) {
        $notification_email = !empty($row['contact_info']) && filter_var($row['contact_info'], FILTER_VALIDATE_EMAIL)
            ? $row['contact_info']
            : $row['email'];

        $insert_query = "INSERT IGNORE INTO report_subscriptions (report_id, user_id, email) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iis", $row['id'], $row['user_id'], $notification_email);
        
        if ($stmt->execute()) {
            $migrated++;
        } else {
            $failed++;
            error_log("Failed to migrate subscription for report {$row['id']}: " . $stmt->error);
        }
        $stmt->close();
    }

    echo "Migration completed:\n";
    echo "Successfully migrated: $migrated\n";
    echo "Failed migrations: $failed\n";
} else {
    echo "Error fetching reports: " . $conn->error . "\n";
} 