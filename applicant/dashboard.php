<?php

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


/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/

$user_id = $_SESSION["user_id"];

$full_name = $_SESSION["full_name"] ?? "Applicant";


/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$applicant = null;
$application = null;
$award = null;

$status = "Not Started";


/*
|--------------------------------------------------------------------------
| FIND APPLICANT
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id
    FROM applicants
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$applicant = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| FIND APPLICATION
|--------------------------------------------------------------------------
*/

if ($applicant) {

    $stmt = $pdo->prepare("
        SELECT
            *
        FROM applications
        WHERE applicant_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $applicant["id"]
    ]);

    $application = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | APPLICATION STATUS
    |--------------------------------------------------------------------------
    */

    if ($application) {

        $status = trim(
            $application["status"] ?? "draft"
        );

    }


    /*
    |--------------------------------------------------------------------------
    | FIND AWARD
    |--------------------------------------------------------------------------
    */

    if ($application) {

        $stmt = $pdo->prepare("
            SELECT
                id,
                application_id,
                award_amount,
                award_date,
                award_status,
                remarks
            FROM awards
            WHERE application_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->execute([
            $application["id"]
        ]);

        $award = $stmt->fetch(PDO::FETCH_ASSOC);

    }

}


/*
|--------------------------------------------------------------------------
| NORMALIZE STATUS
|--------------------------------------------------------------------------
*/

$display_status = strtolower(
    trim($status)
);


/*
|--------------------------------------------------------------------------
| IF AWARD EXISTS, TREAT APPLICATION AS AWARDED
|--------------------------------------------------------------------------
*/

