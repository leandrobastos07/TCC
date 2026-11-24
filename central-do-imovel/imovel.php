<?php
require_once __DIR__ . '/config/funcoes.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$busca = db()->prepare('SELECT i.*, c.nome AS corretor_nome FROM imoveis i LEFT JOIN corretores c ON c.id_corretor = i.id_corretor WHERE i.id_imovel = ?');
$busca->execute([$id ?: 0]);
$imovel = $busca->fetch();
if (!$imovel) { http_response_code(404); cabecalho('Imóvel não encontrado'); echo '<main class="container pagina"><h1>Imóvel não encontrado</h1><p><a href="' . h(caminho()) . '">Voltar aos imóveis</a></p></main>'; rodape(); exit; }
// A foto principal aparece primeiro. A foto antiga é mantida na galeria, se existir.
$galeria = [];
foreach (fotos_imovel((int)$imovel['id_imovel']) as $foto) {
    $galeria[] = caminho($foto['arquivo']);
}
$fotoAntiga = (string)($imovel['imagem'] ?? '');
if (preg_match('~^uploads/[a-zA-Z0-9._-]+$~', $fotoAntiga)) {
    $galeria[] = caminho($fotoAntiga);
}
if (!$galeria) $galeria[] = foto_imovel($imovel);
cabecalho($imovel['titulo']);
?>
<main class="container pagina">
 <p><a class="voltar" href="<?= h(caminho('index.php')) ?>">← Voltar aos imóveis</a></p>
 <div class="detalhe-cabecalho"><div><span class="sobretitulo verde"><?= h($imovel['tipo']) ?> • <?= h($imovel['finalidade']) ?></span><h1><?= h($imovel['titulo']) ?></h1><p class="local">⌖ <?= h($imovel['bairro']) ?>, <?= h($imovel['cidade']) ?> - PR</p></div><strong class="preco-grande"><?= h(dinheiro($imovel['preco'])) ?></strong></div>
 <?php mostrar_mensagem(); ?>
 <div class="detalhe-layout"><div>
  <div class="detalhe-imagem"><img id="foto-principal" src="<?= h($galeria[0]) ?>" alt="Imagem referente ao imóvel: <?= h($imovel['titulo']) ?>"></div>
  <?php if (count($galeria) > 1): ?>
  <div class="galeria-miniaturas" aria-label="Outras fotos do imóvel">
   <?php foreach ($galeria as $indice => $url): ?>
    <button type="button" class="galeria-miniatura<?= $indice === 0 ? ' ativa' : '' ?>" data-foto="<?= h($url) ?>" aria-label="Ver foto <?= $indice + 1 ?>" aria-pressed="<?= $indice === 0 ? 'true' : 'false' ?>">
     <img src="<?= h($url) ?>" alt="Miniatura <?= $indice + 1 ?>" loading="lazy">
    </button>
   <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <div class="bloco-branco"><h2>Características</h2><div class="caracteristicas"><span>▣ <b><?= (int)$imovel['area'] ?></b> m²</span><span>▥ <b><?= (int)$imovel['quartos'] ?></b> quartos</span><span>◫ <b><?= (int)$imovel['banheiros'] ?></b> banheiros</span><span>▤ <b><?= (int)$imovel['vagas'] ?></b> vagas</span></div><h2>Descrição</h2><p class="descricao"><?= nl2br(h($imovel['descricao'] ?: 'Sem descrição cadastrada.')) ?></p><p class="local"><b>Endereço:</b> <?= h($imovel['endereco']) ?>, <?= h($imovel['bairro']) ?>.</p></div>
 </div>
 <aside class="bloco-branco contato-box"><h2>Gostou deste imóvel?</h2><p>Preencha seus dados para solicitar atendimento da imobiliária.</p><form method="post" action="<?= h(caminho('contato.php')) ?>">
 <?php campo_token(); ?><input type="hidden" name="id_imovel" value="<?= (int)$imovel['id_imovel'] ?>">
 <label for="nome">Seu nome</label><input required maxlength="120" id="nome" name="nome" placeholder="Nome completo" value="<?= h(e_visitante() ? usuario()['nome'] : '') ?>">
 <label for="email">Seu e-mail</label><input required type="email" maxlength="150" id="email" name="email" placeholder="seuemail@exemplo.com" value="<?= h(e_visitante() ? usuario()['email'] : '') ?>">
 <label for="telefone">Telefone</label><input maxlength="30" id="telefone" name="telefone" placeholder="(41) 99999-9999">
 <label for="mensagem">Mensagem</label><textarea id="mensagem" maxlength="1500" name="mensagem" rows="4" required>Olá! Tenho interesse neste imóvel e gostaria de mais informações.</textarea>
 <button class="botao largura-total" type="submit">Solicitar atendimento</button></form><p class="nota-contato">Corretor responsável: <?= h($imovel['corretor_nome'] ?: 'A definir') ?></p>
 </aside></div>
</main>
<style>
.galeria-miniaturas {display:flex;flex-wrap:wrap;gap:10px;margin:-8px 0 23px}
.galeria-miniatura {width:90px;height:68px;padding:0;border:3px solid transparent;border-radius:9px;overflow:hidden;background:#eef1f3;cursor:pointer}
.galeria-miniatura.ativa,.galeria-miniatura:focus-visible {border-color:var(--cor-principal)}
.galeria-miniatura img {display:block;width:100%;height:100%;object-fit:cover}
</style>
<script>
// Clicar numa miniatura troca apenas a foto grande; não recarrega a página.
const principal = document.getElementById('foto-principal');
const miniaturas = document.querySelectorAll('.galeria-miniatura');
miniaturas.forEach(function (botao) {
  botao.addEventListener('click', function () {
    principal.src = botao.dataset.foto;
    miniaturas.forEach(function (outra) {
      outra.classList.remove('ativa');
      outra.setAttribute('aria-pressed', 'false');
    });
    botao.classList.add('ativa');
    botao.setAttribute('aria-pressed', 'true');
  });
});
</script>
<?php rodape(); ?>
