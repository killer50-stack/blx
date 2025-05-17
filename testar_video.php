<?php
// Script básico para testar a visualização de um vídeo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Caminho para o vídeo (altere para o nome do seu arquivo)
$videoFile = 'uploads/files/6828e09686fab_1747509398.mp4';

// Verificar se o arquivo existe
$fileExists = file_exists($videoFile);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Vídeo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .video-container {
            width: 100%;
            margin: 20px 0;
        }
        
        .video-container video {
            width: 100%;
            height: auto;
        }
        
        .debug-info {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Teste de Visualização de Vídeo</h1>
    
    <div class="debug-info">
        <h3>Informações de Depuração</h3>
        <p>Caminho do arquivo: <?php echo $videoFile; ?></p>
        <p>O arquivo existe? <?php echo $fileExists ? 'SIM' : 'NÃO'; ?></p>
        <p>Diretório atual: <?php echo getcwd(); ?></p>
        <?php if($fileExists): ?>
        <p>Tamanho do arquivo: <?php echo filesize($videoFile); ?> bytes</p>
        <p>Última modificação: <?php echo date('Y-m-d H:i:s', filemtime($videoFile)); ?></p>
        <?php endif; ?>
    </div>
    
    <?php if($fileExists): ?>
    <div class="video-container">
        <video controls>
            <source src="<?php echo $videoFile; ?>" type="video/mp4">
            Seu navegador não suporta a reprodução deste vídeo.
        </video>
    </div>
    <?php else: ?>
    <div style="background-color: #ffeeee; padding: 15px; border-radius: 5px;">
        <p><strong>Erro:</strong> O arquivo de vídeo não foi encontrado.</p>
    </div>
    <?php endif; ?>
    
    <p><a href="index.php">Voltar para a página inicial</a></p>
</body>
</html> 