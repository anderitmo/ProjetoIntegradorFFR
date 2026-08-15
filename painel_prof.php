<?php
session_start();
if (!isset($_SESSION['prof_id'])) {
    header('Location: index.php');
    exit;
}
$profNome = $_SESSION['prof_nome'] ?? 'Professor';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Professor - PI Management</title>
    <!-- CDN Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CDN FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <!-- CDN SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- CDN FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-custom {
            background-color: #1e293b;
            border-bottom: 1px solid #334155;
        }
        .nav-tabs .nav-link {
            color: #94a3b8;
            border: none;
            font-weight: 600;
        }
        .nav-tabs .nav-link.active {
            color: #38bdf8;
            background-color: transparent;
            border-bottom: 3px solid #38bdf8;
        }
        .tab-content-card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        .fc {
            background-color: #0f172a;
            color: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
        }
        .fc-theme-standard td, .fc-theme-standard th {
            border-color: #334155 !important;
        }
        .kanban-board-prof {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 1rem;
        }
        .kanban-col-prof {
            flex: 1;
            min-width: 250px;
            background-color: #0f172a;
            border-radius: 8px;
            border: 1px solid #334155;
            padding: 0.85rem;
        }
        .kanban-card-prof {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 6px;
            padding: 0.85rem;
            margin-bottom: 0.75rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-custom px-4">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1 text-info fw-bold">
            <i class="fa-solid fa-chalkboard-user me-2"></i>PI Management | Portal Docente
        </span>
        <div class="d-flex align-items-center gap-3">
            <span class="text-light"><i class="fa-solid fa-user-tie me-1 text-primary"></i> <?php echo htmlspecialchars($profNome); ?></span>
            <a href="index.php" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-right-from-bracket me-1"></i>Sair</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 my-4">
    <ul class="nav nav-tabs" id="profTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-kanban" data-bs-toggle="tab" data-bs-target="#content-kanban" type="button" role="tab"><i class="fa-solid fa-kanban me-2"></i>Kanban Geral</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-agenda" data-bs-toggle="tab" data-bs-target="#content-agenda" type="button" role="tab"><i class="fa-regular fa-calendar-check me-2"></i>Agenda de Bancas</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-ciclos" data-bs-toggle="tab" data-bs-target="#content-ciclos" type="button" role="tab"><i class="fa-solid fa-arrows-rotate me-2"></i>Ciclos e Importação</button>
        </li>
    </ul>

    <div class="tab-content" id="profTabsContent">

        <!-- Aba 1: Kanban Geral -->
        <div class="tab-pane fade show active tab-content-card" id="content-kanban" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="text-light mb-0">Acompanhamento dos Grupos</h5>
                <div class="d-flex gap-2">
                    <select id="select-grupo-prof" class="form-select form-select-sm bg-dark text-light border-secondary" style="width: 250px;">
                        <option value="">-- Carregando grupos --</option>
                    </select>
                    <button class="btn btn-sm btn-outline-info" onclick="carregarKanbanProf()"><i class="fa-solid fa-arrows-rotate me-1"></i>Atualizar</button>
                </div>
            </div>

            <div class="kanban-board-prof">
                <div class="kanban-col-prof">
                    <h6 class="text-danger fw-bold"><i class="fa-regular fa-circle-dot me-1"></i>A Fazer (<span id="c-a_fazer">0</span>)</h6>
                    <div id="pcol-a_fazer"></div>
                </div>
                <div class="kanban-col-prof">
                    <h6 class="text-warning fw-bold"><i class="fa-solid fa-spinner me-1"></i>Em Andamento (<span id="c-em_andamento">0</span>)</h6>
                    <div id="pcol-em_andamento"></div>
                </div>
                <div class="kanban-col-prof">
                    <h6 class="text-primary fw-bold"><i class="fa-solid fa-magnifying-glass me-1"></i>Revisão (<span id="c-revisao">0</span>)</h6>
                    <div id="pcol-revisao"></div>
                </div>
                <div class="kanban-col-prof">
                    <h6 class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Concluído (<span id="c-concluido">0</span>)</h6>
                    <div id="pcol-concluido"></div>
                </div>
            </div>
        </div>

        <!-- Aba 2: Agenda de Bancas -->
        <div class="tab-pane fade tab-content-card" id="content-agenda" role="tabpanel">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card bg-dark text-light border-secondary">
                        <div class="card-header border-secondary fw-bold text-info"><i class="fa-solid fa-wand-magic-sparkles me-2"></i>Distribuir Agendamento Automático</div>
                        <div class="card-body">
                            <form id="form-distribuir-agenda" onsubmit="return false;">
                                <div class="mb-3">
                                    <label class="form-label small">Data de Início (Período)</label>
                                    <input type="date" class="form-control form-control-sm bg-secondary text-light border-0" id="agenda_inicio" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Data de Fim (Período)</label>
                                    <input type="date" class="form-control form-control-sm bg-secondary text-light border-0" id="agenda_fim" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Grupos por Sábado</label>
                                    <input type="number" class="form-control form-control-sm bg-secondary text-light border-0" id="agenda_grupos_sabado" value="3" min="1">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Feriados a Excluir (Separados por vírgula: YYYY-MM-DD)</label>
                                    <input type="text" class="form-control form-control-sm bg-secondary text-light border-0" id="agenda_feriados" placeholder="2025-04-19, 2025-05-03">
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="agenda_limpar_futuros">
                                    <label class="form-check-label small" for="agenda_limpar_futuros">
                                        Limpar agendas futuras antes de recalcular
                                    </label>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm w-100" id="btn-gerar-agenda"><i class="fa-solid fa-calendar-plus me-1"></i>Calcular e Distribuir Bancas</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

        <!-- Aba 3: Ciclos e Importação -->
        <div class="tab-pane fade tab-content-card" id="content-ciclos" role="tabpanel">
            <div class="row">
                <div class="col-md-5 mb-4">
                    <div class="card bg-dark text-light border-secondary mb-4">
                        <div class="card-header border-secondary fw-bold text-info"><i class="fa-solid fa-plus-circle me-2"></i>Criar Novo Ciclo / Semestre</div>
                        <div class="card-body">
                            <div class="input-group">
                                <input type="text" id="novo_semestre_nome" class="form-control bg-secondary text-light border-0" placeholder="Ex: 2025.2">
                                <button class="btn btn-success" id="btn-criar-ciclo">Criar e Ativar</button>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-dark text-light border-secondary">
                        <div class="card-header border-secondary fw-bold text-info"><i class="fa-solid fa-copy me-2"></i>Copiar Grupos de Ciclo Anterior</div>
                        <div class="card-body">
                            <p class="small text-muted mb-2">Clona todos os grupos de um ciclo passado para o ciclo atual, incrementando automaticamente o <strong>Nível do PI (Ex: PI 1 -> PI 2)</strong>.</p>
                            <div class="mb-3">
                                <label class="form-label small">Ciclo de Origem (Passado)</label>
                                <select id="select-ciclo-origem" class="form-select form-select-sm bg-secondary text-light border-0"></select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Ciclo de Destino (Novo)</label>
                                <select id="select-ciclo-destino" class="form-select form-select-sm bg-secondary text-light border-0"></select>
                            </div>
                            <button class="btn btn-warning btn-sm w-100 fw-bold" id="btn-duplicar-grupos"><i class="fa-solid fa-arrow-right-arrow-left me-1"></i>Importar e Incrementar PI</button>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <h5 class="text-light mb-3">Ciclos Cadastrados e Grupos</h5>
                    <div id="container-lista-ciclos"></div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- CDN FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<!-- CDN Day.js -->
<script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>
<!-- CDN SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- CDN Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
let calendarInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    carregarGruposSelect();
    carregarCiclos();
    inicializarCalendar();

    // Re-renderiza o FullCalendar quando a aba de agenda é aberta
    document.getElementById('tab-agenda').addEventListener('shown.bs.tab', () => {
        if (calendarInstance) calendarInstance.render();
    });
});

