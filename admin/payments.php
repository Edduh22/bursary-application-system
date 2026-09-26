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
| SEARCH
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");


/*
|--------------------------------------------------------------------------
| FETCH AWARDED APPLICATIONS
|--------------------------------------------------------------------------
|
| We show applications that have an award.
| Payment information is joined when available.
|
*/

$sql = "
    SELECT

        a.id AS application_id,
        a.application_number,
        a.academic_year,
        a.status AS application_status,

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

        p.id AS payment_id,
        p.payment_amount,
        p.payment_date,
        p.payment_method,
        p.transaction_reference,
        p.payment_status,
        p.remarks AS payment_remarks

    FROM applications a

    INNER JOIN applicants ap
        ON a.applicant_id = ap.id

    INNER JOIN users u
        ON ap.user_id = u.id

    LEFT JOIN application_education e
        ON a.id = e.application_id

    INNER JOIN awards aw
        ON a.id = aw.application_id

    LEFT JOIN payments p
        ON aw.id = p.award_id

    WHERE 1 = 1
";


$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH FILTER
|--------------------------------------------------------------------------
*/

if ($search !== "") {

    $sql .= "
        AND (
            a.application_number LIKE ?
            OR u.full_name LIKE ?
            OR u.email LIKE ?
            OR e.institution_name LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        CASE
            WHEN p.payment_status = 'completed' THEN 2
            WHEN p.payment_status = 'pending' THEN 1
            ELSE 0
        END,
        aw.award_date DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$countStmt = $pdo->query("
    SELECT

        COUNT(*) AS total_payments,

        SUM(
            CASE
                WHEN payment_status = 'pending'
                THEN 1
                ELSE 0
            END
        ) AS pending_payments,

        SUM(
            CASE
                WHEN payment_status = 'completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_payments,

        COALESCE(
            SUM(
                CASE
                    WHEN payment_status = 'completed'
                    THEN payment_amount
                    ELSE 0
                END
            ),
            0
        ) AS total_paid

    FROM payments
");


$counts = $countStmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

$success_message = "";

if (isset($_GET["success"])) {

    $success_message = "Payment recorded successfully.";
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
        Payments - Bursary Admin
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
        }

        .stat-title {
            color: #6c757d;
            font-size: 14px;
            font-weight: 600;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            margin-top: 5px;
        }

        .table-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
            overflow: hidden;
        }

        .payment-amount {
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

                <a href="awards.php">
                    🏆 Awards
                </a>

                <a
                    href="payments.php"
                    class="active"
                >
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

            <div class="mb-4">

                <h2 class="fw-bold">
                    💰 Bursary Payments
                </h2>

                <p class="text-muted">
                    Manage payments for awarded bursary applications.
                </p>

            </div>


            <!-- SUCCESS -->

            <?php if ($success_message !== ""): ?>

                <div class="alert alert-success">

                    ✓ <?= htmlspecialchars($success_message) ?>

                </div>

            <?php endif; ?>


            <!-- ==================================================
                 STATISTICS
            =================================================== -->

            <div class="row g-3 mb-4">

                <div class="col-md-3">

                    <div class="stat-card">

                        <div class="stat-title">
                            Total Payments
                        </div>

                        <div class="stat-number text-primary">

                            <?= (int)(
                                $counts["total_payments"] ?? 0
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="stat-card">

                        <div class="stat-title">
                            Pending
                        </div>

                        <div class="stat-number text-warning">

                            <?= (int)(
                                $counts["pending_payments"] ?? 0
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="stat-card">

                        <div class="stat-title">
                            Completed
                        </div>

                        <div class="stat-number text-success">

                            <?= (int)(
                                $counts["completed_payments"] ?? 0
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="stat-card">

                        <div class="stat-title">
                            Total Paid
                        </div>

                        <div class="stat-number">

                            KES
                            <?= number_format(
                                (float)(
                                    $counts["total_paid"] ?? 0
                                ),
                                2
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 SEARCH
            =================================================== -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <form
                        method="GET"
                        class="row g-2"
                    >

                        <div class="col-md-10">

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search application number, applicant, email or institution..."
                                value="<?= htmlspecialchars($search) ?>"
                            >

                        </div>

                        <div class="col-md-2">

                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                            >
                                🔍 Search
                            </button>

                        </div>

                    </form>

                </div>

            </div>


            <!-- ==================================================
                 PAYMENTS TABLE
            =================================================== -->

            <div class="table-card">

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

                        <thead class="table-light">

                        <tr>

                            <th>
                                Application
                            </th>

                            <th>
                                Applicant
                            </th>

                            <th>
                                Institution
                            </th>

                            <th>
                                Award
                            </th>

                            <th>
                                Payment
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


                        <?php if (count($payments) > 0): ?>


                            <?php foreach (
                                $payments
                                as $payment
                            ): ?>


                                <tr>


                                    <!-- APPLICATION -->

                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $payment[
                                                    "application_number"
                                                ]
                                            ) ?>

                                        </strong>

                                        <br>

                                        <small class="text-muted">

                                            <?= htmlspecialchars(
                                                $payment[
                                                    "academic_year"
                                                ]
                                            ) ?>

                                        </small>

                                    </td>


                                    <!-- APPLICANT -->

                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $payment[
                                                    "full_name"
                                                ]
                                            ) ?>

                                        </strong>

                                        <br>

                                        <small class="text-muted">

                                            <?= htmlspecialchars(
                                                $payment[
                                                    "email"
                                                ]
                                            ) ?>

                                        </small>

                                    </td>


                                    <!-- INSTITUTION -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $payment[
                                                "institution_name"
                                            ] ?? "Not provided"
                                        ) ?>

                                        <br>

                                        <small class="text-muted">

                                            <?= htmlspecialchars(
                                                $payment[
                                                    "course"
                                                ] ?? ""
                                            ) ?>

                                        </small>

                                    </td>


                                    <!-- AWARD -->

                                    <td>

                                        <span
                                            class="text-success fw-bold"
                                        >

                                            KES
                                            <?= number_format(
                                                (float)(
                                                    $payment[
                                                        "award_amount"
                                                    ]
                                                ),
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- PAYMENT -->

                                    <td>

                                        <?php if (
                                            $payment[
                                                "payment_amount"
                                            ] !== null
                                        ): ?>

                                            <span
                                                class="payment-amount"
                                            >

                                                KES
                                                <?= number_format(
                                                    (float)(
                                                        $payment[
                                                            "payment_amount"
                                                        ]
                                                    ),
                                                    2
                                                ) ?>

                                            </span>

                                            <br>

                                            <small class="text-muted">

                                                <?= htmlspecialchars(
                                                    $payment[
                                                        "payment_date"
                                                    ] ?? ""
                                                ) ?>

                                            </small>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                Not paid
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php

                                        $status = strtolower(
                                            $payment[
                                                "payment_status"
                                            ] ?? "unpaid"
                                        );

                                        ?>


                                        <?php if (
                                            $status === "completed"
                                        ): ?>

                                            <span
                                                class="badge bg-success"
                                            >
                                                Completed
                                            </span>

                                        <?php elseif (
                                            $status === "pending"
                                        ): ?>

                                            <span
                                                class="badge bg-warning text-dark"
                                            >
                                                Pending
                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="badge bg-secondary"
                                            >
                                                Unpaid
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <?php if (
                                            $payment[
                                                "payment_id"
                                            ] === null
                                        ): ?>

                                            <a
                                                href="create_payment.php?id=<?= (int)$payment["application_id"] ?>"
                                                class="btn btn-sm btn-success"
                                            >
                                                💰 Record Payment
                                            </a>

                                        <?php else: ?>

                                            <a
                                                href="view_payment.php?id=<?= (int)$payment["payment_id"] ?>"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                👁 View Payment
                                            </a>

                                        <?php endif; ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="7"
                                    class="text-center py-5"
                                >

                                    <div class="text-muted">

                                        💰 No awarded applications
                                        are currently available
                                        for payment.

                                    </div>

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