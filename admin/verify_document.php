<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
| GET DOCUMENT ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid document ID.");
}

$document_id = (int) $_GET["id"];


/*
|--------------------------------------------------------------------------
| FETCH DOCUMENT
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        d.id,
        d.application_id,
        d.document_type,
        d.original_name,
        d.stored_name,
        d.file_path,
        d.file_size,
        d.mime_type,
        d.uploaded_at,
        d.verification_status,
        d.verification_remarks,
        d.verified_at,
        d.verified_by,

        a.application_number,
        a.status AS application_status,

        ap.id AS applicant_id,
        ap.national_id,

        u.full_name,
        u.email

    FROM application_documents d

    LEFT JOIN applications a
        ON d.application_id = a.id

    LEFT JOIN applicants ap
        ON a.applicant_id = ap.id

    LEFT JOIN users u
        ON ap.user_id = u.id

    WHERE d.id = ?

    LIMIT 1
";


$stmt = $pdo->prepare($sql);
$stmt->execute([$document_id]);

$document = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| DOCUMENT NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$document) {
    die("
        <div style='
            font-family:Arial;
            padding:40px;
            text-align:center;
        '>
            <h2 style='color:#dc3545;'>Document Not Found</h2>

            <p>
                No document exists with ID:
                <strong>" . htmlspecialchars($document_id) . "</strong>
            </p>

            <a href='document_verification.php'>
                ← Back to Document Verification
            </a>
        </div>
    ");
}


/*
|--------------------------------------------------------------------------
| FORMAT FILE SIZE
|--------------------------------------------------------------------------
*/

function formatFileSize($bytes)
{
    $bytes = (int)$bytes;

    if ($bytes <= 0) {
        return "0 KB";
    }

    if ($bytes < 1024) {
        return $bytes . " B";
    }

    if ($bytes < 1024 * 1024) {
        return number_format($bytes / 1024, 1) . " KB";
    }

    return number_format(
        $bytes / (1024 * 1024),
        1
    ) . " MB";
}


/*
|--------------------------------------------------------------------------
| VERIFICATION BADGE
|--------------------------------------------------------------------------
*/

function verificationBadge($status)
{
    switch ($status) {

        case "verified":

            return '
                <span class="badge verified">
                    ✓ Verified
                </span>
            ';

        case "rejected":

            return '
                <span class="badge rejected">
                    ✕ Rejected
                </span>
            ';

        case "pending":

            return '
                <span class="badge pending">
                    ⏳ Pending Verification
                </span>
            ';

        default:

            return '
                <span class="badge neutral">
                    ' .
                    htmlspecialchars(
                        ucfirst(
                            str_replace(
                                "_",
                                " ",
                                $status
                            )
                        )
                    )
                . '
                </span>
            ';
    }
}


/*
|--------------------------------------------------------------------------
| BUILD FILE URL
|--------------------------------------------------------------------------
*/

$file_path = trim(
    $document["file_path"] ?? ""
);


/*
|--------------------------------------------------------------------------
| NORMALIZE SLASHES
|--------------------------------------------------------------------------
*/

$file_path = str_replace(
    "\\",
    "/",
    $file_path
);


/*
|--------------------------------------------------------------------------
| REMOVE LEADING ../
|--------------------------------------------------------------------------
*/

while (strpos($file_path, "../") === 0) {
    $file_path = substr($file_path, 3);
}


/*
|--------------------------------------------------------------------------
| REMOVE LEADING ./
|--------------------------------------------------------------------------
*/

while (strpos($file_path, "./") === 0) {
    $file_path = substr($file_path, 2);
}


/*
|--------------------------------------------------------------------------
| REMOVE LEADING /
|--------------------------------------------------------------------------
*/

$file_path = ltrim($file_path, "/");


/*
|--------------------------------------------------------------------------
| BUILD URL
|--------------------------------------------------------------------------
|
| This page is inside:
|
| /admin/
|
| Therefore:
|
| ../uploads/documents/file.pdf
|
|--------------------------------------------------------------------------
*/

$file_url = "../" . $file_path;


