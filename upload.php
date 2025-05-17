<?php
// Iniciar sessão
session_start();

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
    
    echo json_encode([
        'success' => false,
        'message' => $errorMessage
    ]);
    exit();
}

// Verificar tipo de arquivo
$file = $_FILES['file'];
$fileType = $file['type'];

if (!isValidFileType($fileType)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tipo de arquivo não permitido. Use apenas imagens, vídeos ou PDFs.'
    ]);
    exit();
}

// Verificar tamanho do arquivo (limite de 29 GB)
if ($file['size'] > MAX_UPLOAD_SIZE) {
    echo json_encode([
        'success' => false,
        'message' => 'O arquivo excede o tamanho máximo permitido (29 GB).'
    ]);
    exit();
}

// Conectar ao banco de dados
$database = new Database();
$db = $database->getConnection();

// Verificar espaço de armazenamento disponível
try {
    $stmt = $db->prepare('SELECT total_size, max_size FROM storage_stats WHERE id = 1');
    $stmt->execute();
    $storageStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalSize = $storageStats['total_size'] ?? 0;
    $maxSize = $storageStats['max_size'] ?? MAX_STORAGE_SIZE;
    
    // Verificar se há espaço suficiente
    if ($totalSize + $file['size'] > $maxSize) {
        echo json_encode([
            'success' => false,
            'message' => 'Espaço de armazenamento insuficiente. Exclua alguns arquivos antes de enviar novos.'
        ]);
        exit();
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao verificar espaço de armazenamento: ' . $e->getMessage()
    ]);
    exit();
}

// Processar upload do arquivo
try {
    // Garantir que o diretório de uploads existe
    if (!file_exists(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0777, true);
    }
    
    // Gerar nome de arquivo único
    $originalFilename = $file['name'];
    $fileExtension = pathinfo($originalFilename, PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $fileExtension;
    $filePath = 'uploads/files/' . $filename;
    $fullPath = UPLOADS_DIR . $filename;
    
    // Mover arquivo para diretório final
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao mover arquivo para o diretório final.'
        ]);
        exit();
    }
    
    // Salvar informações no banco de dados
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
    
    // Atualizar estatísticas de armazenamento
    $stmt = $db->prepare('
        UPDATE storage_stats 
        SET total_size = total_size + :file_size, last_update = datetime("now")
        WHERE id = 1
    ');
    
    $stmt->bindParam(':file_size', $file['size']);
    $stmt->execute();
    
    // Responder sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Arquivo enviado com sucesso.',
        'file_id' => $db->lastInsertId(),
        'file_path' => $filePath
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar upload: ' . $e->getMessage()
    ]);
    
    // Remover arquivo se ocorrer erro no banco de dados
    if (isset($fullPath) && file_exists($fullPath)) {
        @unlink($fullPath);
    }
    
    exit();
} 