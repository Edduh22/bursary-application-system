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
// DELETE DOCUMENT

if (
    isset($_GET["delete"])
    && is_numeric($_GET["delete"])
) {

    $document_id = (int) $_GET["delete"];


    // Get document belonging to this application

    $stmt = $pdo->prepare(
        "SELECT *
         FROM application_documents
         WHERE id = ?
         AND application_id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $document_id,
        $application_id
    ]);

    $document = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($document) {

        // Delete physical file

        $file_path = $document["file_path"];

        if (
            file_exists($file_path)
            && is_file($file_path)
        ) {

            unlink($file_path);
        }


        // Delete database record

        $stmt = $pdo->prepare(
            "DELETE FROM application_documents
             WHERE id = ?
             AND application_id = ?"
        );

        $stmt->execute([
            $document_id,
            $application_id
        ]);


        header(
            "Location: documents.php?deleted=1"
        );

        exit;

    } else {

        header(
            "Location: documents.php?error=invalid"
        );

        exit;
    }
}


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


// UPLOAD DIRECTORY

$upload_directory =
    "../uploads/documents/";


// CREATE DIRECTORY IF IT DOES NOT EXIST

if (!is_dir($upload_directory)) {

    mkdir(
        $upload_directory,
        0755,
        true
    );
}


// ALLOWED DOCUMENT TYPES

$allowed_types = [

    "National ID / Birth Certificate" => [
        "pdf",
        "jpg",
        "jpeg",
        "png"
    ],

    "Admission Letter" => [
        "pdf",
        "jpg",
        "jpeg",
        "png"
    ],

    "Fee Structure" => [
        "pdf",
        "jpg",
        "jpeg",
        "png"
    ],

    "Guardian ID" => [
        "pdf",
        "jpg",
        "jpeg",
        "png"
    ],

    "Other Supporting Document" => [
        "pdf",
        "jpg",
        "jpeg",
        "png"
    ]

];


// MAX FILE SIZE
// 5 MB

$max_file_size = 5 * 1024 * 1024;


// MESSAGE

$message = "";

$message_type = "";
if (isset($_GET["deleted"])) {

    $message =
        "Document deleted successfully.";

    $message_type =
        "success";
}


if (isset($_GET["error"])) {

    $message =
        "Invalid document.";

    $message_type =
        "danger";
}


// UPLOAD FILE

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $document_type =
        trim($_POST["document_type"] ?? "");


    // CHECK DOCUMENT TYPE

    if (!array_key_exists(
        $document_type,
        $allowed_types
    )) {

        $message =
            "Invalid document type.";

        $message_type = "danger";

    } elseif (
        !isset($_FILES["document"])
        ||
        $_FILES["document"]["error"]
        !== UPLOAD_ERR_OK
    ) {

        $message =
            "Please select a valid file.";

        $message_type = "danger";

    } else {

        $file = $_FILES["document"];


        // ORIGINAL FILE NAME

        $original_name =
            basename($file["name"]);


        // FILE SIZE

        $file_size =
            (int)$file["size"];


        // TEMPORARY FILE

        $tmp_name =
            $file["tmp_name"];


        // FILE EXTENSION

        $extension =
            strtolower(
                pathinfo(
                    $original_name,
                    PATHINFO_EXTENSION
                )
            );


        // CHECK SIZE

        if ($file_size > $max_file_size) {

            $message =
                "File is too large. Maximum allowed size is 5 MB.";

            $message_type = "danger";

        }

        // CHECK EXTENSION

        elseif (
            !in_array(
                $extension,
                $allowed_types[$document_type],
                true
            )
        ) {

            $message =
                "This file type is not allowed.";

            $message_type = "danger";

        } else {

            // VERIFY MIME TYPE

            $finfo =
                new finfo(FILEINFO_MIME_TYPE);

            $mime_type =
                $finfo->file($tmp_name);


            $allowed_mimes = [

                "pdf" => [
                    "application/pdf"
                ],

                "jpg" => [
                    "image/jpeg"
                ],

                "jpeg" => [
                    "image/jpeg"
                ],

                "png" => [
                    "image/png"
                ]

            ];


            if (
                !isset($allowed_mimes[$extension])
                ||
                !in_array(
                    $mime_type,
                    $allowed_mimes[$extension],
                    true
                )
            ) {

                $message =
                    "The uploaded file does not match its file extension.";

                $message_type = "danger";

            } else {

                // UNIQUE FILE NAME

                $unique_name =
                    $application_id
                    . "_"
                    . bin2hex(
                        random_bytes(16)
                    )
                    . "."
                    . $extension;


                $destination =
                    $upload_directory
                    . $unique_name;


                // MOVE FILE

                if (
                    move_uploaded_file(
                        $tmp_name,
                        $destination
                    )
                ) {

                    // SAVE DATABASE RECORD

                    $stmt = $pdo->prepare(
                        "INSERT INTO application_documents (

                            application_id,
                            document_type,
                            original_name,
                            stored_name,
                            file_path,
                            file_size,
                            mime_type

                        )

                        VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );


                    $stmt->execute([

                        $application_id,

                        $document_type,

                        $original_name,

                        $unique_name,

                        $destination,

                        $file_size,

                        $mime_type

                    ]);


                    $message =
                        "Document uploaded successfully.";

                    $message_type =
                        "success";

                } else {

                    $message =
                        "Failed to save the uploaded file.";

                    $message_type =
                        "danger";
                }
            }
        }
    }
}


