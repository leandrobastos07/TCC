<?php
require_once __DIR__ . '/../config/funcoes.php';
exigir_admin();
$marca = marca();
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_token();
    $nome = trim((string)($_POST['nome'] ?? ''));
    $slogan = trim((string)($_POST['slogan'] ?? ''));
    $local = trim((string)($_POST['local'] ?? ''));
    $cor = trim((string)($_POST['cor_principal'] ?? ''));
    if ($nome === '' || mb_strlen($nome) > 70 || mb_strlen($slogan) > 130 || mb_strlen($local) > 90 || !preg_match('/^#[0-9a-fA-F]{6}$/', $cor)) {
        $erro = 'Revise nome, slogan, região e cor escolhida.';
    }
    $novo = array_merge($marca, [
        'nome'=>$nome,'slogan'=>$slogan,'local'=>$local,
        'mostrar_nome'=>isset($_POST['mostrar_nome']), 'cor_principal'=>$cor
    ]);
    foreach (['logo'=>'logo','sobreposicao'=>'sobreposicao','banner'=>'banner'] as $campo=>$rotulo) {
        if ($erro) break;
        if (isset($_POST['remover_' . $campo])) $novo[$campo] = '';
        $arquivo = $_FILES[$campo] ?? null;
        if (!$arquivo || $arquivo['error'] === UPLOAD_ERR_NO_FILE) continue;
        if ($arquivo['error'] !== UPLOAD_ERR_OK || $arquivo['size'] > 3*1024*1024) {
            $erro = 'Cada imagem precisa ter no máximo 3 MB.'; break;
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
        $tipos = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if (!isset($tipos[$mime]) || @getimagesize($arquivo['tmp_name']) === false) {
            $erro = 'Envie apenas imagens JPG, PNG ou WebP válidas.'; break;
        }
        $nomeArquivo = bin2hex(random_bytes(10)) . '.' . $tipos[$mime];
        $pasta = __DIR__ . '/../uploads/marca';
        if (!is_dir($pasta) && !mkdir($pasta, 0755, true)) { $erro = 'Não foi possível criar a pasta da marca.'; break; }
        if (!move_uploaded_file($arquivo['tmp_name'], $pasta . '/' . $nomeArquivo)) {
            $erro = 'Falha ao salvar a imagem da marca.'; break;
        }
        $novo[$campo] = 'uploads/marca/' . $nomeArquivo;
    }
    if (!$erro) {
        $json = json_encode($novo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || file_put_contents(__DIR__ . '/../config/marca.json', $json, LOCK_EX) === false) {
            $erro = 'Não foi possível gravar config/marca.json. Verifique as permissões.';
        } else {
            mensagem('Marca personalizada! Atualize a página inicial para conferir.');
            redirecionar('painel/marca.php');
        }
    }
    $marca = $novo;
}
$titulo = 'Personalizar marca';
require __DIR__ . '/_topo.php';
?>
<p>Altere nome, logotipo, selo sobreposto e imagem do banner sem mexer no código. A mudança aparece no cabeçalho, rodapé e título do navegador.</p>
<?php if ($erro): ?><div class="alerta alerta-erro"><?= h($erro) ?></div><?php endif; ?>
<div class="duas-colunas marca-editor">
 <form class="bloco-branco formulario-grande" method="post" enctype="multipart/form-data">
  <?php campo_token(); ?>
  <h2>Nome e identidade</h2>
  <label for="nome">Nome da marca *</label><input id="nome" name="nome" maxlength="70" value="<?= h($marca['nome']) ?>" required>
  <label for="slogan">Frase de apresentação</label><input id="slogan" name="slogan" maxlength="130" value="<?= h($marca['slogan']) ?>">
  <label for="local">Localização abaixo da marca</label><input id="local" name="local" maxlength="90" value="<?= h($marca['local']) ?>">
  <label class="caixa-check"><input type="checkbox" name="mostrar_nome" <?= !empty($marca['mostrar_nome'])?'checked':'' ?>> Exibir nome ao lado do logotipo</label>
  <label for="cor_principal">Cor principal</label><input id="cor_principal" name="cor_principal" type="color" value="<?= h($marca['cor_principal']) ?>">
  <hr>
  <h2>Imagens da marca</h2>
  <label for="logo">Logotipo principal (substitui o ícone da casa)</label>
  <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp">
  <?php if (imagem_marca('logo')): ?><img class="preview-imagem" src="<?= h(imagem_marca('logo')) ?>" alt="Logotipo atual"><label class="caixa-check"><input type="checkbox" name="remover_logo"> Voltar ao ícone padrão</label><?php endif; ?>
  <label for="sobreposicao">Imagem por cima do logotipo (opcional)</label>
  <input id="sobreposicao" name="sobreposicao" type="file" accept="image/png,image/jpeg,image/webp">
  <p class="texto-pequeno">Exemplo: um símbolo ou pequeno selo PNG transparente, colocado por cima no canto inferior direito do logotipo principal.</p>
  <?php if (imagem_marca('sobreposicao')): ?><img class="preview-imagem" src="<?= h(imagem_marca('sobreposicao')) ?>" alt="Imagem sobreposta"><label class="caixa-check"><input type="checkbox" name="remover_sobreposicao"> Remover imagem sobreposta</label><?php endif; ?>
  <label for="banner">Imagem de fundo da página inicial (opcional)</label>
  <input id="banner" name="banner" type="file" accept="image/png,image/jpeg,image/webp">
  <?php if (imagem_marca('banner')): ?><img class="preview-banner" src="<?= h(imagem_marca('banner')) ?>" alt="Banner atual"><label class="caixa-check"><input type="checkbox" name="remover_banner"> Remover banner</label><?php endif; ?>
  <button type="submit" class="botao">Salvar personalização</button>
 </form>
 <aside class="bloco-branco marca-ajuda">
  <h2>Como funciona</h2>
  <p><b>Logotipo:</b> aparece no topo e no rodapé.</p>
  <p><b>Imagem sobreposta:</b> fica por cima do logotipo, no canto inferior direito.</p>
  <p><b>Nome:</b> o texto do site muda automaticamente.</p>
  <p><b>Banner:</b> troca a imagem de fundo da seção principal.</p>
  <p><b>Arquivos:</b> os textos estão em <code>config/marca.json</code> e as imagens enviadas em <code>uploads/marca/</code>.</p>
  <div class="preview-marca"><span class="marca"> <?php logo_marca(); ?> </span></div>
  <p class="texto-pequeno">A prévia mostra a marca salva. Para visualizar as mudanças que você está preenchendo, salve primeiro.</p>
 </aside>
</div>
<?php require __DIR__ . '/_rodape.php'; ?>
