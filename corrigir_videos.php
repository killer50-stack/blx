<?php
// Configurações básicas
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Ferramenta de Correção de Vídeos</h1>";

// Verificar diretório de uploads
$uploadsDir = 'uploads/files/';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
    echo "<p>Diretório de uploads criado: $uploadsDir</p>";
}

// Conectar ao banco de dados
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar arquivos de vídeo no banco
    $stmt = $db->prepare("SELECT * FROM files WHERE file_type LIKE 'video/%'");
    $stmt->execute();
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Vídeos no banco de dados: " . count($videos) . "</h2>";
    
    if (count($videos) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr>
                <th>ID</th>
                <th>Nome Original</th>
                <th>Arquivo</th>
                <th>Caminho DB</th>
                <th>Caminho Real</th>
                <th>Arquivo Existe?</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>";
        
        foreach ($videos as $video) {
            $id = $video['id'];
            $filename = $video['filename'];
            $originalFilename = $video['original_filename'];
            $dbPath = $video['file_path'];
            $fileType = $video['file_type'];
            
            // Verificar diferentes caminhos possíveis
            $path1 = $dbPath;
            $path2 = $uploadsDir . $filename;
            $path3 = 'uploads/files/' . basename($dbPath);
            
            $existsPath1 = file_exists($path1);
            $existsPath2 = file_exists($path2);
            $existsPath3 = file_exists($path3);
            
            $correctPath = null;
            if ($existsPath1) $correctPath = $path1;
            elseif ($existsPath2) $correctPath = $path2;
            elseif ($existsPath3) $correctPath = $path3;
            
            $fileExists = ($correctPath !== null);
            $status = $fileExists ? "OK" : "PROBLEMA";
            $statusColor = $fileExists ? "green" : "red";
            
            echo "<tr>";
            echo "<td>$id</td>";
            echo "<td>$originalFilename</td>";
            echo "<td>$filename</td>";
            echo "<td>$dbPath</td>";
            echo "<td>" . ($correctPath ?? "Não encontrado") . "</td>";
            echo "<td style='color: $statusColor'>" . ($fileExists ? "Sim" : "Não") . "</td>";
            echo "<td style='color: $statusColor'>$status</td>";
            echo "<td>";
            
            if ($fileExists && $correctPath != $dbPath) {
                echo "<a href='?action=update&id=$id&path=" . urlencode($correctPath) . "'>Corrigir Caminho</a>";
            } elseif ($fileExists) {
                echo "<a href='?action=test&id=$id'>Testar</a>";
            }
            
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Mostrar instruções para testar manualmente
        echo "<h3>Testar vídeo manualmente:</h3>";
        echo "<p>Se os vídeos continuam não sendo exibidos após as correções, execute o teste abaixo:</p>";
        
        if (isset($_GET['action']) && $_GET['action'] == 'test' && isset($_GET['id'])) {
            $testId = $_GET['id'];
            
            // Encontrar o vídeo pelo ID
            foreach ($videos as $video) {
                if ($video['id'] == $testId) {
                    $testVideo = $video;
                    break;
                }
            }
            
            if (isset($testVideo)) {
                $testPath = $testVideo['file_path'];
                if (!file_exists($testPath)) {
                    $testPath = $uploadsDir . $testVideo['filename'];
                }
                
                if (file_exists($testPath)) {
                    // Verificar tamanho do arquivo
                    $fileSize = filesize($testPath);
                    $fileSizeMB = round($fileSize / 1048576, 2); // Converter para MB
                    
                    echo "<div style='padding: 20px; background: #f8f8f8; border-radius: 5px; margin: 20px 0;'>";
                    echo "<h4>Testando vídeo: " . htmlspecialchars($testVideo['original_filename']) . "</h4>";
                    echo "<p>Caminho: $testPath</p>";
                    echo "<p>Tamanho: $fileSizeMB MB</p>";
                    echo "<p>Tipo: " . htmlspecialchars($testVideo['file_type']) . "</p>";
                    
                    echo "<div style='max-width: 600px; margin: 20px auto;'>";
                    echo "<video controls style='width: 100%;' preload='metadata'>";
                    echo "<source src='$testPath' type='" . htmlspecialchars($testVideo['file_type']) . "'>";
                    echo "Seu navegador não suporta a reprodução deste vídeo.";
                    echo "</video>";
                    echo "</div>";
                    
                    echo "<p><strong>Se o vídeo acima não é exibido:</strong></p>";
                    echo "<ol>";
                    echo "<li>Verifique se o vídeo está em um formato compatível com o navegador (MP4 com codec H.264)</li>";
                    echo "<li>Tente abrir diretamente: <a href='$testPath' target='_blank'>Link direto para o vídeo</a></li>";
                    echo "<li>Verifique se o MIME type do servidor está correto (deve ser video/mp4 para arquivos .mp4)</li>";
                    echo "</ol>";
                    echo "</div>";
                }
            }
        }
        
        // Atualizar o caminho do arquivo no banco de dados
        if (isset($_GET['action']) && $_GET['action'] == 'update' && isset($_GET['id']) && isset($_GET['path'])) {
            $updateId = $_GET['id'];
            $updatePath = urldecode($_GET['path']);
            
            if (file_exists($updatePath)) {
                try {
                    $updateStmt = $db->prepare("UPDATE files SET file_path = :path WHERE id = :id");
                    $updateStmt->bindParam(':path', $updatePath);
                    $updateStmt->bindParam(':id', $updateId);
                    $updateStmt->execute();
                    
                    echo "<div style='background-color: #e8f5e9; padding: 10px; border-radius: 5px; margin: 20px 0;'>";
                    echo "<p>Caminho atualizado com sucesso para o vídeo ID $updateId!</p>";
                    echo "<p>Novo caminho: $updatePath</p>";
                    echo "<p><a href='corrigir_videos.php'>Atualizar lista</a></p>";
                    echo "</div>";
                } catch (Exception $e) {
                    echo "<div style='background-color: #ffebee; padding: 10px; border-radius: 5px; margin: 20px 0;'>";
                    echo "<p>Erro ao atualizar o caminho: " . $e->getMessage() . "</p>";
                    echo "</div>";
                }
            }
        }
    } else {
        echo "<p>Nenhum vídeo encontrado no banco de dados.</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background-color: #ffebee; padding: 10px; border-radius: 5px;'>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
    echo "</div>";
}

// Criar um vídeo simples de amostra para testar o player
echo "<h2>Teste Universal de Vídeo</h2>";
echo "<p>Este teste usa um vídeo externo para verificar se o player HTML5 está funcionando corretamente:</p>";

echo "<div style='max-width: 600px; margin: 20px auto; padding: 20px; background: #f0f0f0; border-radius: 5px;'>";
echo "<video controls style='width: 100%;'>";
echo "<source src='https://v1.pinimg.com/videos/mc/720p/2f/6b/d5/2f6bd5029e2b3bb71438747562c33bd8.mp4' type='video/mp4'>";
echo "Seu navegador não suporta a reprodução deste vídeo.";
echo "</video>";
echo "</div>";

// Adicionar link para voltar
echo "<p><a href='index.php'>Voltar para a página inicial</a></p>";
?> 