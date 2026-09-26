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
| HELPER FUNCTION
|--------------------------------------------------------------------------
*/

function getCount($pdo, $sql, $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}


/*
|--------------------------------------------------------------------------
| APPLICATION STATISTICS
|--------------------------------------------------------------------------
*/

$totalApplications = getCount(
    $pdo,
    "SELECT COUNT(*) FROM applications"
);

$submittedApplications = getCount(
    $pdo,
    "SELECT COUNT(*) FROM applications WHERE status = ?",
    ["submitted"]
);

$underReview = getCount(
    $pdo,
    "SELECT COUNT(*) FROM applications WHERE status = ?",
    ["under_review"]
);

$approved = getCount(
    $pdo,
    "SELECT COUNT(*) FROM applications WHERE status = ?",
    ["approved"]
);

$awardedApplications = getCount(
    $pdo,
    "SELECT COUNT(*) FROM applications WHERE status = ?",
    ["awarded"]
);

$rejected = getCount(
    $pdo,
    "SELECT COUNT(*) FROM applications WHERE status = ?",
    ["rejected"]
);

$correctionRequired = getCount(
    $pdo,
    "SELECT COUNT(*) FROM applications WHERE status = ?",
    ["correction_required"]
);


/*
|--------------------------------------------------------------------------
| APPLICANT STATISTICS
|--------------------------------------------------------------------------
*/

$totalApplicants = getCount(
    $pdo,
    "SELECT COUNT(*) FROM applicants"
);


/*
|--------------------------------------------------------------------------
| DOCUMENT STATISTICS
|--------------------------------------------------------------------------
*/

$pendingDocuments = getCount(
    $pdo,
    "SELECT COUNT(*)
     FROM application_documents
     WHERE verification_status = ?",
    ["pending"]
);

$verifiedDocuments = getCount(
    $pdo,
    "SELECT COUNT(*)
     FROM application_documents
     WHERE verification_status = ?",
    ["verified"]
);

$rejectedDocuments = getCount(
    $pdo,
    "SELECT COUNT(*)
     FROM application_documents
     WHERE verification_status = ?",
    ["rejected"]
);


/*
|--------------------------------------------------------------------------
| AWARD STATISTICS
|--------------------------------------------------------------------------
*/

$totalAwards = getCount(
    $pdo,
    "SELECT COUNT(*) FROM awards"
);

