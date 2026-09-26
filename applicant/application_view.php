<?php

session_start();

require_once "../config/database.php";


// =====================================================
// SECURITY
// =====================================================

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "applicant") {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION["user_id"];


// =====================================================
// GET APPLICANT + USER INFORMATION
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        a.id AS applicant_id,
        a.user_id,
        a.national_id,
        a.date_of_birth,
        a.gender,
        a.county,
        a.constituency,
        a.ward,
        a.village,
        a.address,

        u.full_name,
        u.email,
        u.phone

    FROM applicants a

    INNER JOIN users u
        ON a.user_id = u.id

    WHERE a.user_id = ?

    LIMIT 1
");

$stmt->execute([$user_id]);

$applicant = $stmt->fetch(PDO::FETCH_ASSOC);


// Applicant profile must exist

if (!$applicant) {
    header("Location: profile.php");
    exit;
}


// =====================================================
// GET APPLICATION
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        id,
        applicant_id,
        application_number,
        academic_year,
        status,
        admin_remarks,
        submitted_at,
        created_at,
        updated_at

    FROM applications

    WHERE applicant_id = ?

    LIMIT 1
");

$stmt->execute([
    $applicant["applicant_id"]
]);

$application = $stmt->fetch(PDO::FETCH_ASSOC);


// Application must exist

if (!$application) {
    header("Location: application.php");
    exit;
}


// =====================================================
// VARIABLES
// =====================================================

$status = $application["status"];


// Choose badge colour

$badge = "secondary";

switch ($status) {

    case "submitted":
        $badge = "success";
        break;

    case "verification":
        $badge = "info";
        break;

    case "under_review":
        $badge = "primary";
        break;

    case "correction_required":
        $badge = "warning";
        break;

    case "approved":
        $badge = "success";
        break;

    case "rejected":
        $badge = "danger";
        break;

    case "awarded":
        $badge = "success";
        break;

    case "paid":
        $badge = "success";
        break;

    case "completed":
        $badge = "success";
        break;
}


// Format status

$display_status = ucwords(
    str_replace(
        "_",
        " ",
        $status
    )
);


// Helper function

function showValue($value)
{
    if ($value === null || trim((string)$value) === "") {
        return "Not provided";
    }

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
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
        My Submitted Application
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- System CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        body {
            background-color: #f7f8fa;
        }

        .page-wrapper {
            max-width: 1100px;
            margin: 0 auto;
        }

        .application-card {
            border: 0;
            border-radius: 12px;
        }

        .section-title {
            font-weight: 600;
        }

        .info-label {
            display: block;
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 4px;
        }

        .info-value {
            font-weight: 600;
            color: #212529;
        }

        .status-badge {
            font-size: 14px;
            padding: 8px 14px;
        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-light bg-white shadow-sm">

    <div class="container">

        <a
            href="dashboard.php"
            class="navbar-brand fw-bold"
        >
            BURSARY APPLICATION SYSTEM
        </a>


        <div>

            <span class="me-3">

                <?php

                echo showValue(
                    $applicant["full_name"]
                );

                ?>

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
     MAIN CONTENT
===================================================== -->

<div class="container py-5">

    <div class="page-wrapper">


        <!-- PAGE HEADER -->

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="fw-bold mb-1">

                    My Submitted Application

                </h2>

                <p class="text-muted mb-0">

                    Review the information submitted
                    in your bursary application.

                </p>

            </div>


            <div>

                <span
                    class="badge bg-<?php echo $badge; ?> status-badge"
                >

                    <?php

                    echo showValue(
                        $display_status
                    );

                    ?>

                </span>

            </div>

        </div>



        <!-- =================================================
             APPLICATION SUMMARY
        ================================================== -->

        <div class="card application-card shadow-sm mb-4">

            <div class="card-body">

                <div class="row">


                    <!-- APPLICATION NUMBER -->

                    <div class="col-md-4 mb-3 mb-md-0">

                        <span class="info-label">
                            Application Number
                        </span>

                        <div class="info-value">

                            <?php

                            echo showValue(
                                $application["application_number"]
                            );

                            ?>

                        </div>

                    </div>


                    <!-- ACADEMIC YEAR -->

                    <div class="col-md-4 mb-3 mb-md-0">

                        <span class="info-label">
                            Academic Year
                        </span>

                        <div class="info-value">

                            <?php

                            echo showValue(
                                $application["academic_year"]
                            );

                            ?>

                        </div>

                    </div>


                    <!-- STATUS -->

                    <div class="col-md-4">

                        <span class="info-label">
                            Application Status
                        </span>

                        <div>

                            <span
                                class="badge bg-<?php echo $badge; ?>"
                            >

                                <?php

                                echo showValue(
                                    $display_status
                                );

                                ?>

                            </span>

                        </div>

                    </div>


                </div>

            </div>

        </div>



        <!-- =================================================
             PERSONAL INFORMATION
        ================================================== -->

        <div class="card application-card shadow-sm mb-4">

            <div class="card-header bg-white py-3">

                <h5 class="section-title mb-0">

                    Personal Information

                </h5>

            </div>


            <div class="card-body">

                <div class="row">


                    <!-- FULL NAME -->

                    <div class="col-md-6 mb-4">

                        <span class="info-label">
                            Full Name
                        </span>

                        <div class="info-value">

                            <?php

                            echo showValue(
                                $applicant["full_name"]
                            );

                            ?>

                        </div>

                    </div>


                    <!-- NATIONAL ID -->

                    <div class="col-md-6 mb-4">

                        <span class="info-label">
                            National ID
                        </span>

                        <div class="info-value">

                            <?php

                            echo showValue(
                                $applicant["national_id"]
                            );

                            ?>

                        </div>

                    </div>


                    <!-- EMAIL -->

                    <div class="col-md-6 mb-4">

                        <span class="info-label">
                            Email Address
                        </span>

                        <div class="info-value">

                            <?php

                            echo showValue(
                                $applicant["email"]
                            );

                            ?>

                        </div>

                    </div>


                    <!-- PHONE -->

                    <div class="col-md-6 mb-4">

                        <span class="info-label">
                            Phone Number
                        </span>

                        <div class="info-value">

                            <?php

                            echo showValue(
                                $applicant["phone"]
                            );

                            ?>

                        </div>

                    </div>


                    <!-- DATE OF BIRTH -->

                    <div class="col-md-6 mb-4">

                        <span class="info-label">
                            Date of Birth
                        </span>

                        <div class="info-value">

                            <?php

                            echo showValue(
                                $applicant["date_of_birth"]
                            );

                            ?>

                        </div>

                    </div>


                    <!-- GENDER -->

                    <div class="col-md-6 mb-4">

                        <span class="info-label">
                            Gender
                        </span>

                        <div class="info-value">

                            <?php

                            echo showValue(
                                $applicant["gender"]
                            );

                            ?>

                        </div>

                    </div>


                    <!-- COUNTY -->

                    <div class="col-md-6 mb-4">

                        <span class="info-label">
                            County
                        </span>

                        <div class="info-value">

                            <?php

                            echo showValue(
                                $applicant["county"]
                            );

                            ?>

                        </div>

                    </div>


                    <!-- CONSTITUENCY -->

                    <div class="col-md-6 mb-4">

                        <span class="info-label">
                            Constituency
                        </span>

                        <div class="info-value">

                            <?php

                            echo showValue(
                                $applicant["constituency"]
                            );

                            ?>

                        </div>

                    </div>


                    <!-- WARD -->

                    <div class="col-md-6 mb-4">

                        <span class="info-label">
                            Ward
                        </span>

                        <div class="info-value">

                            <?php

                            echo showValue(
                                $applicant["ward"]
                            );

                            ?>

                        </div>

                    </div>


                    <!-- VILLAGE -->

                    <div class="col-md-6 mb-4">

                        <span class="info-label">
                            Village
                        </span>

                        <div class="info-value">

                            <?php

                            echo showValue(
                                $applicant["village"]
                            );

                            ?>

                        </div>

                    </div>


                    <!-- ADDRESS -->

                    <div class="col-12">

                        <span class="info-label">
                            Address
                        </span>

                        <div class="info-value">

                            <?php

                            echo showValue(
                                $applicant["address"]
                            );

                            ?>

                        </div>

                    </div>


                </div>

            </div>

        </div>



        <!-- =================================================
             SUBMISSION INFORMATION
        ================================================== -->

        <div class="card application-card shadow-sm mb-4">

            <div class="card-header bg-white py-3">

                <h5 class="section-title mb-0">

                    Submission Information

                </h5>

            </div>


            <div class="card-body">

                <div class="row">


                    <!-- SUBMITTED DATE -->

                    <div class="col-md-6 mb-3">

                        <span class="info-label">
                            Submitted At
                        </span>

                        <div class="info-value">

                            <?php

                            if (
                                !empty(
                                    $application["submitted_at"]
                                )
                            ) {

                                echo showValue(
                                    $application["submitted_at"]
                                );

                            } else {

                                echo "Not submitted";

                            }

                            ?>

                        </div>

                    </div>


                    <!-- CURRENT STATUS -->

                    <div class="col-md-6 mb-3">

                        <span class="info-label">
                            Current Status
                        </span>

                        <div class="info-value">

                            <?php

                            echo showValue(
                                $display_status
                            );

                            ?>

                        </div>

                    </div>


                </div>

            </div>

        </div>



        <!-- =================================================
             ADMIN REMARKS
        ================================================== -->

        <?php

        if (
            !empty(
                $application["admin_remarks"]
            )
        ):

        ?>

            <div class="card application-card shadow-sm mb-4">

                <div class="card-header bg-white py-3">

                    <h5 class="section-title mb-0">

                        Administrator Remarks

                    </h5>

                </div>


                <div class="card-body">

                    <?php

                    echo nl2br(
                        htmlspecialchars(
                            $application["admin_remarks"],
                            ENT_QUOTES,
                            "UTF-8"
                        )
                    );

                    ?>

                </div>

            </div>

        <?php endif; ?>



        <!-- =================================================
             BUTTONS
        ================================================== -->

        <div class="d-flex gap-2 mb-5">

            <a
                href="dashboard.php"
                class="btn btn-primary"
            >

                Back to Dashboard

            </a>


            <button
                type="button"
                onclick="window.print()"
                class="btn btn-outline-secondary"
            >

                Print Application

            </button>

        </div>


    </div>

</div>


</body>

</html>