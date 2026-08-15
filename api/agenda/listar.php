<?php
// Endpoint: api/agenda/listar.php
// Retorna a lista de bancas agendadas em formato compativel com FullCalendar.

require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query('
        SELECT ab.id, ab.grupo_id, ab.data_apresentacao, ab.link_convite_externo,
               g.tema, g.codigo_acesso_unico, g.nivel_pi, c.nome_semestre
        FROM agenda_bancas ab
        JOIN grupos g ON ab.grupo_id = g.id
        JOIN ciclos c ON g.ciclo_id = c.id
        ORDER BY ab.data_apresentacao ASC
    ');
    $bancas = $stmt->fetchAll();

    $eventos = [];
    foreach ($bancas as $banca) {
        $inicio = new DateTime($banca['data_apresentacao']);
        $fim = (clone $inicio)->modify('+1 hour');

        $eventos[] = [
            'id' => $banca['id'],
            'title' => 'Banca PI: ' . $banca['tema'] . ' (' . $banca['codigo_acesso_unico'] . ')',
            'start' => $inicio->format('Y-m-d\TH:i:s'),
            'end' => $fim->format('Y-m-d\TH:i:s'),
            'url' => $banca['link_convite_externo'],
            'extendedProps' => [
                'grupo_id' => $banca['grupo_id'],
                'tema' => $banca['tema'],
                'codigo' => $banca['codigo_acesso_unico'],
                'nivel_pi' => $banca['nivel_pi'],
                'semestre' => $banca['nome_semestre'],
                'link' => $banca['link_convite_externo']
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'eventos' => $eventos
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar agenda de bancas.'], JSON_UNESCAPED_UNICODE);
}
