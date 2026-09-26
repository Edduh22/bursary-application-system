<?php

require_once "../config/database.php";

try {

    $sql = "
        CREATE TABLE IF NOT EXISTS payments (

            id INT AUTO_INCREMENT PRIMARY KEY,

            application_id INT NOT NULL,

            award_id INT NOT NULL,

            payment_amount DECIMAL(12,2) NOT NULL,

            payment_date DATE NOT NULL,

            payment_method VARCHAR(50) DEFAULT NULL,

            transaction_reference VARCHAR(100) DEFAULT NULL,

            payment_status VARCHAR(30) NOT NULL DEFAULT 'pending',

            remarks TEXT DEFAULT NULL,

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,

            INDEX (application_id),
            INDEX (award_id),
            INDEX (payment_status)

        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    $pdo->exec($sql);

    echo "<h2 style='color:green;'>✓ Payments table created successfully.</h2>";

    echo "<p>You can now proceed with the Payments module.</p>";

} catch (PDOException $e) {

    echo "<h2 style='color:red;'>✗ Failed to create payments table.</h2>";

    echo "<pre>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
}