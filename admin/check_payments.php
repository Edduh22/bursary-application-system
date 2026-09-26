<?php

session_start();

require_once "../config/database.php";

echo "<h2>Database Test</h2>";

try {

    echo "<p style='color:green;'>✓ Database connection works.</p>";

    // Check if payments table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'payments'");

    $table = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$table) {

        echo "<p style='color:red;'>✗ The payments table does NOT exist.</p>";

        exit;
    }

    echo "<p style='color:green;'>✓ Payments table exists.</p>";

    // Show columns
    $stmt = $pdo->query("DESCRIBE payments");

    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Payments Table Structure</h3>";

    echo "<table border='1' cellpadding='8' cellspacing='0'>";

    echo "<tr>";
    echo "<th>Field</th>";
    echo "<th>Type</th>";
    echo "<th>Null</th>";
    echo "<th>Key</th>";
    echo "<th>Default</th>";
    echo "</tr>";

    foreach ($columns as $column) {

        echo "<tr>";

        echo "<td>" . htmlspecialchars($column["Field"]) . "</td>";
        echo "<td>" . htmlspecialchars($column["Type"]) . "</td>";
        echo "<td>" . htmlspecialchars($column["Null"]) . "</td>";
        echo "<td>" . htmlspecialchars($column["Key"]) . "</td>";
        echo "<td>" . htmlspecialchars($column["Default"] ?? "NULL") . "</td>";

        echo "</tr>";
    }

    echo "</table>";

} catch (PDOException $e) {

    echo "<p style='color:red;'>Database error:</p>";

    echo "<pre>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
}