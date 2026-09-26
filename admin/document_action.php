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
| VALIDATE REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: document_verification.php");
    exit;

}


$document_id = isset($_POST["document_id"])
    ? (int) $_POST["document_id"]
    : 0;

$action = trim($_POST["action"] ?? "");

$remarks = trim($_POST["remarks"] ?? "");


if ($document_id <= 0) {

    die("Invalid document.");

}


/*
|--------------------------------------------------------------------------
| VALIDATE ACTION
|--------------------------------------------------------------------------
*/

if (!in_array($action, ["verify", "reject"], true)) {

    die("Invalid verification action.");

}


/*
|--------------------------------------------------------------------------
| REJECTION MUST HAVE A REASON
|--------------------------------------------------------------------------
*/

if (
    $action === "reject" &&
    $remarks === ""
) {

    die(
        "A reason is required when rejecting a document."
    );

}


/*
|--------------------------------------------------------------------------
| GET DOCUMENT
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        application_id,
        document_type,
        verification_status
    FROM application_documents
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $document_id
]);

$document = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$document) {

    die("Document not found.");

}


$application_id = (int) $document["application_id"];


/*
|--------------------------------------------------------------------------
| START DATABASE TRANSACTION
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | VERIFY DOCUMENT
    |--------------------------------------------------------------------------
    */

    if ($action === "verify") {


        $stmt = $pdo->prepare("
            UPDATE application_documents
            SET
                verification_status = 'verified',
                verification_remarks = ?,
                verified_at = NOW(),
                verified_by = ?
            WHERE id = ?
        ");


        $stmt->execute([

            $remarks !== ""
                ? $remarks
                : null,

            $_SESSION["user_id"],

            $document_id

        ]);


        /*
        |--------------------------------------------------------------------------
        | CHECK IF ALL DOCUMENTS ARE VERIFIED
        |--------------------------------------------------------------------------
        |
        | If there are no pending/rejected documents remaining,
        | the application can proceed to admin review.
        |
        */

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM application_documents
            WHERE application_id = ?
              AND verification_status != 'verified'
        ");

        $stmt->execute([
            $application_id
        ]);

        $unverified_count = (int) $stmt->fetchColumn();


        /*
        |--------------------------------------------------------------------------
        | IF ALL DOCUMENTS VERIFIED
        |--------------------------------------------------------------------------
        */

        if ($unverified_count === 0) {

            /*
            | Only move submitted applications forward.
            | Do not accidentally change approved/rejected applications.
            */

            $stmt = $pdo->prepare("
                UPDATE applications
                SET status = 'under_review'
                WHERE id = ?
                  AND status = 'submitted'
            ");

            $stmt->execute([
                $application_id
            ]);

        }


    /*
    |--------------------------------------------------------------------------
    | REJECT DOCUMENT
    |--------------------------------------------------------------------------
    */

    } elseif ($action === "reject") {


        /*
        |--------------------------------------------------------------------------
        | SAVE REJECTION
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            UPDATE application_documents
            SET
                verification_status = 'rejected',
                verification_remarks = ?,
                verified_at = NOW(),
                verified_by = ?
            WHERE id = ?
        ");


        $stmt->execute([

            $remarks,

            $_SESSION["user_id"],

            $document_id

        ]);


        /*
        |--------------------------------------------------------------------------
        | CHANGE APPLICATION STATUS TO CORRECTION
        |--------------------------------------------------------------------------
        |
        | This tells the applicant that something needs to be corrected.
        |
        */

        $stmt = $pdo->prepare("
            UPDATE applications
            SET status = 'correction_required'
            WHERE id = ?
              AND status NOT IN (
                  'approved',
                  'rejected'
              )
        ");

        $stmt->execute([
            $application_id
        ]);

    }


    /*
    |--------------------------------------------------------------------------
    | COMMIT TRANSACTION
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


} catch (PDOException $e) {


    /*
    |--------------------------------------------------------------------------
    | ROLLBACK IF SOMETHING FAILS
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    /*
    |--------------------------------------------------------------------------
    | DISPLAY ERROR
    |--------------------------------------------------------------------------
    */

    die(
        "An error occurred while updating the document: "
        . htmlspecialchars($e->getMessage())
    );

}


/*
|--------------------------------------------------------------------------
| REDIRECT BACK
|--------------------------------------------------------------------------
*/

header(
    "Location: verify_document.php?id="
    . $document_id
    . "&success=1"
);

exit;