// GET UPLOADED DOCUMENTS

$stmt = $pdo->prepare(
    "SELECT *
     FROM application_documents
     WHERE application_id = ?
     ORDER BY uploaded_at DESC"
);

$stmt->execute([$application_id]);

$documents =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Documents - Bursary System
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
                    Supporting Documents
                </h2>

                <p class="text-muted">
                    Upload the documents required to support
                    your bursary application.
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


            <!-- UPLOAD FORM -->

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Upload Document
                    </h5>

                </div>


                <div class="card-body">

                    <form
                        method="POST"
                        enctype="multipart/form-data"
                    >


                        <!-- DOCUMENT TYPE -->

                        <div class="mb-3">

                            <label class="form-label">

                                Document Type *

                            </label>


                            <select
                                name="document_type"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    Select Document
                                </option>


                                <?php foreach (
                                    $allowed_types
                                    as $type => $extensions
                                ): ?>

                                    <option
                                        value="<?php
                                        echo htmlspecialchars($type);
                                        ?>"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $type
                                        );

                                        ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- FILE -->

                        <div class="mb-3">

                            <label class="form-label">

                                Select File *

                            </label>


                            <input
                                type="file"
                                name="document"
                                class="form-control"
                                accept=".pdf,.jpg,.jpeg,.png"
                                required
                            >


                            <small class="text-muted">

                                Allowed formats:
                                PDF, JPG, JPEG and PNG.
                                Maximum size: 5 MB.

                            </small>

                        </div>


                        <!-- BUTTON -->

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Upload Document
                        </button>


                    </form>

                </div>

            </div>


            <!-- UPLOADED DOCUMENTS -->

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Uploaded Documents
                    </h5>

                </div>


                <div class="card-body p-0">


                    <?php if (count($documents) > 0): ?>


                        <div class="table-responsive">

                            <table
                                class="table table-hover mb-0"
                            >

                                <thead>

                                    <tr>

                                        <th>
                                            Document
                                        </th>

                                        <th>
                                            File
                                        </th>

                                        <th>
                                            Size
                                        </th>

                                        <th>
    Uploaded
</th>

<th>
    Action
</th>

                                    </tr>

                                </thead>


                                <tbody>


                                <?php foreach (
                                    $documents
                                    as $document
                                ): ?>

                                    <tr>

                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $document["document_type"]
                                            );

                                            ?>

                                        </td>


                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $document["original_name"]
                                            );

                                            ?>

                                        </td>


                                        <td>

                                            <?php

                                            echo number_format(
                                                $document["file_size"]
                                                / 1024,
                                                1
                                            );

                                            ?>

                                            KB

                                        </td>


                                       <td>

    <?php

    echo htmlspecialchars(
        $document["uploaded_at"]
    );

    ?>

</td>


<td>

    <a
        href="documents.php?delete=<?php echo (int)$document["id"]; ?>"
        class="btn btn-sm btn-outline-danger"
        onclick="return confirm('Are you sure you want to delete this document?');"
    >
        Delete
    </a>

</td>

                                    </tr>

                                <?php endforeach; ?>


                                </tbody>

                            </table>

                        </div>


                    <?php else: ?>


                        <div class="p-4 text-center text-muted">

                            No documents uploaded yet.

                        </div>


                    <?php endif; ?>


                </div>

            </div>


            <!-- NAVIGATION -->

            <div class="d-flex justify-content-between mt-4">

                <a
                    href="application.php"
                    class="btn btn-outline-secondary"
                >
                    Back to Application
                </a>

            </div>


        </div>

    </div>

</div>


</body>

</html>