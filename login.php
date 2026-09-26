<?php

session_start();

require_once "config/database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {

        $message = "Please enter your email and password.";
        $message_type = "danger";

    } else {

        $stmt = $pdo->prepare(
            "SELECT id, full_name, email, password, role, status
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {

            $message = "Invalid email or password.";
            $message_type = "danger";

        } elseif (!password_verify($password, $user["password"])) {

            $message = "Invalid email or password.";
            $message_type = "danger";

        } elseif ($user["status"] !== "active") {

            $message = "Your account has been deactivated.";
            $message_type = "danger";

        } else {

            // Create session

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];


            // Redirect according to role

            if ($user["role"] === "admin") {

                header("Location: admin/dashboard.php");
                exit;

            } else {

                header("Location: applicant/dashboard.php");
                exit;
            }
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

    <title>Login - Bursary Application System</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<!-- NAVBAR -->

<nav class="navbar navbar-light bg-white shadow-sm">

    <div class="container">

        <a
            class="navbar-brand fw-bold"
            href="index.php"
        >
            BURSARY APPLICATION SYSTEM
        </a>

        <a
            href="register.php"
            class="btn btn-outline-primary"
        >
            Register
        </a>

    </div>

</nav>


<!-- LOGIN -->

<section class="py-5">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-md-7 col-lg-5">

                <div class="card shadow-sm border-0">

                    <div class="card-body p-4 p-md-5">

                        <div class="text-center mb-4">

                            <h2 class="fw-bold">
                                Welcome Back
                            </h2>

                            <p class="text-muted">
                                Login to your account
                            </p>

                        </div>


                        <?php if (!empty($message)): ?>

                            <div class="alert alert-<?php echo $message_type; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>

                        <?php endif; ?>


                        <form method="POST">


                            <!-- EMAIL -->

                            <div class="mb-3">

                                <label class="form-label">
                                    Email Address
                                </label>

                                <input
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    placeholder="Enter your email"
                                    required
                                >

                            </div>


                            <!-- PASSWORD -->

                            <div class="mb-4">

                                <label class="form-label">
                                    Password
                                </label>

                                <input
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Enter your password"
                                    required
                                >

                            </div>


                            <!-- LOGIN BUTTON -->

                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                            >
                                Login
                            </button>


                        </form>


                        <div class="text-center mt-4">

                            <p class="mb-0">

                                Don't have an account?

                                <a href="register.php">
                                    Create an account
                                </a>

                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

</body>

</html>