// --- MÓDULO KANBAN PROFESSOR ---
async function carregarGruposSelect() {
    try {
        const res = await fetch('api/ciclos/gerenciar.php?action=listar');
        const data = await res.json();

        if (data.success) {
            const select = document.getElementById('select-grupo-prof');
            select.innerHTML = '';

            let todosGrupos = [];
            data.ciclos.forEach(c => {
                if (c.grupos) todosGrupos.push(...c.grupos);
            });

            if (todosGrupos.length === 0) {
                select.innerHTML = '<option value="">Nenhum grupo encontrado</option>';
                return;
            }

            todosGrupos.forEach(g => {
                const opt = document.createElement('option');
                opt.value = g.id;
                opt.textContent = `${g.codigo_acesso_unico} - ${g.tema} (PI ${g.nivel_pi})`;
                select.appendChild(opt);
            });

            carregarKanbanProf();
        }
    } catch (err) {
        console.error('Erro ao listar grupos para o professor', err);
    }
}

document.getElementById('select-grupo-prof').addEventListener('change', carregarKanbanProf);

async function carregarKanbanProf() {
    const grupoId = document.getElementById('select-grupo-prof').value;
    if (!grupoId) return;

    try {
        const res = await fetch(`api/tarefas/listar.php?grupo_id=${grupoId}`);
        const data = await res.json();

        if (data.success) {
            renderizarKanbanProf(data.tarefas);
        }
    } catch (err) {
        console.error('Erro ao carregar tarefas no painel docente', err);
    }
}

