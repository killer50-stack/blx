<?php
// Initialize the session and database connection
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Create required directories if they don't exist
$directories = ['uploads', 'database'];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Get total used space
$totalUsedSpace = getTotalUsedSpace();
$maxStorage = 999 * 1024 * 1024 * 1024; // 999 GB in bytes
$remainingSpace = $maxStorage - $totalUsedSpace;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Storage</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Arquivo Virtual</h1>
            <div class="storage-info">
                <div class="storage-bar">
                    <div class="storage-used" style="width: <?= ($totalUsedSpace / $maxStorage) * 100 ?>%"></div>
                </div>
                <div class="storage-text">
                    <?= formatSize($totalUsedSpace) ?> / 999 GB usado
                </div>
            </div>
            <nav class="main-nav">
                <a href="git-info.php" class="btn">Informações Git</a>
            </nav>
        </header>

        <?php
        // Display success message
        if (isset($_SESSION['upload_success'])) {
            echo '<div class="alert alert-success">' . $_SESSION['upload_success'] . '</div>';
            unset($_SESSION['upload_success']);
        }
        
        // Display error message
        if (isset($_SESSION['upload_error'])) {
            echo '<div class="alert alert-error">' . $_SESSION['upload_error'] . '</div>';
            unset($_SESSION['upload_error']);
        }
        
        // Display general success message
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        
        // Display general error message
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-error">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        ?>

        <main>
            <section class="upload-section">
                <h2>Enviar Arquivo</h2>
                <form action="upload.php" method="post" enctype="multipart/form-data" id="upload-form">
                    <div class="file-input-container">
                        <input type="file" name="file" id="file" class="file-input" required>
                        <label for="file" class="file-label">Escolher arquivo</label>
                        <span id="file-name">Nenhum arquivo selecionado</span>
                    </div>
                    <button type="submit" class="btn upload-btn">Enviar Arquivo</button>
                </form>
                <div class="upload-limit-info">
                    <p>Limite por arquivo: 29 GB</p>
                    <p>Espaço disponível: <?= formatSize($remainingSpace) ?></p>
                </div>
            </section>

            <section class="files-section">
                <h2>Seus Arquivos</h2>
                <div class="files-container">
                    <?php
                    $files = getAllFiles();
                    if (count($files) > 0) {
                        foreach ($files as $file) {
                            $fileExtension = pathinfo($file['filename'], PATHINFO_EXTENSION);
                            $iconClass = getFileIconClass($fileExtension);
                            $fileUrl = 'uploads/' . $file['filepath'];
                            $viewUrl = 'view.php?id=' . $file['id'];
                            ?>
                            <div class="file-item">
                                <div class="file-icon <?= $iconClass ?>"></div>
                                <div class="file-info">
                                    <div class="file-name"><?= htmlspecialchars($file['filename']) ?></div>
                                    <div class="file-meta">
                                        <span class="file-size"><?= formatSize($file['filesize']) ?></span>
                                        <span class="file-date"><?= date('d/m/Y H:i', strtotime($file['uploaded_at'])) ?></span>
                                    </div>
                                </div>
                                <div class="file-actions">
                                    <a href="<?= $viewUrl ?>" class="btn action-btn view-btn">Visualizar</a>
                                    <a href="<?= $fileUrl ?>" class="btn action-btn download-btn" download>Baixar</a>
                                    <button class="btn action-btn delete-btn" data-id="<?= $file['id'] ?>">Excluir</button>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="no-files">Nenhum arquivo enviado</div>';
                    }
                    ?>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> Arquivo Virtual - Todos os direitos reservados</p>
        </footer>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html> 