<?php
// Endpoint: api/agenda/distribuir.php
// Recebe via POST: data_inicio, data_fim, grupos_por_sabado, feriados_excluidos (array de datas), e limpar_futuros (bool).
// Usa a classe DateTime do PHP para encontrar todos os sábados no período.
// Remove os sábados que caírem no array de feriados.
// Busca todos os grupos do ciclo ativo e distribui-os (ex: 3 por sábado) fazendo INSERT na tabela agenda_bancas.
// Se limpar_futuros=true, faz DELETE nas agendas futuras antes de recalcular.

require_once __DIR__ . '/../config/auth_check.php';
verificar_autenticacao('prof');
require_once __DIR__ . '/../config/database.php';

$inputData = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$dataInicioStr = $inputData['data_inicio'] ?? '';
$dataFimStr = $inputData['data_fim'] ?? '';
$gruposPorSabado = intval($inputData['grupos_por_sabado'] ?? 3);
$feriadosExcluidos = $inputData['feriados_excluidos'] ?? [];
$limparFuturos = !empty($inputData['limpar_futuros']) && ($inputData['limpar_futuros'] === true || $inputData['limpar_futuros'] === 'true' || $inputData['limpar_futuros'] == 1);

if ($gruposPorSabado <= 0) {
    $gruposPorSabado = 3;
}

if (!is_array($feriadosExcluidos)) {
    $feriadosExcluidos = [];
}

// Normaliza array de feriados para formato Y-m-d
$feriadosSet = [];
foreach ($feriadosExcluidos as $f) {
    $dt = DateTime::createFromFormat('Y-m-d', trim($f));
    if ($dt) {
        $feriadosSet[$dt->format('Y-m-d')] = true;
    }
}

if (empty($dataInicioStr) || empty($dataFimStr)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datas de início e fim são obrigatórias.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $dtInicio = new DateTime($dataInicioStr);
    $dtFim = new DateTime($dataFimStr);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formato de data inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($dtInicio > $dtFim) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A data inicial deve ser anterior ou igual à data final.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Se solicitado, limpa agendamentos futuros
    if ($limparFuturos) {
        $stmtDel = $pdo->prepare('DELETE FROM agenda_bancas WHERE data_apresentacao >= :data_inicio');
        $stmtDel->execute([':data_inicio' => $dtInicio->format('Y-m-d 00:00:00')]);
    }

    // Encontra todos os sábados no intervalo informado usando DateTime
    $sabadosValidos = [];
    $intervalo = new DateInterval('P1D');
    $periodo = new DatePeriod($dtInicio, $intervalo, $dtFim->modify('+1 day'));

    foreach ($periodo as $data) {
        // 6 representa Sábado em format('N') (1 = Segunda, 7 = Domingo)
        if ($data->format('N') == 6) {
            $dataStr = $data->format('Y-m-d');
            if (!isset($feriadosSet[$dataStr])) {
                $sabadosValidos[] = clone $data;
            }
        }
    }

    if (empty($sabadosValidos)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Nenhum sábado disponível encontrado no período informado após filtrar feriados.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Busca grupos do ciclo ativo (status_ativo = 1)
    $stmtGrupos = $pdo->query('
        SELECT g.id, g.tema, g.codigo_acesso_unico
        FROM grupos g
        JOIN ciclos c ON g.ciclo_id = c.id
        WHERE c.status_ativo = 1
        ORDER BY g.id ASC
    ');
    $grupos = $stmtGrupos->fetchAll();

    if (empty($grupos)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Nenhum grupo ativo foi encontrado no ciclo atual para agendamento.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Algoritmo de distribuição das bancas
    $agendamentosCriados = [];
    $horariosDia = ['08:30:00', '10:00:00', '11:30:00', '14:00:00', '15:30:00', '17:00:00'];

    $indexSabado = 0;
    $contadorNoSabado = 0;

    $stmtInsert = $pdo->prepare('
        INSERT INTO agenda_bancas (grupo_id, data_apresentacao, link_convite_externo)
        VALUES (:grupo_id, :data_apresentacao, :link_convite)
    ');

    foreach ($grupos as $grupo) {
        if ($indexSabado >= count($sabadosValidos)) {
            // Se ultrapassar os sábados disponíveis, usa o último sábado ou encerra
            $indexSabado = count($sabadosValidos) - 1;
        }

        $sabadoAtual = $sabadosValidos[$indexSabado];
        $horarioIndex = $contadorNoSabado % count($horariosDia);
        $horarioStr = $horariosDia[$horarioIndex];

        $dataHoraApresentacao = $sabadoAtual->format('Y-m-d') . ' ' . $horarioStr;
        $linkExterno = 'https://meet.jit.si/PI-Banca-' . $grupo['codigo_acesso_unico'];

        // Remove agendamentos antigos existentes deste grupo se houver
        $stmtDelGrupo = $pdo->prepare('DELETE FROM agenda_bancas WHERE grupo_id = :grupo_id');
        $stmtDelGrupo->execute([':grupo_id' => $grupo['id']]);

        // Insere a nova banca
        $stmtInsert->execute([
            ':grupo_id' => $grupo['id'],
            ':data_apresentacao' => $dataHoraApresentacao,
            ':link_convite' => $linkExterno
        ]);

        $agendamentosCriados[] = [
            'grupo_id' => $grupo['id'],
            'tema' => $grupo['tema'],
            'data_apresentacao' => $dataHoraApresentacao,
            'link' => $linkExterno
        ];

        $contadorNoSabado++;
        if ($contadorNoSabado >= $gruposPorSabado) {
            $contadorNoSabado = 0;
            $indexSabado++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Agendamento de bancas distribuído com sucesso!',
        'total_grupos' => count($grupos),
        'sabados_utilizados' => min(count($sabadosValidos), ceil(count($grupos) / $gruposPorSabado)),
        'agendamentos' => $agendamentosCriados
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno ao distribuir bancas: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