if ($award) {

    $display_status = "awarded";

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
        Applicant Dashboard
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Custom CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        .award-card {

            border-left: 5px solid #198754;

        }

        .award-amount {

            font-size: 28px;
            font-weight: 700;
            color: #198754;

        }

        .award-icon {

            font-size: 35px;

        }

        .status-card {

            min-height: 220px;

        }

        .rejected-card {

            border-left: 5px solid #dc3545;

        }

        .correction-card {

            border-left: 5px solid #ffc107;

        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-light bg-white shadow-sm">

    <div class="container">

        <span class="navbar-brand fw-bold">

            BURSARY APPLICATION SYSTEM

        </span>


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



<!-- =====================================================
     DASHBOARD
===================================================== -->

<div class="container py-5">


    <h2 class="fw-bold">

        Applicant Dashboard

    </h2>


    <p class="text-muted">

        Welcome,

        <?= htmlspecialchars($full_name) ?>.

    </p>



    <!-- =================================================
         REJECTED APPLICATION NOTIFICATION
    ================================================== -->

    <?php if (
        $display_status === "rejected"
    ): ?>

        <div
            class="alert alert-danger shadow-sm"
        >

            <h5 class="alert-heading">

                Application Rejected

            </h5>

            <p class="mb-3">

                Your bursary application was rejected by
                the administrator.

                You can review your application, correct
                any mistakes, and submit it again.

            </p>


            <a
                href="application.php"
                class="btn btn-danger"
            >

                Correct & Resubmit Application

            </a>

        </div>

    <?php endif; ?>



    <!-- =================================================
         CORRECTION NOTIFICATION
    ================================================== -->

    <?php if (
        $display_status === "correction"
    ): ?>

        <div
            class="alert alert-warning shadow-sm"
        >

            <h5 class="alert-heading">

                Correction Required

            </h5>

            <p class="mb-3">

                The administrator has requested corrections
                to your application.

                Please review your application, make the
                required changes, and submit it again.

            </p>


            <a
                href="application.php"
                class="btn btn-warning"
            >

                Correct & Resubmit Application

            </a>

        </div>

    <?php endif; ?>



    <!-- =================================================
         AWARD NOTIFICATION
    ================================================== -->

    <?php if ($award): ?>

        <div
            class="alert alert-success shadow-sm d-flex align-items-center"
        >

            <div class="award-icon me-3">

                🏆

            </div>

            <div>

                <strong>

                    Congratulations!

                </strong>

                <br>

                Your bursary application has been awarded.

                Your awarded amount is

                <strong>

                    KES
                    <?= number_format(
                        (float)$award["award_amount"],
                        2
                    ) ?>

                </strong>.

            </div>

        </div>

    <?php endif; ?>



    <div class="row mt-4">


        <!-- =================================================
             PROFILE
        ================================================== -->

        <div class="col-md-4 mb-4">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-body">

                    <h5>

                        My Profile

                    </h5>


                    <p class="text-muted">

                        Complete your personal information.

                    </p>


                    <a
                        href="profile.php"
                        class="btn btn-primary"
                    >

                        Complete Profile

                    </a>

                </div>

            </div>

        </div>



        <!-- =================================================
             APPLICATION
        ================================================== -->

        <div class="col-md-4 mb-4">

            <div
                class="
                    card
                    shadow-sm
                    border-0
                    h-100

                    <?php
                    if ($display_status === "rejected") {
                        echo "rejected-card";
                    }

                    if ($display_status === "correction") {
                        echo "correction-card";
                    }
                    ?>
                "
            >

                <div class="card-body">

                    <h5>

                        My Application

                    </h5>


                    <p class="text-muted">

                        Start or continue your bursary application.

                    </p>



                    <?php if (!$application): ?>


                        <!-- =================================================
                             NOT STARTED
                        ================================================== -->

                        <span class="badge bg-secondary mb-3">

                            Not Started

                        </span>

                        <br>


                        <a
                            href="application.php"
                            class="btn btn-primary"
                        >

                            Start Application

                        </a>



                    <?php elseif (
                        $display_status === "submitted"
                    ): ?>


                        <!-- =================================================
                             SUBMITTED
                        ================================================== -->

                        <span class="badge bg-success mb-3">

                            Submitted

                        </span>

                        <br>


                        <a
                            href="application_view.php"
                            class="btn btn-outline-primary"
                        >

                            View Application

                        </a>



                    <?php elseif (
                        $display_status === "approved"
                    ): ?>


                        <!-- =================================================
                             APPROVED
                        ================================================== -->

                        <span class="badge bg-primary mb-3">

                            Approved

                        </span>

                        <br>


                        <a
                            href="application_view.php"
                            class="btn btn-outline-primary"
                        >

                            View Application

                        </a>



                    <?php elseif (
                        $display_status === "awarded"
                    ): ?>


                        <!-- =================================================
                             AWARDED
                        ================================================== -->

                        <span class="badge bg-success mb-3">

                            🏆 Bursary Awarded

                        </span>

                        <br>


                        <?php if ($award): ?>

                            <div class="mt-2 mb-3">

                                <small class="text-muted">

                                    Amount Awarded

                                </small>

                                <div class="award-amount">

                                    KES
                                    <?= number_format(
                                        (float)$award["award_amount"],
                                        2
                                    ) ?>

                                </div>

                            </div>

                        <?php endif; ?>


                        <a
                            href="application_view.php"
                            class="btn btn-outline-success"
                        >

                            View Application

                        </a>



                    <?php elseif (
                        $display_status === "rejected"
                    ): ?>


                        <!-- =================================================
                             REJECTED
                        ================================================== -->

                        <span class="badge bg-danger mb-3">

                            Rejected

                        </span>

                        <br>


                        <p class="small text-muted">

                            You may correct your application
                            and submit it again.

                        </p>


                        <a
                            href="application.php"
                            class="btn btn-danger"
                        >

                            Correct & Resubmit

                        </a>



                    <?php elseif (
                        $display_status === "correction"
                    ): ?>


                        <!-- =================================================
                             CORRECTION
                        ================================================== -->

                        <span
                            class="badge bg-warning text-dark mb-3"
                        >

                            Correction Required

                        </span>

                        <br>


                        <a
                            href="application.php"
                            class="btn btn-warning"
                        >

                            Correct Application

                        </a>



                    <?php elseif (
                        $display_status === "draft"
                    ): ?>


                        <!-- =================================================
                             DRAFT
                        ================================================== -->

                        <span class="badge bg-secondary mb-3">

                            Draft

                        </span>

                        <br>


                        <a
                            href="application.php"
                            class="btn btn-primary"
                        >

                            Continue Application

                        </a>



                    <?php else: ?>


                        <!-- =================================================
                             UNKNOWN
                        ================================================== -->

                        <span class="badge bg-secondary mb-3">

                            Not Started

                        </span>

                        <br>


                        <a
                            href="application.php"
                            class="btn btn-primary"
                        >

                            Start Application

                        </a>


                    <?php endif; ?>

                </div>

            </div>

        </div>



        <!-- =================================================
             APPLICATION STATUS / AWARD
        ================================================== -->

        <div class="col-md-4 mb-4">

            <div
                class="
                    card
                    shadow-sm
                    border-0
                    h-100
                    status-card

                    <?php

                    if (
                        $display_status === "awarded"
                    ) {

                        echo "award-card";

                    } elseif (
                        $display_status === "rejected"
                    ) {

                        echo "rejected-card";

                    } elseif (
                        $display_status === "correction"
                    ) {

                        echo "correction-card";

                    }

                    ?>
                "
            >

                <div class="card-body">


                    <?php if (
                        $display_status === "awarded"
                    ): ?>


                        <!-- =================================================
                             AWARD STATUS
                        ================================================== -->

                        <h5>

                            🏆 Bursary Award

                        </h5>


                        <p class="text-muted">

                            Your bursary award details.

                        </p>


                        <div class="mb-3">

                            <small class="text-muted">

                                Status

                            </small>

                            <br>

                            <span class="badge bg-success">

                                Awarded

                            </span>

                        </div>


                        <?php if ($award): ?>


                            <div class="mb-3">

                                <small class="text-muted">

                                    Amount Awarded

                                </small>

                                <div class="award-amount">

                                    KES
                                    <?= number_format(
                                        (float)$award["award_amount"],
                                        2
                                    ) ?>

                                </div>

                            </div>


                            <?php if (
                                !empty(
                                    $award["award_date"]
                                )
                            ): ?>

                                <div class="mb-3">

                                    <small class="text-muted">

                                        Award Date

                                    </small>

                                    <div>

                                        <?= date(
                                            "d M Y",
                                            strtotime(
                                                $award["award_date"]
                                            )
                                        ) ?>

                                    </div>

                                </div>

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $award["award_status"]
                                )
                            ): ?>

                                <div class="mb-3">

                                    <small class="text-muted">

                                        Award Status

                                    </small>

                                    <div>

                                        <?= htmlspecialchars(
                                            ucfirst(
                                                str_replace(
                                                    "_",
                                                    " ",
                                                    $award[
                                                        "award_status"
                                                    ]
                                                )
                                            )
                                        ) ?>

                                    </div>

                                </div>

                            <?php endif; ?>


                        <?php endif; ?>


                        <a
                            href="application_view.php"
                            class="btn btn-outline-success"
                        >

                            View Application

                        </a>



                    <?php elseif (
                        $display_status === "rejected"
                    ): ?>


                        <!-- =================================================
                             REJECTED STATUS
                        ================================================== -->

                        <h5>

                            Application Rejected

                        </h5>


                        <p class="text-muted">

                            Your application needs to be
                            corrected before it can be
                            reviewed again.

                        </p>


                        <div class="mb-3">

                            <small class="text-muted">

                                Current Status

                            </small>

                            <br>

                            <span class="badge bg-danger">

                                Rejected

                            </span>

                        </div>


                        <a
                            href="application_view.php"
                            class="btn btn-outline-danger me-2"
                        >

                            View Details

                        </a>


                        <a
                            href="application.php"
                            class="btn btn-danger"
                        >

                            Correct & Resubmit

                        </a>



                    <?php elseif (
                        $display_status === "correction"
                    ): ?>


                        <!-- =================================================
                             CORRECTION STATUS
                        ================================================== -->

                        <h5>

                            Correction Required

                        </h5>


                        <p class="text-muted">

                            Please correct the issues identified
                            by the administrator.

                        </p>


                        <div class="mb-3">

                            <small class="text-muted">

                                Current Status

                            </small>

                            <br>

                            <span
                                class="
                                    badge
                                    bg-warning
                                    text-dark
                                "
                            >

                                Correction Required

                            </span>

                        </div>


                        <a
                            href="application_view.php"
                            class="btn btn-outline-warning me-2"
                        >

                            View Details

                        </a>


                        <a
                            href="application.php"
                            class="btn btn-warning"
                        >

                            Correct & Resubmit

                        </a>



                    <?php else: ?>


                        <!-- =================================================
                             NORMAL APPLICATION STATUS
                        ================================================== -->

                        <h5>

                            Application Status

                        </h5>


                        <p class="text-muted">

                            Track the progress of your application.

                        </p>



                        <?php if (
                            $display_status === "submitted"
                        ): ?>


                            <span class="badge bg-success mb-3">

                                Submitted

                            </span>

                            <br>


                            <a
                                href="application_view.php"
                                class="btn btn-outline-primary"
                            >

                                View Application

                            </a>



                        <?php elseif (
                            $display_status === "approved"
                        ): ?>


                            <span class="badge bg-primary mb-3">

                                Approved

                            </span>

                            <br>


                            <a
                                href="application_view.php"
                                class="btn btn-outline-primary"
                            >

                                View Application

                            </a>



                        <?php elseif (
                            $display_status === "draft"
                        ): ?>


                            <span class="badge bg-secondary mb-3">

                                Draft

                            </span>

                            <br>


                            <a
                                href="application.php"
                                class="btn btn-outline-primary"
                            >

                                Continue Application

                            </a>



                        <?php else: ?>


                            <span class="badge bg-secondary mb-3">

                                Not Started

                            </span>

                            <br>


                            <a
                                href="application.php"
                                class="btn btn-outline-primary"
                            >

                                Start Application

                            </a>


                        <?php endif; ?>


                    <?php endif; ?>


                </div>

            </div>

        </div>


    </div>


</div>


</body>

</html>
