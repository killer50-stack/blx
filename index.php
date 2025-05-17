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

// Get current folder ID
$current_folder_id = isset($_GET['folder']) ? intval($_GET['folder']) : 1;
$current_folder = getFolderById($current_folder_id);

// If folder doesn't exist, default to root
if (!$current_folder) {
    $current_folder_id = 1;
    $current_folder = getFolderById(1);
}

// Get folder path for breadcrumbs
$folder_path = getFolderPath($current_folder_id);

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .folder-item {
            border: 1px solid var(--secondary-color);
            border-radius: 8px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: #f9f6f3;
        }
        .folder-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .folder-icon {
            font-size: 36px;
            margin-bottom: 10px;
            text-align: center;
            color: var(--primary-color);
        }
        .folder-icon::before {
            content: "📁";
        }
        .folder-actions {
            display: flex;
            justify-content: space-between;
            gap: 5px;
            margin-top: 10px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background-color: var(--white);
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            max-width: 90%;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h3 {
            margin: 0;
        }
        .close {
            font-size: 24px;
            cursor: pointer;
        }
        .folder-select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid var(--secondary-color);
            border-radius: 4px;
        }
        .action-group {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .action-group button {
            margin-left: 10px;
        }
    </style>
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
            <!-- Breadcrumb navigation -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <?php foreach ($folder_path as $index => $folder): ?>
                        <?php if ($index == count($folder_path) - 1): ?>
                            <li class="breadcrumb-item active"><?= htmlspecialchars($folder['name']) ?></li>
                        <?php else: ?>
                            <li class="breadcrumb-item">
                                <a href="index.php?folder=<?= $folder['id'] ?>"><?= htmlspecialchars($folder['name']) ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>
            
            <div class="action-buttons" style="margin-bottom: 20px;">
                <button id="createFolderBtn" class="btn">Nova Pasta</button>
            </div>

            <section class="upload-section">
                <h2>Enviar Arquivo</h2>
                <form action="upload.php" method="post" enctype="multipart/form-data" id="upload-form">
                    <input type="hidden" name="folder_id" value="<?= $current_folder_id ?>">
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
                    // Get folders in current folder
                    $folders = getAllFolders($current_folder_id);
                    foreach ($folders as $folder): 
                    ?>
                        <div class="folder-item">
                            <a href="index.php?folder=<?= $folder['id'] ?>" style="text-decoration: none; color: inherit;">
                                <div class="folder-icon"></div>
                                <div class="file-info">
                                    <div class="file-name"><?= htmlspecialchars($folder['name']) ?></div>
                                </div>
                            </a>
                            <div class="folder-actions">
                                <button class="btn action-btn delete-folder-btn" data-id="<?= $folder['id'] ?>">Excluir</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php
                    // Get files in current folder
                    $files = getAllFiles($current_folder_id);
                    if (count($files) > 0 || count($folders) > 0) {
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
                                    <button class="btn action-btn move-file-btn" data-id="<?= $file['id'] ?>">Mover</button>
                                    <button class="btn action-btn delete-btn" data-id="<?= $file['id'] ?>">Excluir</button>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="no-files">Pasta vazia</div>';
                    }
                    ?>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> Arquivo Virtual - Todos os direitos reservados</p>
        </footer>
    </div>

    <!-- Create Folder Modal -->
    <div id="createFolderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nova Pasta</h3>
                <span class="close">&times;</span>
            </div>
            <form id="createFolderForm" action="folder.php" method="post">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="parent_id" value="<?= $current_folder_id ?>">
                <div>
                    <label for="folderName">Nome da Pasta:</label>
                    <input type="text" id="folderName" name="name" class="folder-select" required>
                </div>
                <div class="action-group">
                    <button type="button" class="btn" id="cancelCreateFolder">Cancelar</button>
                    <button type="submit" class="btn">Criar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Move File Modal -->
    <div id="moveFileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Mover Arquivo</h3>
                <span class="close">&times;</span>
            </div>
            <form id="moveFileForm" action="folder.php" method="post">
                <input type="hidden" name="action" value="move">
                <input type="hidden" name="file_id" id="moveFileId" value="">
                <div>
                    <label for="targetFolder">Pasta de Destino:</label>
                    <select id="targetFolder" name="folder_id" class="folder-select" required>
                        <?php
                        $all_folders = getAllFolders();
                        foreach ($all_folders as $folder) {
                            echo '<option value="' . $folder['id'] . '">' . htmlspecialchars($folder['name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="action-group">
                    <button type="button" class="btn" id="cancelMoveFile">Cancelar</button>
                    <button type="submit" class="btn">Mover</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Create Folder Modal
        const createFolderModal = document.getElementById('createFolderModal');
        const createFolderBtn = document.getElementById('createFolderBtn');
        const cancelCreateFolder = document.getElementById('cancelCreateFolder');
        
        createFolderBtn.addEventListener('click', function() {
            createFolderModal.style.display = 'block';
        });
        
        cancelCreateFolder.addEventListener('click', function() {
            createFolderModal.style.display = 'none';
        });
        
        // Move File Modal
        const moveFileModal = document.getElementById('moveFileModal');
        const moveFileBtns = document.querySelectorAll('.move-file-btn');
        const cancelMoveFile = document.getElementById('cancelMoveFile');
        const moveFileIdInput = document.getElementById('moveFileId');
        
        moveFileBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const fileId = this.getAttribute('data-id');
                moveFileIdInput.value = fileId;
                moveFileModal.style.display = 'block';
            });
        });
        
        cancelMoveFile.addEventListener('click', function() {
            moveFileModal.style.display = 'none';
        });
        
        // Close modals when clicking on X
        const closeButtons = document.querySelectorAll('.close');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.modal').style.display = 'none';
            });
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        });
        
        // Delete folder confirmation
        const deleteFolderBtns = document.querySelectorAll('.delete-folder-btn');
        deleteFolderBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const folderId = this.getAttribute('data-id');
                if (confirm('Tem certeza que deseja excluir esta pasta e todo o seu conteúdo?')) {
                    window.location.href = 'folder.php?action=delete&id=' + folderId;
                }
            });
        });
    </script>
</body>
</html> 