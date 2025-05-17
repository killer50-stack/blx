<?php
// Habilitar exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/config.php';
require_once 'config/database.php';

// Processar upload se o formulário foi enviado
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    // Tentar upload
    try {
        $file = $_FILES['file'];
        
        // Validar arquivo
        if ($file['error'] != UPLOAD_ERR_OK) {
            throw new Exception("Erro no upload: código " . $file['error']);
        }
        
        if (!isValidFileType($file['type'])) {
            throw new Exception("Tipo de arquivo não permitido: " . $file['type']);
        }
        
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            throw new Exception("Tamanho excede o limite de " . formatSize(MAX_UPLOAD_SIZE));
        }
        
        // Verificar/criar diretório
        if (!file_exists(UPLOADS_DIR)) {
            if (!mkdir(UPLOADS_DIR, 0777, true)) {
                throw new Exception("Falha ao criar diretório: " . UPLOADS_DIR);
            }
        }
        
        // Gerar nome de arquivo
        $originalFilename = $file['name'];
        $fileExtension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = 'uploads/files/' . $filename;
        $fullPath = UPLOADS_DIR . $filename;
        
        // Mover arquivo
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new Exception("Falha ao mover arquivo. Permissões: " . substr(sprintf('%o', fileperms(UPLOADS_DIR)), -4));
        }
        
        // Conectar ao banco
        $database = new Database();
        $db = $database->getConnection();
        
        // Inserir no banco
        $stmt = $db->prepare('
            INSERT INTO files (filename, original_filename, file_type, file_size, file_path, upload_date)
            VALUES (:filename, :original_filename, :file_type, :file_size, :file_path, datetime("now"))
        ');
        
        $stmt->bindParam(':filename', $filename);
        $stmt->bindParam(':original_filename', $originalFilename);
        $stmt->bindParam(':file_type', $file['type']);
        $stmt->bindParam(':file_size', $file['size']);
        $stmt->bindParam(':file_path', $filePath);
        
        $stmt->execute();
        
        // Atualizar estatísticas
        $stmt = $db->prepare('
            UPDATE storage_stats 
            SET total_size = total_size + :file_size, last_update = datetime("now")
            WHERE id = 1
        ');
        
        $stmt->bindParam(':file_size', $file['size']);
        $stmt->execute();
        
        $success = true;
        $message = "Arquivo enviado com sucesso!";
        
    } catch (Exception $e) {
        $message = "Erro: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Upload</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #6D4C41; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background-color: #E8F5E9; color: #4CAF50; border: 1px solid #4CAF50; }
        .error { background-color: #FFEBEE; color: #F44336; border: 1px solid #F44336; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        button { background-color: #6D4C41; color: white; border: none; padding: 10px 15px; 
                border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #5D4037; }
        .info { background-color: #E3F2FD; padding: 10px; border-radius: 4px; margin: 20px 0; }
        code { background-color: #ECEFF1; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Teste de Upload Direto</h1>
    
    <?php if ($message): ?>
        <div class="message <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="info">
        <p><strong>Diretório de upload:</strong> <code><?php echo UPLOADS_DIR; ?></code></p>
        <p><strong>Existe?</strong> <code><?php echo file_exists(UPLOADS_DIR) ? 'Sim' : 'Não'; ?></code></p>
        <p><strong>Permissões:</strong> <code><?php echo file_exists(UPLOADS_DIR) ? substr(sprintf('%o', fileperms(UPLOADS_DIR)), -4) : '-'; ?></code></p>
        <p><strong>Tipos permitidos:</strong> <code><?php echo implode(', ', ALLOWED_TYPES); ?></code></p>
        <p><strong>Tamanho máximo:</strong> <code><?php echo formatSize(MAX_UPLOAD_SIZE); ?></code></p>
    </div>
    
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="file">Selecione um arquivo:</label>
            <input type="file" name="file" id="file" required>
        </div>
        
        <button type="submit">Enviar Arquivo</button>
    </form>
    
    <div class="info">
        <h3>Últimos 5 arquivos no banco:</h3>
        <?php
        try {
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->query("SELECT * FROM files ORDER BY id DESC LIMIT 5");
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($files) > 0) {
                echo "<ul>";
                foreach ($files as $file) {
                    echo "<li>";
                    echo "<strong>" . htmlspecialchars($file['original_filename']) . "</strong> ";
                    echo "(" . formatSize($file['file_size']) . ") ";
                    echo "[" . htmlspecialchars($file['file_type']) . "] ";
                    echo "em " . $file['upload_date'];
                    
                    $filePath = $file['file_path'];
                    $fullPath = __DIR__ . '/' . $filePath;
                    echo " - Arquivo existe: " . (file_exists($fullPath) ? "Sim" : "Não");
                    
                    echo "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>Nenhum arquivo encontrado no banco de dados.</p>";
            }
        } catch (Exception $e) {
            echo "<p>Erro ao consultar arquivos: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
</body>
</html> 