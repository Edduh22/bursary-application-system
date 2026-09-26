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


// GET EXISTING FINANCIAL INFORMATION

$stmt = $pdo->prepare(
    "SELECT *
     FROM application_financial
     WHERE application_id = ?
     LIMIT 1"
);

$stmt->execute([$application_id]);

$financial = $stmt->fetch(PDO::FETCH_ASSOC);


// MESSAGE

$message = "";
$message_type = "";


// SAVE FINANCIAL INFORMATION

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $household_income =
        $_POST["household_income"] ?? 0;

    $income_source =
        trim($_POST["income_source"] ?? "");

    $household_members =
        $_POST["household_members"] ?? 0;

    $children_in_school =
        $_POST["children_in_school"] ?? 0;

    $monthly_expenses =
        $_POST["monthly_expenses"] ?? 0;

    $school_fees_required =
        $_POST["school_fees_required"] ?? 0;

    $fees_paid =
        $_POST["fees_paid"] ?? 0;

    $outstanding_fees =
        $_POST["outstanding_fees"] ?? 0;

    $other_support =
        trim($_POST["other_support"] ?? "");

    $financial_challenges =
        trim($_POST["financial_challenges"] ?? "");


    // CONVERT NUMBERS

    $household_income =
        ($household_income === "")
        ? 0
        : (float)$household_income;

    $household_members =
        ($household_members === "")
        ? 0
        : (int)$household_members;

    $children_in_school =
        ($children_in_school === "")
        ? 0
        : (int)$children_in_school;

    $monthly_expenses =
        ($monthly_expenses === "")
        ? 0
        : (float)$monthly_expenses;

    $school_fees_required =
        ($school_fees_required === "")
        ? 0
        : (float)$school_fees_required;

    $fees_paid =
        ($fees_paid === "")
        ? 0
        : (float)$fees_paid;

    // Automatically calculate outstanding fees

    $outstanding_fees =
        max(0, $school_fees_required - $fees_paid);


    // VALIDATION

    if ($household_members < 1) {

        $message =
            "Household members must be at least 1.";

        $message_type = "danger";

    } elseif ($children_in_school < 0) {

        $message =
            "Number of children in school cannot be negative.";

        $message_type = "danger";

    } elseif ($fees_paid > $school_fees_required) {

        $message =
            "Fees paid cannot be greater than fees required.";

        $message_type = "danger";

    } else {

        if ($financial) {

            // UPDATE EXISTING RECORD

            $stmt = $pdo->prepare(
                "UPDATE application_financial SET

                    household_income = ?,
                    income_source = ?,
                    household_members = ?,
                    children_in_school = ?,
                    monthly_expenses = ?,
                    school_fees_required = ?,
                    fees_paid = ?,
                    outstanding_fees = ?,
                    other_support = ?,
                    financial_challenges = ?

                 WHERE application_id = ?"
            );

            $stmt->execute([

                $household_income,
                $income_source,
                $household_members,
                $children_in_school,
                $monthly_expenses,
                $school_fees_required,
                $fees_paid,
                $outstanding_fees,
                $other_support,
                $financial_challenges,
                $application_id

            ]);

        } else {

            // INSERT NEW RECORD

            $stmt = $pdo->prepare(
                "INSERT INTO application_financial (

                    application_id,
                    household_income,
                    income_source,
                    household_members,
                    children_in_school,
                    monthly_expenses,
                    school_fees_required,
                    fees_paid,
                    outstanding_fees,
                    other_support,
                    financial_challenges

                )

                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([

                $application_id,
                $household_income,
                $income_source,
                $household_members,
                $children_in_school,
                $monthly_expenses,
                $school_fees_required,
                $fees_paid,
                $outstanding_fees,
                $other_support,
                $financial_challenges

            ]);
        }


        $message =
            "Financial information saved successfully!";

        $message_type = "success";


        // RELOAD SAVED DATA

        $stmt = $pdo->prepare(
            "SELECT *
             FROM application_financial
             WHERE application_id = ?
             LIMIT 1"
        );

        $stmt->execute([$application_id]);

        $financial = $stmt->fetch(PDO::FETCH_ASSOC);
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
        Financial Information - Bursary System
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


            <div class="mb-4">

                <h2 class="fw-bold">
                    Financial Information
                </h2>

                <p class="text-muted">
                    Provide information about your household
                    financial situation.
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


                <!-- HOUSEHOLD INFORMATION -->

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-white">

                        <h5 class="mb-0">
                            Household Information
                        </h5>

                    </div>


                    <div class="card-body">

                        <div class="row">


                            <!-- HOUSEHOLD MEMBERS -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Number of Household Members *
                                </label>

                                <input
                                    type="number"
                                    name="household_members"
                                    class="form-control"
                                    min="1"
                                    required
                                    value="<?php

                                    echo htmlspecialchars(
                                        $financial["household_members"] ?? ""
                                    );

                                    ?>"
                                >

                            </div>


                            <!-- CHILDREN -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Children Currently in School
                                </label>

                                <input
                                    type="number"
                                    name="children_in_school"
                                    class="form-control"
                                    min="0"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $financial["children_in_school"] ?? "0"
                                    );

                                    ?>"
                                >

                            </div>

                        </div>

                    </div>

                </div>


                <!-- INCOME -->

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-white">

                        <h5 class="mb-0">
                            Household Income
                        </h5>

                    </div>


                    <div class="card-body">

                        <div class="row">


                            <!-- INCOME -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Total Monthly Household Income (KES)
                                </label>

                                <input
                                    type="number"
                                    name="household_income"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $financial["household_income"] ?? ""
                                    );

                                    ?>"
                                >

                            </div>


                            <!-- SOURCE -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Main Source of Income
                                </label>

                                <select
                                    name="income_source"
                                    class="form-select"
                                >

                                    <option value="">
                                        Select Source
                                    </option>

                                    <option
                                        value="Employment"
                                        <?php

                                        echo (($financial["income_source"] ?? "") === "Employment")
                                            ? "selected"
                                            : "";

                                        ?>
                                    >
                                        Employment
                                    </option>

                                    <option
                                        value="Business"
                                        <?php

                                        echo (($financial["income_source"] ?? "") === "Business")
                                            ? "selected"
                                            : "";

                                        ?>
                                    >
                                        Business
                                    </option>

                                    <option
                                        value="Farming"
                                        <?php

                                        echo (($financial["income_source"] ?? "") === "Farming")
                                            ? "selected"
                                            : "";

                                        ?>
                                    >
                                        Farming
                                    </option>

                                    <option
                                        value="Casual Work"
                                        <?php

                                        echo (($financial["income_source"] ?? "") === "Casual Work")
                                            ? "selected"
                                            : "";

                                        ?>
                                    >
                                        Casual Work
                                    </option>

                                    <option
                                        value="Other"
                                        <?php

                                        echo (($financial["income_source"] ?? "") === "Other")
                                            ? "selected"
                                            : "";

                                        ?>
                                    >
                                        Other
                                    </option>

                                </select>

                            </div>


                            <!-- EXPENSES -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Estimated Monthly Household Expenses (KES)
                                </label>

                                <input
                                    type="number"
                                    name="monthly_expenses"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $financial["monthly_expenses"] ?? ""
                                    );

                                    ?>"
                                >

                            </div>

                        </div>

                    </div>

                </div>


                <!-- SCHOOL FEES -->

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-white">

                        <h5 class="mb-0">
                            School Fees
                        </h5>

                    </div>


                    <div class="card-body">

                        <div class="row">


                            <!-- REQUIRED -->

                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Fees Required (KES)
                                </label>

                                <input
                                    type="number"
                                    name="school_fees_required"
                                    id="school_fees_required"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $financial["school_fees_required"] ?? ""
                                    );

                                    ?>"
                                >

                            </div>


                            <!-- PAID -->

                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Fees Already Paid (KES)
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
                                        $financial["fees_paid"] ?? ""
                                    );

                                    ?>"
                                >

                            </div>


                            <!-- OUTSTANDING -->

                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Outstanding Fees (KES)
                                </label>

                                <input
                                    type="text"
                                    id="outstanding_display"
                                    class="form-control"
                                    readonly
                                >

                            </div>

                        </div>

                    </div>

                </div>


                <!-- OTHER SUPPORT -->

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-white">

                        <h5 class="mb-0">
                            Other Financial Support
                        </h5>

                    </div>


                    <div class="card-body">

                        <label class="form-label">
                            Are you receiving financial support from another source?
                        </label>

                        <textarea
                            name="other_support"
                            class="form-control"
                            rows="3"
                            placeholder="Example: HELB, sponsor, family support, etc."
                        ><?php

                        echo htmlspecialchars(
                            $financial["other_support"] ?? ""
                        );

                        ?></textarea>

                    </div>

                </div>


                <!-- FINANCIAL CHALLENGES -->

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-white">

                        <h5 class="mb-0">
                            Financial Challenges
                        </h5>

                    </div>


                    <div class="card-body">

                        <label class="form-label">
                            Explain any financial challenges affecting your education.
                        </label>

                        <textarea
                            name="financial_challenges"
                            class="form-control"
                            rows="5"
                            placeholder="Briefly describe your financial situation..."
                        ><?php

                        echo htmlspecialchars(
                            $financial["financial_challenges"] ?? ""
                        );

                        ?></textarea>

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
                        Save Financial Information
                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


<script>

function calculateOutstanding() {

    const required =
        parseFloat(
            document.getElementById(
                "school_fees_required"
            ).value
        ) || 0;

    const paid =
        parseFloat(
            document.getElementById(
                "fees_paid"
            ).value
        ) || 0;

    const outstanding =
        Math.max(
            0,
            required - paid
        );

    document.getElementById(
        "outstanding_display"
    ).value =
        outstanding.toLocaleString(
            "en-KE",
            {
                minimumFractionDigits: 2
            }
        );
}


document
    .getElementById("school_fees_required")
    .addEventListener(
        "input",
        calculateOutstanding
    );


document
    .getElementById("fees_paid")
    .addEventListener(
        "input",
        calculateOutstanding
    );


calculateOutstanding();

</script>


</body>

</html>