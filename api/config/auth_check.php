<?php
// Middleware de Autenticação baseado em Sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Permite flexibilidade para verificar perfis específicos se solicitado
function verificar_autenticacao($tipo = null) {
    $prof_logado = isset($_SESSION['prof_id']) && !empty($_SESSION['prof_id']);
    $aluno_logado = isset($_SESSION['aluno_id']) && !empty($_SESSION['aluno_id']) && isset($_SESSION['grupo_id']) && !empty($_SESSION['grupo_id']);

    if ($tipo === 'prof') {
        if (!$prof_logado) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Acesso não autorizado. Sessão de professor necessária.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } elseif ($tipo === 'aluno') {
        if (!$aluno_logado) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Acesso não autorizado. Sessão de aluno necessária.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        // Aceita se for professor OU aluno
        if (!$prof_logado && !$aluno_logado) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Acesso não autorizado. Faça login para continuar.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

// Executa verificação padrão ao incluir este arquivo
verificar_autenticacao();
