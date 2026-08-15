<?php
// Endpoint: api/tarefas/listar.php
// GET: Retorna JSON com as tarefas do grupo filtrado pelo $_SESSION['grupo_id'] ou informado via GET por professor.
// POST: Permite ao professor cadastrar uma nova tarefa que é replicada para TODOS os grupos do ciclo ativo.

require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Apenas professor pode cadastrar tarefas que replicam para todos os grupos
    verificar_autenticacao('prof');

    $inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $titulo = trim($inputData['titulo'] ?? '');
    $descricao = trim($inputData['descricao'] ?? '');
    $dataPrazo = !empty($inputData['data_prazo']) ? trim($inputData['data_prazo']) : null;

    if (empty($titulo)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'O título da tarefa é obrigatório.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        // Busca todos os grupos do ciclo ativo
        $stmtGrupos = $pdo->query('
            SELECT g.id
            FROM grupos g
            JOIN ciclos c ON g.ciclo_id = c.id
            WHERE c.status_ativo = 1
        ');
        $gruposAtivos = $stmtGrupos->fetchAll(PDO::FETCH_COLUMN);

        if (empty($gruposAtivos)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Nenhum grupo ativo encontrado no ciclo atual.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pdo->beginTransaction();

        $stmtIns = $pdo->prepare('
            INSERT INTO tarefas (grupo_id, titulo, descricao, status_kanban, data_prazo, criado_em)
            VALUES (:grupo_id, :titulo, :descricao, "a_fazer", :data_prazo, NOW())
        ');

        $totalCriadas = 0;
        foreach ($gruposAtivos as $grupoId) {
            $stmtIns->execute([
                ':grupo_id' => $grupoId,
                ':titulo' => $titulo,
                ':descricao' => $descricao,
                ':data_prazo' => $dataPrazo
            ]);
            $totalCriadas++;
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Tarefa cadastrada e replicada com sucesso para {$totalCriadas} grupos!",
            'total_grupos' => $totalCriadas
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar tarefa: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Método GET: Listar tarefas de um grupo específico
$grupo_id = 0;

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
        SELECT id, grupo_id, titulo, descricao, status_kanban, data_prazo, recado, criado_em
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
