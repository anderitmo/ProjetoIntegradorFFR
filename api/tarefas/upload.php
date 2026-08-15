<?php
// Endpoint: api/tarefas/upload.php
// Recebe arquivo via FormData.
// Valida via mime_content_type (PDF ou DOCX).
// Renomeia com md5(uniqid()) e salva na pasta /uploads/.
// INSERT em tarefa_arquivos controlando a coluna versao.

require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$tarefa_id = intval($_POST['tarefa_id'] ?? 0);
$aluno_id = intval($_SESSION['aluno_id'] ?? ($_POST['aluno_id'] ?? 0));

if (!$tarefa_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Identificador da tarefa não fornecido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado ou erro no upload.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tmpPath = $_FILES['arquivo']['tmp_name'];
$originalName = $_FILES['arquivo']['name'];

// Validação estrita via mime_content_type (ignora extensão do cliente)
$mimeType = mime_content_type($tmpPath);

$mimeTypesValidos = [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/msword'
];

if (!in_array($mimeType, $mimeTypesValidos)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Formato de arquivo inválido. Apenas PDF e DOCX são permitidos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Determina extensão segura com base no mime_type real
$extensao = '.pdf';
if ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
    $extensao = '.docx';
} elseif ($mimeType === 'application/msword') {
    $extensao = '.doc';
}

// Gera nome seguro com md5(uniqid())
$novoNome = md5(uniqid(rand(), true)) . $extensao;
$diretorioDestino = __DIR__ . '/../../uploads/';

if (!is_dir($diretorioDestino)) {
    mkdir($diretorioDestino, 0755, true);
}

$caminhoAbsoluto = $diretorioDestino . $novoNome;
$caminhoRelativo = 'uploads/' . $novoNome;

if (!move_uploaded_file($tmpPath, $caminhoAbsoluto)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Falha ao salvar o arquivo no servidor.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Calcula a próxima versão para esta tarefa
    $stmtVer = $pdo->prepare('SELECT COALESCE(MAX(versao), 0) + 1 AS proxima_versao FROM tarefa_arquivos WHERE tarefa_id = :tarefa_id');
    $stmtVer->execute([':tarefa_id' => $tarefa_id]);
    $proximaVersao = (int) $stmtVer->fetchColumn();

    // Inserção na tabela tarefa_arquivos
    $stmtIns = $pdo->prepare('
        INSERT INTO tarefa_arquivos (tarefa_id, aluno_id, caminho_arquivo, versao, data_envio)
        VALUES (:tarefa_id, :aluno_id, :caminho_arquivo, :versao, NOW())
    ');
    $stmtIns->execute([
        ':tarefa_id' => $tarefa_id,
        ':aluno_id' => $aluno_id,
        ':caminho_arquivo' => $caminhoRelativo,
        ':versao' => $proximaVersao
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Arquivo enviado com sucesso.',
        'arquivo' => [
            'id' => $pdo->lastInsertId(),
            'tarefa_id' => $tarefa_id,
            'caminho' => $caminhoRelativo,
            'versao' => $proximaVersao,
            'data_envio' => date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar arquivo no banco de dados.'], JSON_UNESCAPED_UNICODE);
}
