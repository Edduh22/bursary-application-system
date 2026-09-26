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
| FETCH APPLICANTS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT 
        a.id,
        a.user_id,
        a.national_id,
        a.date_of_birth,
        a.gender,
        a.county,
        a.constituency,
        a.ward,
        a.village,
        u.full_name,
        u.email
    FROM applicants a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Applicants - Bursary Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body>

<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2>Applicants</h2>
            <p class="text-muted">
                Manage registered bursary applicants
            </p>
        </div>

        <a href="dashboard.php" class="btn btn-secondary">
            Back to Dashboard
        </a>

    </div>


    <?php if (count($applicants) > 0): ?>

        <div class="card shadow-sm">

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-dark">

                            <tr>

                                <th>#</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>National ID</th>
                                <th>Gender</th>
                                <th>County</th>
                                <th>Constituency</th>
                                <th>Ward</th>
                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($applicants as $index => $applicant): ?>

                            <tr>

                                <td>
                                    <?php echo $index + 1; ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $applicant["full_name"] ?? "N/A"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $applicant["email"] ?? "N/A"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $applicant["national_id"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $applicant["gender"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $applicant["county"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $applicant["constituency"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $applicant["ward"]
                                    );
                                    ?>
                                </td>

                                <td>

                                    <a
                                        href="view_applicant.php?id=<?php echo $applicant["id"]; ?>"
                                        class="btn btn-sm btn-primary"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    <?php else: ?>

        <div class="alert alert-info">

            No applicants found in the system.

        </div>

    <?php endif; ?>

</div>

</body>

</html>