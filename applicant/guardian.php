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
    "SELECT id
     FROM applicants
     WHERE user_id = ?
     LIMIT 1"
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


// GET EXISTING GUARDIAN INFORMATION

$stmt = $pdo->prepare(
    "SELECT *
     FROM application_guardians
     WHERE application_id = ?
     LIMIT 1"
);

$stmt->execute([$application_id]);

$guardian = $stmt->fetch(PDO::FETCH_ASSOC);


// MESSAGE

$message = "";
$message_type = "";


// SAVE GUARDIAN INFORMATION

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $guardian_name = trim($_POST["guardian_name"] ?? "");
    $relationship = trim($_POST["relationship"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $occupation = trim($_POST["occupation"] ?? "");

    $marital_status = trim($_POST["marital_status"] ?? "");

    $number_of_dependants =
        $_POST["number_of_dependants"] ?? 0;

    $guardian_income =
        $_POST["guardian_income"] ?? "";

    $second_parent_name =
        trim($_POST["second_parent_name"] ?? "");

    $second_parent_phone =
        trim($_POST["second_parent_phone"] ?? "");

    $second_parent_occupation =
        trim($_POST["second_parent_occupation"] ?? "");

    $second_parent_income =
        $_POST["second_parent_income"] ?? "";


    // VALIDATION

    if (
        empty($guardian_name) ||
        empty($relationship) ||
        empty($phone)
    ) {

        $message =
            "Please fill in all required fields.";

        $message_type = "danger";

    } else {

        // Convert numbers

        $number_of_dependants =
            ($number_of_dependants === "")
            ? 0
            : (int)$number_of_dependants;

        $guardian_income =
            ($guardian_income === "")
            ? 0
            : (float)$guardian_income;

        $second_parent_income =
            ($second_parent_income === "")
            ? 0
            : (float)$second_parent_income;


        if ($guardian) {

            // UPDATE EXISTING RECORD

            $stmt = $pdo->prepare(
                "UPDATE application_guardians SET

                    guardian_name = ?,
                    relationship = ?,
                    phone = ?,
                    occupation = ?,

                    marital_status = ?,
                    number_of_dependants = ?,
                    guardian_income = ?,

                    second_parent_name = ?,
                    second_parent_phone = ?,
                    second_parent_occupation = ?,
                    second_parent_income = ?

                 WHERE application_id = ?"
            );

            $stmt->execute([

                $guardian_name,
                $relationship,
                $phone,
                $occupation,

                $marital_status,
                $number_of_dependants,
                $guardian_income,

                $second_parent_name,
                $second_parent_phone,
                $second_parent_occupation,
                $second_parent_income,

                $application_id

            ]);

        } else {

            // CREATE NEW RECORD

            $stmt = $pdo->prepare(
                "INSERT INTO application_guardians (

                    application_id,

                    guardian_name,
                    relationship,
                    phone,
                    occupation,

                    marital_status,
                    number_of_dependants,
                    guardian_income,

                    second_parent_name,
                    second_parent_phone,
                    second_parent_occupation,
                    second_parent_income

                )

                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([

                $application_id,

                $guardian_name,
                $relationship,
                $phone,
                $occupation,

                $marital_status,
                $number_of_dependants,
                $guardian_income,

                $second_parent_name,
                $second_parent_phone,
                $second_parent_occupation,
                $second_parent_income

            ]);
        }


        $message =
            "Guardian information saved successfully!";

        $message_type = "success";


        // Reload saved data

        $stmt = $pdo->prepare(
            "SELECT *
             FROM application_guardians
             WHERE application_id = ?
             LIMIT 1"
        );

        $stmt->execute([$application_id]);

        $guardian = $stmt->fetch(PDO::FETCH_ASSOC);
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
        Guardian Information - Bursary System
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
                    Guardian / Family Information
                </h2>

                <p class="text-muted">
                    Provide information about your parent or guardian.
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

                <div
                    class="alert alert-<?php echo $message_type; ?>"
                >

                    <?php

                    echo htmlspecialchars(
                        $message
                    );

                    ?>

                </div>

            <?php endif; ?>


            <form method="POST">


                <!-- GUARDIAN DETAILS -->

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-white">

                        <h5 class="mb-0">
                            Primary Guardian
                        </h5>

                    </div>


                    <div class="card-body">

                        <div class="row">


                            <!-- NAME -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Full Name *
                                </label>

                                <input
                                    type="text"
                                    name="guardian_name"
                                    class="form-control"
                                    required
                                    value="<?php

                                    echo htmlspecialchars(
                                        $guardian["guardian_name"] ?? ""
                                    );

                                    ?>"
                                >

                            </div>


                            <!-- RELATIONSHIP -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Relationship *
                                </label>

                                <select
                                    name="relationship"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        Select Relationship
                                    </option>

                                    <option
                                        value="Father"
                                        <?php

                                        echo (($guardian["relationship"] ?? "") === "Father")
                                            ? "selected"
                                            : "";

                                        ?>
                                    >
                                        Father
                                    </option>

                                    <option
                                        value="Mother"
                                        <?php

                                        echo (($guardian["relationship"] ?? "") === "Mother")
                                            ? "selected"
                                            : "";

                                        ?>
                                    >
                                        Mother
                                    </option>

                                    <option
                                        value="Guardian"
                                        <?php

                                        echo (($guardian["relationship"] ?? "") === "Guardian")
                                            ? "selected"
                                            : "";

                                        ?>
                                    >
                                        Guardian
                                    </option>

                                    <option
                                        value="Other"
                                        <?php

                                        echo (($guardian["relationship"] ?? "") === "Other")
                                            ? "selected"
                                            : "";

                                        ?>
                                    >
                                        Other
                                    </option>

                                </select>

                            </div>


                            <!-- PHONE -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Phone Number *
                                </label>

                                <input
                                    type="tel"
                                    name="phone"
                                    class="form-control"
                                    placeholder="07XXXXXXXX"
                                    required
                                    value="<?php

                                    echo htmlspecialchars(
                                        $guardian["phone"] ?? ""
                                    );

                                    ?>"
                                >

                            </div>


                            <!-- OCCUPATION -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Occupation
                                </label>

                                <input
                                    type="text"
                                    name="occupation"
                                    class="form-control"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $guardian["occupation"] ?? ""
                                    );

                                    ?>"
                                >

                            </div>


                            <!-- MARITAL STATUS -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Marital Status
                                </label>

                                <select
                                    name="marital_status"
                                    class="form-select"
                                >

                                    <option value="">
                                        Select Status
                                    </option>

                                    <option
                                        value="Married"
                                        <?php

                                        echo (($guardian["marital_status"] ?? "") === "Married")
                                            ? "selected"
                                            : "";

                                        ?>
                                    >
                                        Married
                                    </option>

                                    <option
                                        value="Single"
                                        <?php

                                        echo (($guardian["marital_status"] ?? "") === "Single")
                                            ? "selected"
                                            : "";

                                        ?>
                                    >
                                        Single
                                    </option>

                                    <option
                                        value="Widowed"
                                        <?php

                                        echo (($guardian["marital_status"] ?? "") === "Widowed")
                                            ? "selected"
                                            : "";

                                        ?>
                                    >
                                        Widowed
                                    </option>

                                    <option
                                        value="Divorced"
                                        <?php

                                        echo (($guardian["marital_status"] ?? "") === "Divorced")
                                            ? "selected"
                                            : "";

                                        ?>
                                    >
                                        Divorced
                                    </option>

                                    <option
                                        value="Separated"
                                        <?php

                                        echo (($guardian["marital_status"] ?? "") === "Separated")
                                            ? "selected"
                                            : "";

                                        ?>
                                    >
                                        Separated
                                    </option>

                                </select>

                            </div>


                            <!-- DEPENDANTS -->

                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Number of Dependants
                                </label>

                                <input
                                    type="number"
                                    name="number_of_dependants"
                                    class="form-control"
                                    min="0"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $guardian["number_of_dependants"] ?? "0"
                                    );

                                    ?>"
                                >

                            </div>


                            <!-- INCOME -->

                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Monthly Income (KES)
                                </label>

                                <input
                                    type="number"
                                    name="guardian_income"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $guardian["guardian_income"] ?? ""
                                    );

                                    ?>"
                                >

                            </div>

                        </div>

                    </div>

                </div>


                <!-- SECOND PARENT -->

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-white">

                        <h5 class="mb-0">
                            Second Parent / Guardian
                        </h5>

                    </div>


                    <div class="card-body">

                        <div class="row">


                            <!-- NAME -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    name="second_parent_name"
                                    class="form-control"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $guardian["second_parent_name"] ?? ""
                                    );

                                    ?>"
                                >

                            </div>


                            <!-- PHONE -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Phone Number
                                </label>

                                <input
                                    type="tel"
                                    name="second_parent_phone"
                                    class="form-control"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $guardian["second_parent_phone"] ?? ""
                                    );

                                    ?>"
                                >

                            </div>


                            <!-- OCCUPATION -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Occupation
                                </label>

                                <input
                                    type="text"
                                    name="second_parent_occupation"
                                    class="form-control"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $guardian["second_parent_occupation"] ?? ""
                                    );

                                    ?>"
                                >

                            </div>


                            <!-- INCOME -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Monthly Income (KES)
                                </label>

                                <input
                                    type="number"
                                    name="second_parent_income"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $guardian["second_parent_income"] ?? ""
                                    );

                                    ?>"
                                >

                            </div>

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
                        Save Guardian Information
                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


</body>

</html>