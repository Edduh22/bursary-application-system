<?php

require_once "config/database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];


    // CHECK EMPTY FIELDS

    if (
        empty($full_name) ||
        empty($email) ||
        empty($phone) ||
        empty($password) ||
        empty($confirm_password)
    ) {

        $message = "Please fill in all fields.";
        $message_type = "danger";

    }

    // CHECK EMAIL

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "danger";

    }

    // CHECK PASSWORD

    elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";
        $message_type = "danger";

    }

    // CHECK PASSWORD MATCH

    elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";
        $message_type = "danger";

    }

    else {

        // CHECK IF EMAIL EXISTS

        $check = $pdo->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $check->execute([$email]);

        if ($check->fetch()) {

            $message = "An account with this email already exists.";
            $message_type = "danger";

        } else {

            // HASH PASSWORD

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            // INSERT USER

            $stmt = $pdo->prepare(
                "INSERT INTO users
                (full_name, email, phone, password)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->execute([
                $full_name,
                $email,
                $phone,
                $hashed_password
            ]);


            $message = "Account created successfully! You can now login.";
            $message_type = "success";
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

    <title>Create Account - Bursary System</title>

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
            href="login.php"
            class="btn btn-outline-primary"
        >
            Login
        </a>

    </div>

</nav>


<!-- REGISTRATION -->

<section class="py-5">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-md-7 col-lg-6">

                <div class="card shadow-sm border-0">

                    <div class="card-body p-4 p-md-5">

                        <div class="text-center mb-4">

                            <h2 class="fw-bold">
                                Create Account
                            </h2>

                            <p class="text-muted">
                                Register to apply for a bursary
                            </p>

                        </div>


                        <?php if (!empty($message)): ?>

                            <div class="alert alert-<?php echo $message_type; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>

                        <?php endif; ?>


                        <form method="POST">


                            <!-- FULL NAME -->

                            <div class="mb-3">

                                <label class="form-label">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    name="full_name"
                                    class="form-control"
                                    placeholder="Enter your full name"
                                    required
                                >

                            </div>


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


                            <!-- PHONE -->

                            <div class="mb-3">

                                <label class="form-label">
                                    Phone Number
                                </label>

                                <input
                                    type="tel"
                                    name="phone"
                                    class="form-control"
                                    placeholder="e.g. 0712345678"
                                    required
                                >

                            </div>


                            <!-- PASSWORD -->

                            <div class="mb-3">

                                <label class="form-label">
                                    Password
                                </label>

                                <input
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Minimum 6 characters"
                                    required
                                >

                            </div>


                            <!-- CONFIRM PASSWORD -->

                            <div class="mb-4">

                                <label class="form-label">
                                    Confirm Password
                                </label>

                                <input
                                    type="password"
                                    name="confirm_password"
                                    class="form-control"
                                    placeholder="Confirm your password"
                                    required
                                >

                            </div>


                            <!-- SUBMIT -->

                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                            >
                                Create Account
                            </button>


                        </form>


                        <div class="text-center mt-4">

                            <p class="mb-0">

                                Already have an account?

                                <a href="login.php">
                                    Login here
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