/*
|--------------------------------------------------------------------------
| MIME TYPE
|--------------------------------------------------------------------------
*/

$mime = strtolower(
    trim(
        $document["mime_type"] ?? ""
    )
);


/*
|--------------------------------------------------------------------------
| DETECT MIME TYPE IF DATABASE VALUE IS EMPTY
|--------------------------------------------------------------------------
*/

if ($mime === "") {

    $extension = strtolower(
        pathinfo(
            $document["original_name"] ?? "",
            PATHINFO_EXTENSION
        )
    );

    switch ($extension) {

        case "jpg":
        case "jpeg":
            $mime = "image/jpeg";
            break;

        case "png":
            $mime = "image/png";
            break;

        case "gif":
            $mime = "image/gif";
            break;

        case "webp":
            $mime = "image/webp";
            break;

        case "pdf":
            $mime = "application/pdf";
            break;

        default:
            $mime = "application/octet-stream";
            break;
    }
}


/*
|--------------------------------------------------------------------------
| BACK TO APPLICATION
|--------------------------------------------------------------------------
*/

$back_url = "view_application.php?id=" .
    (int)$document["application_id"];

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
    Verify Document
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<style>

body {
    background: #f5f7fb;
    font-family: Arial, Helvetica, sans-serif;
}

.sidebar {
    width: 250px;
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    background: #09234d;
    color: white;
    padding: 25px 15px;
}

.logo {
    padding: 5px 10px 25px;
    border-bottom: 1px solid rgba(255,255,255,.15);
    margin-bottom: 20px;
}

.logo h4 {
    margin: 0;
    font-size: 18px;
}

.logo small {
    color: #aebed5;
}

.sidebar a {
    display: block;
    padding: 12px 15px;
    margin-bottom: 6px;
    color: #dbe6f5;
    text-decoration: none;
    border-radius: 7px;
}

.sidebar a:hover,
.sidebar a.active {
    background: #1769d1;
    color: white;
}

.content {
    margin-left: 250px;
    padding: 30px;
}

.topbar {
    background: white;
    padding: 20px 25px;
    border-radius: 10px;
    margin-bottom: 25px;
    border: 1px solid #e6eaf0;
}

.card {
    border: 1px solid #e6eaf0;
    border-radius: 10px;
}

.section-title {
    font-weight: 700;
    border-bottom: 2px solid #edf0f4;
    padding-bottom: 12px;
    margin-bottom: 20px;
}

.info-label {
    font-size: 12px;
    color: #687386;
    font-weight: 600;
    margin-bottom: 4px;
}

.info-value {
    font-size: 15px;
    margin-bottom: 18px;
}

