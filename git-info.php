<?php
// Initialize the session and database connection
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Get git repository information
function getGitInfo() {
    $gitInfo = [];
    
    // Check if .git directory exists
    if (file_exists('.git')) {
        $gitInfo['has_repo'] = true;
        
        // Get current branch
        $branch = shell_exec('git branch --show-current 2>&1');
        $gitInfo['branch'] = trim($branch);
        
        // Get latest commit
        $lastCommit = shell_exec('git log -1 --pretty=format:"%h - %s (%ar)" 2>&1');
        $gitInfo['last_commit'] = $lastCommit;
        
        // Get status
        $status = shell_exec('git status -s 2>&1');
        $gitInfo['status'] = $status;
        
        // Count modified files
        $statusLines = explode("\n", trim($status));
        $gitInfo['modified_count'] = count(array_filter($statusLines));
        
        // Check remote
        $remote = shell_exec('git remote -v 2>&1');
        $gitInfo['remote'] = $remote;
    } else {
        $gitInfo['has_repo'] = false;
    }
    
    return $gitInfo;
}

$gitInfo = getGitInfo();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Git Information - Arquivo Virtual</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .git-info {
            background-color: #f5f1ed;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .git-status {
            background-color: #f9f6f3;
            border: 1px solid #e0d6cc;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
            font-family: monospace;
            white-space: pre;
            overflow-x: auto;
        }
        .git-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        .git-actions .btn {
            min-width: 120px;
            text-align: center;
        }
        .no-repo {
            text-align: center;
            padding: 40px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Arquivo Virtual - Git</h1>
            <nav>
                <a href="index.php" class="btn">Voltar para Arquivos</a>
            </nav>
        </header>

        <main>
            <section>
                <h2>Informações do Git</h2>
                
                <?php if ($gitInfo['has_repo']): ?>
                <div class="git-info">
                    <h3>Status do Repositório</h3>
                    <p><strong>Branch atual:</strong> <?= htmlspecialchars($gitInfo['branch']) ?></p>
                    <p><strong>Último commit:</strong> <?= htmlspecialchars($gitInfo['last_commit']) ?></p>
                    <p><strong>Arquivos modificados:</strong> <?= $gitInfo['modified_count'] ?></p>
                    
                    <?php if (!empty($gitInfo['status'])): ?>
                    <h4>Arquivos modificados:</h4>
                    <div class="git-status"><?= htmlspecialchars($gitInfo['status']) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($gitInfo['remote'])): ?>
                    <h4>Repositório remoto:</h4>
                    <div class="git-status"><?= htmlspecialchars($gitInfo['remote']) ?></div>
                    <?php endif; ?>
                    
                    <div class="git-actions">
                        <a href="#" class="btn" onclick="executeGitCommand('git add .'); return false;">Adicionar Todos</a>
                        <a href="#" class="btn" onclick="executeGitCommand('git commit -m \'Update files\''); return false;">Commit</a>
                        <a href="#" class="btn" onclick="executeGitCommand('git push'); return false;">Push</a>
                        <a href="#" class="btn" onclick="executeGitCommand('git pull'); return false;">Pull</a>
                    </div>
                </div>
                <?php else: ?>
                <div class="no-repo">
                    <h3>Repositório Git não encontrado</h3>
                    <p>Este projeto não tem um repositório Git inicializado.</p>
                    <div class="git-actions">
                        <a href="#" class="btn" onclick="executeGitCommand('git init'); return false;">Inicializar Git</a>
                    </div>
                </div>
                <?php endif; ?>
            </section>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> Arquivo Virtual - Todos os direitos reservados</p>
        </footer>
    </div>

    <script>
        function executeGitCommand(command) {
            if (confirm("Deseja executar o comando: " + command + "?")) {
                // Create a form data object
                const formData = new FormData();
                formData.append('command', command);
                
                // Send the AJAX request
                fetch('git-command.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Comando executado com sucesso:\n" + data.output);
                    } else {
                        alert("Erro ao executar o comando:\n" + data.output);
                    }
                    // Refresh the page to show updated git status
                    location.reload();
                })
                .catch(error => {
                    alert("Erro: " + error);
                });
            }
        }
    </script>
</body>
</html> 