<?php
// Endpoint: api/tarefas/mover.php
// Atualiza o status_kanban de uma tarefa específica.

require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$tarefa_id = intval($inputData['tarefa_id'] ?? 0);
$novo_status = trim($inputData['status_kanban'] ?? '');

$status_permitidos = ['a_fazer', 'em_andamento', 'revisao', 'concluido'];

if (!$tarefa_id || !in_array($novo_status, $status_permitidos)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos ou status kanban não reconhecido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Verifica se a tarefa pertence ao grupo do aluno (se não for professor)
    if (!isset($_SESSION['prof_id'])) {
        $grupo_id = $_SESSION['grupo_id'] ?? 0;
        $stmtCheck = $pdo->prepare('SELECT grupo_id FROM tarefas WHERE id = :id LIMIT 1');
        $stmtCheck->execute([':id' => $tarefa_id]);
        $tarefa = $stmtCheck->fetch();

        if (!$tarefa || $tarefa['grupo_id'] != $grupo_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permissão negada para alterar esta tarefa.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $stmtUpdate = $pdo->prepare('UPDATE tarefas SET status_kanban = :status WHERE id = :id');
    $stmtUpdate->execute([
        ':status' => $novo_status,
        ':id' => $tarefa_id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Status da tarefa atualizado com sucesso.',
        'tarefa_id' => $tarefa_id,
        'novo_status' => $novo_status
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status da tarefa.'], JSON_UNESCAPED_UNICODE);
}
