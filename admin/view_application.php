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
| FETCH MAIN APPLICATION + APPLICANT + USER
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        applications.id,
        applications.application_number,
        applications.academic_year,
        applications.status,
        applications.admin_remarks,
        applications.submitted_at,
        applications.created_at,
        applications.updated_at,

        applicants.id AS applicant_id,
        applicants.national_id,
        applicants.date_of_birth,
        applicants.gender,
        applicants.county,
        applicants.constituency,
        applicants.ward,
        applicants.village,
        applicants.address,

        users.full_name,
        users.email

    FROM applications

    INNER JOIN applicants
        ON applications.applicant_id = applicants.id

    INNER JOIN users
        ON applicants.user_id = users.id

    WHERE applications.id = ?

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
| FETCH EDUCATION
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM application_education
    WHERE application_id = ?
    LIMIT 1
");

$stmt->execute([$application_id]);

$education = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| FETCH GUARDIAN
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM application_guardians
    WHERE application_id = ?
    LIMIT 1
");

$stmt->execute([$application_id]);

$guardian = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| FETCH FINANCIAL INFORMATION
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM application_financial
    WHERE application_id = ?
    LIMIT 1
");

$stmt->execute([$application_id]);

$financial = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| FETCH SPECIAL CIRCUMSTANCES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM application_circumstances
    WHERE application_id = ?
    LIMIT 1
");

$stmt->execute([$application_id]);

$circumstances = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| FETCH DOCUMENTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM application_documents
    WHERE application_id = ?
    ORDER BY uploaded_at DESC
");

$stmt->execute([$application_id]);

$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| STATUS BADGE
|--------------------------------------------------------------------------
*/

$status = $application["status"];

$status_class = "secondary";

switch ($status) {

    case "draft":
        $status_class = "secondary";
        break;

    case "submitted":
        $status_class = "primary";
        break;

    case "verification":
        $status_class = "warning";
        break;

    case "under_review":
        $status_class = "info";
        break;

    case "correction_required":
        $status_class = "warning";
        break;

    case "approved":
        $status_class = "success";
        break;

    case "rejected":
        $status_class = "danger";
        break;

    case "awarded":
        $status_class = "success";
        break;

    case "paid":
        $status_class = "success";
        break;
}


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

$success_message = "";

