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
| DEFAULT SETTINGS
|--------------------------------------------------------------------------
|
| These are temporary prototype settings.
| We will connect them to a database table later.
|
*/

$system_name = "Bursary Application System";
$application_year = "2026";
$application_status = "Open";

$message = "";
$message_type = "";


/*
|--------------------------------------------------------------------------
| SAVE SETTINGS
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $system_name = trim($_POST["system_name"] ?? "");
    $application_year = trim($_POST["application_year"] ?? "");
    $application_status = $_POST["application_status"] ?? "";

    if ($system_name === "") {

        $message = "System name cannot be empty.";
        $message_type = "danger";

    } elseif (
        !preg_match('/^\d{4}$/', $application_year)
    ) {

        $message = "Please enter a valid application year.";
        $message_type = "danger";

    } elseif (
        !in_array(
            $application_status,
            ["Open", "Closed"],
            true
        )
    ) {

        $message = "Invalid application status.";
        $message_type = "danger";

    } else {

        /*
        |----------------------------------------------------------------------
        | SAVE TO SESSION FOR PROTOTYPE
        |----------------------------------------------------------------------
        */

        $_SESSION["system_name"] = $system_name;
        $_SESSION["application_year"] = $application_year;
        $_SESSION["application_status"] = $application_status;

        $message = "Settings saved successfully.";
        $message_type = "success";
    }
}


/*
|--------------------------------------------------------------------------
| LOAD SESSION SETTINGS
|--------------------------------------------------------------------------
*/

if (isset($_SESSION["system_name"])) {
    $system_name = $_SESSION["system_name"];
}

if (isset($_SESSION["application_year"])) {
    $application_year = $_SESSION["application_year"];
}

if (isset($_SESSION["application_status"])) {
    $application_status = $_SESSION["application_status"];
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
        Settings - <?php echo htmlspecialchars($system_name); ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">


<!-- NAVBAR -->

<nav class="navbar navbar-dark bg-primary shadow-sm">

    <div class="container-fluid">

        <a
            href="dashboard.php"
            class="navbar-brand fw-bold"
        >
            <?php echo htmlspecialchars($system_name); ?>
        </a>

        <div>

            <a
                href="dashboard.php"
                class="btn btn-light btn-sm me-2"
            >
                Dashboard
            </a>

            <a
                href="../logout.php"
                class="btn btn-outline-light btn-sm"
            >
                Logout
            </a>

        </div>

    </div>

</nav>


<!-- CONTENT -->

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-8">


            <!-- PAGE HEADER -->

            <div class="mb-4">

                <h2 class="fw-bold">
                    System Settings
                </h2>

                <p class="text-muted">
                    Manage the basic configuration of the bursary system.
                </p>

            </div>


            <!-- MESSAGE -->

            <?php if (!empty($message)): ?>

                <div
                    class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show"
                >

                    <?php echo htmlspecialchars($message); ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>


            <!-- SETTINGS CARD -->

            <div class="card border-0 shadow-sm">

                <div class="card-header bg-white py-3">

                    <h5 class="mb-0 fw-bold">
                        General Settings
                    </h5>

                </div>

                <div class="card-body p-4">


                    <form method="POST">


                        <!-- SYSTEM NAME -->

                        <div class="mb-4">

                            <label class="form-label fw-semibold">
                                System Name
                            </label>

                            <input
                                type="text"
                                name="system_name"
                                class="form-control"
                                value="<?php echo htmlspecialchars($system_name); ?>"
                                required
                            >

                            <div class="form-text">
                                Name displayed throughout the system.
                            </div>

                        </div>


                        <!-- APPLICATION YEAR -->

                        <div class="mb-4">

                            <label class="form-label fw-semibold">
                                Application Year
                            </label>

                            <input
                                type="number"
                                name="application_year"
                                class="form-control"
                                min="2020"
                                max="2100"
                                value="<?php echo htmlspecialchars($application_year); ?>"
                                required
                            >

                            <div class="form-text">
                                Current bursary application cycle.
                            </div>

                        </div>


                        <!-- APPLICATION STATUS -->

                        <div class="mb-4">

                            <label class="form-label fw-semibold">
                                Application Status
                            </label>

                            <select
                                name="application_status"
                                class="form-select"
                                required
                            >

                                <option
                                    value="Open"
                                    <?php
                                    echo $application_status === "Open"
                                        ? "selected"
                                        : "";
                                    ?>
                                >
                                    Open
                                </option>

                                <option
                                    value="Closed"
                                    <?php
                                    echo $application_status === "Closed"
                                        ? "selected"
                                        : "";
                                    ?>
                                >
                                    Closed
                                </option>

                            </select>

                            <div class="form-text">
                                Controls whether the current application
                                cycle is open or closed.
                            </div>

                        </div>


                        <!-- BUTTONS -->

                        <div class="d-flex justify-content-between">

                            <a
                                href="dashboard.php"
                                class="btn btn-secondary"
                            >
                                Cancel
                            </a>

                            <button
                                type="submit"
                                class="btn btn-primary px-4"
                            >
                                Save Settings
                            </button>

                        </div>

                    </form>

                </div>

            </div>


            <!-- CURRENT STATUS -->

            <div class="card border-0 shadow-sm mt-4">

                <div class="card-body">

                    <h5 class="fw-bold mb-3">
                        Current System Status
                    </h5>

                    <div class="row">

                        <div class="col-md-4 mb-3">

                            <div class="text-muted small">
                                System Name
                            </div>

                            <strong>
                                <?php
                                echo htmlspecialchars($system_name);
                                ?>
                            </strong>

                        </div>

                        <div class="col-md-4 mb-3">

                            <div class="text-muted small">
                                Application Year
                            </div>

                            <strong>
                                <?php
                                echo htmlspecialchars($application_year);
                                ?>
                            </strong>

                        </div>

                        <div class="col-md-4 mb-3">

                            <div class="text-muted small">
                                Application Status
                            </div>

                            <?php if ($application_status === "Open"): ?>

                                <span class="badge bg-success">
                                    Open
                                </span>

                            <?php else: ?>

                                <span class="badge bg-danger">
                                    Closed
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>