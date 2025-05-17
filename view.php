<?php
// Initialize the session and database connection
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Check if file ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

// Get file information
$fileId = $_GET['id'];
$file = getFileById($fileId);

// If file not found, redirect to index
if (!$file) {
    $_SESSION['error'] = "Arquivo não encontrado.";
    header('Location: index.php');
    exit;
}

// Get folder information
$folder_id = $file['folder_id'] ? $file['folder_id'] : 1;
$folder = getFolderById($folder_id);
$folder_path = getFolderPath($folder_id);

// Get file extension to determine display method
$extension = pathinfo($file['filename'], PATHINFO_EXTENSION);
$extension = strtolower($extension);

// Get file path
$filePath = 'uploads/' . $file['filepath'];

// Check if file exists
if (!file_exists($filePath)) {
    $_SESSION['error'] = "Arquivo não encontrado no servidor.";
    header('Location: index.php');
    exit;
}

// Determine file type
$isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp']);
$isVideo = in_array($extension, ['mp4', 'webm', 'ogg', 'mov']);
$isPdf = ($extension === 'pdf');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizando: <?= htmlspecialchars($file['filename']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            padding: 10px 0;
            margin-bottom: 20px;
            list-style: none;
            background-color: transparent;
            border-radius: 4px;
        }
        .breadcrumb-item {
            display: flex;
            align-items: center;
        }
        .breadcrumb-item + .breadcrumb-item::before {
            display: inline-block;
            padding: 0 8px;
            color: var(--text-light);
            content: "/";
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Visualizando Arquivo</h1>
        </header>
        
        <main>
            <a href="index.php?folder=<?= $folder_id ?>" class="back-link">&laquo; Voltar para a pasta</a>
            
            <!-- Breadcrumb navigation -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <?php foreach ($folder_path as $index => $folder): ?>
                        <li class="breadcrumb-item">
                            <a href="index.php?folder=<?= $folder['id'] ?>"><?= htmlspecialchars($folder['name']) ?></a>
                        </li>
                    <?php endforeach; ?>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($file['filename']) ?></li>
                </ol>
            </nav>
            
            <section>
                <h2><?= htmlspecialchars($file['filename']) ?></h2>
                <div class="file-meta">
                    <p>Tamanho: <?= formatSize($file['filesize']) ?></p>
                    <p>Enviado em: <?= date('d/m/Y H:i', strtotime($file['uploaded_at'])) ?></p>
                </div>
                
                <div class="file-viewer">
                    <?php if ($isImage): ?>
                        <img src="<?= $filePath ?>" alt="<?= htmlspecialchars($file['filename']) ?>">
                    <?php elseif ($isVideo): ?>
                        <video controls>
                            <source src="<?= $filePath ?>" type="<?= $file['filetype'] ?>">
                            Seu navegador não suporta a reprodução deste vídeo.
                        </video>
                    <?php elseif ($isPdf): ?>
                        <div class="pdf-container">
                            <iframe src="<?= $filePath ?>"></iframe>
                        </div>
                    <?php else: ?>
                        <p>Este tipo de arquivo não pode ser visualizado diretamente no navegador.</p>
                        <a href="<?= $filePath ?>" class="btn" download>Baixar Arquivo</a>
                    <?php endif; ?>
                </div>
                
                <div class="file-actions">
                    <a href="<?= $filePath ?>" class="btn" download>Baixar Arquivo</a>
                    <a href="delete.php?id=<?= $fileId ?>" class="btn delete-btn" onclick="return confirm('Tem certeza que deseja excluir este arquivo?')">Excluir Arquivo</a>
                </div>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?= date('Y') ?> Arquivo Virtual - Todos os direitos reservados</p>
        </footer>
    </div>
</body>
</html> 