<?php
// Vitrine da Central do Imóvel: visual de portal de classificados.
// Mantém os mesmos filtros e as mesmas consultas ao banco do projeto.
require_once __DIR__ . '/config/funcoes.php';

$filtros = [
    'finalidade' => trim((string)($_GET['finalidade'] ?? '')),
    'tipo'       => trim((string)($_GET['tipo'] ?? '')),
    'bairro'     => trim((string)($_GET['bairro'] ?? '')),
    'preco'      => trim((string)($_GET['preco'] ?? '')),
];

$sql = 'SELECT * FROM imoveis WHERE 1=1';
$valores = [];
if (in_array($filtros['finalidade'], ['Venda', 'Aluguel'], true)) {
    $sql .= ' AND finalidade = :finalidade';
    $valores['finalidade'] = $filtros['finalidade'];
}
if (in_array($filtros['tipo'], ['Casa', 'Apartamento', 'Terreno', 'Chácara', 'Comercial'], true)) {
    $sql .= ' AND tipo = :tipo';
    $valores['tipo'] = $filtros['tipo'];
}
if ($filtros['bairro'] !== '') {
    $sql .= ' AND bairro LIKE :bairro';
    $valores['bairro'] = '%' . $filtros['bairro'] . '%';
}
if ($filtros['preco'] !== '' && is_numeric($filtros['preco']) && (float)$filtros['preco'] > 0) {
    $sql .= ' AND preco <= :preco';
    $valores['preco'] = (float)$filtros['preco'];
}
$sql .= ' ORDER BY id_imovel DESC';
$consulta = db()->prepare($sql);
$consulta->execute($valores);
$imoveis = $consulta->fetchAll();

// Os atalhos continuam usando os quatro filtros que já existiam.
function link_busca_portal(array $alteracoes = []): string {
    global $filtros;
    $parametros = array_merge($filtros, $alteracoes);
    $parametros = array_filter($parametros, static fn($valor) => $valor !== '');
    return caminho('index.php' . ($parametros ? '?' . http_build_query($parametros) : '') . '#imoveis');
}

$tipos = ['Casa' => 'Casas', 'Apartamento' => 'Apartamentos', 'Terreno' => 'Terrenos', 'Chácara' => 'Chácaras', 'Comercial' => 'Comerciais'];
$bannerMarca = imagem_marca('banner');
$siteMarca = marca();
$total = count($imoveis);
cabecalho('Imóveis em Colombo');
?>
<!-- Menu de classificados: links reais para os filtros existentes. -->
<nav class="portal-menu" aria-label="Navegação dos imóveis">
 <div class="container portal-menu-conteudo">
  <a class="<?= $filtros['finalidade'] === '' ? 'ativo' : '' ?>" href="<?= h(link_busca_portal(['finalidade' => ''])) ?>">Todos os imóveis</a>
  <a class="<?= $filtros['finalidade'] === 'Venda' ? 'ativo' : '' ?>" href="<?= h(link_busca_portal(['finalidade' => 'Venda'])) ?>">Comprar</a>
  <a class="<?= $filtros['finalidade'] === 'Aluguel' ? 'ativo' : '' ?>" href="<?= h(link_busca_portal(['finalidade' => 'Aluguel'])) ?>">Alugar</a>
  <span class="portal-menu-divisoria" aria-hidden="true"></span>
  <a href="<?= h(link_busca_portal(['tipo' => 'Casa'])) ?>">Casas</a>
  <a href="<?= h(link_busca_portal(['tipo' => 'Apartamento'])) ?>">Apartamentos</a>
  <a href="<?= h(link_busca_portal(['tipo' => 'Terreno'])) ?>">Terrenos</a>
  <span class="portal-menu-regiao">Colombo e região</span>
 </div>
</nav>

