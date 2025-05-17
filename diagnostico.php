<?php
// Configurações básicas
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico do Servidor</h1>";

// Verificar versão do PHP
echo "<h2>Informações do PHP</h2>";
echo "<p>Versão do PHP: " . phpversion() . "</p>";
echo "<p>Extensões carregadas: </p><ul>";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    echo "<li>$ext</li>";
}
echo "</ul>";

// Verificar diretórios e permissões
echo "<h2>Verificação de Diretórios</h2>";
$directories = [
    '.' => 'Diretório raiz',
    './uploads' => 'Diretório de uploads',
    './uploads/files' => 'Diretório de arquivos',
    './config' => 'Diretório de configuração',
    './views' => 'Diretório de visualizações',
    './assets' => 'Diretório de assets'
];

foreach ($directories as $dir => $description) {
    if (file_exists($dir)) {
        $isWritable = is_writable($dir) ? 'Sim' : 'Não';
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "<p>$description ($dir): Existe, Permissões: $perms, Gravável: $isWritable</p>";
    } else {
        echo "<p style='color:red'>$description ($dir): Não existe!</p>";
    }
}

// Testar conexão com banco de dados sem usar classes
echo "<h2>Teste Direto de Banco de Dados</h2>";
try {
    $dbPath = __DIR__ . '/db/storage.db';
    echo "<p>Caminho do banco: $dbPath</p>";
    
    if (file_exists($dbPath)) {
        echo "<p>Arquivo do banco existe</p>";
        
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<p style='color:green'>Conexão com banco SQLite estabelecida com sucesso</p>";
        
        // Testar consulta
        $result = $pdo->query("SELECT COUNT(*) as count FROM files");
        $count = $result->fetchColumn();
        echo "<p>Quantidade de arquivos no banco: $count</p>";
    } else {
        echo "<p style='color:red'>Arquivo do banco NÃO existe!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Erro na conexão com banco: " . $e->getMessage() . "</p>";
}

// Verificar arquivo .htaccess
echo "<h2>Verificação do .htaccess</h2>";
if (file_exists('.htaccess')) {
    echo "<p>Arquivo .htaccess existe</p>";
    $htaccess = file_get_contents('.htaccess');
    echo "<pre>" . htmlspecialchars($htaccess) . "</pre>";
} else {
    echo "<p style='color:red'>Arquivo .htaccess NÃO existe!</p>";
}

// Conferir registros no banco
echo "<h2>Registros de arquivos</h2>";
try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT id, filename, file_path FROM files LIMIT 5");
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($files) > 0) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Arquivo</th><th>Caminho</th><th>Arquivo Existe?</th></tr>";
            
            foreach ($files as $file) {
                $fileExists = file_exists($file['file_path']) ? 'Sim' : 'Não';
                echo "<tr>";
                echo "<td>" . $file['id'] . "</td>";
                echo "<td>" . $file['filename'] . "</td>";
                echo "<td>" . $file['file_path'] . "</td>";
                echo "<td>" . $fileExists . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>Nenhum arquivo encontrado no banco</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Erro ao listar arquivos: " . $e->getMessage() . "</p>";
}
?> 