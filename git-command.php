<?php
// Initialize the session
session_start();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the command from POST data
$command = isset($_POST['command']) ? $_POST['command'] : '';

// Validate command (only allow specific git commands for security)
$allowedCommands = [
    'git init',
    'git add .',
    'git commit -m',
    'git push',
    'git pull',
    'git status',
    'git branch'
];

$isAllowed = false;
foreach ($allowedCommands as $allowed) {
    if (strpos($command, $allowed) === 0) {
        $isAllowed = true;
        break;
    }
}

if (!$isAllowed) {
    http_response_code(403);
    echo json_encode(['error' => 'Command not allowed']);
    exit;
}

// Execute the command
$output = [];
$returnCode = 0;
exec($command . ' 2>&1', $output, $returnCode);

// Return the result
header('Content-Type: application/json');
echo json_encode([
    'success' => $returnCode === 0,
    'output' => implode("\n", $output),
    'code' => $returnCode
]); 