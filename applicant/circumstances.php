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


// GET EXISTING CIRCUMSTANCES

$stmt = $pdo->prepare(
    "SELECT *
     FROM application_circumstances
     WHERE application_id = ?
     LIMIT 1"
);

$stmt->execute([$application_id]);

$circumstance = $stmt->fetch(PDO::FETCH_ASSOC);


// MESSAGE

$message = "";
$message_type = "";


// SAVE INFORMATION

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $has_special_circumstances =
        $_POST["has_special_circumstances"] ?? "No";

    $circumstance_type =
        trim($_POST["circumstance_type"] ?? "");

    $description =
        trim($_POST["description"] ?? "");

    $supporting_details =
        trim($_POST["supporting_details"] ?? "");


    // VALIDATION

    if (
        $has_special_circumstances === "Yes" &&
        empty($circumstance_type)
    ) {

        $message =
            "Please select the type of special circumstance.";

        $message_type = "danger";

    } elseif (
        $has_special_circumstances === "Yes" &&
        empty($description)
    ) {

        $message =
            "Please provide a description of your circumstances.";

        $message_type = "danger";

    } else {

        // If applicant selected No,
        // clear the circumstance details.

        if ($has_special_circumstances === "No") {

            $circumstance_type = "";
            $description = "";
            $supporting_details = "";
        }


        if ($circumstance) {

            // UPDATE

            $stmt = $pdo->prepare(
                "UPDATE application_circumstances SET

                    has_special_circumstances = ?,
                    circumstance_type = ?,
                    description = ?,
                    supporting_details = ?

                 WHERE application_id = ?"
            );

            $stmt->execute([

                $has_special_circumstances,
                $circumstance_type,
                $description,
                $supporting_details,
                $application_id

            ]);

        } else {

            // INSERT

            $stmt = $pdo->prepare(
                "INSERT INTO application_circumstances (

                    application_id,
                    has_special_circumstances,
                    circumstance_type,
                    description,
                    supporting_details

                )

                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->execute([

                $application_id,
                $has_special_circumstances,
                $circumstance_type,
                $description,
                $supporting_details

            ]);
        }


        $message =
            "Special circumstances saved successfully!";

        $message_type = "success";


        // RELOAD

        $stmt = $pdo->prepare(
            "SELECT *
             FROM application_circumstances
             WHERE application_id = ?
             LIMIT 1"
        );

        $stmt->execute([$application_id]);

        $circumstance =
            $stmt->fetch(PDO::FETCH_ASSOC);
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
        Special Circumstances - Bursary System
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
                    Special Circumstances
                </h2>

                <p class="text-muted">
                    Tell us about any circumstances that may
                    affect your ability to finance your education.
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


                <!-- QUESTION -->

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-white">

                        <h5 class="mb-0">
                            Special Circumstances
                        </h5>

                    </div>


                    <div class="card-body">

                        <label class="form-label fw-semibold">

                            Do you have any special circumstances
                            affecting your education?

                        </label>


                        <div class="mt-2">

                            <div class="form-check">

                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="has_special_circumstances"
                                    id="circ_yes"
                                    value="Yes"
                                    <?php

                                    echo (($circumstance["has_special_circumstances"] ?? "") === "Yes")
                                        ? "checked"
                                        : "";

                                    ?>
                                >

                                <label
                                    class="form-check-label"
                                    for="circ_yes"
                                >
                                    Yes
                                </label>

                            </div>


                            <div class="form-check">

                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="has_special_circumstances"
                                    id="circ_no"
                                    value="No"
                                    <?php

                                    echo (
                                        ($circumstance["has_special_circumstances"] ?? "No")
                                        === "No"
                                    )
                                        ? "checked"
                                        : "";

                                    ?>
                                >

                                <label
                                    class="form-check-label"
                                    for="circ_no"
                                >
                                    No
                                </label>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- DETAILS -->

                <div
                    id="circumstanceDetails"
                    style="display: none;"
                >


                    <!-- TYPE -->

                    <div class="card shadow-sm border-0 mb-4">

                        <div class="card-header bg-white">

                            <h5 class="mb-0">
                                Type of Circumstance
                            </h5>

                        </div>


                        <div class="card-body">

                            <label class="form-label">
                                Select the circumstance
                            </label>


                            <select
                                name="circumstance_type"
                                id="circumstance_type"
                                class="form-select"
                            >

                                <option value="">
                                    Select Circumstance
                                </option>

                                <option
                                    value="Orphan"
                                    <?php

                                    echo (($circumstance["circumstance_type"] ?? "") === "Orphan")
                                        ? "selected"
                                        : "";

                                    ?>
                                >
                                    Orphan / Loss of Parent
                                </option>

                                <option
                                    value="Disability"
                                    <?php

                                    echo (($circumstance["circumstance_type"] ?? "") === "Disability")
                                        ? "selected"
                                        : "";

                                    ?>
                                >
                                    Disability
                                </option>

                                <option
                                    value="Chronic Illness"
                                    <?php

                                    echo (($circumstance["circumstance_type"] ?? "") === "Chronic Illness")
                                        ? "selected"
                                        : "";

                                    ?>
                                >
                                    Chronic Illness
                                </option>

                                <option
                                    value="Unemployment"
                                    <?php

                                    echo (($circumstance["circumstance_type"] ?? "") === "Unemployment")
                                        ? "selected"
                                        : "";

                                    ?>
                                >
                                    Parent / Guardian Unemployment
                                </option>

                                <option
                                    value="Large Family"
                                    <?php

                                    echo (($circumstance["circumstance_type"] ?? "") === "Large Family")
                                        ? "selected"
                                        : "";

                                    ?>
                                >
                                    Large Family / Many Dependants
                                </option>

                                <option
                                    value="Other"
                                    <?php

                                    echo (($circumstance["circumstance_type"] ?? "") === "Other")
                                        ? "selected"
                                        : "";

                                    ?>
                                >
                                    Other
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="card shadow-sm border-0 mb-4">

                        <div class="card-header bg-white">

                            <h5 class="mb-0">
                                Description
                            </h5>

                        </div>


                        <div class="card-body">

                            <label class="form-label">
                                Explain your circumstances
                            </label>

                            <textarea
                                name="description"
                                class="form-control"
                                rows="6"
                                placeholder="Explain how this circumstance affects your education and financial situation..."
                            ><?php

                            echo htmlspecialchars(
                                $circumstance["description"] ?? ""
                            );

                            ?></textarea>

                        </div>

                    </div>


                    <!-- SUPPORTING DETAILS -->

                    <div class="card shadow-sm border-0 mb-4">

                        <div class="card-header bg-white">

                            <h5 class="mb-0">
                                Additional Information
                            </h5>

                        </div>


                        <div class="card-body">

                            <label class="form-label">

                                Any additional information?

                            </label>

                            <textarea
                                name="supporting_details"
                                class="form-control"
                                rows="4"
                                placeholder="Provide any additional information that may support your application..."
                            ><?php

                            echo htmlspecialchars(
                                $circumstance["supporting_details"] ?? ""
                            );

                            ?></textarea>

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
                        Save Information
                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


<!-- SHOW/HIDE DETAILS -->

<script>

function toggleCircumstances() {

    const yes =
        document.getElementById("circ_yes").checked;

    const details =
        document.getElementById("circumstanceDetails");

    if (yes) {

        details.style.display = "block";

    } else {

        details.style.display = "none";

    }
}


document
    .getElementById("circ_yes")
    .addEventListener(
        "change",
        toggleCircumstances
    );


document
    .getElementById("circ_no")
    .addEventListener(
        "change",
        toggleCircumstances
    );


toggleCircumstances();

</script>


</body>

</html>