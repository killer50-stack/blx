<?php
// Iniciar sessão
session_start();

// Incluir arquivos de configuração
require_once '../config/config.php';
require_once '../config/database.php';

// Verificar se ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$fileId = $_GET['id'];

// Instanciar banco de dados
$database = new Database();
$db = $database->getConnection();

// Obter informações do arquivo
try {
    $stmt = $db->prepare('SELECT * FROM files WHERE id = :id');
    $stmt->bindParam(':id', $fileId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header('Location: dashboard.php');
        exit();
    }
    
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    $filePath = '../' . $file['file_path'];
    $fileType = $file['file_type'];
    
} catch (PDOException $e) {
    $error = "Erro ao carregar arquivo: " . $e->getMessage();
    header('Location: dashboard.php');
    exit();
}

// Determinar o tipo de visualizador
$viewerType = '';
if (strpos($fileType, 'image/') === 0) {
    $viewerType = 'image';
} elseif (strpos($fileType, 'video/') === 0) {
    $viewerType = 'video';
} elseif ($fileType === 'application/pdf') {
    $viewerType = 'pdf';
} else {
    $viewerType = 'unsupported';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar <?php echo htmlspecialchars($file['original_filename']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <header>
        <h1><?php echo SITE_NAME; ?></h1>
    </header>
    
    <div class="container">
        <div class="viewer-header" style="margin-bottom: 1rem;">
            <a href="dashboard.php" class="btn" style="margin-right: 1rem;">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            <h2><?php echo htmlspecialchars($file['original_filename']); ?></h2>
            <div style="margin-top: 0.5rem;">
                <span class="file-meta" style="display: inline-block; margin-right: 1rem;">
                    Tamanho: <?php echo formatSize($file['file_size']); ?>
                </span>
                <span class="file-meta" style="display: inline-block;">
                    Enviado em: <?php echo date('d/m/Y H:i', strtotime($file['upload_date'])); ?>
                </span>
            </div>
        </div>
        
        <div class="viewer-container">
            <?php if ($viewerType === 'image'): ?>
                <div class="image-container">
                    <img src="<?php echo $filePath; ?>" alt="<?php echo htmlspecialchars($file['original_filename']); ?>">
                </div>
            <?php elseif ($viewerType === 'video'): ?>
                <div class="video-container">
                    <video controls>
                        <source src="<?php echo $filePath; ?>" type="<?php echo $fileType; ?>">
                        Seu navegador não suporta a reprodução deste vídeo.
                    </video>
                </div>
            <?php elseif ($viewerType === 'pdf'): ?>
                <div class="pdf-container">
                    <iframe src="<?php echo $filePath; ?>" frameborder="0"></iframe>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-file" style="font-size: 4rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <p>Este tipo de arquivo não pode ser visualizado diretamente no navegador.</p>
                    <a href="<?php echo $filePath; ?>" download class="btn" style="margin-top: 1rem;">
                        <i class="fas fa-download"></i> Baixar Arquivo
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="file-actions" style="margin-top: 1rem;">
            <a href="<?php echo $filePath; ?>" download class="btn btn-secondary">
                <i class="fas fa-download"></i> Baixar
            </a>
            <button class="btn btn-danger delete-file" data-id="<?php echo $file['id']; ?>">
                <i class="fas fa-trash"></i> Excluir
            </button>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Todos os direitos reservados</p>
    </footer>
    
    <script src="../assets/js/main.js"></script>
</body>
</html> 