function renderizarKanbanProf(tarefas) {
    const colunas = ['a_fazer', 'em_andamento', 'revisao', 'concluido'];
    colunas.forEach(st => {
        const container = document.getElementById(`pcol-${st}`);
        container.innerHTML = '';
        const ar = tarefas.filter(t => t.status_kanban === st);
        document.getElementById(`c-${st}`).textContent = ar.length;

        ar.forEach(t => {
            const card = document.createElement('div');
            card.className = 'kanban-card-prof';
            const ultArq = t.arquivos && t.arquivos.length > 0 ? t.arquivos[0] : null;

            card.innerHTML = `
                <div class="fw-bold text-light mb-1">${escapeHtml(t.titulo)}</div>
                <div class="small text-muted mb-2">${escapeHtml(t.descricao || '')}</div>
                ${ultArq ? `
                    <div class="p-2 mb-2 bg-dark rounded border border-secondary small">
                        <div><i class="fa-solid fa-file-pdf text-danger me-1"></i><strong>v${ultArq.versao}</strong> enviada por ${escapeHtml(ultArq.aluno_nome)}</div>
                        <a href="${ultArq.caminho_arquivo}" target="_blank" class="btn btn-link btn-sm text-info p-0 mt-1"><i class="fa-solid fa-download me-1"></i>Baixar Entregável</a>
                    </div>
                ` : ''}
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="badge bg-secondary">${t.data_prazo || 'Sem prazo'}</span>
                    <button class="btn btn-sm btn-outline-warning py-0 px-2" title="Devolver com Ajustes" onclick="devolverAjustes(${t.id})">
                        <i class="fa-solid fa-rotate-left me-1"></i>Devolver
                    </button>
                </div>
            `;
            container.appendChild(card);
        });
    });
}

// Ação de "Devolver com ajustes" -> move status de volta para 'a_fazer'
async function devolverAjustes(tarefaId) {
    const confirm = await Swal.fire({
        title: 'Devolver com ajustes?',
        text: 'A tarefa voltará para o status "A Fazer" no quadro do aluno.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, devolver',
        cancelButtonText: 'Cancelar'
    });

    if (confirm.isConfirmed) {
        try {
            const res = await fetch('api/tarefas/mover.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tarefa_id: tarefaId, status_kanban: 'a_fazer' })
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire('Devolvido!', 'Status atualizado para A Fazer.', 'success');
                carregarKanbanProf();
            }
        } catch (err) {
            Swal.fire('Erro', 'Não foi possível devolver a tarefa.', 'error');
        }
    }
}

// --- MÓDULO AGENDA FULLCALENDAR ---
function inicializarCalendar() {
    const calendarEl = document.getElementById('calendar');
    calendarInstance = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt-br',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
        },
        events: async function(fetchInfo, successCallback, failureCallback) {
            try {
                const res = await fetch('api/agenda/listar.php');
                const data = await res.json();
                if (data.success) {
                    successCallback(data.eventos);
                } else {
                    failureCallback();
                }
            } catch (err) {
                failureCallback(err);
            }
        },
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            const props = info.event.extendedProps;
            Swal.fire({
                title: info.event.title,
                html: `
                    <p><strong>Nível PI:</strong> PI ${props.nivel_pi}</p>
                    <p><strong>Semestre:</strong> ${props.semestre}</p>
                    <p><strong>Data/Hora:</strong> ${dayjs(info.event.start).format('DD/MM/YYYY HH:mm')}</p>
                    <p><a href="${info.event.url}" target="_blank" class="btn btn-sm btn-primary">Acessar Link do Convite</a></p>
                `,
                icon: 'info'
            });
        }
    });
    calendarInstance.render();
}

// Algoritmo de distribuição de agendamento automático
document.getElementById('btn-gerar-agenda').addEventListener('click', async () => {
    const dataInicio = document.getElementById('agenda_inicio').value;
    const dataFim = document.getElementById('agenda_fim').value;
    const gruposPorSabado = document.getElementById('agenda_grupos_sabado').value;
    const feriadosRaw = document.getElementById('agenda_feriados').value;
    const limparFuturos = document.getElementById('agenda_limpar_futuros').checked;

    if (!dataInicio || !dataFim) {
        Swal.fire('Atenção', 'Informe a data inicial e final do período.', 'warning');
        return;
    }

    const feriadosArray = feriadosRaw.split(',').map(s => s.trim()).filter(s => s.length > 0);

    try {
        Swal.fire({ title: 'Distribuindo bancas...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        const res = await fetch('api/agenda/distribuir.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                data_inicio: dataInicio,
                data_fim: dataFim,
                grupos_por_sabado: gruposPorSabado,
                feriados_excluidos: feriadosArray,
                limpar_futuros: limparFuturos
            })
        });
        const data = await res.json();

        if (data.success) {
            Swal.fire('Sucesso!', `${data.message} (${data.total_grupos} grupos alocados em ${data.sabados_utilizados} sábados).`, 'success');
            calendarInstance.refetchEvents();
        } else {
            Swal.fire('Aviso', data.message || 'Não foi possível distribuir.', 'error');
        }
    } catch (err) {
        Swal.fire('Erro', 'Falha na conexão com o servidor.', 'error');
    }
});

