<?php
// Adicionar cabeçalho de erro adequado
http_response_code(500);

// Verificar se a requisição espera uma resposta JSON
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Se for AJAX ou uma rota de API, retornar JSON
if ($isAjax || in_array(basename($_SERVER['SCRIPT_NAME']), ['upload.php', 'delete.php'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Ocorreu um erro interno no servidor'
    ]);
    exit();
}

// Se não for AJAX, mostrar uma página de erro HTML
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro - ArmazenamentoWeb</title>
    <style>
        :root {
            --primary-color: #6D4C41;
            --primary-dark: #5D4037;
            --primary-light: #8D6E63;
            --text-light: #FFFFFF;
            --background-light: #F5F5F5;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: var(--background-light);
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            flex-direction: column;
        }
        
        .error-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            text-align: center;
        }
        
        h1 {
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }
        
        p {
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .error-icon {
            font-size: 5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: var(--text-light);
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        /* Video background */
        .background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
            opacity: 0.7;
        }
        
        .background-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(105, 82, 72, 0.4);
            z-index: -1;
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

    <div class="error-container">
        <div class="error-icon">
            ⚠️
        </div>
        <h1>Ops! Ocorreu um erro</h1>
        <p>Desculpe, ocorreu um erro interno no servidor. Por favor, tente novamente mais tarde.</p>
        <a href="index.php" class="btn">Voltar para a página inicial</a>
    </div>
</body>
</html> 