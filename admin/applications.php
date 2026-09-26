<?php

session_start();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS CONTROL
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| SEARCH & FILTER
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$status = trim($_GET["status"] ?? "");


/*
|--------------------------------------------------------------------------
| APPLICATION QUERY
|--------------------------------------------------------------------------
|
| Relationship:
|
| users
|   ↓
| applicants
|   ↓
| applications
|
*/

$sql = "
    SELECT
        a.id,
        a.applicant_id,
        a.application_number,
        a.academic_year,
        a.status,
        a.admin_remarks,
        a.submitted_at,
        a.created_at,
        a.updated_at,

        ap.national_id,
        ap.date_of_birth,
        ap.gender,
        ap.county,
        ap.constituency,
        ap.ward,

        u.full_name,
        u.email

    FROM applications a

    LEFT JOIN applicants ap
        ON a.applicant_id = ap.id

    LEFT JOIN users u
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
            OR u.email LIKE ?
            OR ap.national_id LIKE ?
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
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($status !== "") {

    $sql .= " AND a.status = ? ";

    $params[] = $status;
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        CASE
            WHEN a.status = 'submitted' THEN 1
            WHEN a.status = 'verification' THEN 2
            WHEN a.status = 'under_review' THEN 3
            ELSE 4
        END,
        a.created_at DESC
";


/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $applications = [];

    $error_message = "Unable to load applications.";

}


/*
|--------------------------------------------------------------------------
| STATUS BADGE
|--------------------------------------------------------------------------
*/

