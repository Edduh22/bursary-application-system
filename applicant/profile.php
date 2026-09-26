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

$message = "";
$message_type = "";


// GET EXISTING PROFILE

$stmt = $pdo->prepare(
    "SELECT * FROM applicants WHERE user_id = ? LIMIT 1"
);

$stmt->execute([$user_id]);

$profile = $stmt->fetch(PDO::FETCH_ASSOC);


// SAVE PROFILE

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $national_id = trim($_POST["national_id"]);
    $date_of_birth = $_POST["date_of_birth"];
    $gender = $_POST["gender"];

    $county = trim($_POST["county"]);
    $constituency = trim($_POST["constituency"]);
    $ward = trim($_POST["ward"]);
    $village = trim($_POST["village"]);
    $address = trim($_POST["address"]);

    $guardian_name = trim($_POST["guardian_name"]);
    $guardian_relationship = trim($_POST["guardian_relationship"]);
    $guardian_phone = trim($_POST["guardian_phone"]);
    $guardian_occupation = trim($_POST["guardian_occupation"]);
    $guardian_income = $_POST["guardian_income"];

    $institution_name = trim($_POST["institution_name"]);
    $course = trim($_POST["course"]);
    $education_level = $_POST["education_level"];
    $year_of_study = trim($_POST["year_of_study"]);
    $admission_number = trim($_POST["admission_number"]);


    // VALIDATION

    if (
        empty($national_id) ||
        empty($date_of_birth) ||
        empty($gender) ||
        empty($county) ||
        empty($constituency) ||
        empty($ward) ||
        empty($guardian_name) ||
        empty($guardian_phone) ||
        empty($institution_name) ||
        empty($education_level)
    ) {

        $message = "Please fill in all required fields.";
        $message_type = "danger";

    } else {

        if ($profile) {

            // UPDATE

            $stmt = $pdo->prepare(
                "UPDATE applicants SET
                    national_id = ?,
                    date_of_birth = ?,
                    gender = ?,
                    county = ?,
                    constituency = ?,
                    ward = ?,
                    village = ?,
                    address = ?,
                    guardian_name = ?,
                    guardian_relationship = ?,
                    guardian_phone = ?,
                    guardian_occupation = ?,
                    guardian_income = ?,
                    institution_name = ?,
                    course = ?,
                    education_level = ?,
                    year_of_study = ?,
                    admission_number = ?
                 WHERE user_id = ?"
            );

            $stmt->execute([
                $national_id,
                $date_of_birth,
                $gender,
                $county,
                $constituency,
                $ward,
                $village,
                $address,
                $guardian_name,
                $guardian_relationship,
                $guardian_phone,
                $guardian_occupation,
                $guardian_income,
                $institution_name,
                $course,
                $education_level,
                $year_of_study,
                $admission_number,
                $user_id
            ]);

        } else {

            // INSERT

            $stmt = $pdo->prepare(
                "INSERT INTO applicants (
                    user_id,
                    national_id,
                    date_of_birth,
                    gender,
                    county,
                    constituency,
                    ward,
                    village,
                    address,
                    guardian_name,
                    guardian_relationship,
                    guardian_phone,
                    guardian_occupation,
                    guardian_income,
                    institution_name,
                    course,
                    education_level,
                    year_of_study,
                    admission_number
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $user_id,
                $national_id,
                $date_of_birth,
                $gender,
                $county,
                $constituency,
                $ward,
                $village,
                $address,
                $guardian_name,
                $guardian_relationship,
                $guardian_phone,
                $guardian_occupation,
                $guardian_income,
                $institution_name,
                $course,
                $education_level,
                $year_of_study,
                $admission_number
            ]);
        }


        $message = "Profile saved successfully!";
        $message_type = "success";


        // Reload profile

        $stmt = $pdo->prepare(
            "SELECT * FROM applicants WHERE user_id = ? LIMIT 1"
        );

        $stmt->execute([$user_id]);

        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
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

    <title>My Profile - Bursary System</title>

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
                <?php echo htmlspecialchars($_SESSION["full_name"]); ?>
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


