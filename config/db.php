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
    
    // Create files table if it doesn't exist
    $db->exec('
        CREATE TABLE IF NOT EXISTS files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT NOT NULL,
            filepath TEXT NOT NULL,
            filesize INTEGER NOT NULL,
            filetype TEXT NOT NULL,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
} 