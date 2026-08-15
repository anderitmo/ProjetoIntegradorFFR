<?php
session_start();
if (!isset($_SESSION['aluno_id']) || !isset($_SESSION['grupo_id'])) {
    header('Location: index.php');
    exit;
}
$alunoNome = $_SESSION['aluno_nome'] ?? 'Aluno';
$grupoTema = $_SESSION['grupo_tema'] ?? 'Projeto Integrador';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Aluno - Kanban PI</title>
    <!-- CDN Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CDN SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- CDN FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .navbar-custom {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .kanban-board {
            display: flex;
            gap: 1.25rem;
            overflow-x: auto;
            padding-bottom: 1.5rem;
        }
        .kanban-col {
            flex: 1;
            min-width: 280px;
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            max-height: 80vh;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
        }
        .col-header {
            padding: 1rem;
            font-weight: 700;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .col-header.a_fazer { color: #dc2626; border-top: 4px solid #ef4444; border-radius: 12px 12px 0 0; }
        .col-header.em_andamento { color: #d97706; border-top: 4px solid #f59e0b; border-radius: 12px 12px 0 0; }
        .col-header.revisao { color: #2563eb; border-top: 4px solid #3b82f6; border-radius: 12px 12px 0 0; }
        .col-header.concluido { color: #059669; border-top: 4px solid #10b981; border-radius: 12px 12px 0 0; }

        .cards-container {
            padding: 1rem;
            flex-grow: 1;
            overflow-y: auto;
            min-height: 150px;
            background-color: #f8fafc;
        }
        .kanban-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.85rem;
            cursor: grab;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
        }
        .kanban-card:active {
            cursor: grabbing;
        }
        .kanban-card:hover {
            border-color: #38bdf8;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .badge-version {
            background-color: #0284c7;
            font-size: 0.75rem;
        }
        .notes-box {
            background-color: #f1f5f9;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            font-size: 0.825rem;
            color: #334155;
            border-left: 3px solid #0284c7;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light navbar-custom px-4 mb-4">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1 text-primary fw-bold">
            <i class="fa-solid fa-kanban me-2"></i>PI Kanban | <?php echo htmlspecialchars($grupoTema); ?>
        </span>
        <div class="d-flex align-items-center gap-3">
            <span class="text-secondary fw-semibold"><i class="fa-solid fa-user me-1 text-primary"></i> <?php echo htmlspecialchars($alunoNome); ?></span>
            <a href="index.php" class="btn btn-outline-danger btn-sm fw-semibold"><i class="fa-solid fa-right-from-bracket me-1"></i>Sair</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-slate-800">Quadro Kanban do Grupo</h4>
        <button class="btn btn-sm btn-outline-primary fw-semibold" onclick="carregarTarefas()"><i class="fa-solid fa-rotate me-1"></i>Atualizar Quadro</button>
    </div>

    <div class="kanban-board">
        <!-- Coluna: A Fazer -->
        <div class="kanban-col">
            <div class="col-header a_fazer">
                <span><i class="fa-regular fa-circle-dot me-2"></i>A Fazer</span>
                <span class="badge bg-danger rounded-pill" id="count-a_fazer">0</span>
            </div>
            <div class="cards-container" id="col-a_fazer" data-status="a_fazer"></div>
        </div>

        <!-- Coluna: Em Andamento -->
        <div class="kanban-col">
            <div class="col-header em_andamento">
                <span><i class="fa-solid fa-spinner me-2"></i>Em Andamento</span>
                <span class="badge bg-warning text-dark rounded-pill" id="count-em_andamento">0</span>
            </div>
            <div class="cards-container" id="col-em_andamento" data-status="em_andamento"></div>
        </div>

        <!-- Coluna: Revisão -->
        <div class="kanban-col">
            <div class="col-header revisao">
                <span><i class="fa-solid fa-magnifying-glass me-2"></i>Revisão</span>
                <span class="badge bg-primary rounded-pill" id="count-revisao">0</span>
            </div>
            <div class="cards-container" id="col-revisao" data-status="revisao"></div>
        </div>

        <!-- Coluna: Concluído -->
        <div class="kanban-col">
            <div class="col-header concluido">
                <span><i class="fa-solid fa-circle-check me-2"></i>Concluído</span>
                <span class="badge bg-success rounded-pill" id="count-concluido">0</span>
            </div>
            <div class="cards-container" id="col-concluido" data-status="concluido"></div>
        </div>
    </div>
</div>

<!-- Modal Upload de Arquivo e Recados -->
<div class="modal fade" id="modalUpload" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-white text-dark border-0 shadow">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold" id="modalUploadTitle">Entregáveis & Recados da Tarefa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="upload_tarefa_id">
        <div class="mb-3">
            <label class="form-label fw-bold small text-secondary">Recado / Comentário para o Professor:</label>
            <div class="input-group">
                <input type="text" id="input_recado_aluno" class="form-control" placeholder="Deixar uma mensagem no card...">
                <button class="btn btn-primary" type="button" id="btn-salvar-recado"><i class="fa-solid fa-paper-plane me-1"></i>Enviar</button>
            </div>
        </div>

        <hr class="my-3">

        <p class="text-muted small">Formatos permitidos para entregáveis: <strong>PDF</strong> ou <strong>DOCX</strong>.</p>
        <div class="mb-3">
            <label for="input_file" class="form-label fw-bold small text-secondary">Selecione o arquivo:</label>
            <input class="form-control" type="file" id="input_file" accept=".pdf,.docx,.doc">
        </div>
        <div id="historico-versoes" class="mt-3">
            <h6 class="fw-bold small text-secondary">Histórico de Envios:</h6>
            <ul class="list-group list-group-flush" id="lista-versoes"></ul>
        </div>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <button type="button" class="btn btn-primary" id="btn-enviar-arquivo"><i class="fa-solid fa-cloud-arrow-up me-1"></i>Enviar Arquivo</button>
      </div>
    </div>
  </div>
</div>

<!-- CDN SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<!-- CDN SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- CDN Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
let tarefasCache = [];
let sortableInstances = [];
let modalUploadInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    modalUploadInstance = new bootstrap.Modal(document.getElementById('modalUpload'));
    carregarTarefas();
    inicializarSortable();
});

// Carrega tarefas via fetch
async function carregarTarefas() {
    try {
        const res = await fetch('api/tarefas/listar.php');
        const data = await res.json();

        if (data.success) {
            tarefasCache = data.tarefas;
            renderizarKanban();
        } else {
            Swal.fire('Erro', data.message || 'Falha ao carregar tarefas.', 'error');
        }
    } catch (err) {
        Swal.fire('Erro', 'Não foi possível conectar ao servidor.', 'error');
    }
}

// Renderiza os cards nos contêineres do Kanban
function renderizarKanban() {
    const colunas = ['a_fazer', 'em_andamento', 'revisao', 'concluido'];

    colunas.forEach(status => {
        const container = document.getElementById(`col-${status}`);
        container.innerHTML = '';

        const tarefasColuna = tarefasCache.filter(t => t.status_kanban === status);
        document.getElementById(`count-${status}`).textContent = tarefasColuna.length;

        tarefasColuna.forEach(tarefa => {
            container.appendChild(criarCardElement(tarefa));
        });
    });
}

// Cria o elemento HTML de um card de tarefa
function criarCardElement(tarefa) {
    const card = document.createElement('div');
    card.className = 'kanban-card';
    card.dataset.id = tarefa.id;

    const ultimaVersao = tarefa.arquivos && tarefa.arquivos.length > 0 ? tarefa.arquivos[0].versao : 0;

    card.innerHTML = `
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="fw-bold mb-0 text-slate-800">${escapeHtml(tarefa.titulo)}</h6>
            ${ultimaVersao > 0 ? `<span class="badge badge-version">v${ultimaVersao}</span>` : ''}
        </div>
        <p class="small text-secondary mb-2">${escapeHtml(tarefa.descricao || 'Sem descrição')}</p>
        ${tarefa.recado ? `<div class="notes-box mb-2"><i class="fa-solid fa-comment-dots me-1 text-primary"></i>${escapeHtml(tarefa.recado)}</div>` : ''}
        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-primary fw-semibold"><i class="fa-regular fa-calendar me-1"></i>${tarefa.data_prazo || 'Sem prazo'}</small>
            <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="abrirModalUpload(${tarefa.id})">
                <i class="fa-solid fa-comments me-1"></i><i class="fa-solid fa-paperclip"></i>
            </button>
        </div>
    `;
    return card;
}

// Inicializa SortableJS para permitir arrastar e soltar
function inicializarSortable() {
    const colunas = document.querySelectorAll('.cards-container');

    colunas.forEach(col => {
        sortableInstances.push(new Sortable(col, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'bg-light',
            onEnd: async function(evt) {
                const itemEl = evt.item;
                const tarefaId = itemEl.dataset.id;
                const novoStatus = evt.to.dataset.status;
                const statusAntigo = evt.from.dataset.status;

                if (novoStatus === statusAntigo) return;

                try {
                    const res = await fetch('api/tarefas/mover.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ tarefa_id: tarefaId, status_kanban: novoStatus })
                    });
                    const data = await res.json();

                    if (!data.success) {
                        Swal.fire('Erro', data.message || 'Falha ao mover a tarefa.', 'error');
                        carregarTarefas();
                    } else {
                        const task = tarefasCache.find(t => t.id == tarefaId);
                        if (task) task.status_kanban = novoStatus;
                        renderizarKanban();
                    }
                } catch (err) {
                    Swal.fire('Erro', 'Erro ao salvar alteração no servidor.', 'error');
                    carregarTarefas();
                }
            }
        }));
    });
}

// Modal Upload e Histórico de Arquivos + Recados
function abrirModalUpload(tarefaId) {
    document.getElementById('upload_tarefa_id').value = tarefaId;
    document.getElementById('input_file').value = '';

    const tarefa = tarefasCache.find(t => t.id == tarefaId);
    document.getElementById('modalUploadTitle').textContent = `Entregáveis & Recados: ${tarefa ? tarefa.titulo : ''}`;
    document.getElementById('input_recado_aluno').value = tarefa ? (tarefa.recado || '') : '';

    const lista = document.getElementById('lista-versoes');
    lista.innerHTML = '';

    if (tarefa && tarefa.arquivos && tarefa.arquivos.length > 0) {
        tarefa.arquivos.forEach(arq => {
            const li = document.createElement('li');
            li.className = 'list-group-item bg-white text-dark border-bottom d-flex justify-content-between align-items-center py-2 px-0';
            li.innerHTML = `
                <div>
                    <strong>Versão ${arq.versao}</strong> <small class="text-muted">(${arq.aluno_nome} em ${arq.data_envio})</small>
                </div>
                <a href="${arq.caminho_arquivo}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-download me-1"></i>Baixar
                </a>
            `;
            lista.appendChild(li);
        });
    } else {
        lista.innerHTML = '<li class="list-group-item bg-white text-muted border-0 py-2 px-0">Nenhum arquivo enviado ainda.</li>';
    }

    modalUploadInstance.show();
}

// Salvar recado/comentário
document.getElementById('btn-salvar-recado').addEventListener('click', async () => {
    const tarefaId = document.getElementById('upload_tarefa_id').value;
    const recado = document.getElementById('input_recado_aluno').value.trim();

    try {
        const res = await fetch('api/tarefas/mover.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tarefa_id: tarefaId, recado: recado })
        });
        const data = await res.json();

        if (data.success) {
            Swal.fire('Salvo!', 'Recado atualizado com sucesso.', 'success');
            carregarTarefas();
        } else {
            Swal.fire('Erro', data.message || 'Erro ao salvar recado.', 'error');
        }
    } catch (err) {
        Swal.fire('Erro', 'Falha na conexão com o servidor.', 'error');
    }
});

// Enviar Arquivo via FormData
document.getElementById('btn-enviar-arquivo').addEventListener('click', async () => {
    const tarefaId = document.getElementById('upload_tarefa_id').value;
    const fileInput = document.getElementById('input_file');

    if (!fileInput.files || fileInput.files.length === 0) {
        Swal.fire('Atenção', 'Selecione um arquivo PDF ou DOCX.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('tarefa_id', tarefaId);
    formData.append('arquivo', fileInput.files[0]);

    try {
        Swal.fire({ title: 'Enviando arquivo...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const res = await fetch('api/tarefas/upload.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            Swal.fire('Sucesso!', 'Arquivo enviado com sucesso.', 'success');
            modalUploadInstance.hide();
            carregarTarefas();
        } else {
            Swal.fire('Erro no Upload', data.message || 'Formato não permitido.', 'error');
        }
    } catch (err) {
        Swal.fire('Erro', 'Falha ao realizar upload.', 'error');
    }
});

function escapeHtml(text) {
    return text ? text.replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    }) : '';
}
</script>
</body>
</html>
