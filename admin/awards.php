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
| FETCH APPROVED / AWARDED APPLICATIONS
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

    LEFT JOIN awards aw
        ON a.id = aw.application_id

    WHERE a.status IN ('approved', 'awarded')
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


$sql .= "
    ORDER BY
        CASE
            WHEN a.status = 'approved' THEN 0
            ELSE 1
        END,
        a.updated_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$countStmt = $pdo->query("
    SELECT

        COUNT(*) AS total,

        SUM(
            CASE
                WHEN status = 'approved'
                THEN 1
                ELSE 0
            END
        ) AS pending_award,

        SUM(
            CASE
                WHEN status = 'awarded'
                THEN 1
                ELSE 0
            END
        ) AS awarded

    FROM applications

    WHERE status IN ('approved', 'awarded')
");

$counts = $countStmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

$success_message = "";

if (isset($_GET["success"])) {

    $success_message =
        "Award created successfully.";
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
        Awards - Bursary Admin
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

        .award-amount {
            font-weight: 700;
            color: #198754;
        }

        @media(max-width:768px) {

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

            <div class="mb-4">

                <h2 class="fw-bold">
                    🏆 Bursary Awards
                </h2>

                <p class="text-muted">
                    Manage awards for approved bursary applications.
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

                <div class="col-md-4">

                    <div class="stat-card">

                        <div class="stat-title">
                            Applications Ready for Award
                        </div>

                        <div class="stat-number text-primary">

                            <?= (int)(
                                $counts["pending_award"] ?? 0
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="stat-card">

                        <div class="stat-title">
                            Awarded Applications
                        </div>

                        <div class="stat-number text-success">

                            <?= (int)(
                                $counts["awarded"] ?? 0
                            ) ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="stat-card">

                        <div class="stat-title">
                            Total Approved / Awarded
                        </div>

                        <div class="stat-number">

                            <?= (int)(
                                $counts["total"] ?? 0
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
                 APPLICATION TABLE
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
                                Fees Balance
                            </th>

                            <th>
                                Award
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


                        <?php if (count($applications) > 0): ?>


                            <?php foreach (
                                $applications
                                as $application
                            ): ?>


                                <tr>


                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $application[
                                                    "application_number"
                                                ]
                                            ) ?>

                                        </strong>

                                        <br>

                                        <small class="text-muted">

                                            <?= htmlspecialchars(
                                                $application[
                                                    "academic_year"
                                                ]
                                            ) ?>

                                        </small>

                                    </td>


                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $application[
                                                    "full_name"
                                                ]
                                            ) ?>

                                        </strong>

                                        <br>

                                        <small class="text-muted">

                                            <?= htmlspecialchars(
                                                $application[
                                                    "email"
                                                ]
                                            ) ?>

                                        </small>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $application[
                                                "institution_name"
                                            ] ?? "Not provided"
                                        ) ?>

                                        <br>

                                        <small class="text-muted">

                                            <?= htmlspecialchars(
                                                $application[
                                                    "course"
                                                ] ?? ""
                                            ) ?>

                                        </small>

                                    </td>


                                    <td>

                                        KES
                                        <?= number_format(
                                            (float)(
                                                $application[
                                                    "fees_balance"
                                                ] ?? 0
                                            ),
                                            2
                                        ) ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            $application[
                                                "award_amount"
                                            ] !== null
                                        ): ?>

                                            <span
                                                class="award-amount"
                                            >

                                                KES
                                                <?= number_format(
                                                    (float)(
                                                        $application[
                                                            "award_amount"
                                                        ]
                                                    ),
                                                    2
                                                ) ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                Not awarded
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            $application["status"]
                                            === "approved"
                                        ): ?>

                                            <span
                                                class="badge bg-primary"
                                            >
                                                Approved
                                            </span>

                                        <?php elseif (
                                            $application["status"]
                                            === "awarded"
                                        ): ?>

                                            <span
                                                class="badge bg-success"
                                            >
                                                Awarded
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            $application["status"]
                                            === "approved"
                                        ): ?>

                                            <a
                                                href="create_award.php?id=<?= (int)$application["application_id"] ?>"
                                                class="btn btn-sm btn-success"
                                            >
                                                🏆 Create Award
                                            </a>

                                        <?php else: ?>

                                            <a
                                                href="view_award.php?id=<?= (int)$application["application_id"] ?>"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                👁 View Award
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

                                        🏆 No approved applications
                                        are currently available for
                                        awarding.

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