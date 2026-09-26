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
| GET APPLICANT ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: applicants.php");
    exit;
}

$applicant_id = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| FETCH APPLICANT
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        a.*,
        u.full_name,
        u.email
    FROM applicants a
    LEFT JOIN users u ON a.user_id = u.id
    WHERE a.id = ?
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$applicant_id]);

$applicant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$applicant) {
    die("Applicant not found.");
}


/*
|--------------------------------------------------------------------------
| FETCH APPLICATION
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM applications
    WHERE applicant_id = ?
    ORDER BY id DESC
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$applicant_id]);

$application = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Applicant - Bursary Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body>

<div class="container mt-4 mb-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2>Applicant Details</h2>
            <p class="text-muted">
                View applicant profile and application information
            </p>
        </div>

        <a href="applicants.php" class="btn btn-secondary">
            Back to Applicants
        </a>

    </div>


    <!-- PERSONAL INFORMATION -->

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Personal Information</h5>
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-6 mb-3">
                    <strong>Full Name:</strong><br>
                    <?php echo htmlspecialchars($applicant["full_name"] ?? "N/A"); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Email:</strong><br>
                    <?php echo htmlspecialchars($applicant["email"] ?? "N/A"); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>National ID:</strong><br>
                    <?php echo htmlspecialchars($applicant["national_id"]); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Date of Birth:</strong><br>
                    <?php echo htmlspecialchars($applicant["date_of_birth"]); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Gender:</strong><br>
                    <?php echo htmlspecialchars($applicant["gender"]); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>County:</strong><br>
                    <?php echo htmlspecialchars($applicant["county"]); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Constituency:</strong><br>
                    <?php echo htmlspecialchars($applicant["constituency"]); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Ward:</strong><br>
                    <?php echo htmlspecialchars($applicant["ward"]); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Village:</strong><br>
                    <?php echo htmlspecialchars($applicant["village"] ?? "N/A"); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Address:</strong><br>
                    <?php echo htmlspecialchars($applicant["address"] ?? "N/A"); ?>
                </div>

            </div>

        </div>

    </div>


    <!-- GUARDIAN INFORMATION -->

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Guardian Information</h5>
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-6 mb-3">
                    <strong>Guardian Name:</strong><br>
                    <?php echo htmlspecialchars($applicant["guardian_name"]); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Relationship:</strong><br>
                    <?php echo htmlspecialchars($applicant["guardian_relationship"] ?? "N/A"); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Guardian Phone:</strong><br>
                    <?php echo htmlspecialchars($applicant["guardian_phone"]); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Occupation:</strong><br>
                    <?php echo htmlspecialchars($applicant["guardian_occupation"] ?? "N/A"); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Guardian Income:</strong><br>
                    <?php echo htmlspecialchars($applicant["guardian_income"] ?? "N/A"); ?>
                </div>

            </div>

        </div>

    </div>


    <!-- EDUCATION -->

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Education Information</h5>
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-6 mb-3">
                    <strong>Institution:</strong><br>
                    <?php echo htmlspecialchars($applicant["institution_name"]); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Course:</strong><br>
                    <?php echo htmlspecialchars($applicant["course"] ?? "N/A"); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Education Level:</strong><br>
                    <?php echo htmlspecialchars($applicant["education_level"]); ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Year of Study:</strong><br>
                    <?php echo htmlspecialchars($applicant["year_of_study"] ?? "N/A"); ?>
                </div>

            </div>

        </div>

    </div>


    <!-- APPLICATION -->

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Application Information</h5>
        </div>

        <div class="card-body">

            <?php if ($application): ?>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <strong>Application Number:</strong><br>
                        <?php
                        echo htmlspecialchars(
                            $application["application_number"]
                        );
                        ?>
                    </div>

                    <div class="col-md-6 mb-3">
                        <strong>Academic Year:</strong><br>
                        <?php
                        echo htmlspecialchars(
                            $application["academic_year"]
                        );
                        ?>
                    </div>

                    <div class="col-md-6 mb-3">
                        <strong>Status:</strong><br>

                        <span class="badge bg-warning text-dark">
                            <?php
                            echo htmlspecialchars(
                                $application["status"]
                            );
                            ?>
                        </span>

                    </div>

                    <div class="col-md-6 mb-3">
                        <strong>Submitted At:</strong><br>
                        <?php
                        echo htmlspecialchars(
                            $application["submitted_at"] ?? "Not submitted"
                        );
                        ?>
                    </div>

                </div>

                <hr>

                <a
                    href="view_application.php?id=<?php echo $application["id"]; ?>"
                    class="btn btn-primary"
                >
                    View Full Application
                </a>

            <?php else: ?>

                <div class="alert alert-info mb-0">
                    This applicant has not created an application yet.
                </div>

            <?php endif; ?>

        </div>

    </div>


    <div class="text-center">

        <a href="applicants.php" class="btn btn-secondary">
            Back to Applicants
        </a>

    </div>

</div>

</body>

</html>