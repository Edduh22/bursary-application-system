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
| ACADEMIC YEAR FILTER
|--------------------------------------------------------------------------
*/

$academic_year = trim($_GET["academic_year"] ?? "");


/*
|--------------------------------------------------------------------------
| APPLICATION STATISTICS
|--------------------------------------------------------------------------
*/

$appSql = "
    SELECT
        COUNT(*) AS total_applications,

        COALESCE(SUM(
            CASE
                WHEN LOWER(status) = 'submitted'
                THEN 1
                ELSE 0
            END
        ), 0) AS submitted,

        COALESCE(SUM(
            CASE
                WHEN LOWER(status) = 'approved'
                THEN 1
                ELSE 0
            END
        ), 0) AS approved,

        COALESCE(SUM(
            CASE
                WHEN LOWER(status) = 'awarded'
                THEN 1
                ELSE 0
            END
        ), 0) AS awarded,

        COALESCE(SUM(
            CASE
                WHEN LOWER(status) = 'rejected'
                THEN 1
                ELSE 0
            END
        ), 0) AS rejected

    FROM applications
";

$appParams = [];

if ($academic_year !== "") {
    $appSql .= " WHERE academic_year = ?";
    $appParams[] = $academic_year;
}

$appStmt = $pdo->prepare($appSql);
$appStmt->execute($appParams);

$applications = $appStmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| AWARD STATISTICS
|--------------------------------------------------------------------------
*/

$awardSql = "
    SELECT
        COUNT(aw.id) AS total_awards,

        COALESCE(
            SUM(aw.award_amount),
            0
        ) AS total_awarded_amount

    FROM awards aw

    INNER JOIN applications a
        ON aw.application_id = a.id
";

$awardParams = [];

if ($academic_year !== "") {
    $awardSql .= "
        WHERE a.academic_year = ?
    ";

    $awardParams[] = $academic_year;
}

$awardStmt = $pdo->prepare($awardSql);
$awardStmt->execute($awardParams);

$awards = $awardStmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| PAYMENT STATISTICS
|--------------------------------------------------------------------------
*/

$paymentSql = "
    SELECT

        COUNT(p.id) AS total_payments,

        COALESCE(SUM(
            CASE
                WHEN LOWER(p.payment_status) = 'completed'
                THEN 1
                ELSE 0
            END
        ), 0) AS completed_payments,

        COALESCE(SUM(
            CASE
                WHEN LOWER(p.payment_status) = 'pending'
                THEN 1
                ELSE 0
            END
        ), 0) AS pending_payments,

        COALESCE(SUM(
            CASE
                WHEN LOWER(p.payment_status) = 'completed'
                THEN p.payment_amount
                ELSE 0
            END
        ), 0) AS total_paid

    FROM payments p

    INNER JOIN awards aw
        ON p.award_id = aw.id

    INNER JOIN applications a
        ON aw.application_id = a.id
";

$paymentParams = [];

if ($academic_year !== "") {
    $paymentSql .= "
        WHERE a.academic_year = ?
    ";

    $paymentParams[] = $academic_year;
}

$paymentStmt = $pdo->prepare($paymentSql);
$paymentStmt->execute($paymentParams);

$payments = $paymentStmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| ACADEMIC YEARS
|--------------------------------------------------------------------------
*/

$yearStmt = $pdo->query("
    SELECT DISTINCT academic_year
    FROM applications
    WHERE academic_year IS NOT NULL
      AND academic_year <> ''
    ORDER BY academic_year DESC
");

$years = $yearStmt->fetchAll(PDO::FETCH_COLUMN);


/*
|--------------------------------------------------------------------------
| RECENT APPLICATIONS
|--------------------------------------------------------------------------
*/

$recentSql = "
    SELECT
        a.application_number,
        a.academic_year,
        a.status,
        u.full_name

    FROM applications a

    INNER JOIN applicants ap
        ON a.applicant_id = ap.id

    INNER JOIN users u
        ON ap.user_id = u.id
";

$recentParams = [];

if ($academic_year !== "") {
    $recentSql .= "
        WHERE a.academic_year = ?
    ";

    $recentParams[] = $academic_year;
}

$recentSql .= "
    ORDER BY a.updated_at DESC
    LIMIT 10
";

$recentStmt = $pdo->prepare($recentSql);
$recentStmt->execute($recentParams);

$recentApplications = $recentStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| SAFE VALUES
|--------------------------------------------------------------------------
*/

$totalApplications = (int)($applications["total_applications"] ?? 0);
$submitted = (int)($applications["submitted"] ?? 0);
$approved = (int)($applications["approved"] ?? 0);
$awarded = (int)($applications["awarded"] ?? 0);
$rejected = (int)($applications["rejected"] ?? 0);

$totalAwards = (int)($awards["total_awards"] ?? 0);
$totalAwardedAmount = (float)($awards["total_awarded_amount"] ?? 0);

$totalPayments = (int)($payments["total_payments"] ?? 0);
$completedPayments = (int)($payments["completed_payments"] ?? 0);
$pendingPayments = (int)($payments["pending_payments"] ?? 0);
$totalPaid = (float)($payments["total_paid"] ?? 0);

$approvedAwarded = $approved + $awarded;

$remainingAmount = $totalAwardedAmount - $totalPaid;

if ($remainingAmount < 0) {
    $remainingAmount = 0;
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
        Reports - Bursary Admin
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
        }

        .sidebar {
            min-height: 100vh;
            background: #212529;
        }

        .sidebar a {
            color: #fff;
            text-decoration: none;
            display: block;
            padding: 12px 20px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #343a40;
        }

        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
            height: 100%;
        }

        .stat-title {
            color: #6c757d;
            font-size: 14px;
            font-weight: 600;
        }

        .stat-number {
            font-size: 27px;
            font-weight: 700;
            margin-top: 5px;
        }

        .report-card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
        }

        .table th {
            white-space: nowrap;
        }

        @media(max-width:768px) {

            .sidebar {
                min-height: auto;
            }

        }

        @media print {

            .sidebar,
            nav,
            .filter-section,
            .print-button {
                display: none !important;
            }

            .col-md-10 {
                width: 100% !important;
            }

            body {
                background: white;
            }

            .stat-card,
            .report-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }

        }

    </style>

