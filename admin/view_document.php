<!-- ==================================================
     UPLOADED DOCUMENTS
=================================================== -->

<div class="card shadow-sm border-0 mb-4">

    <div class="card-body">

        <h5 class="section-title">
            📎 Uploaded Documents
        </h5>

        <?php if (!empty($documents)): ?>

            <?php foreach ($documents as $document): ?>

                <div class="document-card">

                    <div class="row align-items-center">

                        <!-- DOCUMENT INFORMATION -->
                        <div class="col-md-5">

                            <strong>
                                <?= htmlspecialchars(
                                    $document["document_type"] ?? "Document"
                                ); ?>
                            </strong>

                            <br>

                            <small class="text-muted">
                                <?= htmlspecialchars(
                                    $document["original_name"] ?? "Unnamed file"
                                ); ?>
                            </small>

                        </div>


                        <!-- UPLOAD DATE -->
                        <div class="col-md-3">

                            <small class="text-muted">

                                Uploaded:

                                <?= !empty($document["uploaded_at"])
                                    ? htmlspecialchars($document["uploaded_at"])
                                    : "Unknown"; ?>

                            </small>

                        </div>


                        <!-- FILE SIZE -->
                        <div class="col-md-2">

                            <small>

                                <?php

                                $size = (int)($document["file_size"] ?? 0);

                                if ($size >= 1048576) {

                                    echo number_format(
                                        $size / 1048576,
                                        2
                                    ) . " MB";

                                } elseif ($size >= 1024) {

                                    echo number_format(
                                        $size / 1024,
                                        1
                                    ) . " KB";

                                } else {

                                    echo $size . " Bytes";

                                }

                                ?>

                            </small>

                        </div>


                        <!-- VIEW BUTTON -->
                        <div class="col-md-2 text-end">

                            <a
                                href="view_document.php?id=<?= (int)$document["id"]; ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="btn btn-sm btn-primary"
                            >
                                📄 View
                            </a>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="alert alert-warning mb-0">

                📂 No documents have been uploaded for this application.

            </div>

        <?php endif; ?>

    </div>

</div>