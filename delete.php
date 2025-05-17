<?php
// Initialize the session and database connection
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Check if file ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID do arquivo não fornecido.";
    header('Location: index.php');
    exit;
}

// Get file ID
$fileId = $_GET['id'];

// Get the file info to determine its folder
$file = getFileById($fileId);
$folder_id = $file ? ($file['folder_id'] ? $file['folder_id'] : 1) : 1;

// Try to delete the file
if (deleteFile($fileId)) {
    $_SESSION['success'] = "Arquivo excluído com sucesso.";
} else {
    $_SESSION['error'] = "Erro ao excluir o arquivo.";
}

// Redirect back to the folder page
header("Location: index.php?folder={$folder_id}");
exit; 