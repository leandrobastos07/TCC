<?php
require_once __DIR__ . '/../config/funcoes.php';
exigir_equipe();
$id = filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT) ?: 0;
$imovel = ['titulo'=>'','tipo'=>'Casa','finalidade'=>'Venda','endereco'=>'','bairro'=>'','cidade'=>'Colombo','preco'=>'','descricao'=>'','quartos'=>0,'banheiros'=>0,'vagas'=>0,'area'=>0,'imagem'=>'','id_corretor'=>''];
if ($id) {
 $busca = db()->prepare('SELECT * FROM imoveis WHERE id_imovel = ?');
 $busca->execute([$id]);
 $achado = $busca->fetch();
 if (!$achado) redirecionar('painel/imoveis.php');
 $imovel = $achado;
}
$corretores = db()->query('SELECT id_corretor,nome FROM corretores ORDER BY nome')->fetchAll();
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 verificar_token();
 foreach (['titulo','tipo','finalidade','endereco','bairro','cidade','preco','descricao','quartos','banheiros','vagas','area','id_corretor'] as $campo) $imovel[$campo] = trim((string)($_POST[$campo] ?? ''));
 $imovel['id_corretor'] = $imovel['id_corretor'] !== '' ? (int)$imovel['id_corretor'] : null;
 $numeros = ['quartos','banheiros','vagas','area'];
 if ($imovel['titulo'] === '' || mb_strlen($imovel['titulo']) > 180 || !in_array($imovel['tipo'],['Casa','Apartamento','Terreno','Chácara','Comercial'],true) || !in_array($imovel['finalidade'],['Venda','Aluguel'],true) || $imovel['bairro'] === '' || $imovel['cidade'] === '' || $imovel['endereco'] === '' || !is_numeric($imovel['preco']) || (float)$imovel['preco'] <= 0) $erro = 'Preencha o título, tipo, finalidade, endereço, bairro, cidade e preço corretamente.';
 foreach ($numeros as $campo) if (filter_var($imovel[$campo], FILTER_VALIDATE_INT) === false || (int)$imovel[$campo] < 0) $erro = 'Área, quartos, banheiros e vagas devem ser números inteiros não negativos.';
 if (!$erro && !empty($_FILES['imagem']['name'])) {
  $arquivo = $_FILES['imagem'];
  if ($arquivo['error'] !== UPLOAD_ERR_OK || $arquivo['size'] > 3*1024*1024) $erro = 'A imagem deve ter até 3 MB.';
  else {
   $tipoMime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
   $extensoes = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
   if (!isset($extensoes[$tipoMime])) $erro = 'Envie uma imagem JPG, PNG ou WebP.';
   else {
    $nomeArquivo = bin2hex(random_bytes(10)) . '.' . $extensoes[$tipoMime];
    $destino = __DIR__ . '/../uploads/' . $nomeArquivo;
    if (!move_uploaded_file($arquivo['tmp_name'],$destino)) $erro = 'Não foi possível salvar a imagem.';
    else $imovel['imagem'] = 'uploads/' . $nomeArquivo;
   }
  }
 }
 if (!$erro) {
  $dados = [$imovel['titulo'],$imovel['tipo'],$imovel['finalidade'],$imovel['endereco'],$imovel['bairro'],$imovel['cidade'],(float)$imovel['preco'],$imovel['descricao'],(int)$imovel['quartos'],(int)$imovel['banheiros'],(int)$imovel['vagas'],(int)$imovel['area'],$imovel['imagem'] ?: null,$imovel['id_corretor']];
  try {
   if ($id) {
    $comando = db()->prepare('UPDATE imoveis SET titulo=?,tipo=?,finalidade=?,endereco=?,bairro=?,cidade=?,preco=?,descricao=?,quartos=?,banheiros=?,vagas=?,area=?,imagem=?,id_corretor=? WHERE id_imovel=?');
    $dados[] = $id;
   } else $comando = db()->prepare('INSERT INTO imoveis (titulo,tipo,finalidade,endereco,bairro,cidade,preco,descricao,quartos,banheiros,vagas,area,imagem,id_corretor) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
   $comando->execute($dados);
   mensagem($id ? 'Imóvel atualizado!' : 'Imóvel cadastrado!');
   redirecionar('painel/imoveis.php');
  } catch (PDOException $e) { $erro = 'Não foi possível salvar. Verifique se o corretor selecionado existe.'; }
 }
}
$titulo = $id ? 'Editar imóvel' : 'Novo imóvel';
require __DIR__ . '/_topo.php';
?>
<p><a class="voltar" href="<?= h(caminho('painel/imoveis.php')) ?>">← Voltar para imóveis</a></p>
<?php if ($id): ?>
<p><a class="botao botao-borda" href="<?= h(caminho('painel/fotos-imovel.php?id=' . $id)) ?>">📷 Gerenciar fotos deste imóvel</a></p>
<?php else: ?><p class="texto-pequeno">Depois de cadastrar o imóvel, abra a opção <strong>Fotos</strong> na lista para enviar várias imagens.</p><?php endif; ?>
<?php if ($erro): ?><div class="alerta alerta-erro"><?= h($erro) ?></div><?php endif; ?>
<form class="bloco-branco formulario-grande" method="post" enctype="multipart/form-data">
 <?php campo_token(); ?>
 <div class="duas-colunas"><div class="campo-largo"><label for="titulo">Título do anúncio *</label><input id="titulo" name="titulo" value="<?= h($imovel['titulo']) ?>" maxlength="180" required></div>
 <div><label for="tipo">Tipo *</label><select id="tipo" name="tipo"><?php foreach (['Casa','Apartamento','Terreno','Chácara','Comercial'] as $opcao): ?><option <?= $imovel['tipo'] === $opcao ? 'selected' : '' ?>><?= h($opcao) ?></option><?php endforeach; ?></select></div>
 <div><label for="finalidade">Finalidade *</label><select id="finalidade" name="finalidade"><?php foreach (['Venda','Aluguel'] as $opcao): ?><option <?= $imovel['finalidade'] === $opcao ? 'selected' : '' ?>><?= h($opcao) ?></option><?php endforeach; ?></select></div>
 <div><label for="preco">Preço (R$) *</label><input id="preco" name="preco" type="number" step="0.01" min="0.01" value="<?= h($imovel['preco']) ?>" required></div>
 <div><label for="endereco">Endereço *</label><input id="endereco" name="endereco" value="<?= h($imovel['endereco']) ?>" placeholder="Ex.: endereço sob consulta" required></div>
 <div><label for="bairro">Bairro *</label><input id="bairro" name="bairro" value="<?= h($imovel['bairro']) ?>" required></div>
 <div><label for="cidade">Cidade *</label><input id="cidade" name="cidade" value="<?= h($imovel['cidade']) ?>" required></div>
 <div><label for="area">Área em m²</label><input id="area" name="area" type="number" min="0" value="<?= h($imovel['area']) ?>" required></div>
 <div><label for="quartos">Quartos</label><input id="quartos" name="quartos" type="number" min="0" value="<?= h($imovel['quartos']) ?>" required></div>
 <div><label for="banheiros">Banheiros</label><input id="banheiros" name="banheiros" type="number" min="0" value="<?= h($imovel['banheiros']) ?>" required></div>
 <div><label for="vagas">Vagas</label><input id="vagas" name="vagas" type="number" min="0" value="<?= h($imovel['vagas']) ?>" required></div>
 <div><label for="id_corretor">Corretor</label><select id="id_corretor" name="id_corretor"><option value="">Nenhum</option><?php foreach ($corretores as $c): ?><option value="<?= (int)$c['id_corretor'] ?>" <?= (string)$imovel['id_corretor'] === (string)$c['id_corretor'] ? 'selected' : '' ?>><?= h($c['nome']) ?></option><?php endforeach; ?></select></div>
 <div class="campo-largo"><label for="descricao">Descrição do imóvel</label><textarea id="descricao" name="descricao" rows="5"><?= h($imovel['descricao']) ?></textarea></div>
 <div class="campo-largo"><label for="imagem">Imagem única (opcional; se houver fotos na galeria, elas terão prioridade)</label><input id="imagem" name="imagem" type="file" accept="image/jpeg,image/png,image/webp"><?php if ($imovel['imagem']): ?><p class="texto-pequeno">Imagem atual: <?= h($imovel['imagem']) ?></p><?php endif; ?></div>
 </div><button class="botao" type="submit">Salvar imóvel</button>
</form>
<?php require __DIR__ . '/_rodape.php'; ?>
