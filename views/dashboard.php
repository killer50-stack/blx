<?php
// Iniciar sessão
session_start();

// Incluir arquivos de configuração
require_once '../config/config.php';
require_once '../config/database.php';

// Instanciar banco de dados
$database = new Database();
$db = $database->getConnection();

// Obter estatísticas de armazenamento
try {
    $stmt = $db->prepare('SELECT total_size, max_size FROM storage_stats WHERE id = 1');
    $stmt->execute();
    $storageStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalSize = $storageStats['total_size'] ?? 0;
    $maxSize = $storageStats['max_size'] ?? MAX_STORAGE_SIZE;
    $percentageUsed = ($totalSize / $maxSize) * 100;
    
} catch (PDOException $e) {
    $totalSize = 0;
    $maxSize = MAX_STORAGE_SIZE;
    $percentageUsed = 0;
    $error = "Erro ao carregar estatísticas de armazenamento: " . $e->getMessage();
}

// Obter lista de arquivos
try {
    $stmt = $db->prepare('SELECT * FROM files ORDER BY upload_date DESC');
    $stmt->execute();
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $files = [];
    $error = "Erro ao carregar arquivos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Armazenamento de Arquivos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <!-- Vídeo de fundo -->
    <video autoplay muted loop class="background-video">
        <source src="https://v1.pinimg.com/videos/mc/720p/2f/6b/d5/2f6bd5029e2b3bb71438747562c33bd8.mp4" type="video/mp4">
        Seu navegador não suporta vídeos HTML5.
    </video>
    <div class="background-overlay"></div>
    
    <header>
        <h1><?php echo SITE_NAME; ?></h1>
    </header>
    
    <div class="container">
        <!-- Alertas -->
        <div class="alert-container">
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                    <span style="float: right; cursor: pointer;" onclick="this.parentElement.style.display='none'">&times;</span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Estatísticas de armazenamento -->
        <div class="storage-stats">
            <h3>Armazenamento</h3>
            <div class="storage-bar">
                <div class="storage-used" data-percentage="<?php echo round($percentageUsed, 2); ?>"></div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                <span>Usado: <?php echo formatSize($totalSize); ?></span>
                <span>Disponível: <?php echo formatSize($maxSize - $totalSize); ?></span>
                <span>Total: <?php echo formatSize($maxSize); ?></span>
            </div>
        </div>
        
        <!-- Formulário de upload -->
        <div class="upload-form">
            <h2>Fazer Upload de Arquivo</h2>
            <form id="upload-form" action="../upload.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="file" id="file-input" name="file" accept="image/*,video/*,application/pdf" required>
                </div>
                <div class="progress-bar" style="display: none;">
                    <div class="progress"></div>
                </div>
                <button type="submit" class="btn">Fazer Upload</button>
                <div class="loader"></div>
            </form>
        </div>
        
        <!-- Lista de arquivos -->
        <h2>Seus Arquivos</h2>
        <?php if (empty($files)): ?>
            <p>Nenhum arquivo enviado ainda.</p>
        <?php else: ?>
            <div class="files-container">
                <?php foreach ($files as $file): ?>
                    <div class="file-card">
                        <div class="file-preview">
                            <?php 
                            $fileType = $file['file_type'];
                            $filePath = '../' . $file['file_path'];
                            
                            if (strpos($fileType, 'image/') === 0): ?>
                                <img src="<?php echo $filePath; ?>" alt="<?php echo htmlspecialchars($file['original_filename']); ?>">
                            <?php elseif (strpos($fileType, 'video/') === 0): ?>
                                <i class="fas fa-film file-icon"></i>
                            <?php elseif ($fileType === 'application/pdf'): ?>
                                <i class="fas fa-file-pdf file-icon"></i>
                            <?php else: ?>
                                <i class="fas fa-file file-icon"></i>
                            <?php endif; ?>
                        </div>
                        <div class="file-info">
                            <div class="file-name" title="<?php echo htmlspecialchars($file['original_filename']); ?>">
                                <?php echo htmlspecialchars($file['original_filename']); ?>
                            </div>
                            <div class="file-meta">
                                <span><?php echo formatSize($file['file_size']); ?></span>
                                <span><?php echo date('d/m/Y H:i', strtotime($file['upload_date'])); ?></span>
                            </div>
                            <div class="file-actions">
                                <a href="view.php?id=<?php echo $file['id']; ?>" class="btn btn-secondary">Visualizar</a>
                                <button class="btn btn-danger delete-file" data-id="<?php echo $file['id']; ?>">Excluir</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Todos os direitos reservados</p>
    </footer>
    
    <script src="../assets/js/main.js"></script>
</body>
</html> 