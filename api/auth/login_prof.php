<?php
// Endpoint: api/auth/login_prof.php
// Valida usuario e senha na tabela professores e inicia $_SESSION['prof_id']

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Permite JSON no body ou FormData/POST tradicional
$inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$usuario = trim($inputData['usuario'] ?? '');
$senha = trim($inputData['senha'] ?? '');

if (empty($usuario) || empty($senha)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Usuário e senha são obrigatórios.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->prepare('SELECT id, usuario, senha_hash, nome FROM professores WHERE usuario = :usuario LIMIT 1');
    $stmt->execute([':usuario' => $usuario]);
    $prof = $stmt->fetch();

    if ($prof && password_verify($senha, $prof['senha_hash'])) {
        // Regenera ID da sessão para prevenir Session Fixation
        session_regenerate_id(true);
        $_SESSION['prof_id'] = $prof['id'];
        $_SESSION['prof_nome'] = $prof['nome'];

        echo json_encode([
            'success' => true,
            'message' => 'Login realizado com sucesso.',
            'professor' => [
                'id' => $prof['id'],
                'nome' => $prof['nome']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Usuário ou senha inválidos.'], JSON_UNESCAPED_UNICODE);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno no servidor.'], JSON_UNESCAPED_UNICODE);
}
