<?php
// Endpoint: api/tarefas/mover.php
// Atualiza o status_kanban ou o recado/comentário de uma tarefa específica.

require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$tarefa_id = intval($inputData['tarefa_id'] ?? 0);
$novo_status = isset($inputData['status_kanban']) ? trim($inputData['status_kanban']) : null;
$novo_recado = isset($inputData['recado']) ? trim($inputData['recado']) : null;

$status_permitidos = ['a_fazer', 'em_andamento', 'revisao', 'concluido'];

if (!$tarefa_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Identificador da tarefa não informado.'], JSON_UNESCAPED_UNICODE);
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

    if ($novo_status !== null) {
        if (!in_array($novo_status, $status_permitidos)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Status kanban não reconhecido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmtUpdate = $pdo->prepare('UPDATE tarefas SET status_kanban = :status WHERE id = :id');
        $stmtUpdate->execute([':status' => $novo_status, ':id' => $tarefa_id]);
    }

    if ($novo_recado !== null) {
        $stmtUpdateRecado = $pdo->prepare('UPDATE tarefas SET recado = :recado WHERE id = :id');
        $stmtUpdateRecado->execute([':recado' => $novo_recado, ':id' => $tarefa_id]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Tarefa atualizada com sucesso.',
        'tarefa_id' => $tarefa_id
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar tarefa.'], JSON_UNESCAPED_UNICODE);
}
