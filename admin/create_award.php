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

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid application ID.");
}

$application_id = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| FETCH APPROVED APPLICATION
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.application_number,
        a.academic_year,
        a.status,

        ap.id AS applicant_id,
        ap.national_id,
        ap.county,
        ap.constituency,
        ap.ward,

        u.full_name,
        u.email,

        e.institution_name,
        e.education_level,
        e.course,
        e.year_of_study,
        e.admission_number,
        e.school_fees,
        e.fees_paid,
        e.fees_balance

    FROM applications a

    INNER JOIN applicants ap
        ON a.applicant_id = ap.id

    INNER JOIN users u
        ON ap.user_id = u.id

    LEFT JOIN application_education e
        ON a.id = e.application_id

    WHERE a.id = ?

    LIMIT 1
");

$stmt->execute([$application_id]);

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
| ONLY APPROVED APPLICATIONS CAN BE AWARDED
|--------------------------------------------------------------------------
*/

if ($application["status"] !== "approved") {

    die("
        <div style='
            font-family: Arial;
            padding: 40px;
            text-align: center;
        '>

            <h2>Application Not Eligible</h2>

            <p>
                Only approved applications can receive an award.
            </p>

            <a href='awards.php'>
                ← Back to Awards
            </a>

        </div>
    ");
}


/*
|--------------------------------------------------------------------------
| CHECK WHETHER AWARD ALREADY EXISTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM awards
    WHERE application_id = ?
    LIMIT 1
");

$stmt->execute([$application_id]);

$existing_award = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_award) {

    header(
        "Location: view_award.php?id="
        . $application_id
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| FORM PROCESSING
|--------------------------------------------------------------------------
*/

$error_message = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $award_amount = trim(
        $_POST["award_amount"] ?? ""
    );

    $remarks = trim(
        $_POST["remarks"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATE AWARD AMOUNT
    |--------------------------------------------------------------------------
    */

    if ($award_amount === "") {

        $error_message =
            "Please enter the award amount.";

    } elseif (!is_numeric($award_amount)) {

        $error_message =
            "Award amount must be a valid number.";

    } elseif ((float)$award_amount <= 0) {

        $error_message =
            "Award amount must be greater than zero.";

    } elseif (
        (float)$award_amount >
        (float)($application["fees_balance"] ?? 0)
    ) {

        $error_message =
            "Award amount cannot be greater than the outstanding fees balance.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | START TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | CREATE AWARD
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO awards (
                    application_id,
                    award_amount,
                    award_date,
                    award_status,
                    remarks,
                    awarded_by
                )

                VALUES (
                    ?,
                    ?,
                    NOW(),
                    'confirmed',
                    ?,
                    ?
                )
            ");

            $stmt->execute([
                $application_id,
                $award_amount,
                $remarks,
                $_SESSION["user_id"]
            ]);


            /*
            |--------------------------------------------------------------------------
            | UPDATE APPLICATION STATUS
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE applications

                SET
                    status = 'awarded',
                    admin_remarks = ?,
                    updated_at = NOW()

                WHERE id = ?
            ");

            $stmt->execute([
                $remarks,
                $application_id
            ]);


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            */

            header(
                "Location: view_award.php?id="
                . $application_id
                . "&success=1"
            );

            exit;


        } catch (Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | ROLLBACK
            |--------------------------------------------------------------------------
            */

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error_message =
                "Failed to create award. Please try again.";

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
        Create Award -
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
            background-color: #f5f7fb;
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

        .section-title {
            font-weight: 700;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 14px;
        }

        .info-value {
            font-size: 16px;
            margin-bottom: 15px;
        }

        .award-box {
            border-left: 5px solid #198754;
        }

        .balance-box {
            background: #fff3cd;
            border-radius: 8px;
            padding: 20px;
        }

        @media (max-width: 768px) {

            .sidebar {
                min-height: auto;
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

                <a href="awards.php" class="active">
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

                    <h2 class="fw-bold mb-1">
                        🏆 Create Bursary Award
                    </h2>

                    <p class="text-muted mb-0">

                        Application No:

                        <strong>
                            <?= htmlspecialchars(
                                $application["application_number"]
                            ) ?>
                        </strong>

                    </p>

                </div>


                <span class="badge bg-success fs-6">
                    Approved
                </span>

            </div>



            <!-- BACK -->

            <a
                href="awards.php"
                class="btn btn-outline-secondary mb-4"
            >
                ← Back to Awards
            </a>



            <!-- ERROR -->

            <?php if ($error_message !== ""): ?>

                <div class="alert alert-danger">

                    ❌
                    <?= htmlspecialchars(
                        $error_message
                    ) ?>

                </div>

            <?php endif; ?>



            <!-- ==================================================
                 APPLICATION SUMMARY
            =================================================== -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <h5 class="section-title">
                        📋 Application Summary
                    </h5>


                    <div class="row">


                        <div class="col-md-4">

                            <div class="info-label">
                                Application Number
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "application_number"
                                    ]
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

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


                        <div class="col-md-4">

                            <div class="info-label">
                                Applicant
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "full_name"
                                    ]
                                ) ?>

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
                                    ]
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="info-label">
                                National ID
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "national_id"
                                    ] ?? ""
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="info-label">
                                County
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "county"
                                    ] ?? ""
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>



            <!-- ==================================================
                 EDUCATION
            =================================================== -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <h5 class="section-title">
                        🎓 Education Information
                    </h5>


                    <div class="row">


                        <div class="col-md-6">

                            <div class="info-label">
                                Institution
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "institution_name"
                                    ] ?? "Not provided"
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-label">
                                Course
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "course"
                                    ] ?? "Not provided"
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                Education Level
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "education_level"
                                    ] ?? ""
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                Year of Study
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "year_of_study"
                                    ] ?? ""
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                Admission Number
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application[
                                        "admission_number"
                                    ] ?? ""
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>



            <!-- ==================================================
                 FEES SUMMARY
            =================================================== -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <h5 class="section-title">
                        💰 Fees Summary
                    </h5>


                    <div class="row">


                        <div class="col-md-4">

                            <div class="info-label">
                                Total School Fees
                            </div>

                            <div class="info-value fw-bold">

                                KES
                                <?= number_format(
                                    (float)(
                                        $application[
                                            "school_fees"
                                        ] ?? 0
                                    ),
                                    2
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                Fees Already Paid
                            </div>

                            <div class="info-value fw-bold">

                                KES
                                <?= number_format(
                                    (float)(
                                        $application[
                                            "fees_paid"
                                        ] ?? 0
                                    ),
                                    2
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="balance-box">

                                <div class="info-label">
                                    Outstanding Balance
                                </div>

                                <div class="fs-4 fw-bold text-danger">

                                    KES
                                    <?= number_format(
                                        (float)(
                                            $application[
                                                "fees_balance"
                                            ] ?? 0
                                        ),
                                        2
                                    ) ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>



            <!-- ==================================================
                 AWARD FORM
            =================================================== -->

            <div class="card shadow-sm border-0 mb-5 award-box">

                <div class="card-body">

                    <h5 class="section-title">
                        🏆 Award Details
                    </h5>


                    <form
                        method="POST"
                        action=""
                    >


                        <!-- AWARD AMOUNT -->

                        <div class="mb-4">

                            <label
                                for="award_amount"
                                class="form-label fw-semibold"
                            >
                                Award Amount (KES)
                            </label>


                            <div class="input-group">

                                <span class="input-group-text">
                                    KES
                                </span>


                                <input
                                    type="number"
                                    name="award_amount"
                                    id="award_amount"
                                    class="form-control form-control-lg"
                                    min="1"
                                    max="<?= htmlspecialchars(
                                        $application[
                                            "fees_balance"
                                        ] ?? 0
                                    ) ?>"
                                    step="0.01"
                                    placeholder="Enter award amount"
                                    required
                                >

                            </div>


                            <div class="form-text">

                                Maximum award:

                                <strong>

                                    KES
                                    <?= number_format(
                                        (float)(
                                            $application[
                                                "fees_balance"
                                            ] ?? 0
                                        ),
                                        2
                                    ) ?>

                                </strong>

                            </div>

                        </div>



                        <!-- REMARKS -->

                        <div class="mb-4">

                            <label
                                for="remarks"
                                class="form-label fw-semibold"
                            >
                                Award Remarks
                            </label>


                            <textarea
                                name="remarks"
                                id="remarks"
                                class="form-control"
                                rows="5"
                                placeholder="Enter any remarks concerning this award..."
                            ></textarea>

                        </div>



                        <!-- CONFIRMATION -->

                        <div class="alert alert-warning">

                            <strong>
                                ⚠️ Important
                            </strong>

                            <br>

                            Once you confirm this award, the application
                            status will change from

                            <strong>Approved</strong>

                            to

                            <strong>Awarded</strong>.

                            This action should only be performed after
                            confirming the approved award amount.

                        </div>



                        <!-- BUTTONS -->

                        <div class="d-flex gap-2">

                            <a
                                href="awards.php"
                                class="btn btn-outline-secondary"
                            >
                                Cancel
                            </a>


                            <button
                                type="submit"
                                class="btn btn-success"
                                onclick="
                                    return confirm(
                                        'Are you sure you want to confirm this award? The application will be marked as AWARDED.'
                                    );
                                "
                            >
                                🏆 Confirm Award
                            </button>

                        </div>


                    </form>

                </div>

            </div>


        </div>

    </div>

</div>


</body>

</html>