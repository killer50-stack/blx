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
    ini_set('error_log', __DIR__ . '/upload_error.log');

    // Incluir arquivos de configuração
    require_once 'config/config.php';
    require_once 'config/database.php';

    // Definir cabeçalho para resposta JSON
    header('Content-Type: application/json');

    // Verificar se o arquivo foi enviado
    if (!isset($_FILES['file']) || $_FILES['file']['error'] != UPLOAD_ERR_OK) {
        $errorMessage = "Erro no upload: ";
        
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage .= "O arquivo excede o tamanho máximo permitido.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage .= "O upload do arquivo foi feito parcialmente.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMessage .= "Nenhum arquivo foi enviado.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMessage .= "Pasta temporária ausente.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMessage .= "Falha ao gravar arquivo em disco.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $errorMessage .= "Uma extensão PHP interrompeu o upload do arquivo.";
                break;
            default:
                $errorMessage .= "Erro desconhecido.";
                break;
        }
        
        error_log("Erro no upload: " . $errorMessage);
        throw new Exception($errorMessage);
    }

    // Verificar tipo de arquivo
    $file = $_FILES['file'];
    $fileType = $file['type'];

    error_log("Arquivo recebido: " . $file['name'] . ", tipo: " . $fileType . ", tamanho: " . $file['size']);

    if (!isValidFileType($fileType)) {
        error_log("Tipo de arquivo não permitido: " . $fileType);
        throw new Exception('Tipo de arquivo não permitido. Use apenas imagens, vídeos ou PDFs.');
    }

    // Verificar tamanho do arquivo (limite de 29 GB)
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        error_log("Arquivo excede tamanho máximo: " . $file['size']);
        throw new Exception('O arquivo excede o tamanho máximo permitido (29 GB).');
    }

    // Conectar ao banco de dados
    error_log("Tentando conectar ao banco de dados");
    $database = new Database();
    $db = $database->getConnection();
    error_log("Conexão com banco de dados estabelecida");

    // Verificar espaço de armazenamento disponível
    error_log("Verificando espaço de armazenamento");
    $stmt = $db->prepare('SELECT total_size, max_size FROM storage_stats WHERE id = 1');
    $stmt->execute();
    $storageStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalSize = $storageStats['total_size'] ?? 0;
    $maxSize = $storageStats['max_size'] ?? MAX_STORAGE_SIZE;
    
    error_log("Espaço utilizado: " . $totalSize . " de " . $maxSize);
    
    // Verificar se há espaço suficiente
    if ($totalSize + $file['size'] > $maxSize) {
        error_log("Espaço insuficiente");
        throw new Exception('Espaço de armazenamento insuficiente. Exclua alguns arquivos antes de enviar novos.');
    }

    // Processar upload do arquivo
    // Garantir que o diretório de uploads existe
    if (!file_exists(UPLOADS_DIR)) {
        error_log("Criando diretório: " . UPLOADS_DIR);
        if (!mkdir(UPLOADS_DIR, 0777, true)) {
            error_log("Falha ao criar diretório: " . UPLOADS_DIR);
            throw new Exception("Não foi possível criar o diretório de uploads");
        }
    }
    
    // Gerar nome de arquivo único
    $originalFilename = $file['name'];
    $fileExtension = pathinfo($originalFilename, PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $fileExtension;
    $filePath = 'uploads/files/' . $filename;
    $fullPath = UPLOADS_DIR . $filename;
    
    error_log("Tentando mover arquivo para: " . $fullPath);
    
    // Mover arquivo para diretório final
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        error_log("Falha ao mover arquivo. Permissões do diretório: " . substr(sprintf('%o', fileperms(UPLOADS_DIR)), -4));
        throw new Exception('Erro ao mover arquivo para o diretório final.');
    }
    
    error_log("Arquivo movido com sucesso para: " . $fullPath);
    
    // Salvar informações no banco de dados
    error_log("Inserindo informações no banco de dados");
    
    // Verificar se a tabela files existe
    try {
        $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='files'");
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            error_log("Tabela 'files' não encontrada, recriando...");
            $db->exec('
                CREATE TABLE IF NOT EXISTS files (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    filename TEXT NOT NULL,
                    original_filename TEXT NOT NULL,
                    file_type TEXT NOT NULL,
                    file_size INTEGER NOT NULL,
                    file_path TEXT NOT NULL,
                    upload_date DATETIME NOT NULL
                )
            ');
        }
    } catch (PDOException $e) {
        error_log("Erro ao verificar tabela: " . $e->getMessage());
        throw $e;
    }
    
    $stmt = $db->prepare('
        INSERT INTO files (filename, original_filename, file_type, file_size, file_path, upload_date)
        VALUES (:filename, :original_filename, :file_type, :file_size, :file_path, datetime("now"))
    ');
    
    $stmt->bindParam(':filename', $filename);
    $stmt->bindParam(':original_filename', $originalFilename);
    $stmt->bindParam(':file_type', $fileType);
    $stmt->bindParam(':file_size', $file['size']);
    $stmt->bindParam(':file_path', $filePath);
    
    $stmt->execute();
    error_log("Informações do arquivo inseridas no banco de dados");
    
    // Atualizar estatísticas de armazenamento
    $stmt = $db->prepare('
        UPDATE storage_stats 
        SET total_size = total_size + :file_size, last_update = datetime("now")
        WHERE id = 1
    ');
    
    $stmt->bindParam(':file_size', $file['size']);
    $stmt->execute();
    error_log("Estatísticas de armazenamento atualizadas");
    
    // Responder sucesso
    $responseData = [
        'success' => true,
        'message' => 'Arquivo enviado com sucesso.',
        'file_id' => $db->lastInsertId(),
        'file_path' => $filePath
    ];
    error_log("Enviando resposta de sucesso: " . json_encode($responseData));
    
    // Limpar buffer de saída e enviar resposta JSON
    ob_clean();
    echo json_encode($responseData);
    
} catch (Exception $e) {
    error_log("Exceção: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Remover arquivo se ocorrer erro no banco de dados
    if (isset($fullPath) && file_exists($fullPath)) {
        error_log("Removendo arquivo devido a erro: " . $fullPath);
        @unlink($fullPath);
    }
    
    // Limpar buffer de saída e enviar resposta JSON
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar upload: ' . $e->getMessage()
    ]);
}

// Garantir que nenhuma outra saída seja enviada
exit(); 