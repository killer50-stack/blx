<?php
// Configurar tratamento de erros para exibir tudo
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir arquivos de configuração
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Corrigindo registros do banco de dados</h1>";

// Conectar ao banco de dados
try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p>Conexão com o banco de dados estabelecida</p>";
    
    // Verificar os arquivos existentes
    $stmt = $db->prepare('SELECT * FROM files');
    $stmt->execute();
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($files) > 0) {
        echo "<p>Encontrados " . count($files) . " arquivos no banco de dados.</p>";
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Arquivo</th><th>Caminho antigo</th><th>Caminho novo</th><th>Status</th></tr>";
        
        foreach ($files as $file) {
            $oldPath = $file['file_path'];
            
            // Verificar se o arquivo existe diretamente
            $fileExists = file_exists($oldPath);
            
            // Verificar se existe com caminho relativo
            if (!$fileExists && file_exists('uploads/files/' . $file['filename'])) {
                $newPath = 'uploads/files/' . $file['filename'];
                $fileExists = true;
            } elseif (!$fileExists && file_exists('uploads/files/' . basename($oldPath))) {
                $newPath = 'uploads/files/' . basename($oldPath);
                $fileExists = true;
            } else {
                $newPath = $oldPath; // Manter o mesmo caminho
            }
            
            // Se o caminho mudou, atualizar o banco de dados
            if ($oldPath != $newPath) {
                try {
                    $updateStmt = $db->prepare('UPDATE files SET file_path = :new_path WHERE id = :id');
                    $updateStmt->bindParam(':new_path', $newPath);
                    $updateStmt->bindParam(':id', $file['id']);
                    $updateStmt->execute();
                    $status = "Atualizado ✓";
                } catch (Exception $e) {
                    $status = "Erro: " . $e->getMessage();
                }
            } else {
                $status = ($fileExists ? "OK ✓" : "Arquivo não encontrado ✗");
            }
            
            echo "<tr>";
            echo "<td>{$file['id']}</td>";
            echo "<td>{$file['original_filename']}</td>";
            echo "<td>{$oldPath}</td>";
            echo "<td>{$newPath}</td>";
            echo "<td>{$status}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<p><b>Recomendação:</b> Após corrigir os caminhos, verifique o acesso aos arquivos.</p>";
        echo "<p><a href='index.php'>Voltar para a página inicial</a></p>";
        
    } else {
        echo "<p>Nenhum arquivo encontrado no banco de dados.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Erro: " . $e->getMessage() . "</p>";
    echo "<p>Trace:<br>" . nl2br($e->getTraceAsString()) . "</p>";
}
?> 