<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| SECURITY
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "applicant"
) {
    header("Location: ../login.php");
    exit;
}


$user_id = $_SESSION["user_id"];

$full_name = $_SESSION["full_name"] ?? "Applicant";


/*
|--------------------------------------------------------------------------
| GET APPLICANT
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM applicants
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$applicant = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$applicant) {
    header("Location: profile.php");
    exit;
}


$applicant_id = $applicant["id"];


/*
|--------------------------------------------------------------------------
| GET APPLICATION
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM applications
    WHERE applicant_id = ?
    LIMIT 1
");

$stmt->execute([$applicant_id]);

$application = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CREATE APPLICATION IF NONE EXISTS
|--------------------------------------------------------------------------
*/

if (!$application) {

    do {

        $application_number =
            "BAS-" .
            date("Y") .
            "-" .
            strtoupper(substr(uniqid(), -6));

        $check = $pdo->prepare("
            SELECT id
            FROM applications
            WHERE application_number = ?
        ");

        $check->execute([$application_number]);

    } while ($check->fetch());


    $current_year = date("Y");
    $next_year = $current_year + 1;

    $academic_year =
        $current_year . "/" . $next_year;


    $stmt = $pdo->prepare("
        INSERT INTO applications
        (
            applicant_id,
            application_number,
            academic_year,
            status,
            correction_allowed
        )
        VALUES (?, ?, ?, 'draft', 1)
    ");

    $stmt->execute([
        $applicant_id,
        $application_number,
        $academic_year
    ]);


    $application_id = $pdo->lastInsertId();


    $stmt = $pdo->prepare("
        SELECT *
        FROM applications
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$application_id]);

    $application = $stmt->fetch(PDO::FETCH_ASSOC);
}


$application_id = $application["id"];


$status = strtolower(
    trim($application["status"] ?? "draft")
);


/*
|--------------------------------------------------------------------------
| CORRECTION ALLOWED
|--------------------------------------------------------------------------
|
| This value controls whether a rejected application
| can be corrected and submitted again.
|
| 1 = correction allowed
| 0 = correction NOT allowed
|
*/

$correction_allowed = (int)(
    $application["correction_allowed"] ?? 0
);


/*
|--------------------------------------------------------------------------
| GET ADMIN REMARKS
|--------------------------------------------------------------------------
*/

$admin_remarks = trim(
    $application["admin_remarks"] ?? ""
);


/*
|--------------------------------------------------------------------------
| CHECK EDUCATION
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM application_education
    WHERE application_id = ?
    LIMIT 1
");

$stmt->execute([$application_id]);

$education_completed = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CHECK GUARDIAN
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM application_guardians
    WHERE application_id = ?
    LIMIT 1
");

$stmt->execute([$application_id]);

$guardian_completed = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CHECK FINANCIAL
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM application_financial
    WHERE application_id = ?
    LIMIT 1
");

$stmt->execute([$application_id]);

$financial_completed = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CHECK CIRCUMSTANCES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM application_circumstances
    WHERE application_id = ?
    LIMIT 1
");

$stmt->execute([$application_id]);

$circumstances_completed = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CHECK REQUIRED DOCUMENTS
|--------------------------------------------------------------------------
*/

$required_document_types = [
    "National ID / Birth Certificate",
    "Admission Letter",
    "Fee Structure"
];

$placeholders = implode(
    ",",
    array_fill(
        0,
        count($required_document_types),
        "?"
    )
);

$stmt = $pdo->prepare("
    SELECT document_type
    FROM application_documents
    WHERE application_id = ?
    AND document_type IN ($placeholders)
    GROUP BY document_type
");

$stmt->execute(
    array_merge(
        [$application_id],
        $required_document_types
    )
);

$uploaded_required_documents =
    $stmt->fetchAll(PDO::FETCH_COLUMN);

$documents_completed =
    count($uploaded_required_documents)
    === count($required_document_types);


/*
|--------------------------------------------------------------------------
| DETERMINE WHETHER APPLICANT CAN EDIT
|--------------------------------------------------------------------------
|
| Draft:
| Applicant can edit.
|
| Correction Required:
| Applicant can edit.
|
| Rejected:
| Applicant can ONLY edit if correction_allowed = 1.
|
| Submitted / Verification / Under Review / Approved /
| Awarded / Paid:
| Application is locked.
|
*/

$can_edit = false;


/*
|--------------------------------------------------------------------------
| DRAFT
|--------------------------------------------------------------------------
*/

if ($status === "draft") {

    $can_edit = true;
}


/*
|--------------------------------------------------------------------------
| CORRECTION REQUIRED
|--------------------------------------------------------------------------
*/

if ($status === "correction_required") {

    $can_edit = true;
}


/*
|--------------------------------------------------------------------------
| REJECTED
|--------------------------------------------------------------------------
*/

if (
    $status === "rejected" &&
    $correction_allowed === 1
) {

    $can_edit = true;
}


/*
|--------------------------------------------------------------------------
| STATUS DISPLAY
|--------------------------------------------------------------------------
*/

$status_class = "secondary";
$status_text = "Draft";

switch ($status) {

    case "draft":

        $status_class = "secondary";
        $status_text = "Draft";

        break;


    case "submitted":

        $status_class = "success";
        $status_text = "Submitted";

        break;


    case "verification":

        $status_class = "warning";
        $status_text = "Document Verification";

        break;


    case "under_review":

        $status_class = "info";
        $status_text = "Under Review";

        break;


    case "correction_required":

        $status_class = "warning";
        $status_text = "Correction Required";

        break;


    case "approved":

        $status_class = "primary";
        $status_text = "Approved";

        break;


    case "rejected":

        $status_class = "danger";

        if ($correction_allowed === 1) {

            $status_text = "Rejected - Correction Allowed";

        } else {

            $status_text = "Rejected - Final";

        }

        break;


    case "awarded":

        $status_class = "success";
        $status_text = "Awarded";

        break;


    case "paid":

        $status_class = "success";
        $status_text = "Paid";

        break;
}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        My Application - Bursary System
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        .status-banner {

            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;

        }


        .correction-banner {

            background: #fff3cd;
            border: 1px solid #ffecb5;
            color: #664d03;

        }


        .rejected-banner {

            background: #f8d7da;
            border: 1px solid #f5c2c7;
            color: #842029;

        }


        .rejected-correction-banner {

            background: #fff3cd;
            border: 1px solid #ffecb5;
            color: #664d03;

        }


        .locked-banner {

            background: #e9ecef;
            border: 1px solid #dee2e6;
            color: #495057;

        }


        .section-disabled {

            opacity: .65;

        }

    </style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar navbar-light bg-white shadow-sm">

    <div class="container">

        <a
            href="dashboard.php"
            class="navbar-brand fw-bold"
        >
            BURSARY APPLICATION SYSTEM
        </a>


        <div>

            <span class="me-3">

                <?= htmlspecialchars($full_name) ?>

            </span>


            <a
                href="../logout.php"
                class="btn btn-outline-danger btn-sm"
            >
                Logout
            </a>

        </div>

    </div>

</nav>



<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-9">


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div class="mb-4">

                <h2 class="fw-bold">
                    My Bursary Application
                </h2>

                <p class="text-muted">
                    Complete each section before submitting your application.
                </p>

            </div>



            <!-- =================================================
                 CORRECTION REQUIRED MESSAGE
            ================================================== -->

            <?php if ($status === "correction_required"): ?>

                <div class="status-banner correction-banner">

                    <h5 class="fw-bold">

                        ⚠️ Correction Required

                    </h5>


                    <p class="mb-2">

                        The bursary administrator has requested
                        corrections to your application.

                    </p>


                    <?php if ($admin_remarks !== ""): ?>

                        <div class="mt-3">

                            <strong>
                                Administrator's remarks:
                            </strong>


                            <div class="mt-2 p-3 bg-white rounded">

                                <?= nl2br(
                                    htmlspecialchars($admin_remarks)
                                ) ?>

                            </div>

                        </div>

                    <?php endif; ?>


                    <p class="mb-0 mt-3">

                        Please correct the required information below
                        and submit your application again.

                    </p>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 REJECTED MESSAGE
            ================================================== -->

            <?php if ($status === "rejected"): ?>


                <?php if ($correction_allowed === 1): ?>

                    <!-- REJECTED BUT CORRECTION ALLOWED -->

                    <div class="status-banner rejected-correction-banner">

                        <h5 class="fw-bold">

                            ❌ Application Rejected —
                            Correction Allowed

                        </h5>


                        <p class="mb-2">

                            Your application was rejected by the
                            bursary administrator, but you have been
                            allowed to correct the identified issues.

                        </p>


                        <?php if ($admin_remarks !== ""): ?>

                            <div class="mt-3">

                                <strong>
                                    Administrator's remarks:
                                </strong>


                                <div class="mt-2 p-3 bg-white rounded">

                                    <?= nl2br(
                                        htmlspecialchars($admin_remarks)
                                    ) ?>

                                </div>

                            </div>

                        <?php endif; ?>


                        <p class="mb-0 mt-3">

                            Please correct the issues identified by
                            the administrator and submit the application
                            again.

                        </p>

                    </div>


                <?php else: ?>

                    <!-- FINAL REJECTION -->

                    <div class="status-banner rejected-banner">

                        <h5 class="fw-bold">

                            ❌ Application Rejected — Final Decision

                        </h5>


                        <p class="mb-2">

                            Your application has been rejected and
                            corrections are not allowed for this
                            application.

                        </p>


                        <?php if ($admin_remarks !== ""): ?>

                            <div class="mt-3">

                                <strong>
                                    Administrator's remarks:
                                </strong>


                                <div class="mt-2 p-3 bg-white rounded">

                                    <?= nl2br(
                                        htmlspecialchars($admin_remarks)
                                    ) ?>

                                </div>

                            </div>

                        <?php endif; ?>


                        <p class="mb-0 mt-3">

                            This decision is final for this application.
                            Please refer to the administrator's remarks
                            for more information.

                        </p>

                    </div>

                <?php endif; ?>


            <?php endif; ?>



            <!-- =================================================
                 LOCKED APPLICATION MESSAGE
            ================================================== -->

            <?php if (!$can_edit && $status !== "draft"): ?>

                <div class="status-banner locked-banner">

                    <strong>

                        🔒 Application Locked

                    </strong>


                    <p class="mb-0 mt-2">

                        Your application is currently

                        <strong>

                            <?= htmlspecialchars($status_text) ?>

                        </strong>

                        and cannot be edited at this stage.

                    </p>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 APPLICATION SUMMARY
            ================================================== -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <div class="row">


                        <div class="col-md-4 mb-3">

                            <small class="text-muted">
                                Application Number
                            </small>


                            <h5 class="mb-0">

                                <?= htmlspecialchars(
                                    $application["application_number"]
                                ) ?>

                            </h5>

                        </div>



                        <div class="col-md-4 mb-3">

                            <small class="text-muted">
                                Academic Year
                            </small>


                            <h5 class="mb-0">

                                <?= htmlspecialchars(
                                    $application["academic_year"]
                                ) ?>

                            </h5>

                        </div>



                        <div class="col-md-4 mb-3">

                            <small class="text-muted">
                                Status
                            </small>


                            <div>

                                <span
                                    class="badge bg-<?= $status_class ?>"
                                >

                                    <?= htmlspecialchars(
                                        $status_text
                                    ) ?>

                                </span>

                            </div>

                        </div>


                    </div>

                </div>

            </div>



            <!-- =================================================
                 APPLICATION SECTIONS
            ================================================== -->

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Application Sections
                    </h5>

                </div>


                <div class="list-group list-group-flush">


                    <!-- =================================================
                         PERSONAL INFORMATION
                    ================================================== -->

                    <div class="list-group-item d-flex justify-content-between align-items-center">

                        <div>

                            <h6 class="mb-1">
                                Personal Information
                            </h6>

                            <small class="text-muted">
                                Review your personal details.
                            </small>

                        </div>


                        <span class="badge bg-success">
                            Completed
                        </span>

                    </div>



                    <!-- =================================================
                         EDUCATION
                    ================================================== -->

                    <div class="list-group-item d-flex justify-content-between align-items-center">

                        <div>

                            <h6 class="mb-1">
                                Education
                            </h6>


                            <small class="text-muted">

                                <?php if ($education_completed): ?>

                                    Education information has been provided.

                                <?php else: ?>

                                    Provide your education details.

                                <?php endif; ?>

                            </small>

                        </div>


                        <div>

                            <?php if ($education_completed): ?>

                                <span class="badge bg-success me-2">

                                    Completed

                                </span>

                            <?php endif; ?>


                            <?php if ($can_edit): ?>

                                <a
                                    href="education.php"
                                    class="btn btn-sm btn-outline-primary"
                                >

                                    <?= $education_completed
                                        ? "Edit"
                                        : "Open"
                                    ?>

                                </a>

                            <?php endif; ?>

                        </div>

                    </div>



                    <!-- =================================================
                         GUARDIAN
                    ================================================== -->

                    <div class="list-group-item d-flex justify-content-between align-items-center">

                        <div>

                            <h6 class="mb-1">
                                Guardian Information
                            </h6>


                            <small class="text-muted">

                                <?php if ($guardian_completed): ?>

                                    Guardian information has been provided.

                                <?php else: ?>

                                    Provide parent or guardian information.

                                <?php endif; ?>

                            </small>

                        </div>


                        <div>

                            <?php if ($guardian_completed): ?>

                                <span class="badge bg-success me-2">

                                    Completed

                                </span>

                            <?php endif; ?>


                            <?php if ($can_edit): ?>

                                <a
                                    href="guardian.php"
                                    class="btn btn-sm btn-outline-primary"
                                >

                                    <?= $guardian_completed
                                        ? "Edit"
                                        : "Open"
                                    ?>

                                </a>

                            <?php endif; ?>

                        </div>

                    </div>



                    <!-- =================================================
                         FINANCIAL
                    ================================================== -->

                    <div class="list-group-item d-flex justify-content-between align-items-center">

                        <div>

                            <h6 class="mb-1">
                                Financial Information
                            </h6>


                            <small class="text-muted">

                                <?php if ($financial_completed): ?>

                                    Financial information has been provided.

                                <?php else: ?>

                                    Provide your household financial details.

                                <?php endif; ?>

                            </small>

                        </div>


                        <div>

                            <?php if ($financial_completed): ?>

                                <span class="badge bg-success me-2">

                                    Completed

                                </span>

                            <?php endif; ?>


                            <?php if ($can_edit): ?>

                                <a
                                    href="financial.php"
                                    class="btn btn-sm btn-outline-primary"
                                >

                                    <?= $financial_completed
                                        ? "Edit"
                                        : "Open"
                                    ?>

                                </a>

                            <?php endif; ?>

                        </div>

                    </div>



                    <!-- =================================================
                         SPECIAL CIRCUMSTANCES
                    ================================================== -->

                    <div class="list-group-item d-flex align-items-center">

                        <div>

                            <h6 class="mb-1">
                                Special Circumstances
                            </h6>


                            <small class="text-muted">

                                <?php if ($circumstances_completed): ?>

                                    Special circumstances information
                                    has been provided.

                                <?php else: ?>

                                    Tell us about circumstances affecting
                                    your education.

                                <?php endif; ?>

                            </small>

                        </div>


                        <div class="ms-auto">

                            <?php if ($circumstances_completed): ?>

                                <span class="badge bg-success me-2">

                                    Completed

                                </span>

                            <?php endif; ?>


                            <?php if ($can_edit): ?>

                                <a
                                    href="circumstances.php"
                                    class="btn btn-sm btn-outline-primary"
                                >

                                    <?= $circumstances_completed
                                        ? "Edit"
                                        : "Open"
                                    ?>

                                </a>

                            <?php endif; ?>

                        </div>

                    </div>



                    <!-- =================================================
                         DOCUMENTS
                    ================================================== -->

                    <div class="list-group-item d-flex align-items-center">

                        <div>

                            <h6 class="mb-1">
                                Documents
                            </h6>


                            <small class="text-muted">

                                <?php if ($documents_completed): ?>

                                    All required documents have been uploaded.

                                <?php else: ?>

                                    Upload the documents required to
                                    support your application.

                                <?php endif; ?>

                            </small>

                        </div>


                        <div class="ms-auto">

                            <?php if ($documents_completed): ?>

                                <span class="badge bg-success me-2">

                                    Completed

                                </span>

                            <?php endif; ?>


                            <?php if ($can_edit): ?>

                                <a
                                    href="documents.php"
                                    class="btn btn-sm btn-outline-primary"
                                >

                                    <?= $documents_completed
                                        ? "Manage"
                                        : "Open"
                                    ?>

                                </a>

                            <?php endif; ?>

                        </div>

                    </div>


                </div>

            </div>



            <!-- =================================================
                 REJECTED FINAL NOTICE
            ================================================== -->

            <?php if (
                $status === "rejected" &&
                $correction_allowed === 0
            ): ?>

                <div class="card shadow-sm border-danger mt-4">

                    <div class="card-body">

                        <h5 class="text-danger">

                            🔒 Correction Not Available

                        </h5>


                        <p class="mb-0 text-muted">

                            The administrator has marked this rejection
                            as final. The application cannot be edited
                            or resubmitted.

                        </p>

                    </div>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 REVIEW & SUBMIT
            ================================================== -->

            <?php if ($can_edit): ?>

                <div class="card shadow-sm border-0 mt-4">

                    <div class="card-body">

                        <h5>
                            Review & Submit
                        </h5>


                        <p class="text-muted">

                            Review all your information before submitting
                            your application.

                        </p>


                        <a
                            href="review.php"
                            class="btn btn-primary"
                        >

                            🔍 Review Application

                        </a>

                    </div>

                </div>

            <?php else: ?>

                <div class="card shadow-sm border-0 mt-4">

                    <div class="card-body">

                        <h5>
                            Application Status
                        </h5>


                        <p class="text-muted">

                            Your application is currently

                            <strong>

                                <?= htmlspecialchars($status_text) ?>

                            </strong>.

                            Editing is disabled at this stage.

                        </p>


                        <a
                            href="application_view.php"
                            class="btn btn-outline-primary"
                        >

                            View Application

                        </a>

                    </div>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 BACK TO DASHBOARD
            ================================================== -->

            <div class="mt-4">

                <a
                    href="dashboard.php"
                    class="btn btn-outline-secondary"
                >

                    ← Back to Dashboard

                </a>

            </div>


        </div>

    </div>

</div>


</body>

</html>

