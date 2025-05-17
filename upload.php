<?php
// Initialize the session and include required files
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Create uploads directory if it doesn't exist
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if a file was uploaded
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];
        
        // Get file information
        $filename = $file['name'];
        $filesize = $file['size'];
        $filetype = $file['type'];
        $tmp_path = $file['tmp_name'];
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Get folder ID (default to root if not provided)
        $folder_id = isset($_POST['folder_id']) ? intval($_POST['folder_id']) : 1;
        
        // Check if the folder exists
        $folder = getFolderById($folder_id);
        if (!$folder) {
            $folder_id = 1; // Default to root folder if provided folder doesn't exist
        }
        
        // Check if the file type is allowed
        if (!isFileTypeAllowed($extension)) {
            $_SESSION['upload_error'] = "Tipo de arquivo não permitido. Apenas imagens, vídeos e PDFs são aceitos.";
            header("Location: index.php?folder={$folder_id}");
            exit;
        }
        
        // Check file size (29 GB limit)
        $maxFileSize = 29 * 1024 * 1024 * 1024; // 29 GB in bytes
        if ($filesize > $maxFileSize) {
            $_SESSION['upload_error'] = "O arquivo excede o limite de tamanho de 29 GB.";
            header("Location: index.php?folder={$folder_id}");
            exit;
        }
        
        // Check if user has enough storage space
        $maxStorage = 999 * 1024 * 1024 * 1024; // 999 GB in bytes
        $usedSpace = getTotalUsedSpace();
        $remainingSpace = $maxStorage - $usedSpace;
        
        if ($filesize > $remainingSpace) {
            $_SESSION['upload_error'] = "Espaço de armazenamento insuficiente. Você tem " . formatSize($remainingSpace) . " disponíveis.";
            header("Location: index.php?folder={$folder_id}");
            exit;
        }
        
        // Generate a unique filename to prevent overwriting
        $uniqueFilename = generateUniqueFilename($filename);
        $uploadPath = 'uploads/' . $uniqueFilename;
        
        // Move the uploaded file to the destination directory
        if (move_uploaded_file($tmp_path, $uploadPath)) {
            // Add file to database with folder ID
            if (addFileToDatabase($filename, $uniqueFilename, $filesize, $filetype, $folder_id)) {
                $_SESSION['upload_success'] = "Arquivo enviado com sucesso!";
            } else {
                // If database insertion fails, delete the uploaded file
                unlink($uploadPath);
                $_SESSION['upload_error'] = "Erro ao salvar o arquivo no banco de dados.";
            }
        } else {
            $_SESSION['upload_error'] = "Erro ao mover o arquivo para o destino final.";
        }
    } else {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => "O arquivo excede o tamanho máximo permitido pelo servidor.",
            UPLOAD_ERR_FORM_SIZE => "O arquivo excede o tamanho máximo permitido pelo formulário.",
            UPLOAD_ERR_PARTIAL => "O arquivo foi enviado parcialmente.",
            UPLOAD_ERR_NO_FILE => "Nenhum arquivo foi enviado.",
            UPLOAD_ERR_NO_TMP_DIR => "Diretório temporário não encontrado.",
            UPLOAD_ERR_CANT_WRITE => "Falha ao gravar o arquivo no disco.",
            UPLOAD_ERR_EXTENSION => "Uma extensão PHP impediu o upload do arquivo."
        ];
        
        $errorCode = isset($_FILES['file']) ? $_FILES['file']['error'] : UPLOAD_ERR_NO_FILE;
        $errorMessage = isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : "Erro desconhecido ao enviar o arquivo.";
        
        $_SESSION['upload_error'] = $errorMessage;
    }
    
    // Get the folder ID for redirection
    $folder_id = isset($_POST['folder_id']) ? intval($_POST['folder_id']) : 1;
    
    // Redirect back to the same folder
    header("Location: index.php?folder={$folder_id}");
    exit;
}

// Redirect back to index page if accessed directly
header('Location: index.php');
exit; 