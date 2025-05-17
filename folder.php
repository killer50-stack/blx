<?php
// Initialize the session and include required files
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Create a new folder
    if ($action === 'create' && isset($_POST['name']) && isset($_POST['parent_id'])) {
        $name = trim($_POST['name']);
        $parent_id = intval($_POST['parent_id']);
        
        // Validate folder name
        if (empty($name)) {
            $_SESSION['error'] = "O nome da pasta não pode estar vazio.";
        } else {
            // Create the folder
            $folder_id = createFolder($name, $parent_id);
            
            if ($folder_id) {
                $_SESSION['success'] = "Pasta '{$name}' criada com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao criar a pasta.";
            }
        }
        
        // Redirect back to the parent folder
        header("Location: index.php?folder={$parent_id}");
        exit;
    }
    
    // Move a file to another folder
    if ($action === 'move' && isset($_POST['file_id']) && isset($_POST['folder_id'])) {
        $file_id = intval($_POST['file_id']);
        $folder_id = intval($_POST['folder_id']);
        
        // Get current file info to determine current folder
        $file = getFileById($file_id);
        $current_folder = $file ? ($file['folder_id'] ? $file['folder_id'] : 1) : 1;
        
        // Move the file
        if (moveFile($file_id, $folder_id)) {
            $_SESSION['success'] = "Arquivo movido com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao mover o arquivo.";
        }
        
        // Redirect back to the current folder
        header("Location: index.php?folder={$current_folder}");
        exit;
    }
}

// Process GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Delete a folder
    if ($action === 'delete' && isset($_GET['id'])) {
        $folder_id = intval($_GET['id']);
        $folder = getFolderById($folder_id);
        
        if (!$folder) {
            $_SESSION['error'] = "Pasta não encontrada.";
            header("Location: index.php");
            exit;
        }
        
        $parent_id = $folder['parent_id'] ? $folder['parent_id'] : 1;
        
        // Protect root folder
        if ($folder_id === 1) {
            $_SESSION['error'] = "A pasta raiz não pode ser excluída.";
            header("Location: index.php");
            exit;
        }
        
        // Delete the folder and its contents
        if (deleteFolder($folder_id)) {
            $_SESSION['success'] = "Pasta excluída com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao excluir a pasta.";
        }
        
        // Redirect back to the parent folder
        header("Location: index.php?folder={$parent_id}");
        exit;
    }
}

// If we reach here, redirect to index
header("Location: index.php");
exit; 