</head>


<body>


<!-- ==========================================================
     NAVBAR
=========================================================== -->

<nav class="navbar navbar-dark bg-dark">

    <div class="container-fluid">

        <a
            class="navbar-brand fw-bold"
            href="dashboard.php"
        >
            🎓 BURSARY ADMIN
        </a>


        <div class="text-white">

            <?= htmlspecialchars(
                $_SESSION["full_name"] ?? "Administrator"
            ) ?>

            <a
                href="../logout.php"
                class="btn btn-sm btn-outline-light ms-3"
            >
                Logout
            </a>

        </div>

    </div>

</nav>


<div class="container-fluid">

    <div class="row">


        <!-- ==================================================
             SIDEBAR
        =================================================== -->

        <div class="col-md-2 sidebar p-0">

            <div class="py-3">

                <a href="dashboard.php">
                    🏠 Dashboard
                </a>

                <a href="applications.php">
                    📋 Applications
                </a>

                <a href="applicants.php">
                    👥 Applicants
                </a>

                <a href="document_verification.php">
                    📄 Documents
                </a>

                <a href="review_application.php">
                    🔍 Review
                </a>

                <a href="awards.php">
                    🏆 Awards
                </a>

                <a href="payments.php">
                    💰 Payments
                </a>

                <a
                    href="reports.php"
                    class="active"
                >
                    📊 Reports
                </a>

                <a href="users.php">
                    👤 Users
                </a>

                <a href="settings.php">
                    ⚙️ Settings
                </a>

            </div>

        </div>


        <!-- ==================================================
             MAIN CONTENT
        =================================================== -->

        <div class="col-md-10 p-4">


            <!-- HEADER -->

            <div class="mb-4">

                <h2 class="fw-bold">
                    📊 Bursary Reports
                </h2>

                <p class="text-muted">
                    Overview of applications, awards and payments.
                </p>

            </div>


            <!-- ==================================================
                 FILTER
            =================================================== -->

            <div class="card shadow-sm border-0 mb-4 filter-section">

                <div class="card-body">

                    <form
                        method="GET"
                        action="reports.php"
                        class="row g-3 align-items-end"
                    >

                        <div class="col-md-6">

                            <label class="form-label fw-bold">
                                Academic Year
                            </label>

                            <select
                                name="academic_year"
                                class="form-select"
                            >

                                <option value="">
                                    All Academic Years
                                </option>

                                <?php foreach ($years as $year): ?>

                                    <option
                                        value="<?= htmlspecialchars($year) ?>"
                                        <?= ($academic_year === $year)
                                            ? "selected"
                                            : "" ?>
                                    >

                                        <?= htmlspecialchars($year) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-md-3">

                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                            >
                                🔍 Generate Report
                            </button>

                        </div>


                        <div class="col-md-3">

                            <a
                                href="reports.php"
                                class="btn btn-secondary w-100"
                            >
                                Reset
                            </a>

                        </div>

                    </form>

                </div>

            </div>


            <!-- ==================================================
                 APPLICATION SUMMARY
            =================================================== -->

            <h5 class="fw-bold mb-3">
                📋 Application Summary
            </h5>


            <div class="row g-3 mb-4">


                <div class="col-md-3">

                    <div class="stat-card">

                        <div class="stat-title">
                            Total Applications
                        </div>

                        <div class="stat-number text-primary">
                            <?= $totalApplications ?>
                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="stat-card">

                        <div class="stat-title">
                            Submitted
                        </div>

                        <div class="stat-number text-info">
                            <?= $submitted ?>
                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="stat-card">

                        <div class="stat-title">
                            Approved / Awarded
                        </div>

                        <div class="stat-number text-success">
                            <?= $approvedAwarded ?>
                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="stat-card">

                        <div class="stat-title">
                            Rejected
                        </div>

                        <div class="stat-number text-danger">
                            <?= $rejected ?>
                        </div>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 FINANCIAL SUMMARY
            =================================================== -->

            <h5 class="fw-bold mb-3">
                💰 Financial Summary
            </h5>


            <div class="row g-3 mb-4">


                <div class="col-md-3">

                    <div class="stat-card">

                        <div class="stat-title">
                            Total Awards
                        </div>

                        <div class="stat-number text-primary">
                            <?= $totalAwards ?>
                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="stat-card">

                        <div class="stat-title">
                            Total Awarded
                        </div>

                        <div class="stat-number text-success">

                            KES
                            <?= number_format(
                                $totalAwardedAmount,
                                2
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="stat-card">

                        <div class="stat-title">
                            Completed Payments
                        </div>

                        <div class="stat-number text-success">
                            <?= $completedPayments ?>
                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="stat-card">

                        <div class="stat-title">
                            Total Paid
                        </div>

                        <div class="stat-number text-success">

                            KES
                            <?= number_format(
                                $totalPaid,
                                2
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 PAYMENT STATUS
            =================================================== -->

            <div class="row g-3 mb-4">


                <div class="col-md-6">

                    <div class="report-card">

                        <h5 class="fw-bold">
                            💳 Payment Status
                        </h5>

                        <hr>


                        <div class="d-flex justify-content-between mb-3">

                            <span>
                                Total Payments
                            </span>

                            <strong>
                                <?= $totalPayments ?>
                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-3">

                            <span>
                                Pending Payments
                            </span>

                            <strong class="text-warning">
                                <?= $pendingPayments ?>
                            </strong>

                        </div>


                        <div class="d-flex justify-content-between">

                            <span>
                                Completed Payments
                            </span>

                            <strong class="text-success">
                                <?= $completedPayments ?>
                            </strong>

                        </div>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="report-card">

                        <h5 class="fw-bold">
                            🏆 Award vs Payment
                        </h5>

                        <hr>


                        <div class="d-flex justify-content-between mb-3">

                            <span>
                                Total Awarded
                            </span>

                            <strong>

                                KES
                                <?= number_format(
                                    $totalAwardedAmount,
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-3">

                            <span>
                                Total Paid
                            </span>

                            <strong class="text-success">

                                KES
                                <?= number_format(
                                    $totalPaid,
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <hr>


                        <div class="d-flex justify-content-between">

                            <span>
                                Remaining Amount
                            </span>

                            <strong class="text-warning">

                                KES
                                <?= number_format(
                                    $remainingAmount,
                                    2
                                ) ?>

                            </strong>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 RECENT APPLICATIONS
            =================================================== -->

            <div class="report-card">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <h5 class="fw-bold mb-0">
                        📋 Recent Applications
                    </h5>


                    <button
                        type="button"
                        class="btn btn-sm btn-primary print-button"
                        onclick="window.print()"
                    >
                        🖨 Print Report
                    </button>

                </div>


                <div class="table-responsive">

                    <table class="table table-hover">

                        <thead class="table-light">

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

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (!empty($recentApplications)): ?>

                            <?php foreach ($recentApplications as $application): ?>

                                <?php

                                $status = strtolower(
                                    trim(
                                        $application["status"] ?? ""
                                    )
                                );

                                ?>

                                <tr>

                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $application[
                                                    "application_number"
                                                ] ?? "-"
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $application[
                                                "full_name"
                                            ] ?? "-"
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $application[
                                                "academic_year"
                                            ] ?? "-"
                                        ) ?>

                                    </td>


                                    <td>

                                        <?php if ($status === "approved"): ?>

                                            <span class="badge bg-success">
                                                Approved
                                            </span>

                                        <?php elseif ($status === "awarded"): ?>

                                            <span class="badge bg-primary">
                                                Awarded
                                            </span>

                                        <?php elseif ($status === "rejected"): ?>

                                            <span class="badge bg-danger">
                                                Rejected
                                            </span>

                                        <?php elseif ($status === "submitted"): ?>

                                            <span class="badge bg-info">
                                                Submitted
                                            </span>

                                        <?php elseif ($status === "pending"): ?>

                                            <span class="badge bg-warning text-dark">
                                                Pending
                                            </span>

                                        <?php elseif ($status === "under_review"): ?>

                                            <span class="badge bg-warning text-dark">
                                                Under Review
                                            </span>

                                        <?php elseif ($status === "correction_required"): ?>

                                            <span class="badge bg-warning text-dark">
                                                Correction Required
                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-secondary">

                                                <?= htmlspecialchars(
                                                    ucfirst(
                                                        str_replace(
                                                            "_",
                                                            " ",
                                                            $status
                                                        )
                                                    )
                                                ) ?>

                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="4"
                                    class="text-center py-4 text-muted"
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

    </div>

</div>


</body>

</html>