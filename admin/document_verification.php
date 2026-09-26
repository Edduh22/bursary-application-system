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
| SEARCH / FILTER
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$verification_status = trim($_GET["verification_status"] ?? "");


/*
|--------------------------------------------------------------------------
| BUILD QUERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        d.id,
        d.application_id,
        d.document_type,
        d.original_name,
        d.file_path,
        d.file_size,
        d.mime_type,
        d.uploaded_at,
        d.verification_status,
        d.verification_remarks,
        d.verified_at,

        a.application_number,
        a.status AS application_status,

        ap.id AS applicant_id,

        u.full_name,
        u.email

    FROM application_documents d

    INNER JOIN applications a
        ON d.application_id = a.id

    INNER JOIN applicants ap
        ON a.applicant_id = ap.id

    INNER JOIN users u
        ON ap.user_id = u.id

    WHERE 1=1
";

$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== "") {

    $sql .= "
        AND (
            a.application_number LIKE ?
            OR u.full_name LIKE ?
            OR d.original_name LIKE ?
            OR d.document_type LIKE ?
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
| VERIFICATION FILTER
|--------------------------------------------------------------------------
*/

if ($verification_status !== "") {

    $sql .= "
        AND d.verification_status = ?
    ";

    $params[] = $verification_status;
}


/*
|--------------------------------------------------------------------------
| LATEST FIRST
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        CASE
            WHEN d.verification_status = 'pending' THEN 0
            ELSE 1
        END,
        d.uploaded_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$countStmt = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(
            CASE
                WHEN verification_status = 'pending'
                THEN 1 ELSE 0
            END
        ) AS pending,
        SUM(
            CASE
                WHEN verification_status = 'verified'
                THEN 1 ELSE 0
            END
        ) AS verified,
        SUM(
            CASE
                WHEN verification_status = 'rejected'
                THEN 1 ELSE 0
            END
        ) AS rejected
    FROM application_documents
");

$counts = $countStmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| VERIFICATION BADGE
|--------------------------------------------------------------------------
*/

