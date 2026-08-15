<?php
// Endpoint: api/auth/valida_codigo.php
// Recebe codigo_acesso (POST/JSON), busca na tabela grupos e retorna JSON com a lista de alunos desse grupo.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$codigo = trim($inputData['codigo_acesso'] ?? $inputData['codigo'] ?? '');

if (empty($codigo)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Código de acesso não fornecido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    // Busca informações do grupo pelo código único de acesso
    $stmt = $pdo->prepare('
        SELECT g.id, g.tema, g.nivel_pi, c.nome_semestre
        FROM grupos g
        JOIN ciclos c ON g.ciclo_id = c.id
        WHERE g.codigo_acesso_unico = :codigo LIMIT 1
    ');
    $stmt->execute([':codigo' => $codigo]);
    $grupo = $stmt->fetch();

    if (!$grupo) {
        http_response_code(444);
        echo json_encode(['success' => false, 'message' => 'Código de acesso inválido ou não encontrado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Busca os alunos vinculados a esse grupo
    $stmtAlunos = $pdo->prepare('
        SELECT a.id, a.nome, ga.status_aluno
        FROM alunos a
        JOIN grupo_alunos ga ON a.id = ga.aluno_id
        WHERE ga.grupo_id = :grupo_id AND ga.status_aluno = "ativo"
        ORDER BY a.nome ASC
    ');
    $stmtAlunos->execute([':grupo_id' => $grupo['id']]);
    $alunos = $stmtAlunos->fetchAll();

    echo json_encode([
        'success' => true,
        'grupo' => [
            'id' => $grupo['id'],
            'tema' => $grupo['tema'],
            'nivel_pi' => $grupo['nivel_pi'],
            'semestre' => $grupo['nome_semestre']
        ],
        'alunos' => $alunos
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno ao consultar código de acesso.'], JSON_UNESCAPED_UNICODE);
}
