<?php
require_once __DIR__ . '/../config/funcoes.php';
exigir_equipe();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
 // Só o administrador pode excluir, inclusive se tentarem enviar a requisição diretamente.
 exigir_admin();
 verificar_token();
 $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
 if ($id) {
  try {
   $fotosParaRemover = fotos_imovel((int)$id);
   $apagar = db()->prepare('DELETE FROM imoveis WHERE id_imovel=?');
   $apagar->execute([$id]);
   if ($apagar->rowCount()) {
    // O MySQL apaga os registros das fotos automaticamente (ON DELETE CASCADE).
    // Aqui removemos também os arquivos correspondentes, se existirem.
    foreach ($fotosParaRemover as $foto) {
     $arquivo = __DIR__ . '/../' . $foto['arquivo'];
     if (is_file($arquivo)) unlink($arquivo);
    }
    mensagem('Imóvel excluído.');
   } else mensagem('Imóvel não encontrado.');
  } catch (PDOException $e) { mensagem('Não é possível excluir um imóvel com atendimentos associados.', 'erro'); }
 }
 redirecionar('painel/imoveis.php');
}
$imoveis = db()->query('SELECT i.*,c.nome AS corretor FROM imoveis i LEFT JOIN corretores c ON c.id_corretor=i.id_corretor ORDER BY i.id_imovel DESC')->fetchAll();
$titulo = 'Gerenciar imóveis';
require __DIR__ . '/_topo.php'; ?>
<div class="linha-titulo"><p><?= e_admin() ? 'Cadastre, edite ou exclua os anúncios.' : 'Cadastre imóveis, edite informações e organize as fotos. Exclusões ficam com o administrador.' ?></p><a class="botao" href="<?= h(caminho('painel/imovel-form.php')) ?>">+ Novo imóvel</a></div>
<div class="bloco-branco tabela-area"><table><thead><tr><th>Imóvel</th><th>Tipo</th><th>Finalidade</th><th>Preço</th><th>Ações</th></tr></thead><tbody>
<?php foreach ($imoveis as $i): ?><tr><td><?= h($i['titulo']) ?><small><?= h($i['bairro']) ?></small></td><td><?= h($i['tipo']) ?></td><td><?= h($i['finalidade']) ?></td><td><?= h(dinheiro($i['preco'])) ?></td><td class="acoes"><a href="<?= h(caminho('painel/imovel-form.php?id=' . $i['id_imovel'])) ?>">Editar</a> <a href="<?= h(caminho('painel/fotos-imovel.php?id=' . $i['id_imovel'])) ?>">Fotos</a><?php if (e_admin()): ?><form method="post" onsubmit="return confirm('Excluir este imóvel?')"><?php campo_token(); ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$i['id_imovel'] ?>"><button type="submit" class="botao-texto perigoso">Excluir</button></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$imoveis): ?><tr><td colspan="5">Nenhum imóvel cadastrado.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require __DIR__ . '/_rodape.php'; ?>
