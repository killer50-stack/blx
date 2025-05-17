<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Verificar se foi fornecido um ID
$fileId = isset($_GET['id']) ? $_GET['id'] : null;
$videoUrl = null;
$videoTitle = "Vídeo";

if ($fileId) {
    try {
        // Conectar ao banco de dados
        $database = new Database();
        $db = $database->getConnection();
        
        // Obter informações do vídeo
        $stmt = $db->prepare('SELECT * FROM files WHERE id = :id AND file_type LIKE "video/%"');
        $stmt->bindParam(':id', $fileId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $video = $stmt->fetch(PDO::FETCH_ASSOC);
            $videoUrl = 'uploads/files/' . $video['filename'];
            $videoTitle = $video['original_filename'];
        }
    } catch (Exception $e) {
        $error = "Erro ao carregar vídeo: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizador Avançado de Vídeo - <?php echo SITE_NAME; ?></title>
    
    <!-- Estilos básicos -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <!-- Plyr CSS -->
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .video-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .player-wrapper {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #6D4C41;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px;
        }
        
        .btn:hover {
            background-color: #5D4037;
        }
        
        .btn-secondary {
            background-color: #8D6E63;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #d32f2f;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alternative-players {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        
        .tip-box {
            background-color: #e8f5e9;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Vídeo de fundo -->
    <video autoplay muted loop class="background-video">
        <source src="https://v1.pinimg.com/videos/mc/720p/2f/6b/d5/2f6bd5029e2b3bb71438747562c33bd8.mp4" type="video/mp4">
    </video>
    <div class="background-overlay"></div>
    
    <header>
        <h1><?php echo SITE_NAME; ?></h1>
    </header>

    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="index.php" class="btn">
                <i class="fas fa-home"></i> Página Inicial
            </a>
            <a href="views/dashboard.php" class="btn">
                <i class="fas fa-th"></i> Meus Arquivos
            </a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($videoUrl && file_exists($videoUrl)): ?>
            <div class="video-container">
                <h2><?php echo htmlspecialchars($videoTitle); ?></h2>
                
                <div class="player-wrapper">
                    <!-- Player principal (Plyr) -->
                    <video id="player" playsinline controls data-poster="">
                        <source src="<?php echo $videoUrl; ?>" type="video/mp4" />
                    </video>
                </div>
                
                <div style="text-align: center; margin-top: 15px;">
                    <a href="<?php echo $videoUrl; ?>" download class="btn btn-secondary">
                        <i class="fas fa-download"></i> Baixar Vídeo
                    </a>
                </div>
            </div>
            
            <div class="alternative-players">
                <h3>Opções alternativas de visualização</h3>
                <p>Se o player acima não funcionar, tente uma destas opções:</p>
                
                <!-- Player HTML5 padrão -->
                <h4>1. Player HTML5 Padrão</h4>
                <video controls width="100%" height="auto">
                    <source src="<?php echo $videoUrl; ?>" type="video/mp4">
                    Seu navegador não suporta a reprodução deste vídeo.
                </video>
                
                <!-- Link direto -->
                <h4>2. Link direto para o vídeo</h4>
                <p><a href="<?php echo $videoUrl; ?>" target="_blank">Abrir vídeo em nova aba</a></p>
            </div>
            
            <div class="tip-box">
                <h3><i class="fas fa-lightbulb"></i> Dicas para visualização de vídeos</h3>
                <ul>
                    <li>Certifique-se de que seu navegador está atualizado</li>
                    <li>Alguns vídeos podem não funcionar devido a codecs incompatíveis</li>
                    <li>Se encontrar problemas, tente baixar o vídeo e assisti-lo localmente</li>
                    <li>Navegadores recomendados: Chrome, Firefox ou Edge nas versões mais recentes</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="video-container" style="text-align: center;">
                <h2>Vídeo não encontrado</h2>
                <p>O vídeo solicitado não foi encontrado ou não existe.</p>
                <p><a href="views/dashboard.php" class="btn">Ver meus arquivos</a></p>
            </div>
        <?php endif; ?>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Todos os direitos reservados</p>
    </footer>
    
    <!-- Scripts do Plyr -->
    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const player = new Plyr('#player', {
                controls: [
                    'play-large', 'play', 'progress', 'current-time', 'mute', 
                    'volume', 'captions', 'settings', 'pip', 'airplay', 'fullscreen'
                ],
                autoplay: false,
                muted: false
            });
        });
    </script>
</body>
</html> 