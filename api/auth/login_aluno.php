<?php
// Endpoint: api/auth/login_aluno.php
// Recebe aluno_id e grupo_id. Salva ambos em $_SESSION['aluno_id'] e $_SESSION['grupo_id'].

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$aluno_id = intval($inputData['aluno_id'] ?? 0);
$grupo_id = intval($inputData['grupo_id'] ?? 0);

if (!$aluno_id || !$grupo_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Aluno e Grupo são obrigatórios.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    // Valida se a associação do aluno com o grupo existe no banco
    $stmt = $pdo->prepare('
        SELECT ga.id, a.nome, g.tema
        FROM grupo_alunos ga
        JOIN alunos a ON ga.aluno_id = a.id
        JOIN grupos g ON ga.grupo_id = g.id
        WHERE ga.aluno_id = :aluno_id AND ga.grupo_id = :grupo_id AND ga.status_aluno = "ativo"
        LIMIT 1
    ');
    $stmt->execute([
        ':aluno_id' => $aluno_id,
        ':grupo_id' => $grupo_id
    ]);
    $vinculo = $stmt->fetch();

    if (!$vinculo) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Aluno não está associado a este grupo.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Salva em $_SESSION['aluno_id'] e $_SESSION['grupo_id']
    session_regenerate_id(true);
    $_SESSION['aluno_id'] = $aluno_id;
    $_SESSION['grupo_id'] = $grupo_id;
    $_SESSION['aluno_nome'] = $vinculo['nome'];
    $_SESSION['grupo_tema'] = $vinculo['tema'];

    echo json_encode([
        'success' => true,
        'message' => 'Sessão iniciada com sucesso.',
        'redirect' => 'painel_aluno.php'
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno ao realizar login do aluno.'], JSON_UNESCAPED_UNICODE);
}
