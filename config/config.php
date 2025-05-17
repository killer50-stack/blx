<?php
// Configurações gerais do sistema
define('SITE_NAME', 'ArmazenamentoWeb');
define('BASE_URL', 'http://localhost/Nova%20pasta%20(6)');
define('MAX_UPLOAD_SIZE', 31457280000); // 29 GB em bytes
define('MAX_STORAGE_SIZE', 1072668082176); // 999 GB em bytes
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/webm', 'video/ogg', 'application/pdf']);
define('UPLOADS_DIR', __DIR__ . '/../uploads/files/');

// Funções auxiliares
function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

function getFileIcon($fileType) {
    if (strpos($fileType, 'image/') === 0) {
        return 'image';
    } elseif (strpos($fileType, 'video/') === 0) {
        return 'video';
    } elseif ($fileType === 'application/pdf') {
        return 'pdf';
    } else {
        return 'file';
    }
}

function isValidFileType($fileType) {
    return in_array($fileType, ALLOWED_TYPES);
}
?> 