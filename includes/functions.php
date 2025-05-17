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
function getAllFiles($folder_id = null) {
    global $db;
    
    try {
        if ($folder_id === null) {
            $stmt = $db->query("SELECT * FROM files ORDER BY uploaded_at DESC");
        } else {
            $stmt = $db->prepare("SELECT * FROM files WHERE folder_id = :folder_id ORDER BY uploaded_at DESC");
            $stmt->bindParam(':folder_id', $folder_id, PDO::PARAM_INT);
            $stmt->execute();
        }
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
function addFileToDatabase($filename, $filepath, $filesize, $filetype, $folder_id = 1) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO files (filename, filepath, filesize, filetype, folder_id) 
            VALUES (:filename, :filepath, :filesize, :filetype, :folder_id)
        ");
        
        $stmt->bindParam(':filename', $filename, PDO::PARAM_STR);
        $stmt->bindParam(':filepath', $filepath, PDO::PARAM_STR);
        $stmt->bindParam(':filesize', $filesize, PDO::PARAM_INT);
        $stmt->bindParam(':filetype', $filetype, PDO::PARAM_STR);
        $stmt->bindParam(':folder_id', $folder_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get all folders
 */
function getAllFolders($parent_id = null) {
    global $db;
    
    try {
        if ($parent_id === null) {
            $stmt = $db->query("SELECT * FROM folders ORDER BY name ASC");
        } else {
            $stmt = $db->prepare("SELECT * FROM folders WHERE parent_id = :parent_id ORDER BY name ASC");
            $stmt->bindParam(':parent_id', $parent_id, PDO::PARAM_INT);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get a folder by ID
 */
function getFolderById($id) {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT * FROM folders WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create a new folder
 */
function createFolder($name, $parent_id = 1) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO folders (name, parent_id) 
            VALUES (:name, :parent_id)
        ");
        
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':parent_id', $parent_id, PDO::PARAM_INT);
        
        $stmt->execute();
        return $db->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Delete a folder and its contents
 */
function deleteFolder($id) {
    global $db;
    
    if ($id == 1) {
        // Never delete the root folder
        return false;
    }
    
    try {
        $db->beginTransaction();
        
        // Get all files in the folder
        $files = getAllFiles($id);
        foreach ($files as $file) {
            deleteFile($file['id']);
        }
        
        // Get all subfolders
        $subfolders = getAllFolders($id);
        foreach ($subfolders as $subfolder) {
            deleteFolder($subfolder['id']);
        }
        
        // Delete the folder
        $stmt = $db->prepare("DELETE FROM folders WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $db->commit();
        return true;
    } catch (PDOException $e) {
        $db->rollBack();
        return false;
    }
}

/**
 * Move a file to a different folder
 */
function moveFile($file_id, $folder_id) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            UPDATE files 
            SET folder_id = :folder_id 
            WHERE id = :file_id
        ");
        
        $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
        $stmt->bindParam(':folder_id', $folder_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get folder path (breadcrumb)
 */
function getFolderPath($folder_id) {
    global $db;
    
    $path = [];
    $current_id = $folder_id;
    
    while ($current_id) {
        $folder = getFolderById($current_id);
        if (!$folder) {
            break;
        }
        
        array_unshift($path, $folder);
        $current_id = $folder['parent_id'];
    }
    
    return $path;
} 