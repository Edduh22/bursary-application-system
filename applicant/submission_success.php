<?php

session_start();

require_once "../config/database.php";


// =====================================================
// SECURITY CHECK
// =====================================================

if (!isset($_SESSION["user_id"])) {

    header("Location: ../login.php");
    exit;

}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "applicant") {

    header("Location: ../login.php");
    exit;

}


// =====================================================
// GET LOGGED-IN USER
// =====================================================

$user_id = $_SESSION["user_id"];


// =====================================================
// GET APPLICANT
// We only need the applicant ID.
// Do NOT request full_name because that column
// does not exist in your applicants table.
// =====================================================

$stmt = $pdo->prepare(
    "SELECT id
     FROM applicants
     WHERE user_id = ?
     LIMIT 1"
);

$stmt->execute([$user_id]);

$applicant = $stmt->fetch(PDO::FETCH_ASSOC);


// Applicant must exist

if (!$applicant) {

    header("Location: profile.php");
    exit;

}


// =====================================================
// GET APPLICATION
// =====================================================

$stmt = $pdo->prepare(
    "SELECT *
     FROM applications
     WHERE applicant_id = ?
     LIMIT 1"
);

$stmt->execute([
    $applicant["id"]
]);

$application = $stmt->fetch(PDO::FETCH_ASSOC);


// Application must exist

if (!$application) {

    header("Location: application.php");
    exit;

}


// =====================================================
// APPLICATION DETAILS
// =====================================================

$application_number =
    $application["application_number"] ?? "N/A";

$status =
    $application["status"] ?? "Submitted";


// Name comes from the login session

$full_name =
    $_SESSION["full_name"] ?? "Applicant";

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
        Application Submitted
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

                echo htmlspecialchars($full_name);

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

    <div class="row justify-content-center">

        <div class="col-lg-7">


            <div class="card shadow-sm border-0 text-center">


                <div class="card-body p-5">


                    <!-- SUCCESS ICON -->

                    <div
                        class="rounded-circle bg-success text-white
                               d-inline-flex align-items-center
                               justify-content-center mb-4"
                        style="width:70px;height:70px;"
                    >

                        <span style="font-size:32px;">
                            ✓
                        </span>

                    </div>



                    <!-- TITLE -->

                    <h2 class="fw-bold mb-3">

                        Application Submitted Successfully

                    </h2>



                    <!-- MESSAGE -->

                    <p class="text-muted">

                        Thank you,

                        <strong>

                            <?php

                            echo htmlspecialchars($full_name);

                            ?>

                        </strong>.

                        Your bursary application has been
                        successfully submitted.

                    </p>



                    <!-- APPLICATION NUMBER -->

                    <div class="alert alert-light border my-4">

                        <div class="mb-2">

                            <strong>

                                Application Number

                            </strong>

                        </div>


                        <h4 class="mb-0">

                            <?php

                            echo htmlspecialchars(
                                $application_number
                            );

                            ?>

                        </h4>

                    </div>



                    <!-- REFERENCE MESSAGE -->

                    <p class="text-muted">

                        Please keep your application number
                        for future reference.

                    </p>



                    <!-- STATUS -->

                    <div class="mb-4">

                        <span
                            class="badge bg-success fs-6
                                   px-3 py-2"
                        >

                            <?php

                            echo htmlspecialchars($status);

                            ?>

                        </span>

                    </div>



                    <!-- DASHBOARD BUTTON -->

                    <div>

                        <a
                            href="dashboard.php"
                            class="btn btn-primary px-4"
                        >

                            Go to Dashboard

                        </a>

                    </div>


                </div>

            </div>


        </div>

    </div>

</div>



</body>

</html>