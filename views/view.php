<?php
// Configurações básicas
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../view_error.log');

// Iniciar sessão
session_start();

// Incluir arquivos de configuração
require_once '../config/config.php';
require_once '../config/database.php';

try {
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
    $stmt = $db->prepare('SELECT * FROM files WHERE id = :id');
    $stmt->bindParam(':id', $fileId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header('Location: dashboard.php');
        exit();
    }
    
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Simplificar o tratamento de caminho do arquivo
    $fileName = $file['filename'];
    $filePath = '../uploads/files/' . $fileName;
    $fileType = $file['file_type'];
    $fileExists = file_exists($filePath);
    
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
    
} catch (Exception $e) {
    // Registrar erro e redirecionar
    error_log("Erro na página de visualização: " . $e->getMessage());
    header('Location: dashboard.php?error=db');
    exit();
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
    <style>
        /* Estilos embutidos básicos */
        .viewer-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            margin-bottom: 2rem;
            min-height: 300px;
        }
        
        .video-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .video-container video {
            width: 100%;
            height: auto;
        }
        
        .image-container {
            text-align: center;
        }
        
        .image-container img {
            max-width: 100%;
            max-height: 80vh;
            display: block;
            margin: 0 auto;
        }
        
        .pdf-container {
            width: 100%;
            height: 80vh;
        }
        
        .pdf-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
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
        
        <?php if (!$fileExists): ?>
            <div class="alert alert-error">
                <strong>Erro:</strong> O arquivo não foi encontrado no servidor.
                <p>Tente fazer upload novamente.</p>
            </div>
        <?php endif; ?>
        
        <div class="viewer-container">
            <?php if ($viewerType === 'image' && $fileExists): ?>
                <div class="image-container">
                    <img src="<?php echo $filePath; ?>" alt="<?php echo htmlspecialchars($file['original_filename']); ?>">
                </div>
            <?php elseif ($viewerType === 'video' && $fileExists): ?>
                <div class="video-container">
                    <video controls>
                        <source src="<?php echo $filePath; ?>" type="<?php echo $fileType; ?>">
                        Seu navegador não suporta a reprodução deste vídeo.
                    </video>
                </div>
            <?php elseif ($viewerType === 'pdf' && $fileExists): ?>
                <div class="pdf-container">
                    <iframe src="<?php echo $filePath; ?>" frameborder="0"></iframe>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-file" style="font-size: 4rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <p><?php echo $fileExists ? 'Este tipo de arquivo não pode ser visualizado diretamente no navegador.' : 'O arquivo não foi encontrado.'; ?></p>
                    <?php if ($fileExists): ?>
                    <a href="<?php echo $filePath; ?>" download class="btn" style="margin-top: 1rem;">
                        <i class="fas fa-download"></i> Baixar Arquivo
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($fileExists): ?>
        <div class="file-actions" style="margin-top: 1rem;">
            <a href="<?php echo $filePath; ?>" download class="btn btn-secondary">
                <i class="fas fa-download"></i> Baixar
            </a>
            <button class="btn btn-danger delete-file" data-id="<?php echo $file['id']; ?>">
                <i class="fas fa-trash"></i> Excluir
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Todos os direitos reservados</p>
    </footer>
    
    <script src="../assets/js/main.js"></script>
</body>
</html> 