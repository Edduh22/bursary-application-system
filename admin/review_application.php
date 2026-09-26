<?php

session_start();

require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| ADMIN ACCESS CONTROL
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
| GET APPLICATION ID
|--------------------------------------------------------------------------
*/

$application_id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($application_id <= 0) {
    header("Location: applications.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| FETCH APPLICATION
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.applicant_id,
        a.application_number,
        a.academic_year,
        a.status,
        a.admin_remarks,
        a.submitted_at,
        a.created_at,
        a.updated_at,

        ap.national_id,
        ap.date_of_birth,
        ap.gender,
        ap.county,
        ap.constituency,
        ap.ward,

        u.full_name,
        u.email

    FROM applications a

    LEFT JOIN applicants ap
        ON a.applicant_id = ap.id

    LEFT JOIN users u
        ON ap.user_id = u.id

    WHERE a.id = ?

    LIMIT 1
");

$stmt->execute([$application_id]);

$application = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$application) {
    die("Application not found.");
}


/*
|--------------------------------------------------------------------------
| FETCH APPLICATION DOCUMENTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        document_type,
        original_name,
        stored_name,
        file_path,
        file_size,
        mime_type,
        verification_status,
        verification_remarks,
        uploaded_at,
        verified_at
    FROM application_documents
    WHERE application_id = ?
    ORDER BY uploaded_at DESC
");

$stmt->execute([$application_id]);

$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| FORMAT FILE SIZE
|--------------------------------------------------------------------------
*/

function formatFileSize($bytes)
{
    $bytes = (int) $bytes;

    if ($bytes <= 0) {
        return "0 KB";
    }

    if ($bytes < 1024) {
        return $bytes . " B";
    }

    if ($bytes < 1024 * 1024) {
        return number_format($bytes / 1024, 1) . " KB";
    }

    return number_format(
        $bytes / (1024 * 1024),
        1
    ) . " MB";
}


/*
|--------------------------------------------------------------------------
| APPLICATION STATUS BADGE
|--------------------------------------------------------------------------
*/

function applicationStatusBadge($status)
{
    switch ($status) {

        case "submitted":

            return '
                <span class="badge pending">
                    Pending Verification
                </span>
            ';


        case "verification":

            return '
                <span class="badge verification">
                    Document Verification
                </span>
            ';


        case "under_review":

            return '
                <span class="badge review">
                    Under Review
                </span>
            ';


        case "approved":

            return '
                <span class="badge approved">
                    Approved
                </span>
            ';


        case "rejected":

            return '
                <span class="badge rejected">
                    Rejected
                </span>
            ';


        case "correction_required":

            return '
                <span class="badge correction">
                    Correction Required
                </span>
            ';


        case "draft":

            return '
                <span class="badge draft">
                    Draft
                </span>
            ';


        default:

            return '
                <span class="badge neutral">
                    ' .
                    htmlspecialchars(
                        ucwords(
                            str_replace(
                                "_",
                                " ",
                                $status
                            )
                        )
                    )
                    . '
                </span>
            ';
    }
}


/*
|--------------------------------------------------------------------------
| DOCUMENT STATUS BADGE
|--------------------------------------------------------------------------
*/

function documentStatusBadge($status)
{
    switch ($status) {

        case "verified":

            return '
                <span class="badge verified">
                    ✓ Verified
                </span>
            ';


        case "rejected":

            return '
                <span class="badge rejected">
                    ✕ Rejected
                </span>
            ';


        case "pending":

            return '
                <span class="badge pending">
                    ⏳ Pending
                </span>
            ';


        default:

            return '
                <span class="badge neutral">
                    ' .
                    htmlspecialchars(
                        ucfirst(
                            str_replace(
                                "_",
                                " ",
                                $status
                            )
                        )
                    )
                    . '
                </span>
            ';
    }
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
        Review Application |
        <?= htmlspecialchars(
            $application["application_number"]
        ) ?>
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <style>

        body {

            background: #f5f7fb;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

        }


        /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

        .sidebar {

            width: 250px;

            position: fixed;

            top: 0;
            bottom: 0;
            left: 0;

            background: #09234d;

            color: white;

            padding: 25px 15px;

            overflow-y: auto;

        }


        .logo {

            padding:
                5px 10px 25px;

            border-bottom:
                1px solid
                rgba(255,255,255,.15);

            margin-bottom: 20px;

        }


        .logo h4 {

            margin: 0;

            font-size: 18px;

        }


        .logo small {

            color: #aebed5;

        }


        .sidebar a {

            display: block;

            padding: 12px 15px;

            margin-bottom: 6px;

            color: #dbe6f5;

            text-decoration: none;

            border-radius: 7px;

        }


        .sidebar a:hover,
        .sidebar a.active {

            background: #1769d1;

            color: white;

        }


        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .content {

            margin-left: 250px;

            padding: 30px;

        }


        /*
        |--------------------------------------------------------------------------
        | TOPBAR
        |--------------------------------------------------------------------------
        */

        .topbar {

            background: white;

            padding: 20px 25px;

            border-radius: 10px;

            margin-bottom: 25px;

            border:
                1px solid
                #e6eaf0;

        }


        .topbar h2 {

            margin: 0;

            font-size: 24px;

        }


        .topbar p {

            margin:
                5px 0 0;

            color: #748094;

        }


        /*
        |--------------------------------------------------------------------------
        | CARDS
        |--------------------------------------------------------------------------
        */

        .card {

            border:
                1px solid
                #e6eaf0;

            border-radius: 10px;

        }


        .section-title {

            font-weight: 700;

            border-bottom:
                2px solid
                #edf0f4;

            padding-bottom: 12px;

            margin-bottom: 20px;

        }


        /*
        |--------------------------------------------------------------------------
        | INFORMATION
        |--------------------------------------------------------------------------
        */

        .info-label {

            font-size: 12px;

            color: #687386;

            font-weight: 600;

            margin-bottom: 5px;

        }


        .info-value {

            font-size: 15px;

            margin-bottom: 18px;

        }


        /*
        |--------------------------------------------------------------------------
        | BADGES
        |--------------------------------------------------------------------------
        */

        .badge {

            padding:
                8px 12px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 600;

        }


        .pending {

            background: #fff3cd;

            color: #8a6500;

        }


        .verification {

            background: #dff1ff;

            color: #075985;

        }


        .review {

            background: #eee5ff;

            color: #6541a5;

        }


        .approved {

            background: #dff5e6;

            color: #18753a;

        }


        .rejected {

            background: #ffe1e3;

            color: #b4232d;

        }


        .correction {

            background: #fff0dc;

            color: #a35b00;

        }


        .draft {

            background: #e9ecef;

            color: #495057;

        }


        .verified {

            background: #dff5e6;

            color: #18753a;

        }


        .neutral {

            background: #e9ecef;

            color: #495057;

        }


        /*
        |--------------------------------------------------------------------------
        | DOCUMENT ROW
        |--------------------------------------------------------------------------
        */

        .document-row {

            border:
                1px solid
                #e6eaf0;

            border-radius: 8px;

            padding: 15px;

            margin-bottom: 12px;

            background: #fff;

        }


        .document-name {

            font-weight: 600;

            color: #1d3557;

        }


        /*
        |--------------------------------------------------------------------------
        | ACTION CARD
        |--------------------------------------------------------------------------
        */

        .action-card {

            position: sticky;

            top: 20px;

        }


        /*
        |--------------------------------------------------------------------------
        | REMARKS
        |--------------------------------------------------------------------------
        */

        .remarks-box {

            background: #f8f9fa;

            border-radius: 8px;

            padding: 15px;

            white-space: normal;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 900px) {

            .sidebar {

                width: 210px;

            }

            .content {

                margin-left: 210px;

                padding: 20px;

            }

        }


        @media (max-width: 700px) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;

            }

            .content {

                margin-left: 0;

            }

        }

    </style>