function verificationBadge($status)
{
    switch ($status) {

        case "pending":

            return '
                <span class="badge status-pending">
                    Pending
                </span>
            ';

        case "verified":

            return '
                <span class="badge status-verified">
                    ✓ Verified
                </span>
            ';

        case "rejected":

            return '
                <span class="badge status-rejected">
                    ✕ Rejected
                </span>
            ';

        default:

            return '
                <span class="badge status-pending">
                    Pending
                </span>
            ';
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

    <title>Document Verification | Admin</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <style>

        body {
            background: #f5f7fb;
            font-family: Arial, Helvetica, sans-serif;
        }


        /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

        .sidebar {

            width: 250px;

            position: fixed;

            top: 0;
            bottom: 0;
            left: 0;

            background: #09234d;

            color: white;

            padding: 25px 15px;
        }


        .logo {

            padding: 5px 10px 25px;

            border-bottom:
                1px solid
                rgba(255,255,255,.15);

            margin-bottom: 20px;
        }


        .logo h4 {

            margin: 0;

            font-size: 18px;
        }


        .logo small {

            color: #aebed5;
        }


        .sidebar a {

            display: block;

            padding: 12px 15px;

            margin-bottom: 6px;

            color: #dbe6f5;

            text-decoration: none;

            border-radius: 7px;
        }


        .sidebar a:hover,
        .sidebar a.active {

            background: #1769d1;

            color: white;
        }


        .logout {

            position: absolute;

            bottom: 20px;

            left: 15px;

            right: 15px;
        }


        .logout a {

            color: #ff7373 !important;
        }


        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .content {

            margin-left: 250px;

            padding: 30px;
        }


        .topbar {

            background: white;

            padding: 20px 25px;

            border-radius: 10px;

            margin-bottom: 25px;

            border:
                1px solid
                #e6eaf0;
        }


        .topbar h2 {

            margin: 0;

            font-size: 24px;
        }


        .topbar p {

            margin: 5px 0 0;

            color: #748094;
        }


        /*
        |--------------------------------------------------------------------------
        | STAT CARDS
        |--------------------------------------------------------------------------
        */

        .stat-card {

            background: white;

            border:
                1px solid
                #e6eaf0;

            border-radius: 10px;

            padding: 20px;

            height: 100%;
        }


        .stat-title {

            color: #748094;

            font-size: 13px;

            margin-bottom: 8px;
        }


        .stat-number {

            font-size: 28px;

            font-weight: 700;
        }


        /*
        |--------------------------------------------------------------------------
        | PANEL
        |--------------------------------------------------------------------------
        */

        .panel {

            background: white;

            border:
                1px solid
                #e6eaf0;

            border-radius: 10px;

            overflow: hidden;
        }


        .panel-header {

            padding: 20px;

            border-bottom:
                1px solid
                #edf0f4;
        }


        .filter-area {

            padding: 20px;

            background: #fafbfd;

            border-bottom:
                1px solid
                #edf0f4;
        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        th {

            font-size: 12px;

            color: #687386;

            background:
                #fafbfd !important;

            white-space: nowrap;
        }


        td {

            font-size: 13px;

            vertical-align: middle;
        }


        /*
        |--------------------------------------------------------------------------
        | BADGES
        |--------------------------------------------------------------------------
        */

        .badge {

            padding: 7px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 600;
        }


        .status-pending {

            background: #fff3cd;

            color: #8a6500;
        }


        .status-verified {

            background: #dff5e6;

            color: #18753a;
        }


        .status-rejected {

            background: #ffe1e3;

            color: #b4232d;
        }


        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .view-btn {

            background: #1769d1;

            color: white;

            padding: 7px 12px;

            border-radius: 5px;

            text-decoration: none;

            font-size: 12px;
        }


        .view-btn:hover {

            background: #0d57b5;

            color: white;
        }


        .file-name {

            max-width: 230px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;
        }


    </style>

</head>


<body>


<!-- ==========================================================
     SIDEBAR
=========================================================== -->

<div class="sidebar">


    <div class="logo">

        <h4>
            🎓 BURSARY SYSTEM
        </h4>

        <small>
            Administrator Panel
        </small>

    </div>


    <a href="dashboard.php">
        🏠 Dashboard
    </a>


    <a href="applications.php">
        📋 All Applications
    </a>


    <a
        href="document_verification.php"
        class="active"
    >
        📄 Document Verification
    </a>


    <a href="review_application.php">
        🔍 Pending Review
    </a>


    <a href="awards.php">
        🏆 Awards
    </a>


    <a href="payments.php">
        💳 Payments
    </a>


    <a href="applicants.php">
        👥 Applicants
    </a>


    <a href="reports.php">
        📊 Reports
    </a>


    <a href="users.php">
        👤 Users & Roles
    </a>


    <a href="settings.php">
        ⚙️ Settings
    </a>


    <div class="logout">

        <a href="logout.php">
            🚪 Logout
        </a>

    </div>

</div>



<!-- ==========================================================
     MAIN CONTENT
=========================================================== -->

<div class="content">


    <!-- HEADER -->

    <div class="topbar">

        <h2>
            Document Verification
        </h2>

        <p>
            Review and verify documents submitted by applicants.
        </p>

    </div>



    <!-- ======================================================
         STATISTICS
    ======================================================= -->

    <div class="row g-3 mb-4">


        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-title">
                    Total Documents
                </div>

                <div class="stat-number">

                    <?= (int)($counts["total"] ?? 0) ?>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-title">
                    Pending Verification
                </div>

                <div class="stat-number text-warning">

                    <?= (int)($counts["pending"] ?? 0) ?>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-title">
                    Verified
                </div>

                <div class="stat-number text-success">

                    <?= (int)($counts["verified"] ?? 0) ?>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="stat-card">

                <div class="stat-title">
                    Rejected
                </div>

                <div class="stat-number text-danger">

                    <?= (int)($counts["rejected"] ?? 0) ?>

                </div>

            </div>

        </div>


    </div>



    <!-- ======================================================
         DOCUMENT PANEL
    ======================================================= -->

    <div class="panel">


        <div class="panel-header">

            <h5 class="mb-0">
                Submitted Documents
            </h5>

        </div>



        <!-- FILTER -->

        <div class="filter-area">


            <form
                method="GET"
                class="row g-3"
            >


                <div class="col-md-5">

                    <label class="form-label">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Application number, applicant or document..."
                        value="<?= htmlspecialchars($search) ?>"
                    >

                </div>



                <div class="col-md-4">

                    <label class="form-label">
                        Verification Status
                    </label>

                    <select
                        name="verification_status"
                        class="form-select"
                    >

                        <option value="">
                            All Statuses
                        </option>


                        <option
                            value="pending"
                            <?= $verification_status === "pending"
                                ? "selected"
                                : "" ?>
                        >
                            Pending
                        </option>


                        <option
                            value="verified"
                            <?= $verification_status === "verified"
                                ? "selected"
                                : "" ?>
                        >
                            Verified
                        </option>


                        <option
                            value="rejected"
                            <?= $verification_status === "rejected"
                                ? "selected"
                                : "" ?>
                        >
                            Rejected
                        </option>

                    </select>

                </div>



                <div class="col-md-3 d-flex align-items-end">

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
                        🔍 Search
                    </button>

                </div>


            </form>

        </div>



        <!-- ==================================================
             TABLE
        =================================================== -->

        <div class="table-responsive">


            <table class="table table-hover mb-0">


                <thead>

                <tr>

                    <th>#</th>

                    <th>Application</th>

                    <th>Applicant</th>

                    <th>Document</th>

                    <th>Uploaded</th>

                    <th>Status</th>

                    <th>Action</th>

                </tr>

                </thead>



                <tbody>


                <?php if (count($documents) > 0): ?>


                    <?php foreach ($documents as $document): ?>


                        <tr>


                            <td>

                                <?= htmlspecialchars(
                                    $document["id"]
                                ) ?>

                            </td>



                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $document["application_number"]
                                    ) ?>

                                </strong>

                                <br>

                                <small class="text-muted">

                                    ID:
                                    <?= htmlspecialchars(
                                        $document["application_id"]
                                    ) ?>

                                </small>

                            </td>



                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $document["full_name"]
                                    ) ?>

                                </strong>

                                <br>

                                <small class="text-muted">

                                    <?= htmlspecialchars(
                                        $document["email"]
                                    ) ?>

                                </small>

                            </td>



                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $document["document_type"]
                                    ) ?>

                                </strong>

                                <br>

                                <small
                                    class="text-muted file-name d-block"
                                    title="<?= htmlspecialchars(
                                        $document["original_name"]
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        $document["original_name"]
                                    ) ?>

                                </small>

                            </td>



                            <td>

                                <?php

                                if (!empty($document["uploaded_at"])) {

                                    echo date(
                                        "d M Y H:i",
                                        strtotime(
                                            $document["uploaded_at"]
                                        )
                                    );

                                } else {

                                    echo "N/A";

                                }

                                ?>

                            </td>



                            <td>

                                <?= verificationBadge(
                                    $document["verification_status"]
                                ) ?>


                                <?php if (
                                    !empty(
                                        $document["verification_remarks"]
                                    )
                                ): ?>

                                    <br>

                                    <small
                                        class="text-muted"
                                        title="<?= htmlspecialchars(
                                            $document[
                                                "verification_remarks"
                                            ]
                                        ) ?>"
                                    >

                                        📝 Remarks

                                    </small>

                                <?php endif; ?>

                            </td>



                            <td>

                                <a
                                    href="verify_document.php?id=<?= (int)$document["id"] ?>"
                                    class="view-btn"
                                >
                                    Review
                                </a>

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

                                <div
                                    style="font-size:40px;"
                                >
                                    📄
                                </div>

                                <p class="mb-0">

                                    No documents found.

                                </p>

                            </div>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>


            </table>


        </div>



        <div class="p-3 border-top">

            <small class="text-muted">

                Showing

                <strong>
                    <?= count($documents) ?>
                </strong>

                document(s)

            </small>

        </div>


    </div>


</div>


</body>

</html>