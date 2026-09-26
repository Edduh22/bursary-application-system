<?php
session_start();

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: dashboard.php");
    exit;
}

$host = "localhost";
$dbname = "bursary_system";
$username = "root";
$password = "";

$error = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database connection failed.");
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $loginPassword = $_POST["password"] ?? "";

    if ($email === "" || $loginPassword === "") {

        $error = "Please enter your email and password.";

    } else {

        $stmt = $pdo->prepare("
            SELECT id, full_name, email, password, role, status
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if ($user && password_verify($loginPassword, $user["password"])) {

            if ($user["role"] !== "admin") {

                $error = "Access denied. This account is not an administrator.";

            } elseif ($user["status"] !== "active") {

                $error = "This administrator account is inactive.";

            } else {

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];

                header("Location: dashboard.php");
                exit;
            }

        } else {

            $error = "Invalid administrator email or password.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login | Bursary System</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #09234d, #1769d1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            width: 420px;
            background: white;
            border-radius: 14px;
            padding: 40px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-icon {
            width: 65px;
            height: 65px;
            margin: auto;
            border-radius: 50%;
            background: #eaf2ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .logo h1 {
            margin-top: 15px;
            font-size: 23px;
            color: #09234d;
        }

        .logo p {
            color: #7b8492;
            margin-top: 6px;
            font-size: 14px;
        }

        .alert {
            background: #ffe8e8;
            color: #b4232d;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            color: #293449;
            font-size: 14px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #d7dce5;
            border-radius: 7px;
            outline: none;
            font-size: 14px;
        }

        input:focus {
            border-color: #1769d1;
            box-shadow: 0 0 0 3px rgba(23,105,209,0.1);
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 7px;
            background: #1769d1;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #0d57b5;
        }

        .footer {
            text-align: center;
            margin-top: 25px;
            color: #8a93a3;
            font-size: 12px;
        }

    </style>

</head>

<body>

<div class="login-container">

    <div class="logo">

        <div class="logo-icon">
            🎓
        </div>

        <h1>BURSARY SYSTEM</h1>

        <p>Administrator Login</p>

    </div>


    <?php if ($error): ?>

        <div class="alert">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <form method="POST">

        <div class="form-group">

            <label for="email">
                Administrator Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter administrator email"
                required
            >

        </div>


        <div class="form-group">

            <label for="password">
                Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter administrator password"
                required
            >

        </div>


        <button type="submit">
            Login to Admin Panel
        </button>

    </form>


    <div class="footer">
        Bursary Application System &copy; 2026
    </div>

</div>

</body>

</html>