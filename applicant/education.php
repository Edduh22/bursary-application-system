<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once "../config/database.php";


// SECURITY CHECK

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION["role"] !== "applicant") {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION["user_id"];


// GET APPLICANT

$stmt = $pdo->prepare(
    "SELECT id FROM applicants WHERE user_id = ? LIMIT 1"
);

$stmt->execute([$user_id]);

$applicant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$applicant) {
    header("Location: profile.php");
    exit;
}

$applicant_id = $applicant["id"];


// GET APPLICATION

$stmt = $pdo->prepare(
    "SELECT *
     FROM applications
     WHERE applicant_id = ?
     LIMIT 1"
);

$stmt->execute([$applicant_id]);

$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    header("Location: application.php");
    exit;
}

$application_id = $application["id"];


// GET EXISTING EDUCATION

$stmt = $pdo->prepare(
    "SELECT *
     FROM application_education
     WHERE application_id = ?
     LIMIT 1"
);

$stmt->execute([$application_id]);

$education = $stmt->fetch(PDO::FETCH_ASSOC);


// MESSAGE

$message = "";
$message_type = "";


// SAVE EDUCATION

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $institution_name = trim($_POST["institution_name"]);
    $education_level = trim($_POST["education_level"]);
    $course = trim($_POST["course"]);
    $year_of_study = trim($_POST["year_of_study"]);
    $admission_number = trim($_POST["admission_number"]);

    $previous_school = trim($_POST["previous_school"]);
    $previous_grade = trim($_POST["previous_grade"]);

    $school_fees = $_POST["school_fees"];
    $fees_paid = $_POST["fees_paid"];


    // VALIDATION

    if (
        empty($institution_name) ||
        empty($education_level)
    ) {

        $message = "Please fill in all required fields.";
        $message_type = "danger";

    } else {

        // Convert empty amounts to zero

        $school_fees = ($school_fees === "")
            ? 0
            : (float)$school_fees;

        $fees_paid = ($fees_paid === "")
            ? 0
            : (float)$fees_paid;


        // Calculate balance

        $fees_balance = $school_fees - $fees_paid;


        // Prevent negative balance

        if ($fees_balance < 0) {
            $fees_balance = 0;
        }


        if ($education) {

            // UPDATE

            $stmt = $pdo->prepare(
                "UPDATE application_education SET

                    institution_name = ?,
                    education_level = ?,
                    course = ?,
                    year_of_study = ?,
                    admission_number = ?,

                    previous_school = ?,
                    previous_grade = ?,

                    school_fees = ?,
                    fees_paid = ?,
                    fees_balance = ?

                 WHERE application_id = ?"
            );

            $stmt->execute([

                $institution_name,
                $education_level,
                $course,
                $year_of_study,
                $admission_number,

                $previous_school,
                $previous_grade,

                $school_fees,
                $fees_paid,
                $fees_balance,

                $application_id
            ]);

        } else {

            // INSERT

            $stmt = $pdo->prepare(
                "INSERT INTO application_education (

                    application_id,

                    institution_name,
                    education_level,
                    course,
                    year_of_study,
                    admission_number,

                    previous_school,
                    previous_grade,

                    school_fees,
                    fees_paid,
                    fees_balance

                )

                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([

                $application_id,

                $institution_name,
                $education_level,
                $course,
                $year_of_study,
                $admission_number,

                $previous_school,
                $previous_grade,

                $school_fees,
                $fees_paid,
                $fees_balance

            ]);
        }


        $message = "Education information saved successfully!";
        $message_type = "success";


        // Reload education

        $stmt = $pdo->prepare(
            "SELECT *
             FROM application_education
             WHERE application_id = ?
             LIMIT 1"
        );

        $stmt->execute([$application_id]);

        $education = $stmt->fetch(PDO::FETCH_ASSOC);
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
        Education - Bursary System
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>


<body>


<!-- NAVBAR -->

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
                echo htmlspecialchars(
                    $_SESSION["full_name"]
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


<!-- CONTENT -->

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-9">


            <!-- HEADER -->

            <div class="mb-4">

                <h2 class="fw-bold">
                    Education Information
                </h2>

                <p class="text-muted">
                    Provide your current education and school fees information.
                </p>

            </div>


            <!-- APPLICATION NUMBER -->

            <div class="alert alert-light border">

                <strong>
                    Application:
                </strong>

                <?php
                echo htmlspecialchars(
                    $application["application_number"]
                );
                ?>

            </div>


            <!-- MESSAGE -->

            <?php if ($message): ?>

                <div class="alert alert-<?php echo $message_type; ?>">

                    <?php
                    echo htmlspecialchars($message);
                    ?>

                </div>

            <?php endif; ?>


            <form method="POST">


                <!-- CURRENT EDUCATION -->

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-white">

                        <h5 class="mb-0">
                            Current Education
                        </h5>

                    </div>


                    <div class="card-body">

                        <div class="row">


                            <!-- INSTITUTION -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Institution Name *
                                </label>

                                <input
                                    type="text"
                                    name="institution_name"
                                    class="form-control"
                                    required
                                    value="<?php
                                    echo htmlspecialchars(
                                        $education["institution_name"] ?? ""
                                    );
                                    ?>"
                                >

                            </div>


                            <!-- LEVEL -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Education Level *
                                </label>

                                <select
                                    name="education_level"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        Select Level
                                    </option>

                                    <option
                                        value="Secondary"
                                        <?php
                                        echo (($education["education_level"] ?? "") === "Secondary")
                                            ? "selected"
                                            : "";
                                        ?>
                                    >
                                        Secondary
                                    </option>

                                    <option
                                        value="TVET"
                                        <?php
                                        echo (($education["education_level"] ?? "") === "TVET")
                                            ? "selected"
                                            : "";
                                        ?>
                                    >
                                        TVET
                                    </option>

                                    <option
                                        value="College"
                                        <?php
                                        echo (($education["education_level"] ?? "") === "College")
                                            ? "selected"
                                            : "";
                                        ?>
                                    >
                                        College
                                    </option>

                                    <option
                                        value="University"
                                        <?php
                                        echo (($education["education_level"] ?? "") === "University")
                                            ? "selected"
                                            : "";
                                        ?>
                                    >
                                        University
                                    </option>

                                </select>

                            </div>


                            <!-- COURSE -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Course / Programme
                                </label>

                                <input
                                    type="text"
                                    name="course"
                                    class="form-control"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $education["course"] ?? ""
                                    );
                                    ?>"
                                >

                            </div>


                            <!-- YEAR -->

                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Year of Study
                                </label>

                                <input
                                    type="text"
                                    name="year_of_study"
                                    class="form-control"
                                    placeholder="e.g. Year 2"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $education["year_of_study"] ?? ""
                                    );
                                    ?>"
                                >

                            </div>


                            <!-- ADMISSION -->

                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Admission Number
                                </label>

                                <input
                                    type="text"
                                    name="admission_number"
                                    class="form-control"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $education["admission_number"] ?? ""
                                    );
                                    ?>"
                                >

                            </div>

                        </div>

                    </div>

                </div>


                <!-- PREVIOUS EDUCATION -->

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-white">

                        <h5 class="mb-0">
                            Previous Education
                        </h5>

                    </div>


                    <div class="card-body">

                        <div class="row">


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Previous School / Institution
                                </label>

                                <input
                                    type="text"
                                    name="previous_school"
                                    class="form-control"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $education["previous_school"] ?? ""
                                    );
                                    ?>"
                                >

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Previous Grade / Result
                                </label>

                                <input
                                    type="text"
                                    name="previous_grade"
                                    class="form-control"
                                    placeholder="e.g. B+ / Grade 72"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $education["previous_grade"] ?? ""
                                    );
                                    ?>"
                                >

                            </div>

                        </div>

                    </div>

                </div>


                <!-- FEES -->

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-white">

                        <h5 class="mb-0">
                            School Fees Information
                        </h5>

                    </div>


                    <div class="card-body">

                        <div class="row">


                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Total School Fees (KES)
                                </label>

                                <input
                                    type="number"
                                    name="school_fees"
                                    id="school_fees"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $education["school_fees"] ?? ""
                                    );
                                    ?>"
                                >

                            </div>


                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Fees Paid (KES)
                                </label>

                                <input
                                    type="number"
                                    name="fees_paid"
                                    id="fees_paid"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $education["fees_paid"] ?? ""
                                    );
                                    ?>"
                                >

                            </div>


                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Fees Balance (KES)
                                </label>

                                <input
                                    type="text"
                                    id="fees_balance"
                                    class="form-control"
                                    readonly
                                    value="<?php
                                    echo htmlspecialchars(
                                        $education["fees_balance"] ?? "0"
                                    );
                                    ?>"
                                >

                            </div>

                        </div>


                        <div class="alert alert-info mb-0">

                            Fees balance is automatically calculated as:

                            <strong>
                                Total Fees − Fees Paid
                            </strong>

                        </div>

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="d-flex justify-content-between">

                    <a
                        href="application.php"
                        class="btn btn-outline-secondary"
                    >
                        Back to Application
                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary px-5"
                    >
                        Save Education
                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


<!-- BALANCE CALCULATION -->

<script>

function calculateBalance() {

    let fees =
        parseFloat(
            document.getElementById("school_fees").value
        ) || 0;

    let paid =
        parseFloat(
            document.getElementById("fees_paid").value
        ) || 0;


    let balance = fees - paid;


    if (balance < 0) {
        balance = 0;
    }


    document.getElementById("fees_balance").value =
        balance.toFixed(2);
}


document
    .getElementById("school_fees")
    .addEventListener(
        "input",
        calculateBalance
    );


document
    .getElementById("fees_paid")
    .addEventListener(
        "input",
        calculateBalance
    );

</script>


</body>

</html>