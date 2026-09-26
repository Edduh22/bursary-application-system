<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once "../config/database.php";


// =====================================================
// SECURITY CHECK
// =====================================================

if (!isset($_SESSION["user_id"])) {

    header("Location: ../login.php");
    exit;
}

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "applicant"
) {

    header("Location: ../login.php");
    exit;
}


$user_id = $_SESSION["user_id"];


// =====================================================
// GET APPLICANT + USER INFORMATION
// =====================================================
// full_name, email and phone are in users
// national_id and other personal details are in applicants
// =====================================================

$stmt = $pdo->prepare(
    "SELECT
        a.id AS applicant_id,
        a.user_id,
        a.national_id,
        a.date_of_birth,
        a.gender,
        a.county,
        a.constituency,
        a.ward,
        a.village,
        a.address,

        u.full_name,
        u.email,
        u.phone

     FROM applicants a

     INNER JOIN users u
        ON a.user_id = u.id

     WHERE a.user_id = ?

     LIMIT 1"
);

$stmt->execute([$user_id]);

$applicant = $stmt->fetch(PDO::FETCH_ASSOC);


// Applicant must exist

if (!$applicant) {

    header("Location: profile.php");
    exit;
}


$applicant_id = $applicant["applicant_id"];


// =====================================================
// GET APPLICATION
// =====================================================

$stmt = $pdo->prepare(
    "SELECT *
     FROM applications
     WHERE applicant_id = ?
     LIMIT 1"
);

$stmt->execute([$applicant_id]);

$application = $stmt->fetch(PDO::FETCH_ASSOC);


// Application must exist

if (!$application) {

    header("Location: application.php");
    exit;
}


$application_id = $application["id"];


// =====================================================
// PREVENT EDITING AFTER SUBMISSION
// =====================================================

$current_status = strtolower(
    trim($application["status"] ?? "")
);

if ($current_status === "submitted") {

    header("Location: application_view.php");
    exit;
}


// =====================================================
// GET EDUCATION
// =====================================================

$stmt = $pdo->prepare(
    "SELECT *
     FROM application_education
     WHERE application_id = ?
     LIMIT 1"
);

$stmt->execute([$application_id]);

$education = $stmt->fetch(PDO::FETCH_ASSOC);


// =====================================================
// GET GUARDIAN
// =====================================================

$stmt = $pdo->prepare(
    "SELECT *
     FROM application_guardians
     WHERE application_id = ?
     LIMIT 1"
);

$stmt->execute([$application_id]);

$guardian = $stmt->fetch(PDO::FETCH_ASSOC);


// =====================================================
// GET FINANCIAL INFORMATION
// =====================================================

$stmt = $pdo->prepare(
    "SELECT *
     FROM application_financial
     WHERE application_id = ?
     LIMIT 1"
);

$stmt->execute([$application_id]);

$financial = $stmt->fetch(PDO::FETCH_ASSOC);


// =====================================================
// GET SPECIAL CIRCUMSTANCES
// =====================================================

$stmt = $pdo->prepare(
    "SELECT *
     FROM application_circumstances
     WHERE application_id = ?
     LIMIT 1"
);

$stmt->execute([$application_id]);

$circumstance = $stmt->fetch(PDO::FETCH_ASSOC);


// =====================================================
// GET DOCUMENTS
// =====================================================

$stmt = $pdo->prepare(
    "SELECT *
     FROM application_documents
     WHERE application_id = ?
     ORDER BY uploaded_at DESC"
);

$stmt->execute([$application_id]);

$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);


// =====================================================
// REQUIRED DOCUMENTS
// =====================================================

$required_documents = [
    "National ID / Birth Certificate",
    "Admission Letter",
    "Fee Structure"
];


$uploaded_document_types = array_column(
    $documents,
    "document_type"
);


$missing_documents = [];


foreach ($required_documents as $required) {

    if (
        !in_array(
            $required,
            $uploaded_document_types,
            true
        )
    ) {

        $missing_documents[] = $required;
    }
}


// =====================================================
// CHECK APPLICATION COMPLETION
// =====================================================

$missing_sections = [];


// Personal information

if (!$applicant) {

    $missing_sections[] =
        "Personal Information";
}


// Education

if (!$education) {

    $missing_sections[] =
        "Education";
}


// Guardian

if (!$guardian) {

    $missing_sections[] =
        "Guardian Information";
}


// Financial

if (!$financial) {

    $missing_sections[] =
        "Financial Information";
}