$awardStmt = $pdo->query("
    SELECT COALESCE(SUM(award_amount), 0)
    FROM awards
");

$totalAwardedAmount = (float) $awardStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| PAYMENT STATISTICS
|--------------------------------------------------------------------------
*/

$totalPayments = getCount(
    $pdo,
    "SELECT COUNT(*) FROM payments"
);

$completedPayments = getCount(
    $pdo,
    "SELECT COUNT(*)
     FROM payments
     WHERE payment_status = ?",
    ["completed"]
);

$pendingPayments = getCount(
    $pdo,
    "SELECT COUNT(*)
     FROM payments
     WHERE payment_status = ?",
    ["pending"]
);

$paymentStmt = $pdo->query("
    SELECT
        COALESCE(
            SUM(
                CASE
                    WHEN payment_status = 'completed'
                    THEN payment_amount
                    ELSE 0
                END
            ),
            0
        )
    FROM payments
");

$totalPaid = (float) $paymentStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| USER STATISTICS
|--------------------------------------------------------------------------
*/

$totalUsers = getCount(
    $pdo,
    "SELECT COUNT(*) FROM users"
);


/*
|--------------------------------------------------------------------------
| RECENT APPLICATIONS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        a.id,
        a.application_number,
        a.academic_year,
        a.status,
        a.submitted_at,
        a.created_at,

        u.full_name

    FROM applications a

    INNER JOIN applicants ap
        ON a.applicant_id = ap.id

    INNER JOIN users u
        ON ap.user_id = u.id

    ORDER BY
        COALESCE(a.updated_at, a.created_at) DESC

    LIMIT 8
");

$recentApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| STATUS DISPLAY
|--------------------------------------------------------------------------
*/

function displayStatus($status)
{
    switch ($status) {

        case "submitted":

            return '<span class="status pending">
                        ⏳ Pending Verification
                    </span>';

        case "under_review":

            return '<span class="status review">
                        🔍 Under Review
                    </span>';

        case "approved":

            return '<span class="status approved">
                        ✓ Approved
                    </span>';

        case "awarded":

            return '<span class="status awarded">
                        🏆 Awarded
                    </span>';

        case "rejected":

            return '<span class="status rejected">
                        ✕ Rejected
                    </span>';

        case "correction_required":

            return '<span class="status correction">
                        ⚠ Correction Required
                    </span>';

        default:

            return '<span class="status neutral">'
                . htmlspecialchars(
                    ucwords(
                        str_replace(
                            "_",
                            " ",
                            $status
                        )
                    )
                )
                . '</span>';
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
        Admin Dashboard - Bursary Application System
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f7fb;
            font-family: Arial, Helvetica, sans-serif;
            color: #263238;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {

            width: 250px;

            position: fixed;

            top: 0;
            left: 0;
            bottom: 0;

            background: #09234d;

            color: white;

            padding: 25px 15px;

            overflow-y: auto;
        }

        .logo {

            padding:
                5px
                10px
                25px;

            border-bottom:
                1px solid
                rgba(255,255,255,.15);

            margin-bottom: 20px;
        }

        .logo h4 {

            margin: 0;

            font-size: 18px;

            font-weight: bold;
        }

        .logo small {

            color: #aebed5;
        }

        .sidebar a {

            display: block;

            padding:
                12px
                15px;

            margin-bottom: 6px;

            color: #dbe6f5;

            text-decoration: none;

            border-radius: 7px;

            font-size: 14px;
        }

        .sidebar a:hover,
        .sidebar a.active {

            background: #1769d1;

            color: white;
        }

        .logout {

            position: absolute;

            bottom: 20px;

            left: 15px;

            right: 15px;
        }

        .logout a {

            color: #ff7373 !important;
        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .content {

            margin-left: 250px;

            padding: 30px;
        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {

            background: white;

            padding: 22px 25px;

            border-radius: 10px;

            border:
                1px solid
                #e6eaf0;

            margin-bottom: 25px;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }

        .topbar h2 {

            margin: 0;

            font-size: 25px;

            font-weight: bold;
        }

        .topbar p {

            margin:
                5px
                0
                0;

            color: #748094;

            font-size: 14px;
        }

        .admin-info {

            text-align: right;
        }

        .admin-info strong {

            display: block;

            font-size: 14px;
        }

        .admin-info small {

            color: #748094;
        }


        /* =====================================================
           STAT CARDS
        ===================================================== */

        .stat-card {

            background: white;

            border:
                1px solid
                #e6eaf0;

            border-radius: 10px;

            padding: 20px;

            height: 100%;

            transition: .2s;
        }

        .stat-card:hover {

            box-shadow:
                0 5px 18px
                rgba(0,0,0,.07);

            transform: translateY(-2px);
        }

        .stat-title {

            color: #748094;

            font-size: 13px;

            margin-bottom: 8px;
        }

        .stat-number {

            font-size: 28px;

            font-weight: bold;

            color: #09234d;
        }

        .stat-money {

            font-size: 20px;

            font-weight: bold;

            color: #198754;
        }

        .stat-link {

            display: inline-block;

            margin-top: 10px;

            font-size: 12px;

            text-decoration: none;

            color: #1769d1;
        }


        /* =====================================================
           PANELS
        ===================================================== */

        .panel {

            background: white;

            border:
                1px solid
                #e6eaf0;

            border-radius: 10px;

            overflow: hidden;
        }

        .panel-header {

            padding: 20px;

            border-bottom:
                1px solid
                #edf0f4;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }

        .panel-header h5 {

            margin: 0;

            font-weight: bold;
        }


        /* =====================================================
           TABLE
        ===================================================== */

        table {

            width: 100%;
        }

        th {

            background: #fafbfd !important;

            color: #687386 !important;

            font-size: 12px;

            white-space: nowrap;
        }

        td {

            font-size: 13px;

            vertical-align: middle;
        }


        /* =====================================================
           STATUS
        ===================================================== */

        .status {

            display: inline-block;

            padding:
                6px
                10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 600;

            white-space: nowrap;
        }

        .pending {

            background: #fff3cd;

            color: #8a6500;
        }

        .review {

            background: #eee5ff;

            color: #6541a5;
        }

        .approved {

            background: #dff5e6;

            color: #18753a;
        }

        .awarded {

            background: #dbeafe;

            color: #1554a0;
        }

        .rejected {

            background: #ffe1e3;

            color: #b4232d;
        }

        .correction {

            background: #fff0dc;

            color: #a35b00;
        }

        .neutral {

            background: #e9ecef;

            color: #495057;
        }


        /* =====================================================
           VIEW BUTTON
        ===================================================== */

        .view-btn {

            background: #1769d1;

            color: white;

            padding:
                7px
                12px;

            border-radius: 5px;

            text-decoration: none;

            font-size: 12px;
        }

        .view-btn:hover {

            background: #0d57b5;

            color: white;
        }


        /* =====================================================
           QUICK ACTIONS
        ===================================================== */

        .quick-action {

            display: block;

            text-decoration: none;

            background: #f8faff;

            border:
                1px solid
                #e4eaf3;

            border-radius: 8px;

            padding: 15px;

            margin-bottom: 10px;

            color: #263238;
        }

        .quick-action:hover {

            background: #eef5ff;

            border-color: #1769d1;
        }

        .quick-action strong {

            display: block;

            font-size: 14px;
        }

        .quick-action small {

            color: #748094;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 900px) {

            .sidebar {

                width: 210px;
            }

            .content {

                margin-left: 210px;
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

                padding: 15px;
            }

            .logout {

                position: relative;

                bottom: auto;

                left: auto;

                right: auto;
            }

            .topbar {

                display: block;
            }

            .admin-info {

                text-align: left;

                margin-top: 15px;
            }
        }

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<div class="sidebar">

    <div class="logo">

        <h4>
            🎓BURSARY SYSTEM 
        </h4>

        <small>
            Administrator Panel
        </small>

    </div>


    <a
        href="dashboard.php"
        class="active"
    >
        🏠 Dashboard
    </a>

    <a href="applications.php">
        📋 All Applications
    </a>

    <a href="document_verification.php">
        📄 Document Verification
    </a>

    <a href="review_application.php">
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


    <div class="logout">

        <a href="../logout.php">
            🚪 Logout
        </a>

    </div>

</div>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<div class="content">


    <!-- HEADER -->

    <div class="topbar">

        <div>

            <h2>
                Admin Dashboard
            </h2>

            <p>
                Bursary Application System Management Centre
            </p>

        </div>

        <div class="admin-info">

            <strong>
                <?= htmlspecialchars(
                    $_SESSION["full_name"] ?? "Administrator"
                ) ?>
            </strong>

            <small>
                Administrator
            </small>

        </div>

    </div>


    <!-- =====================================================
         APPLICATION STATISTICS
    ====================================================== -->

    <h5 class="fw-bold mb-3">
        📋 Application Overview
    </h5>

    <div class="row g-4 mb-4">


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Total Applications
                </div>

                <div class="stat-number">
                    <?= $totalApplications ?>
                </div>

                <a
                    href="applications.php"
                    class="stat-link"
                >
                    View applications →
                </a>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Submitted
                </div>

                <div class="stat-number">
                    <?= $submittedApplications ?>
                </div>

                <a
                    href="applications.php?status=submitted"
                    class="stat-link"
                >
                    View submissions →
                </a>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Under Review
                </div>

                <div class="stat-number">
                    <?= $underReview ?>
                </div>

                <a
                    href="applications.php?status=under_review"
                    class="stat-link"
                >
                    View reviews →
                </a>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Approved
                </div>

                <div class="stat-number">
                    <?= $approved ?>
                </div>

                <a
                    href="applications.php?status=approved"
                    class="stat-link"
                >
                    View approved →
                </a>

            </div>

        </div>

    </div>


    <!-- SECOND ROW -->

    <div class="row g-4 mb-4">


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Awarded Applications
                </div>

                <div class="stat-number">
                    <?= $awardedApplications ?>
                </div>

                <a
                    href="awards.php"
                    class="stat-link"
                >
                    View awards →
                </a>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Rejected
                </div>

                <div class="stat-number">
                    <?= $rejected ?>
                </div>

                <a
                    href="applications.php?status=rejected"
                    class="stat-link"
                >
                    View rejected →
                </a>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Correction Required
                </div>

                <div class="stat-number">
                    <?= $correctionRequired ?>
                </div>

                <a
                    href="applications.php?status=correction_required"
                    class="stat-link"
                >
                    View corrections →
                </a>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Registered Applicants
                </div>

                <div class="stat-number">
                    <?= $totalApplicants ?>
                </div>

                <a
                    href="applicants.php"
                    class="stat-link"
                >
                    Manage applicants →
                </a>

            </div>

        </div>

    </div>


    <!-- =====================================================
         DOCUMENT / FINANCIAL STATISTICS
    ====================================================== -->

    <h5 class="fw-bold mb-3">
        💰 Documents & Financial Overview
    </h5>

    <div class="row g-4 mb-4">


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Documents Pending Verification
                </div>

                <div class="stat-number">
                    <?= $pendingDocuments ?>
                </div>

                <a
                    href="document_verification.php"
                    class="stat-link"
                >
                    Verify documents →
                </a>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Verified Documents
                </div>

                <div class="stat-number">
                    <?= $verifiedDocuments ?>
                </div>

                <a
                    href="document_verification.php"
                    class="stat-link"
                >
                    View documents →
                </a>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Total Awards
                </div>

                <div class="stat-number">
                    <?= $totalAwards ?>
                </div>

                <a
                    href="awards.php"
                    class="stat-link"
                >
                    Manage awards →
                </a>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Total Awarded
                </div>

                <div class="stat-money">

                    KES
                    <?= number_format(
                        $totalAwardedAmount,
                        2
                    ) ?>

                </div>

                <a
                    href="reports.php"
                    class="stat-link"
                >
                    View financial report →
                </a>

            </div>

        </div>

    </div>


    <!-- PAYMENT ROW -->

    <div class="row g-4 mb-4">


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Total Payments
                </div>

                <div class="stat-number">
                    <?= $totalPayments ?>
                </div>

                <a
                    href="payments.php"
                    class="stat-link"
                >
                    View payments →
                </a>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Pending Payments
                </div>

                <div class="stat-number">
                    <?= $pendingPayments ?>
                </div>

                <a
                    href="payments.php"
                    class="stat-link"
                >
                    Process payments →
                </a>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Completed Payments
                </div>

                <div class="stat-number">
                    <?= $completedPayments ?>
                </div>

                <a
                    href="payments.php"
                    class="stat-link"
                >
                    View completed →
                </a>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-title">
                    Total Paid
                </div>

                <div class="stat-money">

                    KES
                    <?= number_format(
                        $totalPaid,
                        2
                    ) ?>

                </div>

                <a
                    href="reports.php"
                    class="stat-link"
                >
                    View report →
                </a>

            </div>

        </div>

    </div>


    <!-- =====================================================
         RECENT APPLICATIONS + QUICK ACTIONS
    ====================================================== -->

    <div class="row g-4">


        <!-- RECENT APPLICATIONS -->

        <div class="col-lg-8">

            <div class="panel">

                <div class="panel-header">

                    <h5>
                        📋 Recent Applications
                    </h5>

                    <a
                        href="applications.php"
                        class="btn btn-sm btn-outline-primary"
                    >
                        View All
                    </a>

                </div>


                <div class="table-responsive">

                    <table class="table table-hover mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Application
                                </th>

                                <th>
                                    Applicant
                                </th>

                                <th>
                                    Academic Year
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>


                        <?php if (
                            !empty($recentApplications)
                        ): ?>


                            <?php foreach (
                                $recentApplications
                                as $application
                            ): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $application[
                                                    "application_number"
                                                ]
                                            ) ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $application[
                                                "full_name"
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $application[
                                                "academic_year"
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= displayStatus(
                                            $application[
                                                "status"
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <a
                                            href="view_application.php?id=<?= (int)$application["id"] ?>"
                                            class="view-btn"
                                        >
                                            View
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center py-5 text-muted"
                                >
                                    No applications found.
                                </td>

                            </tr>

                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <!-- QUICK ACTIONS -->

        <div class="col-lg-4">

            <div class="panel">

                <div class="panel-header">

                    <h5>
                        ⚡ Quick Actions
                    </h5>

                </div>


                <div class="p-3">


                    <a
                        href="applications.php"
                        class="quick-action"
                    >

                        <strong>
                            📋 Manage Applications
                        </strong>

                        <small>
                            View and process bursary applications
                        </small>

                    </a>


                    <a
                        href="document_verification.php"
                        class="quick-action"
                    >

                        <strong>
                            📄 Verify Documents
                        </strong>

                        <small>
                            Check applicant supporting documents
                        </small>

                    </a>


                    <a
                        href="review_application.php"
                        class="quick-action"
                    >

                        <strong>
                            🔍 Review Applications
                        </strong>

                        <small>
                            Evaluate submitted applications
                        </small>

                    </a>


                    <a
                        href="awards.php"
                        class="quick-action"
                    >

                        <strong>
                            🏆 Manage Awards
                        </strong>

                        <small>
                            Create and manage bursary awards
                        </small>

                    </a>


                    <a
                        href="payments.php"
                        class="quick-action"
                    >

                        <strong>
                            💳 Manage Payments
                        </strong>

                        <small>
                            Record and track bursary payments
                        </small>

                    </a>


                    <a
                        href="reports.php"
                        class="quick-action"
                    >

                        <strong>
                            📊 Generate Reports
                        </strong>

                        <small>
                            View system statistics and reports
                        </small>

                    </a>


                    <a
                        href="users.php"
                        class="quick-action"
                    >

                        <strong>
                            👤 Manage Users
                        </strong>

                        <small>
                            Manage accounts and system roles
                        </small>

                    </a>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         SYSTEM SUMMARY
    ====================================================== -->

    <div class="panel mt-4">

        <div class="panel-header">

            <h5>
                📊 System Summary
            </h5>

        </div>

        <div class="p-4">

            <div class="row text-center">


                <div class="col-md-4 mb-3 mb-md-0">

                    <div class="text-muted small">
                        Registered Users
                    </div>

                    <div class="fw-bold fs-4">
                        <?= $totalUsers ?>
                    </div>

                </div>


                <div class="col-md-4 mb-3 mb-md-0">

                    <div class="text-muted small">
                        Verified Documents
                    </div>

                    <div class="fw-bold fs-4 text-success">
                        <?= $verifiedDocuments ?>
                    </div>

                </div>


                <div class="col-md-4">

                    <div class="text-muted small">
                        Rejected Documents
                    </div>

                    <div class="fw-bold fs-4 text-danger">
                        <?= $rejectedDocuments ?>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


</body>

</html>