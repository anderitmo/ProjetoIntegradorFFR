<?php
// Endpoint: api/tarefas/listar.php
// Retorna JSON com as tarefas do grupo filtrado pelo $_SESSION['grupo_id'] (ou grupo informado por professor)

require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$grupo_id = 0;

// Se for professor, pode passar grupo_id via GET, caso contrário usa $_SESSION['grupo_id']
if (isset($_SESSION['prof_id']) && isset($_GET['grupo_id'])) {
    $grupo_id = intval($_GET['grupo_id']);
} elseif (isset($_SESSION['grupo_id'])) {
    $grupo_id = intval($_SESSION['grupo_id']);
}

if (!$grupo_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Identificador do grupo ausente.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Busca tarefas do grupo
    $stmt = $pdo->prepare('
        SELECT id, grupo_id, titulo, descricao, status_kanban, data_prazo, criado_em
        FROM tarefas
        WHERE grupo_id = :grupo_id
        ORDER BY id ASC
    ');
    $stmt->execute([':grupo_id' => $grupo_id]);
    $tarefas = $stmt->fetchAll();

    // Para cada tarefa, busca o histórico de arquivos enviados
    foreach ($tarefas as &$tarefa) {
        $stmtArq = $pdo->prepare('
            SELECT ta.id, ta.caminho_arquivo, ta.versao, ta.data_envio, a.nome as aluno_nome
            FROM tarefa_arquivos ta
            JOIN alunos a ON ta.aluno_id = a.id
            WHERE ta.tarefa_id = :tarefa_id
            ORDER BY ta.versao DESC
        ');
        $stmtArq->execute([':tarefa_id' => $tarefa['id']]);
        $tarefa['arquivos'] = $stmtArq->fetchAll();
    }

    echo json_encode([
        'success' => true,
        'grupo_id' => $grupo_id,
        'tarefas' => $tarefas
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar tarefas.'], JSON_UNESCAPED_UNICODE);
}
