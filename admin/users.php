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
| MESSAGES
|--------------------------------------------------------------------------
*/

$message = "";
$message_type = "";


/*
|--------------------------------------------------------------------------
| UPDATE USER
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id = isset($_POST["user_id"]) ? (int) $_POST["user_id"] : 0;
    $action = $_POST["action"] ?? "";

    if ($user_id <= 0) {

        $message = "Invalid user.";
        $message_type = "danger";

    } else {

        /*
        |----------------------------------------------------------------------
        | ACTIVATE / DEACTIVATE
        |----------------------------------------------------------------------
        */

        if ($action === "toggle_status") {

            // Prevent admin from deactivating their own account
            if ($user_id === (int) $_SESSION["user_id"]) {

                $message = "You cannot deactivate your own account.";
                $message_type = "warning";

            } else {

                $stmt = $pdo->prepare(
                    "SELECT status
                     FROM users
                     WHERE id = ?
                     LIMIT 1"
                );

                $stmt->execute([$user_id]);

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {

                    $message = "User not found.";
                    $message_type = "danger";

                } else {

                    $new_status =
                        ($user["status"] === "active")
                        ? "inactive"
                        : "active";

                    $stmt = $pdo->prepare(
                        "UPDATE users
                         SET status = ?
                         WHERE id = ?"
                    );

                    $stmt->execute([
                        $new_status,
                        $user_id
                    ]);

                    $message = "User status updated successfully.";
                    $message_type = "success";
                }
            }
        }


        /*
        |----------------------------------------------------------------------
        | CHANGE ROLE
        |----------------------------------------------------------------------
        */

        elseif ($action === "change_role") {

            $new_role = $_POST["role"] ?? "";

            if (!in_array($new_role, ["admin", "applicant"], true)) {

                $message = "Invalid role selected.";
                $message_type = "danger";

            } elseif ($user_id === (int) $_SESSION["user_id"] && $new_role !== "admin") {

                $message = "You cannot remove your own admin role.";
                $message_type = "warning";

            } else {

                $stmt = $pdo->prepare(
                    "UPDATE users
                     SET role = ?
                     WHERE id = ?"
                );

                $stmt->execute([
                    $new_role,
                    $user_id
                ]);

                $message = "User role updated successfully.";
                $message_type = "success";
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET USERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT id, full_name, email, role, status
     FROM users
     ORDER BY id DESC"
);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Users & Roles - Bursary Application System</title>

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
            BURSARY ADMIN
        </a>

        <div>

            <a
                href="dashboard.php"
                class="btn btn-light btn-sm me-2"
            >
                Dashboard
            </a>

            <a
                href="logout.php"
                class="btn btn-outline-light btn-sm"
            >
                Logout
            </a>

        </div>

    </div>

</nav>


<!-- PAGE -->

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Users & Roles
            </h2>

            <p class="text-muted mb-0">
                Manage registered users and their account access.
            </p>

        </div>

        <a
            href="dashboard.php"
            class="btn btn-secondary"
        >
            ← Back to Dashboard
        </a>

    </div>


    <!-- MESSAGE -->

    <?php if (!empty($message)): ?>

        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">

            <?php echo htmlspecialchars($message); ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- USERS TABLE -->

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white py-3">

            <h5 class="mb-0 fw-bold">
                Registered Users
            </h5>

        </div>

        <div class="card-body">

            <?php if (empty($users)): ?>

                <div class="text-center py-5">

                    <h5 class="text-muted">
                        No users found.
                    </h5>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-light">

                            <tr>

                                <th>#</th>

                                <th>Full Name</th>

                                <th>Email</th>

                                <th>Role</th>

                                <th>Status</th>

                                <th>Actions</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($users as $user): ?>

                                <tr>

                                    <td>
                                        <?php echo (int) $user["id"]; ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $user["full_name"]
                                            );
                                            ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $user["email"]
                                        );
                                        ?>
                                    </td>


                                    <!-- ROLE -->

                                    <td>

                                        <?php if ($user["role"] === "admin"): ?>

                                            <span class="badge bg-primary">
                                                Admin
                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-secondary">
                                                Applicant
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?php if ($user["status"] === "active"): ?>

                                            <span class="badge bg-success">
                                                Active
                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-danger">
                                                Inactive
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="d-flex gap-2 flex-wrap">


                                            <!-- TOGGLE STATUS -->

                                            <?php if (
                                                $user["id"] != $_SESSION["user_id"]
                                            ): ?>

                                                <form
                                                    method="POST"
                                                    class="d-inline"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="user_id"
                                                        value="<?php echo (int) $user["id"]; ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="toggle_status"
                                                    >

                                                    <?php if ($user["status"] === "active"): ?>

                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Deactivate this user?');"
                                                        >
                                                            Deactivate
                                                        </button>

                                                    <?php else: ?>

                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-outline-success"
                                                        >
                                                            Activate
                                                        </button>

                                                    <?php endif; ?>

                                                </form>

                                            <?php endif; ?>


                                            <!-- CHANGE ROLE -->

                                            <?php if (
                                                $user["id"] != $_SESSION["user_id"]
                                            ): ?>

                                                <form
                                                    method="POST"
                                                    class="d-inline"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="user_id"
                                                        value="<?php echo (int) $user["id"]; ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="change_role"
                                                    >

                                                    <?php if ($user["role"] === "applicant"): ?>

                                                        <input
                                                            type="hidden"
                                                            name="role"
                                                            value="admin"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-outline-primary"
                                                            onclick="return confirm('Change this user to Admin?');"
                                                        >
                                                            Make Admin
                                                        </button>

                                                    <?php else: ?>

                                                        <input
                                                            type="hidden"
                                                            name="role"
                                                            value="applicant"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-outline-secondary"
                                                            onclick="return confirm('Change this user to Applicant?');"
                                                        >
                                                            Make Applicant
                                                        </button>

                                                    <?php endif; ?>

                                                </form>

                                            <?php else: ?>

                                                <span class="text-muted small">
                                                    Current account
                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>