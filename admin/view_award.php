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
| GET APPLICATION ID
|--------------------------------------------------------------------------
*/

$application_id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($application_id <= 0) {
    header("Location: awards.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| FETCH AWARD DETAILS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        a.id AS application_id,
        a.application_number,
        a.academic_year,
        a.status,

        u.full_name,
        u.email,

        e.institution_name,
        e.course,
        e.school_fees,
        e.fees_paid,
        e.fees_balance,

        aw.id AS award_id,
        aw.award_amount,
        aw.award_date,
        aw.award_status,
        aw.remarks AS award_remarks

    FROM applications a

    INNER JOIN applicants ap
        ON a.applicant_id = ap.id

    INNER JOIN users u
        ON ap.user_id = u.id

    LEFT JOIN application_education e
        ON a.id = e.application_id

    INNER JOIN awards aw
        ON a.id = aw.application_id

    WHERE a.id = ?

    LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $application_id
]);

$award = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| AWARD NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$award) {

    header("Location: awards.php");

    exit;
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
        View Award - Bursary Admin
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

        .info-card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
        }

        .info-label {
            color: #6c757d;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 500;
        }

        .award-box {
            border-left: 5px solid #198754;
        }

        .award-amount {
            font-size: 32px;
            font-weight: 700;
            color: #198754;
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

                <a
                    href="awards.php"
                    class="active"
                >
                    🏆 Awards
                </a>

                <a href="payments.php">
                    💰 Payments
                </a>

                <a href="reports.php">
                    📊 Reports
                </a>

                <a href="users.php">
                    👤 Users
                </a>

            </div>

        </div>


        <!-- ==================================================
             MAIN CONTENT
        =================================================== -->

        <div class="col-md-10 p-4">


            <!-- HEADER -->

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h2 class="fw-bold">
                        👁 Award Details
                    </h2>

                    <p class="text-muted mb-0">
                        View bursary award information.
                    </p>

                </div>


                <a
                    href="awards.php"
                    class="btn btn-secondary"
                >
                    ← Back to Awards
                </a>

            </div>


            <!-- ==================================================
                 APPLICANT INFORMATION
            =================================================== -->

            <div class="info-card mb-4">

                <h5 class="fw-bold mb-4">
                    👤 Applicant Information
                </h5>


                <div class="row g-4">


                    <div class="col-md-6">

                        <div class="info-label">
                            Full Name
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $award["full_name"]
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="info-label">
                            Email
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $award["email"]
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="info-label">
                            Application Number
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $award["application_number"]
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="info-label">
                            Academic Year
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $award["academic_year"]
                            ) ?>

                        </div>

                    </div>


                </div>

            </div>


            <!-- ==================================================
                 EDUCATION INFORMATION
            =================================================== -->

            <div class="info-card mb-4">

                <h5 class="fw-bold mb-4">
                    🎓 Education Information
                </h5>


                <div class="row g-4">


                    <div class="col-md-6">

                        <div class="info-label">
                            Institution
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $award["institution_name"]
                                ?? "Not provided"
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="info-label">
                            Course
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $award["course"]
                                ?? "Not provided"
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="info-label">
                            School Fees
                        </div>

                        <div class="info-value">

                            KES
                            <?= number_format(
                                (float)(
                                    $award["school_fees"]
                                    ?? 0
                                ),
                                2
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="info-label">
                            Fees Paid
                        </div>

                        <div class="info-value">

                            KES
                            <?= number_format(
                                (float)(
                                    $award["fees_paid"]
                                    ?? 0
                                ),
                                2
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="info-label">
                            Fees Balance
                        </div>

                        <div class="info-value">

                            KES
                            <?= number_format(
                                (float)(
                                    $award["fees_balance"]
                                    ?? 0
                                ),
                                2
                            ) ?>

                        </div>

                    </div>


                </div>

            </div>


            <!-- ==================================================
                 AWARD INFORMATION
            =================================================== -->

            <div class="info-card award-box mb-4">

                <h5 class="fw-bold mb-4">
                    🏆 Award Information
                </h5>


                <div class="row g-4">


                    <div class="col-md-4">

                        <div class="info-label">
                            Award Amount
                        </div>

                        <div class="award-amount">

                            KES
                            <?= number_format(
                                (float)(
                                    $award["award_amount"]
                                ),
                                2
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="info-label">
                            Award Date
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $award["award_date"]
                                ?? "Not provided"
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="info-label">
                            Award Status
                        </div>

                        <div>

                            <?php

                            $award_status =
                                strtolower(
                                    $award["award_status"]
                                    ?? ""
                                );

                            if (
                                $award_status === "approved"
                                ||
                                $award_status === "awarded"
                            ):

                            ?>

                                <span class="badge bg-success fs-6">

                                    <?= htmlspecialchars(
                                        ucfirst(
                                            $award_status
                                        )
                                    ) ?>

                                </span>

                            <?php elseif (
                                $award_status === "pending"
                            ): ?>

                                <span class="badge bg-warning text-dark fs-6">

                                    Pending

                                </span>

                            <?php else: ?>

                                <span class="badge bg-secondary fs-6">

                                    <?= htmlspecialchars(
                                        $award["award_status"]
                                        ?? "Unknown"
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                    </div>


                    <div class="col-12">

                        <div class="info-label">
                            Remarks
                        </div>

                        <div class="info-value">

                            <?= nl2br(
                                htmlspecialchars(
                                    $award["award_remarks"]
                                    ?? "No remarks provided."
                                )
                            ) ?>

                        </div>

                    </div>


                </div>

            </div>


            <!-- ==================================================
                 ACTIONS
            =================================================== -->

            <div class="d-flex gap-2">

                <a
                    href="awards.php"
                    class="btn btn-secondary"
                >
                    ← Back to Awards
                </a>

                <button
                    type="button"
                    class="btn btn-primary"
                    onclick="window.print()"
                >
                    🖨 Print Award
                </button>

            </div>


        </div>

    </div>

</div>


</body>

</html>