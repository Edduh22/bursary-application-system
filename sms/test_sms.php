<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "sms_helper.php";

$phone = "YOUR_PHONE_NUMBER";

$message = "Bursary System test: SMS notifications are working successfully.";

echo "<h3>SMS Test</h3>";

echo "<p><strong>Sending to:</strong> "
    . htmlspecialchars($phone)
    . "</p>";

try {

    $result = sendSMS($phone, $message);

    echo "<h4>Raw Result:</h4>";

    echo "<pre>";
    var_dump($result);
    echo "</pre>";

} catch (Throwable $e) {

    echo "<div style='color:red;'>";

    echo "<h4>ERROR</h4>";

    echo "<p>";
    echo htmlspecialchars($e->getMessage());
    echo "</p>";

    echo "<pre>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";

    echo "</div>";
}

?>