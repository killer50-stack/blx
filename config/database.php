<?php
// Configuração do banco de dados SQLite
class Database {
    private $db;
    
    public function __construct() {
        $dbPath = __DIR__ . '/../db/storage.db';
        try {
            // Criar conexão com o banco de dados SQLite
            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Criar tabela de arquivos se não existir
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS files (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    filename TEXT NOT NULL,
                    original_filename TEXT NOT NULL,
                    file_type TEXT NOT NULL,
                    file_size INTEGER NOT NULL,
                    file_path TEXT NOT NULL,
                    upload_date DATETIME NOT NULL
                )
            ');
            
            // Criar tabela de estatísticas de armazenamento
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS storage_stats (
                    id INTEGER PRIMARY KEY,
                    total_size INTEGER DEFAULT 0,
                    max_size INTEGER DEFAULT 1072668082176, -- 999 GB em bytes
                    last_update DATETIME NOT NULL
                )
            ');
            
            // Inicializar estatísticas se não existirem
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM storage_stats');
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if ($count == 0) {
                $stmt = $this->db->prepare('INSERT INTO storage_stats (id, total_size, last_update) VALUES (1, 0, datetime("now"))');
                $stmt->execute();
            }
            
        } catch (PDOException $e) {
            die("Erro na conexão com o banco de dados: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->db;
    }
}
?> 