// Special circumstances

if (!$circumstance) {

    $missing_sections[] =
        "Special Circumstances";
}


// Documents

if (count($missing_documents) > 0) {

    $missing_sections[] =
        "Required Documents";
}


// =====================================================
// SUBMIT APPLICATION
// =====================================================

$message = "";
$message_type = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {


    // ---------------------------------------------
    // Check if application is complete
    // ---------------------------------------------

    if (count($missing_sections) > 0) {

        $message =
            "Your application is incomplete. Please complete all sections before submitting.";

        $message_type =
            "danger";

    } else {


        try {

            // Start transaction

            $pdo->beginTransaction();


            // ---------------------------------------------
            // UPDATE APPLICATION
            // ---------------------------------------------
            // IMPORTANT:
            // status must be lowercase "submitted"
            // submitted_at records exact submission date/time
            // ---------------------------------------------

            $stmt = $pdo->prepare(
                "UPDATE applications

                 SET
                    status = 'submitted',
                    submitted_at = NOW(),
                    updated_at = CURRENT_TIMESTAMP

                 WHERE id = ?
                 AND applicant_id = ?"
            );


            $stmt->execute([
                $application_id,
                $applicant_id
            ]);


            // ---------------------------------------------
            // Make sure update actually happened
            // ---------------------------------------------

            if ($stmt->rowCount() === 0) {

                throw new Exception(
                    "The application could not be submitted."
                );
            }


            // Commit changes

            $pdo->commit();


            // ---------------------------------------------
            // Redirect to success page
            // ---------------------------------------------

            header(
                "Location: submission_success.php"
            );

            exit;


        } catch (Exception $e) {


            // Rollback if transaction is active

            if ($pdo->inTransaction()) {

                $pdo->rollBack();
            }


            $message =
                "An error occurred while submitting your application. Please try again.";

            $message_type =
                "danger";
        }
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
        Review Application - Bursary System
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

                echo htmlspecialchars(
                    $applicant["full_name"]
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


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-10">


            <!-- PAGE HEADER -->

            <div class="mb-4">

                <h2 class="fw-bold">

                    Review Application

                </h2>


                <p class="text-muted">

                    Carefully review your information before
                    submitting your bursary application.

                </p>

            </div>


            <!-- =================================================
                 APPLICATION NUMBER
            ================================================== -->

            <div class="alert alert-light border">

                <strong>

                    Application Number:

                </strong>

                <?php

                echo htmlspecialchars(
                    $application["application_number"]
                );

                ?>

            </div>


            <!-- =================================================
                 MESSAGE
            ================================================== -->

            <?php if ($message): ?>

                <div
                    class="alert alert-<?php echo htmlspecialchars($message_type); ?>"
                >

                    <?php

                    echo htmlspecialchars(
                        $message
                    );

                    ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 INCOMPLETE WARNING
            ================================================== -->

            <?php if (count($missing_sections) > 0): ?>

                <div class="alert alert-warning">

                    <h5 class="alert-heading">

                        Application Incomplete

                    </h5>


                    <p>

                        Please complete the following:

                    </p>


                    <ul class="mb-0">

                        <?php foreach (
                            $missing_sections
                            as $missing
                        ): ?>

                            <li>

                                <?php

                                echo htmlspecialchars(
                                    $missing
                                );

                                ?>

                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 PERSONAL INFORMATION
            ================================================== -->

            <div class="card shadow-sm border-0 mb-4">


                <div
                    class="card-header bg-white
                           d-flex justify-content-between
                           align-items-center"
                >

                    <h5 class="mb-0">

                        Personal Information

                    </h5>


                    <a
                        href="profile.php"
                        class="btn btn-sm btn-outline-primary"
                    >

                        Edit

                    </a>

                </div>


                <div class="card-body">

                    <div class="row">


                        <!-- FULL NAME -->

                        <div class="col-md-6 mb-3">

                            <strong>
                                Full Name
                            </strong>

                            <div>

                                <?php

                                echo htmlspecialchars(
                                    $applicant["full_name"]
                                    ?? "Not provided"
                                );

                                ?>

                            </div>

                        </div>


                        <!-- NATIONAL ID -->

                        <div class="col-md-6 mb-3">

                            <strong>
                                ID Number
                            </strong>

                            <div>

                                <?php

                                echo htmlspecialchars(
                                    $applicant["national_id"]
                                    ?? "Not provided"
                                );

                                ?>

                            </div>

                        </div>


                        <!-- PHONE -->

                        <div class="col-md-6 mb-3">

                            <strong>
                                Phone
                            </strong>

                            <div>

                                <?php

                                echo htmlspecialchars(
                                    $applicant["phone"]
                                    ?? "Not provided"
                                );

                                ?>

                            </div>

                        </div>


                        <!-- EMAIL -->

                        <div class="col-md-6 mb-3">

                            <strong>
                                Email
                            </strong>

                            <div>

                                <?php

                                echo htmlspecialchars(
                                    $applicant["email"]
                                    ?? "Not provided"
                                );

                                ?>

                            </div>

                        </div>


                        <!-- DATE OF BIRTH -->

                        <div class="col-md-6 mb-3">

                            <strong>
                                Date of Birth
                            </strong>

                            <div>

                                <?php

                                echo htmlspecialchars(
                                    $applicant["date_of_birth"]
                                    ?? "Not provided"
                                );

                                ?>

                            </div>

                        </div>


                        <!-- GENDER -->

                        <div class="col-md-6 mb-3">

                            <strong>
                                Gender
                            </strong>

                            <div>

                                <?php

                                echo htmlspecialchars(
                                    $applicant["gender"]
                                    ?? "Not provided"
                                );

                                ?>

                            </div>

                        </div>


                        <!-- COUNTY -->

                        <div class="col-md-6 mb-3">

                            <strong>
                                County
                            </strong>

                            <div>

                                <?php

                                echo htmlspecialchars(
                                    $applicant["county"]
                                    ?? "Not provided"
                                );

                                ?>

                            </div>

                        </div>


                        <!-- CONSTITUENCY -->

                        <div class="col-md-6 mb-3">

                            <strong>
                                Constituency
                            </strong>

                            <div>

                                <?php

                                echo htmlspecialchars(
                                    $applicant["constituency"]
                                    ?? "Not provided"
                                );

                                ?>

                            </div>

                        </div>


                        <!-- WARD -->

                        <div class="col-md-6 mb-3">

                            <strong>
                                Ward
                            </strong>

                            <div>

                                <?php

                                echo htmlspecialchars(
                                    $applicant["ward"]
                                    ?? "Not provided"
                                );

                                ?>

                            </div>

                        </div>


                        <!-- VILLAGE -->

                        <div class="col-md-6 mb-3">

                            <strong>
                                Village
                            </strong>

                            <div>

                                <?php

                                echo htmlspecialchars(
                                    $applicant["village"]
                                    ?? "Not provided"
                                );

                                ?>

                            </div>

                        </div>


                        <!-- ADDRESS -->

                        <div class="col-12 mb-3">

                            <strong>
                                Address
                            </strong>

                            <div>

                                <?php

                                echo htmlspecialchars(
                                    $applicant["address"]
                                    ?? "Not provided"
                                );

                                ?>

                            </div>

                        </div>


                    </div>

                </div>

            </div>


            <!-- =================================================
                 EDUCATION
            ================================================== -->

            <div class="card shadow-sm border-0 mb-4">


                <div
                    class="card-header bg-white
                           d-flex justify-content-between
                           align-items-center"
                >

                    <h5 class="mb-0">

                        Education

                    </h5>


                    <a
                        href="education.php"
                        class="btn btn-sm btn-outline-primary"
                    >

                        Edit

                    </a>

                </div>


                <div class="card-body">


                    <?php if ($education): ?>

                        <div class="row">


                            <div class="col-md-6 mb-3">

                                <strong>
                                    Institution
                                </strong>

                                <div>

                                    <?php

                                    echo htmlspecialchars(
                                        $education["institution_name"]
                                        ?? ""
                                    );

                                    ?>

                                </div>

                            </div>


                            <div class="col-md-6 mb-3">

                                <strong>
                                    Course / Programme
                                </strong>

                                <div>

                                    <?php

                                    echo htmlspecialchars(
                                        $education["course"]
                                        ?? ""
                                    );

                                    ?>

                                </div>

                            </div>


                            <div class="col-md-6 mb-3">

                                <strong>
                                    Year of Study
                                </strong>

                                <div>

                                    <?php

                                    echo htmlspecialchars(
                                        $education["year_of_study"]
                                        ?? ""
                                    );

                                    ?>

                                </div>

                            </div>


                        </div>


                    <?php else: ?>

                        <p class="text-danger mb-0">

                            Education information not completed.

                        </p>

                    <?php endif; ?>


                </div>

            </div>


            <!-- =================================================
                 GUARDIAN
            ================================================== -->

            <div class="card shadow-sm border-0 mb-4">


                <div
                    class="card-header bg-white
                           d-flex justify-content-between
                           align-items-center"
                >

                    <h5 class="mb-0">

                        Guardian Information

                    </h5>


                    <a
                        href="guardian.php"
                        class="btn btn-sm btn-outline-primary"
                    >

                        Edit

                    </a>

                </div>


                <div class="card-body">


                    <?php if ($guardian): ?>

                        <div class="row">


                            <div class="col-md-6 mb-3">

                                <strong>
                                    Guardian Name
                                </strong>

                                <div>

                                    <?php

                                    echo htmlspecialchars(
                                        $guardian["guardian_name"]
                                        ?? ""
                                    );

                                    ?>

                                </div>

                            </div>


                            <div class="col-md-6 mb-3">

                                <strong>
                                    Relationship
                                </strong>

                                <div>

                                    <?php

                                    echo htmlspecialchars(
                                        $guardian["guardian_relationship"]
                                        ?? $guardian["relationship"]
                                        ?? ""
                                    );

                                    ?>

                                </div>

                            </div>


                            <div class="col-md-6 mb-3">

                                <strong>
                                    Phone
                                </strong>

                                <div>

                                    <?php

                                    echo htmlspecialchars(
                                        $guardian["guardian_phone"]
                                        ?? $guardian["phone"]
                                        ?? ""
                                    );

                                    ?>

                                </div>

                            </div>


                            <?php if (
                                isset($guardian["guardian_occupation"])
                            ): ?>

                                <div class="col-md-6 mb-3">

                                    <strong>
                                        Occupation
                                    </strong>

                                    <div>

                                        <?php

                                        echo htmlspecialchars(
                                            $guardian["guardian_occupation"]
                                        );

                                        ?>

                                    </div>

                                </div>

                            <?php endif; ?>


                        </div>


                    <?php else: ?>

                        <p class="text-danger mb-0">

                            Guardian information not completed.

                        </p>

                    <?php endif; ?>


                </div>

            </div>


            <!-- =================================================
                 FINANCIAL INFORMATION
            ================================================== -->

            <div class="card shadow-sm border-0 mb-4">


                <div
                    class="card-header bg-white
                           d-flex justify-content-between
                           align-items-center"
                >

                    <h5 class="mb-0">

                        Financial Information

                    </h5>


                    <a
                        href="financial.php"
                        class="btn btn-sm btn-outline-primary"
                    >

                        Edit

                    </a>

                </div>


                <div class="card-body">


                    <?php if ($financial): ?>

                        <div class="row">


                            <div class="col-md-6 mb-3">

                                <strong>
                                    Household Income
                                </strong>

                                <div>

                                    KES

                                    <?php

                                    echo number_format(
                                        (float) (
                                            $financial["household_income"]
                                            ?? 0
                                        ),
                                        2
                                    );

                                    ?>

                                </div>

                            </div>


                            <div class="col-md-6 mb-3">

                                <strong>
                                    Income Source
                                </strong>

                                <div>

                                    <?php

                                    echo htmlspecialchars(
                                        $financial["income_source"]
                                        ?? ""
                                    );

                                    ?>

                                </div>

                            </div>


                            <div class="col-md-6 mb-3">

                                <strong>
                                    Household Members
                                </strong>

                                <div>

                                    <?php

                                    echo htmlspecialchars(
                                        $financial["household_members"]
                                        ?? ""
                                    );

                                    ?>

                                </div>

                            </div>


                            <div class="col-md-6 mb-3">

                                <strong>
                                    Outstanding Fees
                                </strong>

                                <div>

                                    KES

                                    <?php

                                    echo number_format(
                                        (float) (
                                            $financial["outstanding_fees"]
                                            ?? 0
                                        ),
                                        2
                                    );

                                    ?>

                                </div>

                            </div>


                        </div>


                    <?php else: ?>

                        <p class="text-danger mb-0">

                            Financial information not completed.

                        </p>

                    <?php endif; ?>


                </div>

            </div>


            <!-- =================================================
                 SPECIAL CIRCUMSTANCES
            ================================================== -->

            <div class="card shadow-sm border-0 mb-4">


                <div
                    class="card-header bg-white
                           d-flex justify-content-between
                           align-items-center"
                >

                    <h5 class="mb-0">

                        Special Circumstances

                    </h5>


                    <a
                        href="circumstances.php"
                        class="btn btn-sm btn-outline-primary"
                    >

                        Edit

                    </a>

                </div>


                <div class="card-body">


                    <?php if ($circumstance): ?>


                        <p>

                            <strong>
                                Special Circumstances:
                            </strong>

                            <?php

                            echo htmlspecialchars(
                                $circumstance[
                                    "has_special_circumstances"
                                ] ?? ""
                            );

                            ?>

                        </p>


                        <?php if (
                            isset(
                                $circumstance[
                                    "has_special_circumstances"
                                ]
                            )
                            &&
                            strtolower(
                                $circumstance[
                                    "has_special_circumstances"
                                ]
                            ) === "yes"
                        ): ?>


                            <p>

                                <strong>
                                    Type:
                                </strong>

                                <?php

                                echo htmlspecialchars(
                                    $circumstance[
                                        "circumstance_type"
                                    ] ?? ""
                                );

                                ?>

                            </p>


                            <p>

                                <strong>
                                    Description:
                                </strong>

                            </p>


                            <p>

                                <?php

                                echo nl2br(
                                    htmlspecialchars(
                                        $circumstance[
                                            "description"
                                        ] ?? ""
                                    )
                                );

                                ?>

                            </p>


                        <?php endif; ?>


                    <?php else: ?>

                        <p class="text-danger mb-0">

                            Special circumstances not completed.

                        </p>

                    <?php endif; ?>


                </div>

            </div>


            <!-- =================================================
                 DOCUMENTS
            ================================================== -->

            <div class="card shadow-sm border-0 mb-4">


                <div
                    class="card-header bg-white
                           d-flex justify-content-between
                           align-items-center"
                >

                    <h5 class="mb-0">

                        Documents

                    </h5>


                    <a
                        href="documents.php"
                        class="btn btn-sm btn-outline-primary"
                    >

                        Manage

                    </a>

                </div>


                <div class="card-body">


                    <?php if (count($documents) > 0): ?>


                        <ul class="list-group">


                            <?php foreach (
                                $documents
                                as $document
                            ): ?>


                                <li
                                    class="list-group-item
                                           d-flex
                                           justify-content-between
                                           align-items-center"
                                >


                                    <span>

                                        <?php

                                        echo htmlspecialchars(
                                            $document[
                                                "document_type"
                                            ]
                                        );

                                        ?>

                                    </span>


                                    <small class="text-muted">

                                        <?php

                                        echo htmlspecialchars(
                                            $document[
                                                "original_name"
                                            ]
                                        );

                                        ?>

                                    </small>


                                </li>


                            <?php endforeach; ?>


                        </ul>


                    <?php else: ?>


                        <p class="text-danger mb-0">

                            No documents uploaded.

                        </p>


                    <?php endif; ?>


                </div>

            </div>


            <!-- =================================================
                 DECLARATION
            ================================================== -->

            <div class="card border-warning mb-4">


                <div class="card-body">


                    <div class="form-check">


                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="declaration"
                        >


                        <label
                            class="form-check-label"
                            for="declaration"
                        >

                            I confirm that the information provided
                            in this application is true and accurate
                            to the best of my knowledge. I understand
                            that providing false information may result
                            in disqualification.

                        </label>


                    </div>


                </div>

            </div>


            <!-- =================================================
                 SUBMIT FORM
            ================================================== -->

            <form
                method="POST"
                id="submitForm"
            >


                <div
                    class="d-flex justify-content-between"
                >


                    <a
                        href="application.php"
                        class="btn btn-outline-secondary"
                    >

                        Back to Application

                    </a>


                    <button
                        type="submit"
                        id="submitButton"
                        class="btn btn-success px-5"
                        disabled
                    >

                        Submit Application

                    </button>


                </div>


            </form>


        </div>

    </div>

</div>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

const declaration =
    document.getElementById("declaration");

const submitButton =
    document.getElementById("submitButton");


declaration.addEventListener(
    "change",
    function () {

        submitButton.disabled =
            !declaration.checked;

    }
);


document.getElementById("submitForm")
    .addEventListener(
        "submit",
        function (event) {

            if (!declaration.checked) {

                event.preventDefault();

                return;
            }


            submitButton.disabled = true;

            submitButton.innerText =
                "Submitting...";

        }
    );

</script>


</body>

</html>