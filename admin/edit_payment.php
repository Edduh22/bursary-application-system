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
| FETCH PAYMENT
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.*,

        aw.award_amount,
        aw.application_id,

        a.application_number,

        u.full_name,
        u.email

    FROM payments p

    INNER JOIN awards aw
        ON p.award_id = aw.id

    INNER JOIN applications a
        ON aw.application_id = a.id

    INNER JOIN applicants ap
        ON a.applicant_id = ap.id

    INNER JOIN users u
        ON ap.user_id = u.id

    WHERE p.id = ?

    LIMIT 1
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $payment_id
]);

$payment = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$payment) {
    header("Location: payments.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| FORM VARIABLES
|--------------------------------------------------------------------------
*/

$error_message = "";

$payment_amount = $payment["payment_amount"];
$payment_date = $payment["payment_date"];
$payment_method = $payment["payment_method"];
$transaction_reference = $payment["transaction_reference"];
$payment_status = $payment["payment_status"];
$remarks = $payment["remarks"];


/*
|--------------------------------------------------------------------------
| UPDATE PAYMENT
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
        $_POST["payment_status"] ?? ""
    );

    $remarks = trim(
        $_POST["remarks"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $payment_amount === "" ||
        !is_numeric($payment_amount) ||
        (float)$payment_amount <= 0
    ) {

        $error_message =
            "Please enter a valid payment amount.";

    } elseif ($payment_date === "") {

        $error_message =
            "Please select the payment date.";

    } elseif ($payment_method === "") {

        $error_message =
            "Please select a payment method.";

    } elseif (
        !in_array(
            $payment_status,
            ["pending", "completed"],
            true
        )
    ) {

        $error_message =
            "Invalid payment status.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | UPDATE DATABASE
        |--------------------------------------------------------------------------
        */

        $updateSql = "
            UPDATE payments

            SET
                payment_amount = ?,
                payment_date = ?,
                payment_method = ?,
                transaction_reference = ?,
                payment_status = ?,
                remarks = ?

            WHERE id = ?
        ";

        $updateStmt = $pdo->prepare($updateSql);

        $updateStmt->execute([

            $payment_amount,
            $payment_date,
            $payment_method,
            $transaction_reference,
            $payment_status,
            $remarks,
            $payment_id

        ]);


        /*
        |--------------------------------------------------------------------------
        | REDIRECT
        |--------------------------------------------------------------------------
        */

        header(
            "Location: view_payment.php?id="
            . $payment_id
            . "&success=1"
        );

        exit;
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
        Edit Payment - Bursary Admin
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

        .form-card {
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
        }

        .info-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
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
                        ✏️ Edit Payment
                    </h2>

                    <p class="text-muted mb-0">
                        Update payment information.
                    </p>

                </div>


                <a
                    href="view_payment.php?id=<?= $payment_id ?>"
                    class="btn btn-secondary"
                >
                    ← Back
                </a>

            </div>


            <!-- ==================================================
                 ERROR
            =================================================== -->

            <?php if ($error_message !== ""): ?>

                <div class="alert alert-danger">

                    ❌
                    <?= htmlspecialchars($error_message) ?>

                </div>

            <?php endif; ?>


            <!-- ==================================================
                 APPLICANT SUMMARY
            =================================================== -->

            <div class="info-box mb-4">

                <div class="row">

                    <div class="col-md-4">

                        <strong>
                            Applicant
                        </strong>

                        <br>

                        <?= htmlspecialchars(
                            $payment["full_name"]
                        ) ?>

                    </div>


                    <div class="col-md-4">

                        <strong>
                            Application
                        </strong>

                        <br>

                        <?= htmlspecialchars(
                            $payment["application_number"]
                        ) ?>

                    </div>


                    <div class="col-md-4">

                        <strong>
                            Award Amount
                        </strong>

                        <br>

                        KES
                        <?= number_format(
                            (float)$payment["award_amount"],
                            2
                        ) ?>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 FORM
            =================================================== -->

            <div class="form-card">

                <form
                    method="POST"
                >


                    <!-- PAYMENT AMOUNT -->

                    <div class="mb-3">

                        <label class="form-label fw-bold">

                            Payment Amount

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

                        <label class="form-label fw-bold">

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

                        <label class="form-label fw-bold">

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
                                value="Cash"
                                <?= $payment_method === "Cash"
                                    ? "selected"
                                    : "" ?>
                            >
                                Cash
                            </option>

                        </select>

                    </div>


                    <!-- TRANSACTION REFERENCE -->

                    <div class="mb-3">

                        <label class="form-label fw-bold">

                            Transaction Reference

                        </label>

                        <input
                            type="text"
                            name="transaction_reference"
                            class="form-control"
                            placeholder="e.g. M-Pesa transaction code"
                            value="<?= htmlspecialchars(
                                $transaction_reference ?? ""
                            ) ?>"
                        >

                    </div>


                    <!-- PAYMENT STATUS -->

                    <div class="mb-3">

                        <label class="form-label fw-bold">

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
                                value="completed"
                                <?= $payment_status === "completed"
                                    ? "selected"
                                    : "" ?>
                            >
                                Completed
                            </option>

                        </select>

                    </div>


                    <!-- REMARKS -->

                    <div class="mb-4">

                        <label class="form-label fw-bold">

                            Remarks

                        </label>

                        <textarea
                            name="remarks"
                            class="form-control"
                            rows="4"
                            placeholder="Enter payment remarks..."
                        ><?= htmlspecialchars(
                            $remarks ?? ""
                        ) ?></textarea>

                    </div>


                    <!-- BUTTONS -->

                    <div class="d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-success"
                        >
                            💾 Update Payment
                        </button>


                        <a
                            href="view_payment.php?id=<?= $payment_id ?>"
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