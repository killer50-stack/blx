<?php
// Iniciar sessão
session_start();

// Incluir arquivos de configuração
require_once 'config/config.php';
require_once 'config/database.php';

// Definir cabeçalho para resposta JSON
header('Content-Type: application/json');

// Verificar se o ID do arquivo foi fornecido
if (!isset($_POST['file_id']) || empty($_POST['file_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID do arquivo não fornecido'
    ]);
    exit();
}

$fileId = $_POST['file_id'];

// Conectar ao banco de dados
$database = new Database();
$db = $database->getConnection();

try {
    // Obter informações do arquivo
    $stmt = $db->prepare('SELECT * FROM files WHERE id = :id');
    $stmt->bindParam(':id', $fileId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Arquivo não encontrado'
        ]);
        exit();
    }
    
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    $filePath = __DIR__ . '/' . $file['file_path'];
    $fileSize = $file['file_size'];
    
    // Excluir arquivo físico
    if (file_exists($filePath)) {
        if (!unlink($filePath)) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao excluir arquivo físico'
            ]);
            exit();
        }
    }
    
    // Excluir registro do banco de dados
    $stmt = $db->prepare('DELETE FROM files WHERE id = :id');
    $stmt->bindParam(':id', $fileId);
    $stmt->execute();
    
    // Atualizar estatísticas de armazenamento
    $stmt = $db->prepare('
        UPDATE storage_stats 
        SET total_size = total_size - :file_size, last_update = datetime("now")
        WHERE id = 1
    ');
    
    $stmt->bindParam(':file_size', $fileSize);
    $stmt->execute();
    
    // Obter estatísticas atualizadas
    $stmt = $db->prepare('SELECT total_size, max_size FROM storage_stats WHERE id = 1');
    $stmt->execute();
    $storageStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalSize = $storageStats['total_size'] ?? 0;
    $maxSize = $storageStats['max_size'] ?? MAX_STORAGE_SIZE;
    $percentageUsed = ($totalSize / $maxSize) * 100;
    
    // Responder sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Arquivo excluído com sucesso',
        'storagePercentage' => round($percentageUsed, 2)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao excluir arquivo: ' . $e->getMessage()
    ]);
    exit();
} 