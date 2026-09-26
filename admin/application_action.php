<?php

session_start();

require_once "../config/database.php";
require_once "../sms/sms_helper.php";


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
| ONLY POST REQUESTS
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: applications.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET INPUT
|--------------------------------------------------------------------------
*/

$application_id = isset($_POST["application_id"])
    ? (int) $_POST["application_id"]
    : 0;

$action = trim($_POST["action"] ?? "");

$remarks = trim($_POST["remarks"] ?? "");


/*
|--------------------------------------------------------------------------
| VALIDATE APPLICATION ID
|--------------------------------------------------------------------------
*/

if ($application_id <= 0) {
    die("Invalid application ID.");
}


/*
|--------------------------------------------------------------------------
| VALIDATE ACTION
|--------------------------------------------------------------------------
*/

$allowed_actions = [
    "start_review",
    "request_correction",
    "approve",
    "reject"
];

if (!in_array($action, $allowed_actions, true)) {
    die("Invalid application action.");
}


/*
|--------------------------------------------------------------------------
| GET APPLICATION + APPLICANT + PHONE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        applications.id,
        applications.application_number,
        applications.status,
        applications.admin_remarks,

        applicants.user_id,

        users.full_name,
        users.phone

    FROM applications

    INNER JOIN applicants
        ON applications.applicant_id = applicants.id

    INNER JOIN users
        ON applicants.user_id = users.id

    WHERE applications.id = ?

    LIMIT 1
");

$stmt->execute([
    $application_id
]);

$application = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| APPLICATION NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$application) {
    die("Application not found.");
}


/*
|--------------------------------------------------------------------------
| CURRENT APPLICATION INFORMATION
|--------------------------------------------------------------------------
*/

$current_status = strtolower(
    trim($application["status"] ?? "")
);

$applicant_name = trim(
    $application["full_name"] ?? "Applicant"
);

$applicant_phone = trim(
    $application["phone"] ?? ""
);

$application_number = trim(
    $application["application_number"] ?? ""
);


/*
|--------------------------------------------------------------------------
| SMS NOTIFICATION FUNCTION
|--------------------------------------------------------------------------
|
| Important:
|
| SMS failure must NOT prevent the application status
| from being updated.
|
*/

function notifyApplicant(
    $phone,
    $name,
    $application_number,
    $message
) {

    /*
    |----------------------------------------------------------------------
    | No phone number
    |----------------------------------------------------------------------
    */

    if (trim($phone) === "") {
        return false;
    }


    /*
    |----------------------------------------------------------------------
    | Clean applicant name
    |----------------------------------------------------------------------
    */

    $name = trim($name);

    if ($name === "") {
        $name = "Applicant";
    }


    /*
    |----------------------------------------------------------------------
    | Build SMS
    |----------------------------------------------------------------------
    */

    $sms_message =
        "Bursary System: Dear " .
        $name .
        ", " .
        $message .
        " Application: " .
        $application_number .
        ".";


    /*
    |----------------------------------------------------------------------
    | Send SMS safely
    |----------------------------------------------------------------------
    */

    try {

        return sendSMS(
            $phone,
            $sms_message
        );

    } catch (Throwable $e) {

        /*
        |------------------------------------------------------------------
        | Do not stop application processing if SMS fails.
        |------------------------------------------------------------------
        */

        error_log(
            "SMS notification failed for application " .
            $application_number .
            ": " .
            $e->getMessage()
        );

        return false;
    }
}


/*
|--------------------------------------------------------------------------
| START REVIEW
|--------------------------------------------------------------------------
|
| submitted
| verification
|       ↓
| under_review
|
*/

if ($action === "start_review") {

    /*
    |----------------------------------------------------------------------
    | Validate current status
    |----------------------------------------------------------------------
    */

    if (
        $current_status !== "submitted" &&
        $current_status !== "verification"
    ) {

        die(
            "This application cannot be moved to review from its current status."
        );
    }


    /*
    |----------------------------------------------------------------------
    | Update application status
    |----------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE applications
        SET
            status = 'under_review',
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $application_id
    ]);


    /*
    |----------------------------------------------------------------------
    | Send SMS
    |----------------------------------------------------------------------
    */

    notifyApplicant(
        $applicant_phone,
        $applicant_name,
        $application_number,
        "Your bursary application is now under review."
    );
}


/*
|--------------------------------------------------------------------------
| REQUEST CORRECTION
|--------------------------------------------------------------------------
|
| under_review
|       ↓
| correction_required
|
*/

elseif ($action === "request_correction") {

    /*
    |----------------------------------------------------------------------
    | Validate current status
    |----------------------------------------------------------------------
    */

    if ($current_status !== "under_review") {

        die(
            "Correction can only be requested for applications under review."
        );
    }


    /*
    |----------------------------------------------------------------------
    | Validate remarks
    |----------------------------------------------------------------------
    */

    if ($remarks === "") {

        die(
            "Please provide correction remarks."
        );
    }


    /*
    |----------------------------------------------------------------------
    | Update application
    |----------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE applications
        SET
            status = 'correction_required',
            admin_remarks = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $remarks,
        $application_id
    ]);


    /*
    |----------------------------------------------------------------------
    | Send SMS
    |----------------------------------------------------------------------
    */

    notifyApplicant(
        $applicant_phone,
        $applicant_name,
        $application_number,
        "Corrections are required on your application. Please log in to your account to view the administrator's remarks."
    );
}


/*
|--------------------------------------------------------------------------
| APPROVE APPLICATION
|--------------------------------------------------------------------------
|
| under_review
|       ↓
| approved
|
*/

elseif ($action === "approve") {

    /*
    |----------------------------------------------------------------------
    | Validate current status
    |----------------------------------------------------------------------
    */

    if ($current_status !== "under_review") {

        die(
            "Only applications under review can be approved."
        );
    }


    /*
    |----------------------------------------------------------------------
    | Update application
    |----------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE applications
        SET
            status = 'approved',
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $application_id
    ]);


    /*
    |----------------------------------------------------------------------
    | Send SMS
    |----------------------------------------------------------------------
    */

    notifyApplicant(
        $applicant_phone,
        $applicant_name,
        $application_number,
        "Congratulations! Your bursary application has been approved."
    );
}


/*
|--------------------------------------------------------------------------
| REJECT APPLICATION
|--------------------------------------------------------------------------
|
| under_review
|       ↓
| rejected
|
*/

elseif ($action === "reject") {

    /*
    |----------------------------------------------------------------------
    | Validate current status
    |----------------------------------------------------------------------
    */

    if ($current_status !== "under_review") {

        die(
            "Only applications under review can be rejected."
        );
    }


    /*
    |----------------------------------------------------------------------
    | Validate rejection reason
    |----------------------------------------------------------------------
    */

    if ($remarks === "") {

        die(
            "Please provide a rejection reason."
        );
    }


    /*
    |----------------------------------------------------------------------
    | Update application
    |----------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE applications
        SET
            status = 'rejected',
            admin_remarks = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $remarks,
        $application_id
    ]);


    /*
    |----------------------------------------------------------------------
    | Send SMS
    |----------------------------------------------------------------------
    */

    notifyApplicant(
        $applicant_phone,
        $applicant_name,
        $application_number,
        "Your bursary application has been rejected. Please log in to your account to view the administrator's remarks."
    );
}


/*
|--------------------------------------------------------------------------
| REDIRECT BACK TO APPLICATION
|--------------------------------------------------------------------------
*/

header(
    "Location: view_application.php?id=" .
    $application_id .
    "&success=1"
);

exit;

?>