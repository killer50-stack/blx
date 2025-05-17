<?php
// Prevenir que qualquer saída seja enviada antes da resposta JSON
ob_start();

// Iniciar sessão
session_start();

// Configurar tratamento de erros para capturar todos os erros
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error = [
        'success' => false,
        'message' => "Erro PHP: $errstr em $errfile linha $errline"
    ];
    
    // Limpar qualquer saída anterior
    ob_clean();
    
    // Enviar cabeçalho e resposta JSON
    header('Content-Type: application/json');
    echo json_encode($error);
    exit();
}

// Definir manipulador de erro personalizado
set_error_handler("customErrorHandler", E_ALL);

// Capturar erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $errorMsg = [
            'success' => false,
            'message' => "Erro fatal: " . $error['message'] . " em " . $error['file'] . " linha " . $error['line']
        ];
        
        // Limpar qualquer saída anterior
        ob_clean();
        
        // Enviar cabeçalho e resposta JSON
        header('Content-Type: application/json');
        echo json_encode($errorMsg);
    }
});

try {
    // Habilitar exibição de erros para depuração
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // Registrar erros em um arquivo de log
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/delete_error.log');

    // Incluir arquivos de configuração
    require_once 'config/config.php';
    require_once 'config/database.php';

    // Definir cabeçalho para resposta JSON
    header('Content-Type: application/json');

    // Verificar se o ID do arquivo foi fornecido
    if (!isset($_POST['file_id']) || empty($_POST['file_id'])) {
        error_log("ID do arquivo não fornecido na requisição");
        throw new Exception('ID do arquivo não fornecido');
    }

    $fileId = $_POST['file_id'];
    error_log("Solicitação para excluir arquivo com ID: " . $fileId);

    // Conectar ao banco de dados
    error_log("Conectando ao banco de dados");
    $database = new Database();
    $db = $database->getConnection();
    error_log("Conexão com banco de dados estabelecida");

    // Obter informações do arquivo
    error_log("Buscando informações do arquivo ID: " . $fileId);
    $stmt = $db->prepare('SELECT * FROM files WHERE id = :id');
    $stmt->bindParam(':id', $fileId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        error_log("Arquivo não encontrado: " . $fileId);
        throw new Exception('Arquivo não encontrado');
    }
    
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    $filePath = __DIR__ . '/' . $file['file_path'];
    $fileSize = $file['file_size'];
    
    error_log("Arquivo encontrado: " . $file['original_filename'] . ", caminho: " . $filePath);
    
    // Excluir arquivo físico
    if (file_exists($filePath)) {
        error_log("Tentando excluir arquivo físico: " . $filePath);
        if (!unlink($filePath)) {
            error_log("Falha ao excluir arquivo físico. Permissões: " . substr(sprintf('%o', fileperms(dirname($filePath))), -4));
            throw new Exception('Erro ao excluir arquivo físico');
        }
        error_log("Arquivo físico excluído com sucesso");
    } else {
        error_log("Aviso: Arquivo físico não encontrado em: " . $filePath);
    }
    
    // Excluir registro do banco de dados
    error_log("Excluindo registro do banco de dados");
    $stmt = $db->prepare('DELETE FROM files WHERE id = :id');
    $stmt->bindParam(':id', $fileId);
    $stmt->execute();
    error_log("Registro excluído do banco de dados");
    
    // Atualizar estatísticas de armazenamento
    error_log("Atualizando estatísticas de armazenamento");
    $stmt = $db->prepare('
        UPDATE storage_stats 
        SET total_size = total_size - :file_size, last_update = datetime("now")
        WHERE id = 1
    ');
    
    $stmt->bindParam(':file_size', $fileSize);
    $stmt->execute();
    error_log("Estatísticas de armazenamento atualizadas");
    
    // Obter estatísticas atualizadas
    $stmt = $db->prepare('SELECT total_size, max_size FROM storage_stats WHERE id = 1');
    $stmt->execute();
    $storageStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalSize = $storageStats['total_size'] ?? 0;
    $maxSize = $storageStats['max_size'] ?? MAX_STORAGE_SIZE;
    $percentageUsed = ($totalSize / $maxSize) * 100;
    
    error_log("Novo percentual de uso: " . round($percentageUsed, 2) . "%");
    
    // Responder sucesso
    $responseData = [
        'success' => true,
        'message' => 'Arquivo excluído com sucesso',
        'storagePercentage' => round($percentageUsed, 2)
    ];
    error_log("Enviando resposta de sucesso: " . json_encode($responseData));
    
    // Limpar buffer de saída e enviar resposta JSON
    ob_clean();
    echo json_encode($responseData);
    
} catch (Exception $e) {
    error_log("Exceção: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Limpar buffer de saída e enviar resposta JSON
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao excluir arquivo: ' . $e->getMessage()
    ]);
}

// Garantir que nenhuma outra saída seja enviada
exit(); 