</head>


<body>


<!-- ==========================================================
     SIDEBAR
=========================================================== -->

<div class="sidebar">


    <div class="logo">

        <h4>
            🎓 BURSARY SYSTEM
        </h4>

        <small>
            Administrator Panel
        </small>

    </div>


    <a href="dashboard.php">

        🏠 Dashboard

    </a>


    <a href="applications.php">

        📋 All Applications

    </a>


    <a href="document_verification.php">

        📄 Document Verification

    </a>


    <a
        href="review_application.php"
        class="active"
    >

        🔍 Pending Review

    </a>


    <a href="awards.php">

        🏆 Awards

    </a>


    <a href="payments.php">

        💳 Payments

    </a>


    <a href="applicants.php">

        👥 Applicants

    </a>


    <a href="reports.php">

        📊 Reports

    </a>


    <a href="users.php">

        👤 Users & Roles

    </a>


    <a href="settings.php">

        ⚙️ Settings

    </a>


    <div
        style="
            position:absolute;
            bottom:20px;
            left:15px;
            right:15px;
        "
    >

        <a href="../logout.php">

            🚪 Logout

        </a>

    </div>


</div>



<!-- ==========================================================
     MAIN CONTENT
=========================================================== -->

<div class="content">


    <!-- HEADER -->

    <div class="topbar">

        <h2>

            🔍 Application Review

        </h2>

        <p>

            Reviewing application

            <strong>

                <?= htmlspecialchars(
                    $application["application_number"]
                ) ?>

            </strong>

        </p>

    </div>



    <!-- BACK -->

    <a
        href="applications.php"
        class="btn btn-outline-secondary mb-4"
    >

        ← Back to Applications

    </a>



    <div class="row g-4">


        <!-- ==================================================
             LEFT COLUMN
        =================================================== -->

        <div class="col-lg-8">


            <!-- APPLICATION INFORMATION -->

            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <h5 class="section-title">

                        📋 Application Information

                    </h5>


                    <div class="row">


                        <div class="col-md-6">

                            <div class="info-label">

                                Application Number

                            </div>

                            <div class="info-value">

                                <strong>

                                    <?= htmlspecialchars(
                                        $application[
                                            "application_number"
                                        ]
                                    ) ?>

                                </strong>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-label">

                                Academic Year

                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "academic_year"
                                    ]
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-label">

                                Application Status

                            </div>

                            <div class="info-value">

                                <?= applicationStatusBadge(
                                    $application["status"]
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-label">

                                Submitted At

                            </div>

                            <div class="info-value">

                                <?php if (
                                    !empty(
                                        $application["submitted_at"]
                                    )
                                ): ?>

                                    <?= date(
                                        "d M Y H:i",
                                        strtotime(
                                            $application[
                                                "submitted_at"
                                            ]
                                        )
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-muted">

                                        Not submitted

                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                    </div>

                </div>

            </div>



            <!-- APPLICANT DETAILS -->

            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <h5 class="section-title">

                        👤 Applicant Information

                    </h5>


                    <div class="row">


                        <div class="col-md-6">

                            <div class="info-label">

                                Full Name

                            </div>

                            <div class="info-value">

                                <strong>

                                    <?= htmlspecialchars(
                                        $application[
                                            "full_name"
                                        ] ?? "Not provided"
                                    ) ?>

                                </strong>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-label">

                                Email

                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "email"
                                    ] ?? "Not provided"
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-label">

                                National ID

                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "national_id"
                                    ] ?? "Not provided"
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-label">

                                Date of Birth

                            </div>

                            <div class="info-value">

                                <?= !empty(
                                    $application[
                                        "date_of_birth"
                                    ]
                                )

                                    ? date(
                                        "d M Y",
                                        strtotime(
                                            $application[
                                                "date_of_birth"
                                            ]
                                        )
                                    )

                                    : "Not provided"
                                ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">

                                Gender

                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "gender"
                                    ] ?? "Not provided"
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">

                                County

                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "county"
                                    ] ?? "Not provided"
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">

                                Constituency

                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "constituency"
                                    ] ?? "Not provided"
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-label">

                                Ward

                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "ward"
                                    ] ?? "Not provided"
                                ) ?>

                            </div>

                        </div>


                    </div>

                </div>

            </div>



            <!-- DOCUMENTS -->

            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <h5 class="section-title">

                        📄 Application Documents

                    </h5>


                    <?php if (count($documents) > 0): ?>


                        <?php foreach (
                            $documents
                            as $document
                        ): ?>


                            <div class="document-row">


                                <div
                                    class="d-flex
                                           justify-content-between
                                           align-items-center
                                           flex-wrap
                                           gap-3"
                                >


                                    <div>

                                        <div class="document-name">

                                            📄

                                            <?= htmlspecialchars(
                                                $document[
                                                    "document_type"
                                                ]
                                            ) ?>

                                        </div>


                                        <small
                                            class="text-muted"
                                        >

                                            <?= htmlspecialchars(
                                                $document[
                                                    "original_name"
                                                ]
                                            ) ?>

                                            •

                                            <?= formatFileSize(
                                                $document[
                                                    "file_size"
                                                ]
                                            ) ?>

                                        </small>


                                        <div class="mt-2">

                                            <?= documentStatusBadge(
                                                $document[
                                                    "verification_status"
                                                ]
                                            ) ?>

                                        </div>


                                    </div>


                                    <div>

                                        <a
                                            href="verify_document.php?id=<?= (int)$document["id"] ?>"
                                            class="btn btn-primary btn-sm"
                                        >

                                            👁 Review Document

                                        </a>

                                    </div>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div
                            class="alert alert-warning"
                        >

                            No documents have been uploaded
                            for this application.

                        </div>


                    <?php endif; ?>


                </div>

            </div>



            <!-- ADMIN REMARKS -->

            <?php if (
                !empty(
                    $application["admin_remarks"]
                )
            ): ?>

                <div class="card shadow-sm mb-4">

                    <div class="card-body">

                        <h5 class="section-title">

                            📝 Existing Admin Remarks

                        </h5>


                        <div class="remarks-box">

                            <?= nl2br(
                                htmlspecialchars(
                                    $application[
                                        "admin_remarks"
                                    ]
                                )
                            ) ?>

                        </div>

                    </div>

                </div>

            <?php endif; ?>


        </div>



        <!-- ==================================================
             RIGHT COLUMN
        =================================================== -->

        <div class="col-lg-4">


            <div class="card shadow-sm action-card">

                <div class="card-body">


                    <h5 class="section-title">

                        ⚙️ Review Action

                    </h5>



                    <?php if (
                        isset($_GET["success"])
                    ): ?>

                        <div class="alert alert-success">

                            ✓ Application status updated
                            successfully.

                        </div>

                    <?php endif; ?>



                    <!-- CURRENT STATUS -->

                    <div class="mb-4">

                        <div class="info-label">

                            Current Status

                        </div>

                        <div class="mt-2">

                            <?= applicationStatusBadge(
                                $application["status"]
                            ) ?>

                        </div>

                    </div>



                    <!-- START REVIEW -->

                    <?php if (
                        $application["status"] === "submitted" ||
                        $application["status"] === "verification"
                    ): ?>


                        <form
                            method="POST"
                            action="application_action.php"
                            class="mb-3"
                        >

                            <input
                                type="hidden"
                                name="application_id"
                                value="<?= (int)$application["id"] ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="start_review"
                            >

                            <button
                                type="submit"
                                class="btn btn-warning w-100"
                            >

                                🔍 Start Review

                            </button>

                        </form>


                    <?php endif; ?>



                    <!-- REQUEST CORRECTION -->

                    <?php if (
                        $application["status"] === "under_review"
                    ): ?>


                        <button
                            type="button"
                            class="btn btn-info w-100 mb-3"
                            data-bs-toggle="modal"
                            data-bs-target="#correctionModal"
                        >

                            📄 Request Correction

                        </button>



                        <!-- APPROVE -->

                        <form
                            method="POST"
                            action="application_action.php"
                            class="mb-3"
                        >

                            <input
                                type="hidden"
                                name="application_id"
                                value="<?= (int)$application["id"] ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="approve"
                            >

                            <button
                                type="submit"
                                class="btn btn-success w-100"
                                onclick="
                                    return confirm(
                                        'Are you sure you want to approve this application?'
                                    );
                                "
                            >

                                ✓ Approve Application

                            </button>

                        </form>



                        <!-- REJECT -->

                        <button
                            type="button"
                            class="btn btn-danger w-100"
                            data-bs-toggle="modal"
                            data-bs-target="#rejectModal"
                        >

                            ✕ Reject Application

                        </button>


                    <?php endif; ?>



                    <!-- APPROVED -->

                    <?php if (
                        $application["status"] === "approved"
                    ): ?>

                        <div class="alert alert-success mb-0">

                            ✓ This application has been approved.

                            <br><br>

                            It is ready for the award stage.

                        </div>

                    <?php endif; ?>



                    <!-- REJECTED -->

                    <?php if (
                        $application["status"] === "rejected"
                    ): ?>

                        <div class="alert alert-danger mb-0">

                            ✕ This application has been rejected.

                        </div>

                    <?php endif; ?>



                    <!-- CORRECTION -->

                    <?php if (
                        $application["status"] === "correction_required"
                    ): ?>

                        <div class="alert alert-warning mb-0">

                            📄 Correction has been requested
                            from the applicant.

                        </div>

                    <?php endif; ?>


                </div>

            </div>


        </div>


    </div>


