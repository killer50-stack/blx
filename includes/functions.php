<?php
// Helper functions for the file storage application

/**
 * Format file size in a human-readable format
 */
function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Get the total used space by all files
 */
function getTotalUsedSpace() {
    global $db;
    
    try {
        $stmt = $db->query("SELECT SUM(filesize) as total FROM files");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ? $result['total'] : 0;
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get all files from the database
 */
function getAllFiles() {
    global $db;
    
    try {
        $stmt = $db->query("SELECT * FROM files ORDER BY uploaded_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get a file by ID
 */
function getFileById($id) {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT * FROM files WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete a file by ID
 */
function deleteFile($id) {
    global $db;
    
    try {
        // Get the file info first
        $file = getFileById($id);
        if (!$file) {
            return false;
        }
        
        // Delete the file from the database
        $stmt = $db->prepare("DELETE FROM files WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Delete the actual file from the server
        $filePath = 'uploads/' . $file['filepath'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get the appropriate icon class based on file extension
 */
function getFileIconClass($extension) {
    // Convert extension to lowercase
    $extension = strtolower($extension);
    
    // Image files
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
    if (in_array($extension, $imageExtensions)) {
        return 'image';
    }
    
    // Video files
    $videoExtensions = ['mp4', 'webm', 'avi', 'mov', 'wmv', 'flv', 'mkv'];
    if (in_array($extension, $videoExtensions)) {
        return 'video';
    }
    
    // PDF files
    if ($extension === 'pdf') {
        return 'pdf';
    }
    
    // Document files
    $docExtensions = ['doc', 'docx', 'txt', 'rtf', 'odt', 'xls', 'xlsx', 'ppt', 'pptx'];
    if (in_array($extension, $docExtensions)) {
        return 'document';
    }
    
    // Default for other file types
    return 'other';
}

/**
 * Check if a file is allowed based on its extension
 */
function isFileTypeAllowed($extension) {
    // Convert extension to lowercase
    $extension = strtolower($extension);
    
    // Allowed file types
    $allowedExtensions = [
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp',
        // Videos
        'mp4', 'webm', 'avi', 'mov', 'wmv', 'flv', 'mkv',
        // Documents
        'pdf', 'doc', 'docx', 'txt', 'rtf', 'odt', 'xls', 'xlsx', 'ppt', 'pptx'
    ];
    
    return in_array($extension, $allowedExtensions);
}

/**
 * Generate a unique filename
 */
function generateUniqueFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $timestamp = time();
    $randomString = bin2hex(random_bytes(8));
    
    return $timestamp . '_' . $randomString . '.' . $extension;
}

/**
 * Add a new file to the database
 */
function addFileToDatabase($filename, $filepath, $filesize, $filetype) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO files (filename, filepath, filesize, filetype) 
            VALUES (:filename, :filepath, :filesize, :filetype)
        ");
        
        $stmt->bindParam(':filename', $filename, PDO::PARAM_STR);
        $stmt->bindParam(':filepath', $filepath, PDO::PARAM_STR);
        $stmt->bindParam(':filesize', $filesize, PDO::PARAM_INT);
        $stmt->bindParam(':filetype', $filetype, PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
} 