if (isset($_GET["success"])) {
    $success_message = "Application status updated successfully.";
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
        View Application -
        <?= htmlspecialchars($application["application_number"]) ?>
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
            word-break: break-word;
        }

        .document-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: #fff;
        }

        .action-card {
            border-left: 5px solid #1769d1;
        }

        .status-box {
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
        }

        @media (max-width: 768px) {

            .sidebar {
                min-height: auto;
            }

            .info-value {
                font-size: 14px;
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

            <?= htmlspecialchars($_SESSION["full_name"] ?? "Administrator") ?>

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

                <a
                    href="applications.php"
                    class="active"
                >
                    📋 Applications
                </a>

                <a href="applicants.php">
                    👥 Applicants
                </a>

                <a href="document_verification.php">
                    📄 Documents
                </a>

                <a href="awards.php">
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


            <!-- ==================================================
                 HEADER
            =================================================== -->

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h2 class="fw-bold mb-1">
                        Application Details
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


                <div>

                    <span
                        class="badge bg-<?= $status_class ?> fs-6"
                    >

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

                </div>

            </div>


            <!-- ==================================================
                 BACK BUTTON
            =================================================== -->

            <a
                href="applications.php"
                class="btn btn-outline-secondary mb-4"
            >
                ← Back to Applications
            </a>


            <!-- ==================================================
                 SUCCESS MESSAGE
            =================================================== -->

            <?php if ($success_message !== ""): ?>

                <div class="alert alert-success alert-dismissible fade show">

                    ✓ <?= htmlspecialchars($success_message) ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


            <!-- ==================================================
                 APPLICATION INFORMATION
            =================================================== -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <h5 class="section-title">
                        📋 Application Information
                    </h5>

                    <div class="row">

                        <div class="col-md-4">

                            <div class="info-label">
                                Application Number
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application["application_number"]
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                Academic Year
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application["academic_year"]
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                Submitted At
                            </div>

                            <div class="info-value">

                                <?php if (!empty($application["submitted_at"])): ?>

                                    <?= htmlspecialchars(
                                        date(
                                            "d M Y H:i",
                                            strtotime(
                                                $application["submitted_at"]
                                            )
                                        )
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        Not submitted
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                Created At
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    date(
                                        "d M Y H:i",
                                        strtotime(
                                            $application["created_at"]
                                        )
                                    )
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                Last Updated
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    date(
                                        "d M Y H:i",
                                        strtotime(
                                            $application["updated_at"]
                                        )
                                    )
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                Applicant ID
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application["applicant_id"]
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 PERSONAL INFORMATION
            =================================================== -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <h5 class="section-title">
                        👤 Applicant Information
                    </h5>

                    <div class="row">

                        <div class="col-md-6">

                            <div class="info-label">
                                Full Name
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application["full_name"]
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-label">
                                Email
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application["email"]
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                National ID
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application["national_id"] ?? ""
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                Date of Birth
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application["date_of_birth"] ?? ""
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                Gender
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application["gender"] ?? ""
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                County
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application["county"] ?? ""
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                Constituency
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application["constituency"] ?? ""
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-label">
                                Ward
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application["ward"] ?? ""
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-label">
                                Village
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application["village"] ?? ""
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-label">
                                Address
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $application["address"] ?? ""
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

                    <?php if ($education): ?>

                        <div class="row">

                            <div class="col-md-6">

                                <div class="info-label">
                                    Institution
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $education["institution_name"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-6">

                                <div class="info-label">
                                    Education Level
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $education["education_level"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-6">

                                <div class="info-label">
                                    Course
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $education["course"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="info-label">
                                    Year of Study
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $education["year_of_study"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="info-label">
                                    Admission Number
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $education["admission_number"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-6">

                                <div class="info-label">
                                    Previous School
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $education["previous_school"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-6">

                                <div class="info-label">
                                    Previous Grade
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $education["previous_grade"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="info-label">
                                    School Fees Required
                                </div>

                                <div class="info-value">

                                    KES
                                    <?= number_format(
                                        (float)($education["school_fees"] ?? 0),
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
                                        (float)($education["fees_paid"] ?? 0),
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
                                        (float)($education["fees_balance"] ?? 0),
                                        2
                                    ) ?>

                                </div>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="alert alert-warning mb-0">
                            No education information submitted.
                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- ==================================================
                 GUARDIAN
            =================================================== -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <h5 class="section-title">
                        👨‍👩‍👧 Guardian / Parent Information
                    </h5>

                    <?php if ($guardian): ?>

                        <div class="row">

                            <div class="col-md-6">

                                <div class="info-label">
                                    Guardian Name
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $guardian["guardian_name"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-6">

                                <div class="info-label">
                                    Relationship
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $guardian["relationship"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="info-label">
                                    Phone
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $guardian["phone"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="info-label">
                                    Occupation
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $guardian["occupation"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="info-label">
                                    Marital Status
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $guardian["marital_status"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="info-label">
                                    Number of Dependants
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $guardian["number_of_dependants"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="info-label">
                                    Guardian Income
                                </div>

                                <div class="info-value">

                                    KES
                                    <?= number_format(
                                        (float)($guardian["guardian_income"] ?? 0),
                                        2
                                    ) ?>

                                </div>

                            </div>


                            <div class="col-md-6">

                                <div class="info-label">
                                    Second Parent
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $guardian["second_parent_name"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-6">

                                <div class="info-label">
                                    Second Parent Phone
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $guardian["second_parent_phone"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-6">

                                <div class="info-label">
                                    Second Parent Occupation
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $guardian["second_parent_occupation"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-6">

                                <div class="info-label">
                                    Second Parent Income
                                </div>

                                <div class="info-value">

                                    KES
                                    <?= number_format(
                                        (float)($guardian["second_parent_income"] ?? 0),
                                        2
                                    ) ?>

                                </div>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="alert alert-warning mb-0">
                            No guardian information submitted.
                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- ==================================================
                 FINANCIAL
            =================================================== -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <h5 class="section-title">
                        💰 Financial Information
                    </h5>

                    <?php if ($financial): ?>

                        <div class="row">

                            <div class="col-md-4">

                                <div class="info-label">
                                    Household Income
                                </div>

                                <div class="info-value">

                                    KES
                                    <?= number_format(
                                        (float)($financial["household_income"] ?? 0),
                                        2
                                    ) ?>

                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="info-label">
                                    Income Source
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $financial["income_source"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="info-label">
                                    Household Members
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $financial["household_members"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="info-label">
                                    Children in School
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $financial["children_in_school"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="info-label">
                                    Monthly Expenses
                                </div>

                                <div class="info-value">

                                    KES
                                    <?= number_format(
                                        (float)($financial["monthly_expenses"] ?? 0),
                                        2
                                    ) ?>

                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="info-label">
                                    School Fees Required
                                </div>

                                <div class="info-value">

                                    KES
                                    <?= number_format(
                                        (float)($financial["school_fees_required"] ?? 0),
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
                                        (float)($financial["fees_paid"] ?? 0),
                                        2
                                    ) ?>

                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="info-label">
                                    Outstanding Fees
                                </div>

                                <div class="info-value">

                                    KES
                                    <?= number_format(
                                        (float)($financial["outstanding_fees"] ?? 0),
                                        2
                                    ) ?>

                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="info-label">
                                    Other Support
                                </div>

                                <div class="info-value">
                                    <?= htmlspecialchars(
                                        $financial["other_support"] ?? ""
                                    ) ?>
                                </div>

                            </div>


                            <div class="col-12">

                                <div class="info-label">
                                    Financial Challenges
                                </div>

                                <div class="info-value">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $financial["financial_challenges"] ?? ""
                                        )
                                    ) ?>

                                </div>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="alert alert-warning mb-0">
                            No financial information submitted.
                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- ==================================================
                 SPECIAL CIRCUMSTANCES
            =================================================== -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <h5 class="section-title">
                        📝 Special Circumstances
                    </h5>

                    <?php if ($circumstances): ?>

                        <div class="row">

                            <div class="col-md-4">

                                <div class="info-label">
                                    Has Special Circumstances?
                                </div>

                                <div class="info-value">

                                    <?= htmlspecialchars(
                                        $circumstances[
                                            "has_special_circumstances"
                                        ] ?? ""
                                    ) ?>

                                </div>

                            </div>


                            <div class="col-md-8">

                                <div class="info-label">
                                    Circumstance Type
                                </div>

                                <div class="info-value">

                                    <?= htmlspecialchars(
                                        $circumstances[
                                            "circumstance_type"
                                        ] ?? ""
                                    ) ?>

                                </div>

                            </div>


                            <div class="col-12">

                                <div class="info-label">
                                    Description
                                </div>

                                <div class="info-value">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $circumstances[
                                                "description"
                                            ] ?? ""
                                        )
                                    ) ?>

                                </div>

                            </div>


                            <div class="col-12">

                                <div class="info-label">
                                    Supporting Details
                                </div>

                                <div class="info-value">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $circumstances[
                                                "supporting_details"
                                            ] ?? ""
                                        )
                                    ) ?>

                                </div>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="alert alert-info mb-0">
                            No special circumstances information submitted.
                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- ==================================================
                 DOCUMENTS
            =================================================== -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <h5 class="section-title">
                        📎 Uploaded Documents
                    </h5>


                    <?php if (count($documents) > 0): ?>

                        <?php foreach ($documents as $document): ?>

                            <div class="document-card">

                                <div class="row align-items-center">

                                    <div class="col-md-5">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $document["document_type"] ?? ""
                                            ) ?>

                                        </strong>

                                        <br>

                                        <small class="text-muted">

                                            <?= htmlspecialchars(
                                                $document["original_name"] ?? ""
                                            ) ?>

                                        </small>

                                    </div>


                                    <div class="col-md-3">

                                        <small class="text-muted">

                                            Uploaded:

                                            <?= htmlspecialchars(
                                                $document["uploaded_at"] ?? ""
                                            ) ?>

                                        </small>

                                    </div>


                                    <div class="col-md-2">

                                        <small>

                                            <?php

                                            $size =
                                                (int)($document["file_size"] ?? 0);

                                            echo number_format(
                                                $size / 1024,
                                                1
                                            );

                                            ?>

                                            KB

                                        </small>

                                    </div>


                                    <!-- ==================================================
                                         FIXED DOCUMENT BUTTON
                                    =================================================== -->

                                    <div class="col-md-2 text-end">

                                        <a
                                            href="verify_document.php?id=<?= (int)$document["id"] ?>"
                                            target="_blank"
                                            class="btn btn-sm btn-primary"
                                        >
                                            👁 View
                                        </a>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="alert alert-warning mb-0">
                            No documents uploaded.
                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- ==================================================
                 ADMIN REMARKS
            =================================================== -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">

                    <h5 class="section-title">
                        💬 Admin Remarks
                    </h5>


                    <?php if (!empty($application["admin_remarks"])): ?>

                        <div class="alert alert-secondary">

                            <?= nl2br(
                                htmlspecialchars(
                                    $application["admin_remarks"]
                                )
                            ) ?>

                        </div>

                    <?php else: ?>

                        <p class="text-muted">
                            No remarks have been added yet.
                        </p>

                    <?php endif; ?>

                </div>

            </div>


            <!-- ==================================================
                 ADMIN ACTIONS
            =================================================== -->

            <div class="card shadow-sm border-0 mb-5 action-card">

                <div class="card-body">

                    <h5 class="section-title">
                        ⚙️ Application Actions
                    </h5>


                    <?php if (isset($_GET["success"])): ?>

                        <div class="alert alert-success alert-dismissible fade show">

                            ✓ Application status updated successfully.

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                            ></button>

                        </div>

                    <?php endif; ?>


                    <!-- START REVIEW -->

                    <?php if (
                        $application["status"] === "submitted" ||
                        $application["status"] === "verification"
                    ): ?>

                        <form
                            method="POST"
                            action="application_action.php"
                            class="d-inline"
                        >

                            <input
                                type="hidden"
                                name="application_id"
                                value="<?= htmlspecialchars(
                                    $application["id"]
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="start_review"
                            >

                            <button
                                type="submit"
                                class="btn btn-warning"
                                onclick="return confirm('Start reviewing this application?');"
                            >
                                🔍 Start Review
                            </button>

                        </form>

                    <?php endif; ?>


                    <!-- REQUEST CORRECTION -->

                    <?php if (
                        $application["status"] === "under_review"
                    ): ?>

                        <button
                            type="button"
                            class="btn btn-info"
                            data-bs-toggle="modal"
                            data-bs-target="#correctionModal"
                        >
                            📄 Request Correction
                        </button>


                        <!-- APPROVE -->

                        <form
                            method="POST"
                            action="application_action.php"
                            class="d-inline"
                        >

                            <input
                                type="hidden"
                                name="application_id"
                                value="<?= htmlspecialchars(
                                    $application["id"]
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="approve"
                            >

                            <button
                                type="submit"
                                class="btn btn-success"
                                onclick="return confirm('Are you sure you want to approve this application?');"
                            >
                                ✓ Approve
                            </button>

                        </form>


                        <!-- REJECT -->

                        <button
                            type="button"
                            class="btn btn-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#rejectModal"
                        >
                            ✕ Reject
                        </button>

                    <?php endif; ?>


                    <!-- APPROVED -->

                    <?php if (
                        $application["status"] === "approved"
                    ): ?>

                        <div class="alert alert-success mt-3 mb-0">

                            ✓ This application has been approved and is ready
                            for the award stage.

                        </div>

                    <?php endif; ?>


                    <!-- REJECTED -->

                    <?php if (
                        $application["status"] === "rejected"
                    ): ?>

                        <div class="alert alert-danger mt-3 mb-0">

                            ✕ This application has been rejected.

                        </div>

                    <?php endif; ?>


                    <!-- CORRECTION REQUIRED -->

                    <?php if (
                        $application["status"] === "correction_required"
                    ): ?>

                        <div class="alert alert-warning mt-3 mb-0">

                            📄 Correction has been requested from the
                            applicant.

                        </div>

                    <?php endif; ?>


                    <!-- DRAFT -->

                    <?php if (
                        $application["status"] === "draft"
                    ): ?>

                        <div class="alert alert-secondary mt-3 mb-0">

                            📝 This application is still a draft and has not
                            yet been submitted by the applicant.

                        </div>

                    <?php endif; ?>


                    <!-- AWARDED -->

                    <?php if (
                        $application["status"] === "awarded"
                    ): ?>

                        <div class="alert alert-success mt-3 mb-0">

                            🏆 This application has been awarded.

                        </div>

                    <?php endif; ?>


                    <!-- PAID -->

                    <?php if (
                        $application["status"] === "paid"
                    ): ?>

                        <div class="alert alert-success mt-3 mb-0">

                            💳 Payment for this application has been
                            completed.

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- ==========================================================
     CORRECTION MODAL
=========================================================== -->

<div
    class="modal fade"
    id="correctionModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog">

        <div class="modal-content">

            <form
                method="POST"
                action="application_action.php"
            >

                <div class="modal-header">

                    <h5 class="modal-title">
                        📄 Request Correction
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">

                    <input
                        type="hidden"
                        name="application_id"
                        value="<?= htmlspecialchars(
                            $application["id"]
                        ) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="request_correction"
                    >


                    <label class="form-label fw-semibold">
                        Correction Required
                    </label>


                    <textarea
                        name="remarks"
                        class="form-control"
                        rows="5"
                        placeholder="Explain what the applicant needs to correct..."
                        required
                    ></textarea>


                    <div class="form-text">

                        Be specific so the applicant knows exactly what
                        needs to be corrected.

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>


                    <button
                        type="submit"
                        class="btn btn-info"
                    >
                        📄 Send Correction Request
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- ==========================================================
     REJECTION MODAL
=========================================================== -->

<div
    class="modal fade"
    id="rejectModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog">

        <div class="modal-content">

            <form
                method="POST"
                action="application_action.php"
            >

                <div class="modal-header">

                    <h5 class="modal-title">
                        ✕ Reject Application
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">

                    <input
                        type="hidden"
                        name="application_id"
                        value="<?= htmlspecialchars(
                            $application["id"]
                        ) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="reject"
                    >


                    <label class="form-label fw-semibold">
                        Reason for Rejection
                    </label>


                    <textarea
                        name="remarks"
                        class="form-control"
                        rows="5"
                        placeholder="Enter reason for rejecting this application..."
                        required
                    ></textarea>


                    <div class="form-text">

                        The rejection reason will be stored as an admin
                        remark.

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>


                    <button
                        type="submit"
                        class="btn btn-danger"
                        onclick="return confirm('Are you sure you want to reject this application?');"
                    >
                        ✕ Reject Application
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- ==========================================================
     BOOTSTRAP JAVASCRIPT
=========================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>