<main class="pagina-inicial portal-imoveis">
 <section class="portal-abertura" <?= $bannerMarca ? 'style="background-image:linear-gradient(90deg,rgba(24,36,49,.84),rgba(24,36,49,.7)),url(\'' . h($bannerMarca) . '\')"' : '' ?>>
  <div class="container">
   <div class="portal-migalhas"><a href="<?= h(caminho('index.php')) ?>">Início</a><span>›</span> Imóveis <span>›</span> Paraná <span>›</span> Colombo</div>
   <h1>Imóveis em Colombo - PR</h1>
   <p><?= h($siteMarca['slogan']) ?>. Encontre casas, apartamentos e terrenos para compra ou locação.</p>
  </div>
 </section>

 <!-- Busca rápida, sem JavaScript e com os nomes originais dos campos. -->
 <section class="portal-busca-area" aria-label="Pesquisar imóveis">
  <div class="container">
   <div class="portal-busca-atalhos">
    <span>Você está procurando:</span>
    <a href="<?= h(link_busca_portal(['finalidade' => ''])) ?>" class="<?= $filtros['finalidade'] === '' ? 'selecionado' : '' ?>">Todos</a>
    <a href="<?= h(link_busca_portal(['finalidade' => 'Venda'])) ?>" class="<?= $filtros['finalidade'] === 'Venda' ? 'selecionado' : '' ?>">Comprar</a>
    <a href="<?= h(link_busca_portal(['finalidade' => 'Aluguel'])) ?>" class="<?= $filtros['finalidade'] === 'Aluguel' ? 'selecionado' : '' ?>">Alugar</a>
   </div>
   <form class="portal-busca-form" method="get" action="<?= h(caminho('index.php')) ?>#imoveis">
    <input type="hidden" name="finalidade" value="<?= h($filtros['finalidade']) ?>">
    <label><span>Tipo de imóvel</span>
     <select name="tipo" aria-label="Tipo de imóvel">
      <option value="">Todos os tipos</option>
      <?php foreach ($tipos as $tipo => $rotulo): ?><option value="<?= h($tipo) ?>" <?= $filtros['tipo'] === $tipo ? 'selected' : '' ?>><?= h($rotulo) ?></option><?php endforeach; ?>
     </select>
    </label>
    <label><span>Localização / bairro</span><input type="text" name="bairro" placeholder="Ex.: Maracanã, Guaraituba..." value="<?= h($filtros['bairro']) ?>"></label>
    <label><span>Preço até (R$)</span><input type="number" name="preco" min="0" step="1" placeholder="Qualquer valor" value="<?= h($filtros['preco']) ?>"></label>
    <button type="submit" class="portal-busca-botao">⌕ &nbsp; Buscar imóveis</button>
   </form>
  </div>
 </section>

 <div class="container portal-conteudo">
  <aside class="portal-lateral" aria-label="Filtros de pesquisa">
   <form class="portal-filtros" method="get" action="<?= h(caminho('index.php')) ?>#imoveis">
    <div class="portal-filtros-titulo"><h2>Filtros</h2><a href="<?= h(caminho('index.php#imoveis')) ?>">Limpar</a></div>
    <fieldset class="portal-filtro-grupo">
     <legend>Finalidade</legend>
     <label class="portal-radio"><input type="radio" name="finalidade" value="" <?= $filtros['finalidade'] === '' ? 'checked' : '' ?>> Todos os imóveis</label>
     <label class="portal-radio"><input type="radio" name="finalidade" value="Venda" <?= $filtros['finalidade'] === 'Venda' ? 'checked' : '' ?>> Comprar</label>
     <label class="portal-radio"><input type="radio" name="finalidade" value="Aluguel" <?= $filtros['finalidade'] === 'Aluguel' ? 'checked' : '' ?>> Alugar</label>
    </fieldset>
    <div class="portal-filtro-grupo">
     <label class="portal-filtro-legenda" for="filtro-tipo">Tipo de imóvel</label>
     <select id="filtro-tipo" name="tipo">
      <option value="">Todos os tipos</option>
      <?php foreach ($tipos as $tipo => $rotulo): ?><option value="<?= h($tipo) ?>" <?= $filtros['tipo'] === $tipo ? 'selected' : '' ?>><?= h($rotulo) ?></option><?php endforeach; ?>
     </select>
    </div>
    <div class="portal-filtro-grupo">
     <label class="portal-filtro-legenda" for="filtro-bairro">Bairro em Colombo</label>
     <input id="filtro-bairro" name="bairro" placeholder="Digite o bairro" value="<?= h($filtros['bairro']) ?>">
    </div>
    <div class="portal-filtro-grupo">
     <label class="portal-filtro-legenda" for="filtro-preco">Preço máximo</label>
     <input id="filtro-preco" name="preco" type="number" min="0" step="1" placeholder="R$ sem limite" value="<?= h($filtros['preco']) ?>">
    </div>
    <button class="portal-filtro-aplicar" type="submit">Aplicar filtros</button>
    <p class="portal-filtro-rodape">Pesquise gratuitamente. Não é necessário ter conta para ver os anúncios.</p>
   </form>
   <div class="portal-atalhos-laterais">
    <h3>Buscas mais comuns</h3>
    <?php foreach ($tipos as $tipo => $rotulo): ?>
     <a href="<?= h(link_busca_portal(['tipo' => $tipo])) ?>"><?= h($rotulo) ?> em Colombo <span>›</span></a>
    <?php endforeach; ?>
   </div>
  </aside>

  <section class="portal-resultados" id="imoveis" aria-label="Anúncios de imóveis">
   <div class="portal-resultados-cabecalho">
    <div><p class="portal-categoria">RESULTADOS DA PESQUISA</p><h2><?= $total ?> <?= $total === 1 ? 'imóvel encontrado' : 'imóveis encontrados' ?></h2><p>Confira os imóveis cadastrados em Colombo e região.</p></div>
    <a class="portal-limpar" href="<?= h(caminho('index.php#imoveis')) ?>">Limpar pesquisa</a>
   </div>
   <div class="portal-refino" aria-label="Atalhos de filtro">
    <a href="<?= h(link_busca_portal(['tipo' => 'Casa'])) ?>" class="<?= $filtros['tipo'] === 'Casa' ? 'ativo' : '' ?>">Casas</a>
    <a href="<?= h(link_busca_portal(['tipo' => 'Apartamento'])) ?>" class="<?= $filtros['tipo'] === 'Apartamento' ? 'ativo' : '' ?>">Apartamentos</a>
    <a href="<?= h(link_busca_portal(['tipo' => 'Terreno'])) ?>" class="<?= $filtros['tipo'] === 'Terreno' ? 'ativo' : '' ?>">Terrenos</a>
    <a href="<?= h(link_busca_portal(['tipo' => 'Chácara'])) ?>" class="<?= $filtros['tipo'] === 'Chácara' ? 'ativo' : '' ?>">Chácaras</a>
    <a href="<?= h(link_busca_portal(['tipo' => ''])) ?>" class="<?= $filtros['tipo'] === '' ? 'ativo' : '' ?>">Ver todos</a>
   </div>
   <?php if (!$imoveis): ?>
    <div class="portal-sem-resultados"><h3>Nenhum imóvel encontrado</h3><p>Tente selecionar outro tipo de imóvel, bairro ou preço.</p><a href="<?= h(caminho('index.php#imoveis')) ?>">Mostrar todos os imóveis</a></div>
   <?php else: ?>
   <div class="portal-lista">
    <?php foreach ($imoveis as $imovel):
        $urlImovel = caminho('imovel.php?id=' . (int)$imovel['id_imovel']);
        $eVenda = $imovel['finalidade'] === 'Venda';
    ?>
    <article class="portal-anuncio">
     <a class="portal-foto" href="<?= h($urlImovel) ?>" aria-label="Ver <?= h($imovel['titulo']) ?>">
      <img src="<?= h(foto_imovel($imovel)) ?>" alt="Foto do imóvel: <?= h($imovel['titulo']) ?>" loading="lazy">
      <span class="portal-foto-finalidade"><?= $eVenda ? 'VENDA' : 'ALUGUEL' ?></span>
      <span class="portal-foto-referencia">Cód. <?= (int)$imovel['id_imovel'] ?></span>
     </a>
     <div class="portal-anuncio-conteudo">
      <span class="portal-anuncio-tipo"><?= h($imovel['tipo']) ?> · <?= $eVenda ? 'À venda' : 'Para alugar' ?></span>
      <h3><a href="<?= h($urlImovel) ?>"><?= h($imovel['titulo']) ?></a></h3>
      <p class="portal-anuncio-endereco">⌖ <?= h($imovel['bairro']) ?>, <?= h($imovel['cidade']) ?> - PR</p>
      <div class="portal-anuncio-detalhes">
       <?php if ((int)$imovel['area'] > 0): ?><span><b><?= (int)$imovel['area'] ?></b> m²</span><?php endif; ?>
       <?php if ((int)$imovel['quartos'] > 0): ?><span><b><?= (int)$imovel['quartos'] ?></b> <?= (int)$imovel['quartos'] === 1 ? 'quarto' : 'quartos' ?></span><?php endif; ?>
       <?php if ((int)$imovel['banheiros'] > 0): ?><span><b><?= (int)$imovel['banheiros'] ?></b> <?= (int)$imovel['banheiros'] === 1 ? 'banheiro' : 'banheiros' ?></span><?php endif; ?>
       <?php if ((int)$imovel['vagas'] > 0): ?><span><b><?= (int)$imovel['vagas'] ?></b> <?= (int)$imovel['vagas'] === 1 ? 'vaga' : 'vagas' ?></span><?php endif; ?>
      </div>
      <?php if (!empty($imovel['descricao'])): ?><p class="portal-anuncio-descricao"><?= h(trim((string)$imovel['descricao'])) ?></p><?php endif; ?>
     </div>
     <div class="portal-anuncio-preco">
      <span class="portal-anuncio-valor-label"><?= $eVenda ? 'Valor de venda' : 'Valor do aluguel' ?></span>
      <strong><?= h(dinheiro($imovel['preco'])) ?></strong>
      <a class="portal-ver-imovel" href="<?= h($urlImovel) ?>">Ver imóvel</a>
      <a class="portal-contato-link" href="<?= h($urlImovel) ?>">Solicitar atendimento ›</a>
     </div>
    </article>
    <?php endforeach; ?>
   </div>
   <?php endif; ?>
   <div class="portal-buscas-rodape"><h3>Outros imóveis para pesquisar</h3><div>
    <a href="<?= h(link_busca_portal(['finalidade' => 'Venda', 'tipo' => 'Casa'])) ?>">Casas à venda em Colombo</a>
    <a href="<?= h(link_busca_portal(['finalidade' => 'Aluguel', 'tipo' => 'Apartamento'])) ?>">Apartamentos para alugar</a>
    <a href="<?= h(link_busca_portal(['tipo' => 'Terreno'])) ?>">Terrenos em Colombo</a>
   </div></div>
  </section>
 </div>
</main>
<?php rodape(); ?>