</div>



<!-- ==========================================================
     CORRECTION MODAL
=========================================================== -->

<div
    class="modal fade"
    id="correctionModal"
    tabindex="-1"
>

    <div class="modal-dialog">

        <div class="modal-content">


            <form
                method="POST"
                action="application_action.php"
            >


                <div class="modal-header">

                    <h5 class="modal-title">

                        📄 Request Correction

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">


                    <input
                        type="hidden"
                        name="application_id"
                        value="<?= (int)$application["id"] ?>"
                    >


                    <input
                        type="hidden"
                        name="action"
                        value="request_correction"
                    >


                    <label class="form-label">

                        Correction Required

                    </label>


                    <textarea
                        name="remarks"
                        class="form-control"
                        rows="5"
                        placeholder="Explain what the applicant needs to correct..."
                        required
                    ></textarea>


                </div>


                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >

                        Cancel

                    </button>


                    <button
                        type="submit"
                        class="btn btn-info"
                    >

                        Send Correction Request

                    </button>


                </div>


            </form>


        </div>

    </div>

</div>



<!-- ==========================================================
     REJECTION MODAL
=========================================================== -->

<div
    class="modal fade"
    id="rejectModal"
    tabindex="-1"
>

    <div class="modal-dialog">

        <div class="modal-content">


            <form
                method="POST"
                action="application_action.php"
            >


                <div class="modal-header">

                    <h5 class="modal-title">

                        ✕ Reject Application

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">


                    <input
                        type="hidden"
                        name="application_id"
                        value="<?= (int)$application["id"] ?>"
                    >


                    <input
                        type="hidden"
                        name="action"
                        value="reject"
                    >


                    <label class="form-label">

                        Reason for Rejection

                    </label>


                    <textarea
                        name="remarks"
                        class="form-control"
                        rows="5"
                        placeholder="Enter reason for rejecting this application..."
                        required
                    ></textarea>


                </div>


                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >

                        Cancel

                    </button>


                    <button
                        type="submit"
                        class="btn btn-danger"
                    >

                        Reject Application

                    </button>


                </div>


            </form>


        </div>

    </div>

</div>



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>