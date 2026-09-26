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
| GET PAYMENT ID
|--------------------------------------------------------------------------
*/

$payment_id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($payment_id <= 0) {
    header("Location: payments.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| FETCH PAYMENT DETAILS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        p.id AS payment_id,
        p.payment_amount,
        p.payment_date,
        p.payment_method,
        p.transaction_reference,
        p.payment_status,
        p.remarks AS payment_remarks,

        aw.id AS award_id,
        aw.award_amount,
        aw.award_date,

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
        e.fees_balance

    FROM payments p

    INNER JOIN awards aw
        ON p.award_id = aw.id

    INNER JOIN applications a
        ON aw.application_id = a.id

    INNER JOIN applicants ap
        ON a.applicant_id = ap.id

    INNER JOIN users u
        ON ap.user_id = u.id

    LEFT JOIN application_education e
        ON a.id = e.application_id

    WHERE p.id = ?

    LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $payment_id
]);

$payment = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| PAYMENT NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$payment) {
    header("Location: payments.php");
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
        View Payment - Bursary Admin
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
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 500;
        }

        .payment-box {
            border-left: 5px solid #198754;
        }

        .payment-amount {
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

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h2 class="fw-bold">
                        👁 Payment Details
                    </h2>

                    <p class="text-muted mb-0">
                        View bursary payment information.
                    </p>

                </div>


                <a
                    href="payments.php"
                    class="btn btn-secondary"
                >
                    ← Back to Payments
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
                                $payment["full_name"]
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="info-label">
                            Email
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $payment["email"]
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="info-label">
                            Application Number
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $payment["application_number"]
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="info-label">
                            Academic Year
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $payment["academic_year"]
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
                                $payment["institution_name"]
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
                                $payment["course"]
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
                                    $payment["school_fees"] ?? 0
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
                                    $payment["fees_paid"] ?? 0
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
                                    $payment["fees_balance"] ?? 0
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

            <div class="info-card mb-4">

                <h5 class="fw-bold mb-4">
                    🏆 Award Information
                </h5>


                <div class="row g-4">


                    <div class="col-md-4">

                        <div class="info-label">
                            Award Amount
                        </div>

                        <div class="info-value text-success fw-bold">

                            KES
                            <?= number_format(
                                (float)(
                                    $payment["award_amount"]
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
                                $payment["award_date"]
                                ?? "Not provided"
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="info-label">
                            Award ID
                        </div>

                        <div class="info-value">

                            #<?= (int)$payment["award_id"] ?>

                        </div>

                    </div>


                </div>

            </div>


            <!-- ==================================================
                 PAYMENT INFORMATION
            =================================================== -->

            <div class="info-card payment-box mb-4">

                <h5 class="fw-bold mb-4">
                    💰 Payment Information
                </h5>


                <div class="row g-4">


                    <div class="col-md-4">

                        <div class="info-label">
                            Payment Amount
                        </div>

                        <div class="payment-amount">

                            KES
                            <?= number_format(
                                (float)(
                                    $payment["payment_amount"]
                                ),
                                2
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="info-label">
                            Payment Date
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $payment["payment_date"]
                                ?? "Not provided"
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="info-label">
                            Payment Method
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $payment["payment_method"]
                                ?? "Not provided"
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="info-label">
                            Transaction Reference
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $payment[
                                    "transaction_reference"
                                ] ?? "Not provided"
                            ) ?>

                        </div>

                    </div>


                    <div class="col-md-6">

                        <div class="info-label">
                            Payment Status
                        </div>

                        <div>

                            <?php

                            $status = strtolower(
                                $payment["payment_status"]
                                ?? ""
                            );

                            if ($status === "completed"):

                            ?>

                                <span
                                    class="badge bg-success fs-6"
                                >
                                    Completed
                                </span>

                            <?php elseif (
                                $status === "pending"
                            ): ?>

                                <span
                                    class="badge bg-warning text-dark fs-6"
                                >
                                    Pending
                                </span>

                            <?php else: ?>

                                <span
                                    class="badge bg-secondary fs-6"
                                >
                                    <?= htmlspecialchars(
                                        ucfirst($status ?: "Unknown")
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
                                    $payment[
                                        "payment_remarks"
                                    ] ?? "No remarks provided."
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
        href="payments.php"
        class="btn btn-secondary"
    >
        ← Back to Payments
    </a>

    <a
        href="edit_payment.php?id=<?= (int)$payment["payment_id"] ?>"
        class="btn btn-warning"
    >
        ✏️ Edit Payment
    </a>

    <button
        type="button"
        class="btn btn-primary"
        onclick="window.print()"
    >
        🖨 Print Payment
    </button>

</div>


        </div>

    </div>

</div>


</body>

</html>