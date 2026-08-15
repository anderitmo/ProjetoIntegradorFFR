<?php
// Endpoint: api/ciclos/gerenciar.php
// Suporta operações de listagem, criação de ciclo, cadastro de grupos, gestão de professores e duplicação.

require_once __DIR__ . '/../config/auth_check.php';
verificar_autenticacao('prof');
require_once __DIR__ . '/../config/database.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? 'listar');

try {
    if ($action === 'listar') {
        // Listar todos os ciclos e grupos por ciclo
        $stmtCiclos = $pdo->query('SELECT * FROM ciclos ORDER BY id DESC');
        $ciclos = $stmtCiclos->fetchAll();

        foreach ($ciclos as &$ciclo) {
            $stmtG = $pdo->prepare('
                SELECT g.*,
                       (SELECT COUNT(*) FROM grupo_alunos ga WHERE ga.grupo_id = g.id) AS total_alunos
                FROM grupos g
                WHERE g.ciclo_id = :ciclo_id
                ORDER BY g.id ASC
            ');
            $stmtG->execute([':ciclo_id' => $ciclo['id']]);
            $ciclo['grupos'] = $stmtG->fetchAll();
        }

        // Listar professores cadastrados
        $stmtProf = $pdo->query('SELECT id, usuario, nome, criado_em FROM professores ORDER BY id ASC');
        $professores = $stmtProf->fetchAll();

        echo json_encode([
            'success' => true,
            'ciclos' => $ciclos,
            'professores' => $professores
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'criar_ciclo') {
        $inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $nomeSemestre = trim($inputData['nome_semestre'] ?? '');

        if (empty($nomeSemestre)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nome do semestre é obrigatório (ex: 2025.2).'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Desativa ciclos anteriores
        $pdo->exec('UPDATE ciclos SET status_ativo = 0');

        $stmtIns = $pdo->prepare('INSERT INTO ciclos (nome_semestre, status_ativo) VALUES (:nome, 1)');
        $stmtIns->execute([':nome' => $nomeSemestre]);

        echo json_encode([
            'success' => true,
            'message' => 'Novo ciclo criado e ativado com sucesso!',
            'ciclo_id' => $pdo->lastInsertId()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'criar_grupo') {
        $inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $cicloId = intval($inputData['ciclo_id'] ?? 0);
        $codigoAcesso = trim($inputData['codigo_acesso_unico'] ?? '');
        $tema = trim($inputData['tema'] ?? '');
        $nivelPi = intval($inputData['nivel_pi'] ?? 1);
        $alunosNomes = $inputData['alunos_nomes'] ?? []; // Array de nomes de alunos

        if (!$cicloId || empty($codigoAcesso) || empty($tema)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ciclo, código de acesso e tema são obrigatórios.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pdo->beginTransaction();

        $stmtG = $pdo->prepare('INSERT INTO grupos (ciclo_id, codigo_acesso_unico, tema, nivel_pi) VALUES (:ciclo_id, :codigo, :tema, :nivel_pi)');
        $stmtG->execute([
            ':ciclo_id' => $cicloId,
            ':codigo' => $codigoAcesso,
            ':tema' => $tema,
            ':nivel_pi' => $nivelPi
        ]);
        $grupoId = $pdo->lastInsertId();

        // Cadastra e vincula alunos se fornecidos
        if (is_array($alunosNomes)) {
            foreach ($alunosNomes as $nome) {
                $nomeTrim = trim($nome);
                if (!empty($nomeTrim)) {
                    $stmtA = $pdo->prepare('INSERT INTO alunos (nome) VALUES (:nome)');
                    $stmtA->execute([':nome' => $nomeTrim]);
                    $alunoId = $pdo->lastInsertId();

                    $stmtGA = $pdo->prepare('INSERT INTO grupo_alunos (grupo_id, aluno_id, status_aluno) VALUES (:grupo_id, :aluno_id, "ativo")');
                    $stmtGA->execute([':grupo_id' => $grupoId, ':aluno_id' => $alunoId]);
                }
            }
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Novo grupo cadastrado com sucesso!',
            'grupo_id' => $grupoId
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'salvar_professor') {
        $inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $profId = intval($inputData['id'] ?? 0);
        $usuario = trim($inputData['usuario'] ?? '');
        $nome = trim($inputData['nome'] ?? '');
        $senha = trim($inputData['senha'] ?? '');

        if (empty($usuario) || empty($nome)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Usuário e nome são obrigatórios.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($profId > 0) {
            // Edição
            if (!empty($senha)) {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE professores SET usuario = :usuario, nome = :nome, senha_hash = :senha WHERE id = :id');
                $stmt->execute([':usuario' => $usuario, ':nome' => $nome, ':senha' => $senhaHash, ':id' => $profId]);
            } else {
                $stmt = $pdo->prepare('UPDATE professores SET usuario = :usuario, nome = :nome WHERE id = :id');
                $stmt->execute([':usuario' => $usuario, ':nome' => $nome, ':id' => $profId]);
            }
            $msg = 'Professor atualizado com sucesso!';
        } else {
            // Novo cadastro
            if (empty($senha)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Senha é obrigatória para novo cadastro.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO professores (usuario, senha_hash, nome) VALUES (:usuario, :senha, :nome)');
            $stmt->execute([':usuario' => $usuario, ':senha' => $senhaHash, ':nome' => $nome]);
            $msg = 'Novo professor cadastrado com sucesso!';
        }

        echo json_encode(['success' => true, 'message' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'excluir_professor') {
        $inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $profId = intval($inputData['id'] ?? 0);

        if (!$profId || $profId === $_SESSION['prof_id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Não é possível excluir o professor logado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM professores WHERE id = :id');
        $stmt->execute([':id' => $profId]);

        echo json_encode(['success' => true, 'message' => 'Professor removido com sucesso.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'duplicar_grupos') {
        $inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $cicloOrigemId = intval($inputData['ciclo_origem_id'] ?? 0);
        $cicloDestinoId = intval($inputData['ciclo_destino_id'] ?? 0);

        if (!$cicloOrigemId || !$cicloDestinoId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ciclo de origem e destino devem ser selecionados.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmtGruposOrigem = $pdo->prepare('SELECT * FROM grupos WHERE ciclo_id = :ciclo_id');
        $stmtGruposOrigem->execute([':ciclo_id' => $cicloOrigemId]);
        $gruposOrigem = $stmtGruposOrigem->fetchAll();

        if (empty($gruposOrigem)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Nenhum grupo encontrado no ciclo de origem selecionado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pdo->beginTransaction();
        $duplicadosCount = 0;

        foreach ($gruposOrigem as $g) {
            $novoNivel = $g['nivel_pi'] + 1;
            $novoCodigo = $g['codigo_acesso_unico'] . '-PI' . $novoNivel;

            $stmtCheckCod = $pdo->prepare('SELECT COUNT(*) FROM grupos WHERE codigo_acesso_unico = :cod');
            $stmtCheckCod->execute([':cod' => $novoCodigo]);
            if ($stmtCheckCod->fetchColumn() > 0) {
                $novoCodigo = 'PI-' . substr(md5(uniqid()), 0, 6);
            }

            $stmtInsG = $pdo->prepare('
                INSERT INTO grupos (ciclo_id, codigo_acesso_unico, tema, nivel_pi)
                VALUES (:ciclo_id, :codigo, :tema, :nivel_pi)
            ');
            $stmtInsG->execute([
                ':ciclo_id' => $cicloDestinoId,
                ':codigo' => $novoCodigo,
                ':tema' => $g['tema'],
                ':nivel_pi' => $novoNivel
            ]);
            $novoGrupoId = $pdo->lastInsertId();

            $stmtAlunosG = $pdo->prepare('SELECT aluno_id FROM grupo_alunos WHERE grupo_id = :grupo_id AND status_aluno = "ativo"');
            $stmtAlunosG->execute([':grupo_id' => $g['id']]);
            $alunos = $stmtAlunosG->fetchAll();

            $stmtInsGA = $pdo->prepare('INSERT INTO grupo_alunos (grupo_id, aluno_id, status_aluno) VALUES (:grupo_id, :aluno_id, "ativo")');
            foreach ($alunos as $al) {
                $stmtInsGA->execute([
                    ':grupo_id' => $novoGrupoId,
                    ':aluno_id' => $al['aluno_id']
                ]);
            }

            $duplicadosCount++;
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "{$duplicadosCount} grupos copiados com sucesso com incremento de nível de PI!",
            'grupos_copiados' => $duplicadosCount
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ação não reconhecida.'], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno na gestão de ciclos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
