<?php
require_once __DIR__ . '/../config/funcoes.php';
exigir_admin();
$erro = '';
$editarId = filter_input(INPUT_GET,'editar',FILTER_VALIDATE_INT) ?: 0;
$editar = ['nome'=>'','email'=>'','tipo'=>'corretor'];
if ($editarId) {
    $q = db()->prepare("SELECT id_usuario,nome,email,tipo FROM usuarios WHERE id_usuario=? AND tipo IN ('administrador','corretor')");
    $q->execute([$editarId]);
    $registroEquipe = $q->fetch();
    if (!$registroEquipe) redirecionar('painel/usuarios.php');
    $editar = $registroEquipe;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_token();
    $acao = (string)($_POST['acao'] ?? '');
    $id = filter_var($_POST['id'] ?? null,FILTER_VALIDATE_INT) ?: 0;
    if ($id) {
        $verificacao = db()->prepare("SELECT COUNT(*) FROM usuarios WHERE id_usuario=? AND tipo IN ('administrador','corretor')");
        $verificacao->execute([$id]);
        if (!(int)$verificacao->fetchColumn()) {
            mensagem('Usuario da equipe nao encontrado.', 'erro');
            redirecionar('painel/usuarios.php');
        }
    }
    if ($acao === 'excluir' && $id) {
        if ($id === (int)usuario()['id']) mensagem('Você não pode excluir sua própria conta.', 'erro');
        else {
            db()->prepare('DELETE FROM usuarios WHERE id_usuario=?')->execute([$id]);
            mensagem('Usuário excluído.');
        }
        redirecionar('painel/usuarios.php');
    }
    if ($acao === 'salvar') {
        $editar = ['nome'=>trim((string)($_POST['nome'] ?? '')),
                   'email'=>trim((string)($_POST['email'] ?? '')),
                   'tipo'=>(string)($_POST['tipo'] ?? 'corretor')];
        $senha = (string)($_POST['senha'] ?? '');
        if (mb_strlen($editar['nome']) < 2 || mb_strlen($editar['nome']) > 120 || !filter_var($editar['email'], FILTER_VALIDATE_EMAIL) || !in_array($editar['tipo'],['administrador','corretor'],true) || (!$id && strlen($senha)<8) || ($senha !== '' && strlen($senha)<8)) {
            $erro = 'Preencha os dados e use senha de pelo menos 8 caracteres.';
        } elseif ($id === (int)usuario()['id'] && $editar['tipo'] !== 'administrador') {
            $erro = 'Você não pode tirar o próprio acesso de administrador.';
        } else {
            try {
                if ($id) {
                    if ($senha !== '') {
                        db()->prepare('UPDATE usuarios SET nome=?,email=?,tipo=?,senha=? WHERE id_usuario=?')->execute([$editar['nome'],$editar['email'],$editar['tipo'],password_hash($senha,PASSWORD_DEFAULT),$id]);
                    } else {
                        db()->prepare('UPDATE usuarios SET nome=?,email=?,tipo=? WHERE id_usuario=?')->execute([$editar['nome'],$editar['email'],$editar['tipo'],$id]);
                    }
                } else {
                    db()->prepare('INSERT INTO usuarios (nome,email,tipo,senha) VALUES (?,?,?,?)')->execute([$editar['nome'],$editar['email'],$editar['tipo'],password_hash($senha,PASSWORD_DEFAULT)]);
                }
                mensagem('Usuário salvo com sucesso.');
                redirecionar('painel/usuarios.php');
            } catch (PDOException $e) { $erro = 'Não foi possível salvar. Este e-mail já pode estar cadastrado.'; }
        }
    }
}
$usuarios = db()->query("SELECT id_usuario,nome,email,tipo FROM usuarios WHERE tipo IN ('administrador','corretor') ORDER BY nome")->fetchAll();
$titulo = 'Usuários do sistema';
require __DIR__ . '/_topo.php';
?>
<p>O administrador pode criar contas para outros administradores e corretores.</p>
<form method="post" class="bloco-branco formulario-grande">
 <?php campo_token(); ?><input type="hidden" name="acao" value="salvar"><input type="hidden" name="id" value="<?= $editarId ?>">
 <h2><?= $editarId ? 'Editar usuário' : 'Cadastrar usuário' ?></h2>
 <?php if ($erro): ?><div class="alerta alerta-erro"><?= h($erro) ?></div><?php endif; ?>
 <div class="duas-colunas"><div><label for="nome">Nome *</label><input id="nome" name="nome" maxlength="120" required value="<?= h($editar['nome']) ?>"></div>
 <div><label for="email">E-mail *</label><input id="email" name="email" type="email" maxlength="150" required value="<?= h($editar['email']) ?>"></div>
 <div><label for="tipo">Nível de acesso</label><select id="tipo" name="tipo"><option value="corretor" <?= $editar['tipo']==='corretor'?'selected':'' ?>>Corretor</option><option value="administrador" <?= $editar['tipo']==='administrador'?'selected':'' ?>>Administrador</option></select></div>
 <div><label for="senha">Senha <?= $editarId ? '(deixe vazia para manter)' : '(mínimo 8 caracteres)' ?></label><input id="senha" name="senha" type="password" minlength="8" <?= $editarId?'':'required' ?> autocomplete="new-password"></div></div>
 <div class="botoes-form"><button class="botao" type="submit">Salvar usuário</button><?php if ($editarId): ?><a class="botao botao-borda" href="<?= h(caminho('painel/usuarios.php')) ?>">Cancelar</a><?php endif; ?></div>
</form>
<div class="bloco-branco tabela-area"><h2>Contas cadastradas</h2><table><thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Ações</th></tr></thead><tbody>
<?php foreach ($usuarios as $u): ?><tr><td><?= h($u['nome']) ?></td><td><?= h($u['email']) ?></td><td><?= h($u['tipo']) ?></td><td class="acoes"><a href="<?= h(caminho('painel/usuarios.php?editar=' . $u['id_usuario'])) ?>">Editar</a><?php if ((int)$u['id_usuario'] !== (int)usuario()['id']): ?><form method="post" onsubmit="return confirm('Excluir este usuário?')"><?php campo_token(); ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$u['id_usuario'] ?>"><button type="submit" class="botao-texto perigoso">Excluir</button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php require __DIR__ . '/_rodape.php'; ?>
