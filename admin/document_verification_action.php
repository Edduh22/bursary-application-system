<?php

session_start();

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| ADMIN SECURITY
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| REQUEST METHOD
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: document_verification.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET POST DATA
|--------------------------------------------------------------------------
*/

$document_id = isset($_POST["document_id"])
    ? (int) $_POST["document_id"]
    : 0;

$action = trim($_POST["action"] ?? "");

$remarks = trim($_POST["remarks"] ?? "");


/*
|--------------------------------------------------------------------------
| VALIDATE DOCUMENT ID
|--------------------------------------------------------------------------
*/

if ($document_id <= 0) {

    header(
        "Location: document_verification.php?error="
        . urlencode("Invalid document ID.")
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDATE ACTION
|--------------------------------------------------------------------------
*/

$allowed_actions = [
    "verify",
    "reject"
];

if (!in_array($action, $allowed_actions, true)) {

    header(
        "Location: verify_document.php?id="
        . $document_id
        . "&error="
        . urlencode("Invalid verification action.")
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| FETCH DOCUMENT
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        application_id,
        verification_status
    FROM application_documents
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $document_id
]);

$document = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| DOCUMENT NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$document) {

    header(
        "Location: document_verification.php?error="
        . urlencode("Document not found.")
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| DETERMINE NEW STATUS
|--------------------------------------------------------------------------
*/

if ($action === "verify") {

    $new_status = "verified";

    if ($remarks === "") {

        $remarks = "Document verified successfully.";

    }

} elseif ($action === "reject") {

    $new_status = "rejected";

    if ($remarks === "") {

        header(
            "Location: verify_document.php?id="
            . $document_id
            . "&error="
            . urlencode(
                "Please provide a reason for rejecting the document."
            )
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE DOCUMENT
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        UPDATE application_documents

        SET
            verification_status = ?,
            verification_remarks = ?,
            verified_at = NOW(),
            verified_by = ?

        WHERE id = ?
    ");

    $stmt->execute([
        $new_status,
        $remarks,
        (int) $_SESSION["user_id"],
        $document_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | REDIRECT SUCCESS
    |--------------------------------------------------------------------------
    */

    header(
        "Location: verify_document.php?id="
        . $document_id
        . "&success=1"
    );

    exit;


} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | DATABASE ERROR
    |--------------------------------------------------------------------------
    |
    | Do not expose database details to the administrator.
    |
    */

    header(
        "Location: verify_document.php?id="
        . $document_id
        . "&error="
        . urlencode(
            "Unable to update document verification status."
        )
    );

    exit;
}