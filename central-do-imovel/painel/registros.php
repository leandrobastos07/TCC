<?php
require_once __DIR__ . '/../config/funcoes.php';
exigir_equipe();
$tipo = ($_GET['tipo'] ?? '') === 'corretores' ? 'corretores' : 'clientes';
// Corretores podem consultar clientes, mas só administradores gerenciam corretores.
if ($tipo === 'corretores' && !e_admin()) {
    http_response_code(403);
    exit('Apenas o administrador pode acessar o cadastro de corretores.');
}
$config = $tipo === 'corretores'
    ? ['tabela'=>'corretores', 'pk'=>'id_corretor', 'singular'=>'corretor']
    : ['tabela'=>'clientes', 'pk'=>'id_cliente', 'singular'=>'cliente'];
$tabela = $config['tabela']; $pk = $config['pk']; $singular = $config['singular'];
$erro = '';
$editarId = filter_input(INPUT_GET,'editar',FILTER_VALIDATE_INT) ?: 0;
$editar = ['nome'=>'', 'email'=>'', 'telefone'=>''];
if ($editarId) {
    $consulta = db()->prepare("SELECT * FROM $tabela WHERE $pk = ?");
    $consulta->execute([$editarId]);
    $editar = $consulta->fetch() ?: $editar;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_admin();
    verificar_token();
    $acao = (string)($_POST['acao'] ?? '');
    $id = filter_var($_POST['id'] ?? null,FILTER_VALIDATE_INT) ?: 0;
    if ($acao === 'excluir' && $id) {
        try {
            $consulta = db()->prepare("DELETE FROM $tabela WHERE $pk = ?");
            $consulta->execute([$id]);
            mensagem(ucfirst($singular) . ' excluído com sucesso.');
        } catch (PDOException $e) { mensagem('Não é possível excluir esse cadastro porque ele já está em uso.', 'erro'); }
        redirecionar('painel/registros.php?tipo=' . $tipo);
    }
    if ($acao === 'salvar') {
        $editar = ['nome'=>trim((string)($_POST['nome'] ?? '')),
                   'email'=>trim((string)($_POST['email'] ?? '')),
                   'telefone'=>trim((string)($_POST['telefone'] ?? ''))];
        if (mb_strlen($editar['nome']) < 2 || mb_strlen($editar['nome']) > 120 ||
            ($editar['email'] !== '' && !filter_var($editar['email'],FILTER_VALIDATE_EMAIL)) ||
            mb_strlen($editar['email']) > 150 || mb_strlen($editar['telefone']) > 30) {
            $erro = 'Confira nome, e-mail e telefone.';
        } else {
            try {
                if ($id) {
                    $sql = "UPDATE $tabela SET nome=?,email=?,telefone=? WHERE $pk=?";
                    db()->prepare($sql)->execute([$editar['nome'],$editar['email'],$editar['telefone'],$id]);
                } else {
                    $sql = "INSERT INTO $tabela (nome,email,telefone) VALUES (?,?,?)";
                    db()->prepare($sql)->execute([$editar['nome'],$editar['email'],$editar['telefone']]);
                }
                mensagem(ucfirst($singular) . ' salvo com sucesso.');
                redirecionar('painel/registros.php?tipo=' . $tipo);
            } catch (PDOException $e) { $erro = 'Não foi possível salvar o cadastro.'; }
        }
    }
}
$registros = db()->query("SELECT * FROM $tabela ORDER BY nome ASC")->fetchAll();
$titulo = $tipo === 'corretores' ? 'Corretores' : 'Clientes';
require __DIR__ . '/_topo.php';
?>
<div class="linha-titulo"><p>Consulte os dados <?= $tipo === 'clientes' ? 'dos clientes' : 'dos corretores' ?>.</p></div>
<?php if (e_admin()): ?>
 <form method="post" class="bloco-branco formulario-grande">
  <?php campo_token(); ?><input type="hidden" name="acao" value="salvar"><input type="hidden" name="id" value="<?= $editarId ?>">
  <h2><?= $editarId ? 'Editar ' : 'Cadastrar ' ?><?= h($singular) ?></h2>
  <?php if ($erro): ?><div class="alerta alerta-erro"><?= h($erro) ?></div><?php endif; ?>
  <div class="duas-colunas">
   <div><label for="nome">Nome completo *</label><input id="nome" name="nome" required maxlength="120" value="<?= h($editar['nome']) ?>"></div>
   <div><label for="email">E-mail</label><input id="email" name="email" type="email" maxlength="150" value="<?= h($editar['email']) ?>"></div>
   <div><label for="telefone">Telefone</label><input id="telefone" name="telefone" maxlength="30" value="<?= h($editar['telefone']) ?>"></div>
  </div>
  <div class="botoes-form"><button class="botao" type="submit">Salvar</button><?php if ($editarId): ?><a class="botao botao-borda" href="<?= h(caminho('painel/registros.php?tipo=' . $tipo)) ?>">Cancelar edição</a><?php endif; ?></div>
 </form>
<?php endif; ?>
<div class="bloco-branco tabela-area"><h2>Cadastros (<?= count($registros) ?>)</h2><table><thead><tr><th>Nome</th><th>E-mail</th><th>Telefone</th><?php if (e_admin()): ?><th>Ações</th><?php endif; ?></tr></thead><tbody>
<?php foreach ($registros as $r): ?><tr><td><?= h($r['nome']) ?></td><td><?= h($r['email']) ?></td><td><?= h($r['telefone']) ?></td><?php if (e_admin()): ?><td class="acoes"><a href="<?= h(caminho('painel/registros.php?tipo=' . $tipo . '&editar=' . $r[$pk])) ?>">Editar</a><form method="post" onsubmit="return confirm('Excluir este cadastro?')"><?php campo_token(); ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$r[$pk] ?>"><button type="submit" class="botao-texto perigoso">Excluir</button></form></td><?php endif; ?></tr><?php endforeach; ?>
<?php if (!$registros): ?><tr><td colspan="4">Nenhum cadastro encontrado.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require __DIR__ . '/_rodape.php'; ?>
