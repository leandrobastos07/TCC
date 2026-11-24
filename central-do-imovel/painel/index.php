<?php
require_once __DIR__ . '/../config/funcoes.php';
exigir_equipe();

// Administrador vê o panorama geral; corretor vê as funções ligadas ao seu trabalho.
$totais = [];
foreach (['imoveis', 'clientes', 'corretores', 'atendimentos'] as $tabela) {
    $totais[$tabela] = (int) db()->query('SELECT COUNT(*) FROM ' . $tabela)->fetchColumn();
}
$ultimos = db()->query(
    'SELECT a.id_atendimento, a.data, a.status, c.nome AS cliente, i.titulo AS imovel
     FROM atendimentos a
     JOIN clientes c ON c.id_cliente = a.id_cliente
     JOIN imoveis i ON i.id_imovel = a.id_imovel
     ORDER BY a.id_atendimento DESC LIMIT 5'
)->fetchAll();

$titulo = e_admin() ? 'Resumo do sistema' : 'Painel do corretor';
require __DIR__ . '/_topo.php';
?>
<div class="bloco-branco">
 <h2><?= e_admin() ? 'Bem-vindo ao painel administrativo' : 'Bem-vindo à área do corretor' ?></h2>
 <p><?= e_admin()
     ? 'Você pode administrar imóveis, usuários, clientes, corretores, atendimentos e a marca do site.'
     : 'Aqui você pode cadastrar e editar imóveis, organizar as fotos e consultar clientes e atendimentos. Exclusões e configurações ficam com o administrador.' ?></p>
 <div class="botoes-form" style="flex-wrap:wrap">
  <a class="botao" href="<?= h(caminho('painel/imovel-form.php')) ?>">+ Cadastrar imóvel</a>
  <a class="botao botao-borda" href="<?= h(caminho('painel/imoveis.php')) ?>">Ver imóveis</a>
  <a class="botao botao-borda" href="<?= h(caminho('painel/registros.php?tipo=clientes')) ?>">Consultar clientes</a>
 </div>
</div>
<div class="cards-resumo">
 <?php
 $indicadores = e_admin()
     ? ['imoveis'=>'Imóveis', 'clientes'=>'Clientes', 'corretores'=>'Corretores', 'atendimentos'=>'Atendimentos']
     : ['imoveis'=>'Imóveis', 'clientes'=>'Clientes', 'atendimentos'=>'Atendimentos'];
 foreach ($indicadores as $chave => $rotulo): ?>
 <div class="resumo-item"><span><?= h($rotulo) ?></span><strong><?= $totais[$chave] ?></strong></div>
 <?php endforeach; ?>
</div>
<div class="bloco-branco">
 <div class="linha-titulo"><h2>Últimas solicitações</h2><a href="<?= h(caminho('painel/atendimentos.php')) ?>">Ver todas →</a></div>
 <?php if (!$ultimos): ?>
 <p>Nenhum atendimento solicitado ainda.</p>
 <?php else: ?>
 <div class="tabela-area"><table>
  <thead><tr><th>Cliente</th><th>Imóvel</th><th>Data</th><th>Status</th></tr></thead>
  <tbody>
   <?php foreach ($ultimos as $a): ?>
   <tr><td><?= h($a['cliente']) ?></td><td><?= h($a['imovel']) ?></td><td><?= h(date('d/m/Y', strtotime($a['data']))) ?></td><td><?= h($a['status']) ?></td></tr>
   <?php endforeach; ?>
  </tbody>
 </table></div>
 <?php endif; ?>
</div>
<?php require __DIR__ . '/_rodape.php'; ?>
