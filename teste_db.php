<?php
// Habilitar exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Teste de Conexão com SQLite</h1>";

// Verificar se o SQLite está habilitado
if (!extension_loaded('sqlite3')) {
    die("<p style='color:red'>Extensão SQLite3 não está habilitada!</p>");
}
if (!extension_loaded('pdo_sqlite')) {
    die("<p style='color:red'>Extensão PDO_SQLite não está habilitada!</p>");
}

echo "<p style='color:green'>Extensões SQLite estão habilitadas</p>";

// Testar conexão direta
$dbPath = __DIR__ . '/db/storage.db';
echo "<p>Tentando conectar ao banco em: $dbPath</p>";

try {
    // Verificar se o diretório existe
    if (!file_exists(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0777, true);
        echo "<p>Diretório db/ criado</p>";
    }
    
    // Tentar abrir o banco
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>Conexão com banco de dados estabelecida com sucesso!</p>";
    
    // Criar tabela de teste
    $db->exec("CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY, name TEXT)");
    echo "<p>Tabela de teste criada</p>";
    
    // Inserir dado de teste
    $db->exec("INSERT INTO test (name) VALUES ('Test ".date('Y-m-d H:i:s')."')");
    echo "<p>Registro de teste inserido</p>";
    
    // Ler dado
    $stmt = $db->query("SELECT * FROM test ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Último registro: ID={$row['id']}, Nome={$row['name']}</p>";
    
    // Verificar tabelas
    echo "<h2>Tabelas existentes:</h2>";
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    echo "<ul>";
    while ($table = $tables->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>{$table['name']}</li>";
        
        // Se for a tabela files, mostrar estrutura
        if ($table['name'] == 'files') {
            $stmt = $db->query("PRAGMA table_info(files)");
            echo "<ul>";
            while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<li>{$col['name']} - {$col['type']}</li>";
            }
            echo "</ul>";
            
            // Contar registros
            $count = $db->query("SELECT COUNT(*) FROM files")->fetchColumn();
            echo "<p>Total de registros: $count</p>";
        }
        
        // Se for a tabela storage_stats, mostrar dados
        if ($table['name'] == 'storage_stats') {
            $stmt = $db->query("SELECT * FROM storage_stats LIMIT 1");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($stats) {
                echo "<ul>";
                foreach ($stats as $key => $value) {
                    echo "<li>$key: $value</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>Nenhum registro de estatísticas encontrado</p>";
            }
        }
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Erro na conexão: " . $e->getMessage() . "</p>";
    echo "<p>Trace:<br>" . nl2br($e->getTraceAsString()) . "</p>";
}
?> 