<!-- PROFILE -->

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-9">

            <h2 class="fw-bold">
                Complete Your Profile
            </h2>

            <p class="text-muted mb-4">
                Complete your information before starting your bursary application.
            </p>


            <?php if ($message): ?>

                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>


            <form method="POST">


                <!-- PERSONAL INFORMATION -->

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">Personal Information</h5>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    National ID Number *
                                </label>

                                <input
                                    type="text"
                                    name="national_id"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($profile["national_id"] ?? ""); ?>"
                                    required
                                >

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Date of Birth *
                                </label>

                                <input
                                    type="date"
                                    name="date_of_birth"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($profile["date_of_birth"] ?? ""); ?>"
                                    required
                                >

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Gender *
                                </label>

                                <select
                                    name="gender"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        Select Gender
                                    </option>

                                    <option
                                        value="Male"
                                        <?php echo (($profile["gender"] ?? "") === "Male") ? "selected" : ""; ?>
                                    >
                                        Male
                                    </option>

                                    <option
                                        value="Female"
                                        <?php echo (($profile["gender"] ?? "") === "Female") ? "selected" : ""; ?>
                                    >
                                        Female
                                    </option>

                                    <option
                                        value="Other"
                                        <?php echo (($profile["gender"] ?? "") === "Other") ? "selected" : ""; ?>
                                    >
                                        Other
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- LOCATION -->

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">Location Information</h5>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    County *
                                </label>

                                <input
                                    type="text"
                                    name="county"
                                    class="form-control"
                                    required
                                    value="<?php echo htmlspecialchars($profile["county"] ?? ""); ?>"
                                >

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Constituency *
                                </label>

                                <input
                                    type="text"
                                    name="constituency"
                                    class="form-control"
                                    required
                                    value="<?php echo htmlspecialchars($profile["constituency"] ?? ""); ?>"
                                >

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Ward *
                                </label>

                                <input
                                    type="text"
                                    name="ward"
                                    class="form-control"
                                    required
                                    value="<?php echo htmlspecialchars($profile["ward"] ?? ""); ?>"
                                >

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Village / Location
                                </label>

                                <input
                                    type="text"
                                    name="village"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($profile["village"] ?? ""); ?>"
                                >

                            </div>


                            <div class="col-12 mb-3">

                                <label class="form-label">
                                    Physical Address
                                </label>

                                <textarea
                                    name="address"
                                    class="form-control"
                                    rows="2"
                                ><?php echo htmlspecialchars($profile["address"] ?? ""); ?></textarea>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- GUARDIAN -->

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            Parent / Guardian Information
                        </h5>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Guardian Name *
                                </label>

                                <input
                                    type="text"
                                    name="guardian_name"
                                    class="form-control"
                                    required
                                    value="<?php echo htmlspecialchars($profile["guardian_name"] ?? ""); ?>"
                                >

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Relationship
                                </label>

                                <input
                                    type="text"
                                    name="guardian_relationship"
                                    class="form-control"
                                    placeholder="e.g. Father, Mother, Guardian"
                                    value="<?php echo htmlspecialchars($profile["guardian_relationship"] ?? ""); ?>"
                                >

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Guardian Phone *
                                </label>

                                <input
                                    type="tel"
                                    name="guardian_phone"
                                    class="form-control"
                                    required
                                    value="<?php echo htmlspecialchars($profile["guardian_phone"] ?? ""); ?>"
                                >

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Guardian Occupation
                                </label>

                                <input
                                    type="text"
                                    name="guardian_occupation"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($profile["guardian_occupation"] ?? ""); ?>"
                                >

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Estimated Monthly Income (KES)
                                </label>

                                <input
                                    type="number"
                                    name="guardian_income"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value="<?php echo htmlspecialchars($profile["guardian_income"] ?? ""); ?>"
                                >

                            </div>

                        </div>

                    </div>

                </div>


                <!-- EDUCATION -->

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            Education Information
                        </h5>
                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Institution Name *
                                </label>

                                <input
                                    type="text"
                                    name="institution_name"
                                    class="form-control"
                                    required
                                    value="<?php echo htmlspecialchars($profile["institution_name"] ?? ""); ?>"
                                >

                            </div>


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
                                        Select Education Level
                                    </option>

                                    <option value="Secondary">
                                        Secondary
                                    </option>

                                    <option value="TVET">
                                        TVET
                                    </option>

                                    <option value="College">
                                        College
                                    </option>

                                    <option value="University">
                                        University
                                    </option>

                                </select>

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Course / Programme
                                </label>

                                <input
                                    type="text"
                                    name="course"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($profile["course"] ?? ""); ?>"
                                >

                            </div>


                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Year of Study
                                </label>

                                <input
                                    type="text"
                                    name="year_of_study"
                                    class="form-control"
                                    placeholder="e.g. Year 2"
                                    value="<?php echo htmlspecialchars($profile["year_of_study"] ?? ""); ?>"
                                >

                            </div>


                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Admission Number
                                </label>

                                <input
                                    type="text"
                                    name="admission_number"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($profile["admission_number"] ?? ""); ?>"
                                >

                            </div>

                        </div>

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="d-flex justify-content-between mb-5">

                    <a
                        href="dashboard.php"
                        class="btn btn-outline-secondary"
                    >
                        Back to Dashboard
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary px-5"
                    >
                        Save Profile
                    </button>

                </div>


            </form>

        </div>

    </div>

</div>

</body>
</html>