function statusBadge($status)
{
    switch ($status) {

        case "draft":

            return '
                <span class="badge draft">
                    Draft
                </span>
            ';


        case "submitted":

            return '
                <span class="badge pending">
                    Pending Verification
                </span>
            ';


        case "verification":

            return '
                <span class="badge verification">
                    Document Verification
                </span>
            ';


        case "under_review":

            return '
                <span class="badge review">
                    Under Review
                </span>
            ';


        case "approved":

            return '
                <span class="badge approved">
                    Approved
                </span>
            ';


        case "rejected":

            return '
                <span class="badge rejected">
                    Rejected
                </span>
            ';


        case "correction_required":

            return '
                <span class="badge correction">
                    Correction Required
                </span>
            ';


        default:

            return '
                <span class="badge neutral">
                    ' .
                    htmlspecialchars(
                        ucwords(
                            str_replace("_", " ", $status)
                        )
                    )
                    . '
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

    <title>
        Applications | Admin
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <style>

        body {

            background: #f5f7fb;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

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

            overflow-y: auto;

        }


        .logo {

            padding:
                5px 10px 25px;

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

            transition: .2s;

        }


        .sidebar a:hover,
        .sidebar a.active {

            background: #1769d1;

            color: white;

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


        /*
        |--------------------------------------------------------------------------
        | TOP BAR
        |--------------------------------------------------------------------------
        */

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

            margin:
                5px 0 0;

            color: #748094;

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

        table {

            width: 100%;

        }


        th {

            font-size: 12px;

            color: #687386;

            background: #fafbfd !important;

            white-space: nowrap;

        }


        td {

            font-size: 13px;

            vertical-align: middle;

        }


        /*
        |--------------------------------------------------------------------------
        | STATUS BADGES
        |--------------------------------------------------------------------------
        */

        .badge {

            padding:
                7px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 600;

            white-space: nowrap;

        }


        .draft {

            background: #e9ecef;

            color: #495057;

        }


        .pending {

            background: #fff3cd;

            color: #8a6500;

        }


        .verification {

            background: #dff1ff;

            color: #075985;

        }


        .review {

            background: #eee5ff;

            color: #6541a5;

        }


        .approved {

            background: #dff5e6;

            color: #18753a;

        }


        .rejected {

            background: #ffe1e3;

            color: #b4232d;

        }


        .correction {

            background: #fff0dc;

            color: #a35b00;

        }


        .neutral {

            background: #e9ecef;

            color: #495057;

        }


        /*
        |--------------------------------------------------------------------------
        | VIEW BUTTON
        |--------------------------------------------------------------------------
        */

        .view-btn {

            background: #1769d1;

            color: white;

            padding:
                7px 12px;

            border-radius: 5px;

            text-decoration: none;

            font-size: 12px;

            display: inline-block;

        }


        .view-btn:hover {

            background: #0d57b5;

            color: white;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */

        .empty-icon {

            font-size: 40px;

            margin-bottom: 10px;

        }


        /*
        |--------------------------------------------------------------------------
        | LOGOUT
        |--------------------------------------------------------------------------
        */

        .logout {

            margin-top: 30px;

        }


        .logout a {

            color: #ff7373 !important;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 900px) {

            .sidebar {

                width: 210px;

            }

            .content {

                margin-left: 210px;

                padding: 20px;

            }

        }


        @media (max-width: 700px) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;

            }

            .content {

                margin-left: 0;

            }

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


    <a
        href="applications.php"
        class="active"
    >

        📋 All Applications

    </a>


    <a href="document_verification.php">

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
            Applications
        </h2>

        <p>
            Manage and review bursary applications
        </p>

    </div>



    <!-- ======================================================
         APPLICATION PANEL
    ======================================================= -->

    <div class="panel">


        <!-- PANEL HEADER -->

        <div class="panel-header">

            <div
                class="d-flex
                       justify-content-between
                       align-items-center"
            >

                <div>

                    <h5 class="mb-1">

                        All Applications

                    </h5>

                    <small class="text-muted">

                        View and manage applicant submissions

                    </small>

                </div>


                <div>

                    <span class="badge bg-primary">

                        <?= count($applications) ?>

                        Application(s)

                    </span>

                </div>

            </div>

        </div>



        <!-- ==================================================
             ERROR
        =================================================== -->

        <?php if (isset($error_message)): ?>

            <div class="alert alert-danger m-3">

                <?= htmlspecialchars($error_message) ?>

            </div>

        <?php endif; ?>



        <!-- ==================================================
             FILTERS
        =================================================== -->

        <div class="filter-area">


            <form
                method="GET"
                class="row g-3"
            >


                <!-- SEARCH -->

                <div class="col-md-5">

                    <label class="form-label">

                        Search Applicant

                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Application No, Name, Email or National ID"
                        value="<?= htmlspecialchars($search) ?>"
                    >

                </div>



                <!-- STATUS -->

                <div class="col-md-4">

                    <label class="form-label">

                        Filter by Status

                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">

                            All Statuses

                        </option>


                        <option
                            value="draft"
                            <?= $status === "draft" ? "selected" : "" ?>
                        >

                            Draft

                        </option>


                        <option
                            value="submitted"
                            <?= $status === "submitted" ? "selected" : "" ?>
                        >

                            Pending Verification

                        </option>


                        <option
                            value="verification"
                            <?= $status === "verification" ? "selected" : "" ?>
                        >

                            Document Verification

                        </option>


                        <option
                            value="under_review"
                            <?= $status === "under_review" ? "selected" : "" ?>
                        >

                            Under Review

                        </option>


                        <option
                            value="approved"
                            <?= $status === "approved" ? "selected" : "" ?>
                        >

                            Approved

                        </option>


                        <option
                            value="rejected"
                            <?= $status === "rejected" ? "selected" : "" ?>
                        >

                            Rejected

                        </option>


                        <option
                            value="correction_required"
                            <?= $status === "correction_required" ? "selected" : "" ?>
                        >

                            Correction Required

                        </option>

                    </select>

                </div>



                <!-- SEARCH BUTTON -->

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
             APPLICATION TABLE
        =================================================== -->

        <div class="table-responsive">

            <table class="table table-hover mb-0">


                <thead>

                <tr>

                    <th>
                        #
                    </th>

                    <th>
                        Applicant
                    </th>

                    <th>
                        Application No.
                    </th>

                    <th>
                        National ID
                    </th>

                    <th>
                        Academic Year
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Submitted
                    </th>

                    <th>
                        Action
                    </th>

                </tr>

                </thead>



                <tbody>


                <?php if (count($applications) > 0): ?>


                    <?php foreach ($applications as $application): ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <?= htmlspecialchars(
                                    $application["id"]
                                ) ?>

                            </td>



                            <!-- APPLICANT -->

                            <td>

                                <?php if (!empty($application["full_name"])): ?>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $application["full_name"]
                                        ) ?>

                                    </strong>

                                    <br>

                                    <small class="text-muted">

                                        <?= htmlspecialchars(
                                            $application["email"] ?? ""
                                        ) ?>

                                    </small>

                                <?php else: ?>

                                    <span class="text-muted">

                                        Applicant #<?= htmlspecialchars(
                                            $application["applicant_id"]
                                        ) ?>

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- APPLICATION NUMBER -->

                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $application["application_number"]
                                    ) ?>

                                </strong>

                            </td>



                            <!-- NATIONAL ID -->

                            <td>

                                <?= !empty(
                                    $application["national_id"]
                                )

                                    ? htmlspecialchars(
                                        $application["national_id"]
                                    )

                                    : '<span class="text-muted">N/A</span>'
                                ?>

                            </td>



                            <!-- ACADEMIC YEAR -->

                            <td>

                                <?= htmlspecialchars(
                                    $application["academic_year"]
                                ) ?>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <?= statusBadge(
                                    $application["status"]
                                ) ?>

                            </td>



                            <!-- SUBMITTED -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $application["submitted_at"]
                                    )
                                ):

                                    echo date(
                                        "d M Y H:i",
                                        strtotime(
                                            $application["submitted_at"]
                                        )
                                    );

                                else:

                                ?>

                                    <span class="text-muted">

                                        Not submitted

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- ACTION -->

                            <td>

                                <a
                                    href="view_application.php?id=<?= urlencode($application["id"]) ?>"
                                    class="view-btn"
                                >

                                    👁 View

                                </a>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <!-- EMPTY -->

                    <tr>

                        <td
                            colspan="8"
                            class="text-center py-5"
                        >

                            <div
                                class="empty-icon"
                            >

                                📋

                            </div>

                            <h6>

                                No applications found

                            </h6>

                            <p class="text-muted mb-0">

                                Try changing your search or filter.

                            </p>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>



        <!-- ==================================================
             FOOTER
        =================================================== -->

        <div class="p-3 border-top">

            <small class="text-muted">

                Showing

                <strong>
                    <?= count($applications) ?>
                </strong>

                application(s)

            </small>

        </div>


    </div>


</div>


</body>

</html>