// --- MÓDULO CICLOS E IMPORTAÇÃO ---
async function carregarCiclos() {
    try {
        const res = await fetch('api/ciclos/gerenciar.php?action=listar');
        const data = await res.json();

        if (data.success) {
            const container = document.getElementById('container-lista-ciclos');
            const selOrigem = document.getElementById('select-ciclo-origem');
            const selDestino = document.getElementById('select-ciclo-destino');

            container.innerHTML = '';
            selOrigem.innerHTML = '';
            selDestino.innerHTML = '';

            data.ciclos.forEach(c => {
                // Preenche selects
                const opt1 = new Option(`${c.nome_semestre} ${c.status_ativo == 1 ? '(Ativo)' : ''}`, c.id);
                const opt2 = new Option(`${c.nome_semestre} ${c.status_ativo == 1 ? '(Ativo)' : ''}`, c.id);
                selOrigem.add(opt1);
                selDestino.add(opt2);

                // Renderiza card do ciclo
                const div = document.createElement('div');
                div.className = 'card bg-dark text-light border-secondary mb-3';
                div.innerHTML = `
                    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                        <strong class="text-info">${c.nome_semestre}</strong>
                        ${c.status_ativo == 1 ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>'}
                    </div>
                    <div class="card-body">
                        <h6>Grupos Cadastrados (${c.grupos ? c.grupos.length : 0}):</h6>
                        <ul class="list-group list-group-flush">
                            ${c.grupos && c.grupos.length > 0 ? c.grupos.map(g => `
                                <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between py-1 px-0">
                                    <span><strong>${escapeHtml(g.codigo_acesso_unico)}</strong> - ${escapeHtml(g.tema)}</span>
                                    <span class="badge bg-primary">Nível ${g.nivel_pi}</span>
                                </li>
                            `).join('') : '<li class="list-group-item bg-dark text-muted border-secondary py-1 px-0">Nenhum grupo vinculado.</li>'}
                        </ul>
                    </div>
                `;
                container.appendChild(div);
            });
        }
    } catch (err) {
        console.error('Erro ao carregar ciclos', err);
    }
}

// Criar Novo Ciclo
document.getElementById('btn-criar-ciclo').addEventListener('click', async () => {
    const nome = document.getElementById('novo_semestre_nome').value.trim();
    if (!nome) {
        Swal.fire('Atenção', 'Informe o nome do semestre (Ex: 2025.2).', 'warning');
        return;
    }

    try {
        const res = await fetch('api/ciclos/gerenciar.php?action=criar_ciclo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome_semestre: nome })
        });
        const data = await res.json();

        if (data.success) {
            Swal.fire('Sucesso!', 'Novo ciclo criado e ativado com sucesso.', 'success');
            document.getElementById('novo_semestre_nome').value = '';
            carregarCiclos();
            carregarGruposSelect();
        } else {
            Swal.fire('Erro', data.message || 'Falha ao criar ciclo.', 'error');
        }
    } catch (err) {
        Swal.fire('Erro', 'Erro na requisição ao servidor.', 'error');
    }
});

// Duplicar/Importar Grupos
document.getElementById('btn-duplicar-grupos').addEventListener('click', async () => {
    const origemId = document.getElementById('select-ciclo-origem').value;
    const destinoId = document.getElementById('select-ciclo-destino').value;

    if (origemId === destinoId) {
        Swal.fire('Atenção', 'O ciclo de origem e destino devem ser diferentes.', 'warning');
        return;
    }

    try {
        const res = await fetch('api/ciclos/gerenciar.php?action=duplicar_grupos', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ciclo_origem_id: origemId, ciclo_destino_id: destinoId })
        });
        const data = await res.json();

        if (data.success) {
            Swal.fire('Sucesso!', data.message, 'success');
            carregarCiclos();
            carregarGruposSelect();
        } else {
            Swal.fire('Erro', data.message || 'Falha ao copiar grupos.', 'error');
        }
    } catch (err) {
        Swal.fire('Erro', 'Erro ao processar importação.', 'error');
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
