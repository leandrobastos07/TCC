<?php
require_once __DIR__ . '/../config/funcoes.php';
exigir_equipe();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_admin(); verificar_token();
    $id = filter_var($_POST['id'] ?? null,FILTER_VALIDATE_INT);
    $status = (string)($_POST['status'] ?? '');
    if ($id && in_array($status, ['Novo','Respondido'],true)) {
        db()->prepare('UPDATE atendimentos SET status=? WHERE id_atendimento=?')->execute([$status,$id]);
        mensagem('Status do atendimento atualizado.');
    }
    redirecionar('painel/atendimentos.php');
}
$pedidos = db()->query('SELECT a.*, c.nome AS cliente, c.email, c.telefone, i.titulo AS imovel FROM atendimentos a JOIN clientes c ON a.id_cliente=c.id_cliente JOIN imoveis i ON a.id_imovel=i.id_imovel ORDER BY a.id_atendimento DESC')->fetchAll();
$titulo = 'Atendimentos';
require __DIR__ . '/_topo.php';
?>
<p>Pedidos de informação enviados pelos visitantes na página de cada imóvel.</p>
<?php if (!$pedidos): ?><div class="bloco-branco"><p>Nenhum atendimento foi solicitado.</p></div><?php endif; ?>
<div class="grade-atendimentos">
<?php foreach ($pedidos as $pedido): ?>
<article class="bloco-branco atendimento-cartao">
 <div class="linha-titulo"><h2><?= h($pedido['cliente']) ?></h2><span class="etiqueta <?= $pedido['status']==='Novo'?'etiqueta-novo':'' ?>"><?= h($pedido['status']) ?></span></div>
 <p><b>Imóvel:</b> <a href="<?= h(caminho('imovel.php?id=' . $pedido['id_imovel'])) ?>"><?= h($pedido['imovel']) ?></a></p>
 <p><b>Data:</b> <?= h(date('d/m/Y',strtotime($pedido['data']))) ?></p>
 <p><b>E-mail:</b> <?= h($pedido['email']) ?> | <b>Telefone:</b> <?= h($pedido['telefone']) ?></p>
 <p class="mensagem-cliente"><?= nl2br(h($pedido['mensagem'])) ?></p>
 <?php if (e_admin()): ?><form method="post" class="acoes-atendimento"><?php campo_token(); ?><input type="hidden" name="id" value="<?= (int)$pedido['id_atendimento'] ?>"><label for="status-<?= (int)$pedido['id_atendimento'] ?>">Situação</label><select name="status" id="status-<?= (int)$pedido['id_atendimento'] ?>"><option <?= $pedido['status']==='Novo'?'selected':'' ?>>Novo</option><option <?= $pedido['status']==='Respondido'?'selected':'' ?>>Respondido</option></select><button type="submit" class="botao botao-pequeno">Atualizar</button></form><?php endif; ?>
</article>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/_rodape.php'; ?>
