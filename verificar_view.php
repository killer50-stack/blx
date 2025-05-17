<?php
// Configurar tratamento de erros para exibir tudo
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Verificando visualização de arquivos</h1>";

// 1. Verificar estilos CSS
echo "<h2>Verificando CSS</h2>";
$cssFile = 'assets/css/style.css';
if (file_exists($cssFile)) {
    $css = file_get_contents($cssFile);
    
    // Verificar regras específicas
    $requiredRules = [
        '.viewer-container',
        '.video-container',
        '.video-container video',
        '.image-container',
        '.image-container img',
        '.pdf-container',
        '.pdf-container iframe'
    ];
    
    echo "<ul>";
    foreach ($requiredRules as $rule) {
        if (strpos($css, $rule) !== false) {
            echo "<li style='color:green'>Regra CSS encontrada: $rule</li>";
        } else {
            echo "<li style='color:red'>ERRO: Regra CSS não encontrada: $rule</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color:red'>ERRO: Arquivo CSS não encontrado: $cssFile</p>";
}

// 2. Verificar arquivos no banco de dados
echo "<h2>Verificando arquivos no banco de dados</h2>";
if (file_exists('config/config.php') && file_exists('config/database.php')) {
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare('SELECT * FROM files');
        $stmt->execute();
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($files) > 0) {
            echo "<p>Encontrados " . count($files) . " arquivos no banco de dados:</p>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Arquivo</th><th>Tipo</th><th>Caminho</th><th>Tamanho</th><th>Existe?</th></tr>";
            
            foreach ($files as $file) {
                $filePath = $file['file_path'];
                $fileExists = file_exists($filePath) ? "Sim" : "Não";
                $fileExistsColor = $fileExists == "Sim" ? "green" : "red";
                
                echo "<tr>";
                echo "<td>{$file['id']}</td>";
                echo "<td>{$file['original_filename']}</td>";
                echo "<td>{$file['file_type']}</td>";
                echo "<td>{$file['file_path']}</td>";
                echo "<td>" . formatSize($file['file_size']) . "</td>";
                echo "<td style='color:$fileExistsColor'>$fileExists</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p style='color:orange'>Nenhum arquivo encontrado no banco de dados</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red'>ERRO ao conectar ao banco de dados: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>ERRO: Arquivos de configuração não encontrados</p>";
}

// 3. Verificar caminhos de visualização
echo "<h2>Testando caminhos de visualização</h2>";
if (isset($files) && count($files) > 0) {
    $testFile = $files[0]; // Pegar o primeiro arquivo
    $viewUrl = "views/view.php?id=" . $testFile['id'];
    
    echo "<p>URL para teste: <a href='$viewUrl' target='_blank'>$viewUrl</a></p>";
    
    // Mostrar como o arquivo seria renderizado
    echo "<h3>Visualização do arquivo ID {$testFile['id']} - {$testFile['original_filename']}</h3>";
    
    $fileType = $testFile['file_type'];
    $filePath = $testFile['file_path'];
    
    if (strpos($fileType, 'image/') === 0) {
        echo "<div style='border:1px solid #ccc; padding:10px; max-width:800px; margin:0 auto;'>";
        echo "<img src='$filePath' style='max-width:100%; max-height:500px; display:block; margin:0 auto;' />";
        echo "</div>";
    } elseif (strpos($fileType, 'video/') === 0) {
        echo "<div style='border:1px solid #ccc; padding:10px; max-width:800px; margin:0 auto;'>";
        echo "<video controls style='width:100%; max-height:500px;'>";
        echo "<source src='$filePath' type='$fileType'>";
        echo "Seu navegador não suporta a reprodução deste vídeo.";
        echo "</video>";
        echo "</div>";
    } elseif ($fileType === 'application/pdf') {
        echo "<div style='border:1px solid #ccc; padding:10px; width:100%; height:500px; margin:0 auto;'>";
        echo "<iframe src='$filePath' style='width:100%; height:100%; border:none;'></iframe>";
        echo "</div>";
    } else {
        echo "<p>Tipo de arquivo não suportado para visualização</p>";
    }
}
?> 