.badge {
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.pending {
    background: #fff3cd;
    color: #8a6500;
}

.verified {
    background: #dff5e6;
    color: #18753a;
}

.rejected {
    background: #ffe1e3;
    color: #b4232d;
}

.neutral {
    background: #e9ecef;
    color: #495057;
}

.document-preview {
    min-height: 500px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;

    display: flex;
    align-items: center;
    justify-content: center;

    overflow: hidden;
    padding: 15px;
}

.document-preview img {
    max-width: 100%;
    max-height: 650px;
    object-fit: contain;
}

.document-preview iframe {
    width: 100%;
    height: 650px;
    border: none;
    background: white;
}

.file-placeholder {
    text-align: center;
    padding: 50px;
}

.file-icon {
    font-size: 70px;
    margin-bottom: 15px;
}

.action-card {
    position: sticky;
    top: 20px;
}

.remarks-box {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
}

@media (max-width: 900px) {

    .sidebar {
        position: relative;
        width: 100%;
    }

    .content {
        margin-left: 0;
        padding: 20px;
    }

    .action-card {
        position: relative;
    }

}

</style>

</head>


<body>


<!-- SIDEBAR -->

<div class="sidebar">

    <div class="logo">

        <h4>🎓 BURSARY SYSTEM</h4>

        <small>
            Administrator Panel
        </small>

    </div>


    <a href="dashboard.php">
        🏠 Dashboard
    </a>


    <a href="applications.php">
        📋 All Applications
    </a>


    <a
        href="document_verification.php"
        class="active"
    >
        📄 Document Verification
    </a>


    <a href="review_application.php">
        🔍 Pending Review
    </a>


    <a href="awards.php">
        🏆 Awards
    </a>


    <a href="payments.php">
        💳 Payments
    </a>


    <a href="applicants.php">
        👥 Applicants
    </a>


    <a href="reports.php">
        📊 Reports
    </a>


    <a href="users.php">
        👤 Users & Roles
    </a>


    <a href="settings.php">
        ⚙️ Settings
    </a>

</div>



<!-- MAIN CONTENT -->

<div class="content">


    <div class="topbar">

        <h2>
            📄 Document Verification
        </h2>

        <p>
            Reviewing:
            <strong>
                <?= htmlspecialchars(
                    $document["original_name"]
                ) ?>
            </strong>
        </p>

    </div>


    <a
        href="<?= htmlspecialchars($back_url) ?>"
        class="btn btn-outline-secondary mb-4"
    >
        ← Back to Application
    </a>


    <div class="row g-4">


        <!-- LEFT -->

        <div class="col-lg-8">


            <!-- DOCUMENT INFORMATION -->

            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <h5 class="section-title">
                        📄 Document Information
                    </h5>


                    <div class="row">


                        <div class="col-md-6">

                            <div class="info-label">
                                Document Type
                            </div>

                            <div class="info-value">

                                <strong>
                                    <?= htmlspecialchars(
                                        $document["document_type"]
                                    ) ?>
                                </strong>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-label">
                                Verification Status
                            </div>

                            <div class="info-value">

                                <?= verificationBadge(
                                    $document["verification_status"]
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-label">
                                Original File Name
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $document["original_name"]
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="info-label">
                                File Size
                            </div>

                            <div class="info-value">

                                <?= formatFileSize(
                                    $document["file_size"]
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="info-label">
                                File Type
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $mime
                                ) ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-label">
                                Uploaded At
                            </div>

                            <div class="info-value">

                                <?= !empty(
                                    $document["uploaded_at"]
                                )
                                    ? date(
                                        "d M Y H:i",
                                        strtotime(
                                            $document["uploaded_at"]
                                        )
                                    )
                                    : "Unknown"
                                ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>



            <!-- DOCUMENT PREVIEW -->

            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <h5 class="section-title">
                        👁️ Document Preview
                    </h5>


                    <div class="document-preview">


                        <?php if (
                            strpos($mime, "image/") === 0
                        ): ?>


                            <img
                                src="<?= htmlspecialchars($file_url) ?>"
                                alt="Uploaded document"
                            >


                        <?php elseif (
                            $mime === "application/pdf"
                        ): ?>


                            <iframe
                                src="<?= htmlspecialchars($file_url) ?>"
                                title="Document preview"
                            ></iframe>


                        <?php else: ?>


                            <div class="file-placeholder">

                                <div class="file-icon">
                                    📄
                                </div>

                                <h5>
                                    Preview not available
                                </h5>

                                <p class="text-muted">
                                    This file type cannot be previewed
                                    directly.
                                </p>

                                <a
                                    href="<?= htmlspecialchars($file_url) ?>"
                                    target="_blank"
                                    class="btn btn-primary"
                                >
                                    ↗ Open Document
                                </a>

                            </div>


                        <?php endif; ?>


                    </div>


                    <div class="text-center mt-3">

                        <a
                            href="<?= htmlspecialchars($file_url) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-primary"
                        >
                            ↗ Open Document in New Tab
                        </a>

                    </div>


                </div>

            </div>

        </div>



        <!-- RIGHT -->

        <div class="col-lg-4">


            <!-- APPLICANT -->

            <div class="card shadow-sm mb-4">

                <div class="card-body">

                    <h5 class="section-title">
                        👤 Applicant
                    </h5>


                    <div class="info-label">
                        Full Name
                    </div>

                    <div class="info-value">

                        <strong>
                            <?= htmlspecialchars(
                                $document["full_name"]
                                ?? "Not provided"
                            ) ?>
                        </strong>

                    </div>


                    <div class="info-label">
                        Email
                    </div>

                    <div class="info-value">

                        <?= htmlspecialchars(
                            $document["email"]
                            ?? "Not provided"
                        ) ?>

                    </div>


                    <div class="info-label">
                        National ID
                    </div>

                    <div class="info-value">

                        <?= htmlspecialchars(
                            $document["national_id"]
                            ?? "Not provided"
                        ) ?>

                    </div>


                    <div class="info-label">
                        Application Number
                    </div>

                    <div class="info-value">

                        <a
                            href="view_application.php?id=<?= (int)$document["application_id"] ?>"
                        >

                            <?= htmlspecialchars(
                                $document["application_number"]
                                ?? "Not available"
                            ) ?>

                        </a>

                    </div>


                    <div class="info-label">
                        Application Status
                    </div>

                    <div class="info-value">

                        <?= htmlspecialchars(
                            ucfirst(
                                str_replace(
                                    "_",
                                    " ",
                                    $document["application_status"]
                                    ?? ""
                                )
                            )
                        ) ?>

                    </div>

                </div>

            </div>



            <!-- VERIFICATION ACTION -->

            <div class="card shadow-sm action-card">

                <div class="card-body">

                    <h5 class="section-title">
                        ⚙️ Verification Action
                    </h5>


                    <div class="mb-4">

                        <div class="info-label">
                            Current Status
                        </div>

                        <div class="mt-2">

                            <?= verificationBadge(
                                $document["verification_status"]
                            ) ?>

                        </div>

                    </div>


                    <?php if (
                        !empty(
                            $document["verification_remarks"]
                        )
                    ): ?>

                        <div class="mb-4">

                            <div class="info-label">
                                Existing Remarks
                            </div>

                            <div class="remarks-box mt-2">

                                <?= nl2br(
                                    htmlspecialchars(
                                        $document[
                                            "verification_remarks"
                                        ]
                                    )
                                ) ?>

                            </div>

                        </div>

                    <?php endif; ?>



                    <!-- VERIFY -->

                    <?php if (
                        $document["verification_status"]
                        !== "verified"
                    ): ?>

                        <form
                            method="POST"
                            action="document_action.php"
                        >

                            <input
                                type="hidden"
                                name="document_id"
                                value="<?= (int)$document["id"] ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="verify"
                            >


                            <div class="mb-3">

                                <label class="form-label">
                                    Verification Remarks
                                </label>

                                <textarea
                                    name="remarks"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Optional remarks..."
                                ></textarea>

                            </div>


                            <button
                                type="submit"
                                class="btn btn-success w-100"
                                onclick="return confirm('Are you sure you want to verify this document?');"
                            >
                                ✓ Verify Document
                            </button>

                        </form>

                    <?php endif; ?>



                    <!-- REJECT -->

                    <?php if (
                        $document["verification_status"]
                        !== "rejected"
                    ): ?>

                        <hr class="my-4">


                        <form
                            method="POST"
                            action="document_action.php"
                        >

                            <input
                                type="hidden"
                                name="document_id"
                                value="<?= (int)$document["id"] ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="reject"
                            >


                            <div class="mb-3">

                                <label class="form-label">
                                    Reason for Rejection
                                </label>

                                <textarea
                                    name="remarks"
                                    class="form-control"
                                    rows="4"
                                    required
                                    placeholder="Explain why this document is being rejected..."
                                ></textarea>

                            </div>


                            <button
                                type="submit"
                                class="btn btn-danger w-100"
                                onclick="return confirm('Are you sure you want to reject this document?');"
                            >
                                ✕ Reject Document
                            </button>

                        </form>

                    <?php endif; ?>



                    <?php if (
                        $document["verification_status"]
                        === "verified"
                    ): ?>

                        <div class="alert alert-success mb-0">

                            ✓ This document has been verified.

                        </div>

                    <?php endif; ?>


                    <?php if (
                        $document["verification_status"]
                        === "rejected"
                    ): ?>

                        <div class="alert alert-danger mb-0">

                            ✕ This document has been rejected.

                        </div>

                    <?php endif; ?>


                </div>

            </div>

        </div>

    </div>

</div>


</body>

</html>