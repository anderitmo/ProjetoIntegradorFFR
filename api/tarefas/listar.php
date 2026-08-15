<?php
// Endpoint: api/tarefas/listar.php
// GET: Retorna JSON com as tarefas do grupo informado via GET ou $_SESSION['grupo_id']. Se grupo_id=todos para professor, retorna todas as tarefas de todos os grupos.
// POST: Permite ao professor cadastrar uma nova tarefa. Pode ser para 'todos' os grupos do ciclo ativo ou para um 'grupo_id' específico.

require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Apenas professor pode cadastrar tarefas
    verificar_autenticacao('prof');

    $inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $titulo = trim($inputData['titulo'] ?? '');
    $descricao = trim($inputData['descricao'] ?? '');
    $dataPrazo = !empty($inputData['data_prazo']) ? trim($inputData['data_prazo']) : null;
    $alvoGrupoId = $inputData['grupo_id'] ?? 'todos';

    if (empty($titulo)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'O título da tarefa é obrigatório.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        if ($alvoGrupoId === 'todos') {
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
                'message' => "Tarefa cadastrada e replicada com sucesso para todos os {$totalCriadas} grupos!",
                'total_grupos' => $totalCriadas
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            // Cadastra a tarefa para apenas UM grupo específico
            $grupoIdEspecidico = intval($alvoGrupoId);

            if (!$grupoIdEspecidico) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Grupo alvo inválido.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmtIns = $pdo->prepare('
                INSERT INTO tarefas (grupo_id, titulo, descricao, status_kanban, data_prazo, criado_em)
                VALUES (:grupo_id, :titulo, :descricao, "a_fazer", :data_prazo, NOW())
            ');
            $stmtIns->execute([
                ':grupo_id' => $grupoIdEspecidico,
                ':titulo' => $titulo,
                ':descricao' => $descricao,
                ':data_prazo' => $dataPrazo
            ]);

            echo json_encode([
                'success' => true,
                'message' => "Tarefa cadastrada com sucesso para o grupo selecionado!",
                'tarefa_id' => $pdo->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar tarefa: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Método GET: Listar tarefas de um grupo específico ou de Todos os Grupos (se professor passar grupo_id=todos)
$grupo_id_raw = $_GET['grupo_id'] ?? null;

if (isset($_SESSION['prof_id']) && $grupo_id_raw === 'todos') {
    // Retorna todas as tarefas de todos os grupos do ciclo ativo com informações do grupo
    try {
        $stmt = $pdo->query('
            SELECT t.id, t.grupo_id, t.titulo, t.descricao, t.status_kanban, t.data_prazo, t.recado, t.criado_em,
                   g.codigo_acesso_unico AS grupo_codigo, g.tema AS grupo_tema, g.nivel_pi AS grupo_nivel
            FROM tarefas t
            JOIN grupos g ON t.grupo_id = g.id
            JOIN ciclos c ON g.ciclo_id = c.id
            WHERE c.status_ativo = 1
            ORDER BY g.id ASC, t.id ASC
        ');
        $tarefas = $stmt->fetchAll();

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
            'visão_geral' => true,
            'tarefas' => $tarefas
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar visão geral das tarefas: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

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
    // Busca tarefas do grupo específico
    $stmt = $pdo->prepare('
        SELECT t.id, t.grupo_id, t.titulo, t.descricao, t.status_kanban, t.data_prazo, t.recado, t.criado_em,
               g.codigo_acesso_unico AS grupo_codigo, g.tema AS grupo_tema
        FROM tarefas t
        JOIN grupos g ON t.grupo_id = g.id
        WHERE t.grupo_id = :grupo_id
        ORDER BY t.id ASC
    ');
    $stmt->execute([':grupo_id' => $grupo_id]);
    $tarefas = $stmt->fetchAll();

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
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar tarefas: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
