<?php
// Database configuration

// Create config directory if it doesn't exist
if (!file_exists(dirname(__FILE__))) {
    mkdir(dirname(__FILE__), 0777, true);
}

// Database file path
$databasePath = __DIR__ . '/../database/storage.db';
$databaseDir = dirname($databasePath);

// Create database directory if it doesn't exist
if (!file_exists($databaseDir)) {
    mkdir($databaseDir, 0777, true);
}

// Connect to SQLite database
try {
    $db = new PDO('sqlite:' . $databasePath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create folders table if it doesn't exist
    $db->exec('
        CREATE TABLE IF NOT EXISTS folders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            parent_id INTEGER DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES folders(id) ON DELETE CASCADE
        )
    ');
    
    // Create files table if it doesn't exist
    $db->exec('
        CREATE TABLE IF NOT EXISTS files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT NOT NULL,
            filepath TEXT NOT NULL,
            filesize INTEGER NOT NULL,
            filetype TEXT NOT NULL,
            folder_id INTEGER DEFAULT NULL,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL
        )
    ');
    
    // Check if folder_id column exists in files table
    $result = $db->query("PRAGMA table_info(files);");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    $hasColumn = false;
    
    foreach ($columns as $column) {
        if ($column['name'] === 'folder_id') {
            $hasColumn = true;
            break;
        }
    }
    
    // Add folder_id column if it doesn't exist
    if (!$hasColumn) {
        $db->exec('ALTER TABLE files ADD COLUMN folder_id INTEGER DEFAULT NULL REFERENCES folders(id) ON DELETE SET NULL');
    }
    
    // Insert root folder if it doesn't exist
    $stmt = $db->query("SELECT COUNT(*) as count FROM folders WHERE id = 1");
    $rootExists = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($rootExists == 0) {
        $db->exec("INSERT INTO folders (id, name, parent_id) VALUES (1, 'Root', NULL)");
    }
    
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
} 