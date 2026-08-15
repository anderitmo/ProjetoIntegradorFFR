<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PI Management - Portal de Acesso</title>
    <!-- CDN Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CDN SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #0f172a;
            padding: 1rem;
        }
        .login-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            max-width: 440px;
            width: 100%;
        }
        @media (max-width: 576px) {
            .login-card {
                padding: 1.5rem;
            }
        }
        .brand-logo {
            font-size: clamp(1.5rem, 5vw, 1.875rem);
            font-weight: 800;
            color: #0284c7;
            text-align: center;
            letter-spacing: -0.025em;
            margin-bottom: 0.25rem;
        }
        .sub-title {
            text-align: center;
            color: #64748b;
            font-size: clamp(0.85rem, 3.5vw, 0.925rem);
            margin-bottom: 2rem;
        }
        .btn-custom {
            background-color: #0284c7;
            color: #ffffff;
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .btn-custom:hover {
            background-color: #0369a1;
            color: #ffffff;
        }
        .toggle-link {
            color: #0284c7;
            cursor: pointer;
            font-weight: 600;
            text-decoration: underline;
        }
        .form-control, .form-select {
            border-color: #cbd5e1;
            padding: 0.65rem 0.85rem;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-logo">PI Management</div>
    <div class="sub-title">Gerenciamento de Projetos Integradores</div>

    <!-- Seção de Acesso de Aluno -->
    <div id="secao-aluno">
        <h5 class="mb-3 text-dark fw-bold fs-6">Acesso do Aluno</h5>
        <div class="mb-3">
            <label for="codigo_acesso" class="form-label text-secondary small fw-semibold">Código Único da Sala / Grupo</label>
            <div class="d-flex flex-column flex-sm-row gap-2">
                <input type="text" class="form-control" id="codigo_acesso" placeholder="Ex: PI2025-G1">
                <button class="btn btn-outline-primary fw-semibold text-nowrap" type="button" id="btn-validar-codigo">Validar</button>
            </div>
        </div>

        <!-- Seleção de Aluno (Exibido após validação) -->
        <div id="container-selecao-aluno" class="d-none mb-3">
            <label for="select_aluno" class="form-label text-secondary small fw-semibold">Selecione seu Nome na Lista</label>
            <select id="select_aluno" class="form-select mb-3">
                <option value="">-- Escolha seu nome --</option>
            </select>
            <button class="btn btn-custom w-100" id="btn-entrar-aluno">Entrar no Painel do Aluno</button>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">É docente? <span class="toggle-link" id="link-sou-professor">Sou Professor</span></small>
        </div>
    </div>

    <!-- Seção de Login do Professor -->
    <div id="secao-professor" class="d-none">
        <h5 class="mb-3 text-dark fw-bold fs-6">Acesso do Professor</h5>
        <form id="form-login-prof" onsubmit="return false;">
            <div class="mb-3">
                <label for="prof_usuario" class="form-label text-secondary small fw-semibold">Usuário</label>
                <input type="text" class="form-control" id="prof_usuario" required placeholder="Digite seu usuário">
            </div>
            <div class="mb-3">
                <label for="prof_senha" class="form-label text-secondary small fw-semibold">Senha</label>
                <input type="password" class="form-control" id="prof_senha" required placeholder="Digite sua senha">
            </div>
            <button type="submit" class="btn btn-custom w-100 mb-3" id="btn-entrar-prof">Entrar no Painel Docente</button>
        </form>

        <div class="text-center">
            <small class="text-muted">É aluno? <span class="toggle-link" id="link-sou-aluno">Sou Aluno</span></small>
        </div>
    </div>
</div>

<!-- CDN SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- CDN Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
let grupoAtualId = null;

// Alterna visibilidade entre perfis
document.getElementById('link-sou-professor').addEventListener('click', () => {
    document.getElementById('secao-aluno').classList.add('d-none');
    document.getElementById('secao-professor').classList.remove('d-none');
});

document.getElementById('link-sou-aluno').addEventListener('click', () => {
    document.getElementById('secao-professor').classList.add('d-none');
    document.getElementById('secao-aluno').classList.remove('d-none');
});

// Validação do Código de Sala
document.getElementById('btn-validar-codigo').addEventListener('click', async () => {
    const codigo = document.getElementById('codigo_acesso').value.trim();
    if (!codigo) {
        Swal.fire('Atenção', 'Informe o código da sala.', 'warning');
        return;
    }

    try {
        const res = await fetch('api/auth/valida_codigo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ codigo_acesso: codigo })
        });
        const data = await res.json();

        if (data.success) {
            grupoAtualId = data.grupo.id;
            const selectAluno = document.getElementById('select_aluno');
            selectAluno.innerHTML = '<option value="">-- Escolha seu nome --</option>';

            data.alunos.forEach(aluno => {
                const opt = document.createElement('option');
                opt.value = aluno.id;
                opt.textContent = aluno.nome;
                selectAluno.appendChild(opt);
            });

            document.getElementById('container-selecao-aluno').classList.remove('d-none');
            Swal.fire({
                icon: 'success',
                title: 'Grupo encontrado!',
                text: `Tema: ${data.grupo.tema} (Nível ${data.grupo.nivel_pi})`,
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire('Erro', data.message || 'Código inválido.', 'error');
        }
    } catch (err) {
        Swal.fire('Erro', 'Não foi possível conectar ao servidor.', 'error');
    }
});

// Login do Aluno
document.getElementById('btn-entrar-aluno').addEventListener('click', async () => {
    const alunoId = document.getElementById('select_aluno').value;
    if (!alunoId || !grupoAtualId) {
        Swal.fire('Atenção', 'Selecione seu nome na lista.', 'warning');
        return;
    }

    try {
        const res = await fetch('api/auth/login_aluno.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ aluno_id: alunoId, grupo_id: grupoAtualId })
        });
        const data = await res.json();

        if (data.success) {
            window.location.href = 'painel_aluno.php';
        } else {
            Swal.fire('Erro', data.message || 'Falha na autenticação.', 'error');
        }
    } catch (err) {
        Swal.fire('Erro', 'Erro ao conectar ao servidor.', 'error');
    }
});

// Login do Professor
document.getElementById('btn-entrar-prof').addEventListener('click', async () => {
    const usuario = document.getElementById('prof_usuario').value.trim();
    const senha = document.getElementById('prof_senha').value.trim();

    if (!usuario || !senha) {
        Swal.fire('Atenção', 'Preencha usuário e senha.', 'warning');
        return;
    }

    try {
        const res = await fetch('api/auth/login_prof.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario, senha })
        });
        const data = await res.json();

        if (data.success) {
            window.location.href = 'painel_prof.php';
        } else {
            Swal.fire('Erro', data.message || 'Usuário ou senha incorretos.', 'error');
        }
    } catch (err) {
        Swal.fire('Erro', 'Erro na conexão com o servidor.', 'error');
    }
});
</script>
</body>
</html>
