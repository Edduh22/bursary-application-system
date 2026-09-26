<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
    header("Location: payments.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| FETCH AWARD / APPLICATION INFORMATION
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT

        a.id AS application_id,
        a.application_number,
        a.academic_year,

        u.full_name,
        u.email,

        e.institution_name,
        e.course,
        e.fees_balance,

        aw.id AS award_id,
        aw.award_amount,
        aw.award_date,
        aw.award_status

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
    header("Location: payments.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK IF PAYMENT ALREADY EXISTS
|--------------------------------------------------------------------------
*/

$checkStmt = $pdo->prepare("
    SELECT id
    FROM payments
    WHERE award_id = ?
    LIMIT 1
");

$checkStmt->execute([
    $award["award_id"]
]);

$existingPayment = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($existingPayment) {

    header(
        "Location: view_payment.php?id="
        . (int)$existingPayment["id"]
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| FORM VARIABLES
|--------------------------------------------------------------------------
*/

$error_message = "";

$payment_amount = "";
$payment_date = date("Y-m-d");
$payment_method = "";
$transaction_reference = "";
$payment_status = "pending";
$remarks = "";


/*
|--------------------------------------------------------------------------
| PROCESS PAYMENT
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $payment_amount = trim(
        $_POST["payment_amount"] ?? ""
    );

    $payment_date = trim(
        $_POST["payment_date"] ?? ""
    );

    $payment_method = trim(
        $_POST["payment_method"] ?? ""
    );

    $transaction_reference = trim(
        $_POST["transaction_reference"] ?? ""
    );

    $payment_status = trim(
        $_POST["payment_status"] ?? "pending"
    );

    $remarks = trim(
        $_POST["remarks"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($payment_amount === "") {

        $error_message =
            "Please enter the payment amount.";

    } elseif (!is_numeric($payment_amount)) {

        $error_message =
            "Payment amount must be a valid number.";

    } elseif ((float)$payment_amount <= 0) {

        $error_message =
            "Payment amount must be greater than zero.";

    } elseif ($payment_date === "") {

        $error_message =
            "Please select the payment date.";

    } elseif ($payment_method === "") {

        $error_message =
            "Please select a payment method.";

    } elseif (
        !in_array(
            $payment_status,
            ["pending", "processing", "completed", "failed"],
            true
        )
    ) {

        $error_message =
            "Invalid payment status.";

    }


    /*
    |--------------------------------------------------------------------------
    | INSERT PAYMENT
    |--------------------------------------------------------------------------
    */

    if ($error_message === "") {

        try {

            $pdo->beginTransaction();


            $insertStmt = $pdo->prepare("
                INSERT INTO payments (

                    award_id,
                    payment_amount,
                    payment_date,
                    payment_method,
                    transaction_reference,
                    payment_status,
                    remarks

                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");


            $insertStmt->execute([

                $award["award_id"],

                (float)$payment_amount,

                $payment_date,

                $payment_method,

                $transaction_reference !== ""
                    ? $transaction_reference
                    : null,

                $payment_status,

                $remarks !== ""
                    ? $remarks
                    : null

            ]);


            /*
            |--------------------------------------------------------------------------
            | UPDATE APPLICATION WHEN PAYMENT IS COMPLETED
            |--------------------------------------------------------------------------
            */

            if ($payment_status === "completed") {

                $updateStmt = $pdo->prepare("
                    UPDATE applications

                    SET status = 'completed'

                    WHERE id = ?
                ");

                $updateStmt->execute([
                    $application_id
                ]);
            }


            $pdo->commit();


            header(
                "Location: payments.php?success=1"
            );

            exit;


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error_message =
                "Unable to record payment. "
                . $e->getMessage();
        }
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
        Record Payment - Bursary Admin
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

        .award-amount {
            font-size: 28px;
            font-weight: 700;
            color: #198754;
        }

    </style>

</head>


<body>


<!-- NAVBAR -->

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


        <!-- SIDEBAR -->

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


        <!-- MAIN CONTENT -->

        <div class="col-md-10 p-4">


            <!-- HEADER -->

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h2 class="fw-bold">
                        💰 Record Payment
                    </h2>

                    <p class="text-muted mb-0">
                        Record a payment for an awarded application.
                    </p>

                </div>

                <a
                    href="payments.php"
                    class="btn btn-secondary"
                >
                    ← Back to Payments
                </a>

            </div>


            <!-- ERROR -->

            <?php if ($error_message !== ""): ?>

                <div class="alert alert-danger">

                    ❌ <?= htmlspecialchars($error_message) ?>

                </div>

            <?php endif; ?>


            <!-- APPLICATION INFORMATION -->

            <div class="info-card mb-4">

                <h5 class="fw-bold mb-4">
                    👤 Application Information
                </h5>

                <div class="row g-4">

                    <div class="col-md-6">

                        <div class="info-label">
                            Applicant
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


                    <div class="col-md-6">

                        <div class="info-label">
                            Award Amount
                        </div>

                        <div class="award-amount">

                            KES
                            <?= number_format(
                                (float)$award["award_amount"],
                                2
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- PAYMENT FORM -->

            <div class="info-card">

                <h5 class="fw-bold mb-4">
                    💳 Payment Information
                </h5>

                <form method="POST">


                    <!-- PAYMENT AMOUNT -->

                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            Payment Amount (KES)

                        </label>

                        <input
                            type="number"
                            name="payment_amount"
                            class="form-control"
                            step="0.01"
                            min="0.01"
                            value="<?= htmlspecialchars(
                                $payment_amount
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- PAYMENT DATE -->

                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            Payment Date

                        </label>

                        <input
                            type="date"
                            name="payment_date"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $payment_date
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- PAYMENT METHOD -->

                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            Payment Method

                        </label>

                        <select
                            name="payment_method"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Select payment method
                            </option>

                            <option
                                value="M-Pesa"
                                <?= $payment_method === "M-Pesa"
                                    ? "selected"
                                    : "" ?>
                            >
                                M-Pesa
                            </option>

                            <option
                                value="Bank Transfer"
                                <?= $payment_method === "Bank Transfer"
                                    ? "selected"
                                    : "" ?>
                            >
                                Bank Transfer
                            </option>

                            <option
                                value="Cheque"
                                <?= $payment_method === "Cheque"
                                    ? "selected"
                                    : "" ?>
                            >
                                Cheque
                            </option>

                            <option
                                value="Other"
                                <?= $payment_method === "Other"
                                    ? "selected"
                                    : "" ?>
                            >
                                Other
                            </option>

                        </select>

                    </div>


                    <!-- TRANSACTION REFERENCE -->

                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            Transaction Reference

                        </label>

                        <input
                            type="text"
                            name="transaction_reference"
                            class="form-control"
                            placeholder="e.g. MPESA123456"
                            value="<?= htmlspecialchars(
                                $transaction_reference
                            ) ?>"
                        >

                    </div>


                    <!-- PAYMENT STATUS -->

                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            Payment Status

                        </label>

                        <select
                            name="payment_status"
                            class="form-select"
                            required
                        >

                            <option
                                value="pending"
                                <?= $payment_status === "pending"
                                    ? "selected"
                                    : "" ?>
                            >
                                Pending
                            </option>

                            <option
                                value="processing"
                                <?= $payment_status === "processing"
                                    ? "selected"
                                    : "" ?>
                            >
                                Processing
                            </option>

                            <option
                                value="completed"
                                <?= $payment_status === "completed"
                                    ? "selected"
                                    : "" ?>
                            >
                                Completed
                            </option>

                            <option
                                value="failed"
                                <?= $payment_status === "failed"
                                    ? "selected"
                                    : "" ?>
                            >
                                Failed
                            </option>

                        </select>

                    </div>


                    <!-- REMARKS -->

                    <div class="mb-4">

                        <label class="form-label fw-semibold">

                            Remarks

                        </label>

                        <textarea
                            name="remarks"
                            class="form-control"
                            rows="4"
                            placeholder="Enter any payment remarks..."
                        ><?= htmlspecialchars(
                            $remarks
                        ) ?></textarea>

                    </div>


                    <!-- BUTTONS -->

                    <div class="d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-success"
                        >
                            💰 Record Payment
                        </button>

                        <a
                            href="payments.php"
                            class="btn btn-secondary"
                        >
                            Cancel
                        </a>

                    </div>


                </form>

            </div>


        </div>

    </div>

</div>


</body>

</html>