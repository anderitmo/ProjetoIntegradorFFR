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
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #f8fafc;
        }
        .login-card {
            background-color: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            padding: 2.5rem;
            max-width: 480px;
            width: 100%;
        }
        .brand-logo {
            font-size: 2rem;
            font-weight: 700;
            color: #38bdf8;
            text-align: center;
            margin-bottom: 0.5rem;
        }
        .sub-title {
            text-align: center;
            color: #94a3b8;
            margin-bottom: 2rem;
        }
        .btn-custom {
            background-color: #0284c7;
            color: #fff;
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-custom:hover {
            background-color: #0369a1;
            color: #fff;
        }
        .toggle-link {
            color: #38bdf8;
            cursor: pointer;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-logo">PI Management</div>
    <div class="sub-title">Gerenciamento de Projetos Integradores</div>

    <!-- Seção de Acesso de Aluno -->
    <div id="secao-aluno">
        <h5 class="mb-3 text-light">Acesso do Aluno</h5>
        <div class="mb-3">
            <label for="codigo_acesso" class="form-label">Código Único da Sala / Grupo</label>
            <div class="input-group">
                <input type="text" class="form-control" id="codigo_acesso" placeholder="Ex: PI2025-G1">
                <button class="btn btn-primary" type="button" id="btn-validar-codigo">Validar</button>
            </div>
        </div>

        <!-- Seleção de Aluno (Exibido após validação) -->
        <div id="container-selecao-aluno" class="d-none mb-3">
            <label for="select_aluno" class="form-label">Selecione seu Nome na Lista</label>
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
        <h5 class="mb-3 text-light">Acesso do Professor</h5>
        <form id="form-login-prof" onsubmit="return false;">
            <div class="mb-3">
                <label for="prof_usuario" class="form-label">Usuário</label>
                <input type="text" class="form-control" id="prof_usuario" required placeholder="Digite seu usuário">
            </div>
            <div class="mb-3">
                <label for="prof_senha" class="form-label">Senha</label>
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
