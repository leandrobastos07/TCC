<?php
require_once __DIR__ . '/../config/funcoes.php';
exigir_equipe();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$consulta = db()->prepare('SELECT id_imovel, titulo FROM imoveis WHERE id_imovel = ?');
$consulta->execute([$id]);
$imovel = $consulta->fetch();
if (!$imovel) redirecionar('painel/imoveis.php');

// O mesmo site continua funcionando antes de importar a atualização SQL.
$tabelaExiste = (bool) db()->query("SHOW TABLES LIKE 'fotos_imoveis'")->fetchColumn();
$erro = '';
$limiteTotal = 8;
$limitePorEnvio = 2;
$limiteBytes = 3 * 1024 * 1024;

if ($tabelaExiste && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_token();
    $acao = (string)($_POST['acao'] ?? '');
    $banco = db();

    if ($acao === 'enviar') {
        $atuais = fotos_imovel($id);
        $upload = $_FILES['fotos'] ?? [];
        $arquivos = [];
        if (isset($upload['name']) && is_array($upload['name'])) {
            foreach ($upload['name'] as $indice => $nome) {
                if (($upload['error'][$indice] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $arquivos[] = [
                        'nome' => (string)$nome,
                        'temporario' => (string)($upload['tmp_name'][$indice] ?? ''),
                        'tamanho' => (int)($upload['size'][$indice] ?? 0),
                        'erro' => (int)($upload['error'][$indice] ?? UPLOAD_ERR_NO_FILE),
                    ];
                }
            }
        }

        if (!$arquivos) {
            $erro = 'Escolha pelo menos uma foto.';
        } elseif (count($arquivos) > $limitePorEnvio) {
            $erro = 'Envie no máximo 2 fotos por vez.';
        } elseif (count($atuais) + count($arquivos) > $limiteTotal) {
            $erro = 'Esse imóvel pode ter no máximo 8 fotos. Exclua alguma antes de enviar outra.';
        }

        $validos = [];
        if (!$erro) {
            $extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $verificador = new finfo(FILEINFO_MIME_TYPE);
            foreach ($arquivos as $arquivo) {
                if ($arquivo['erro'] !== UPLOAD_ERR_OK) {
                    $erro = 'Não foi possível receber uma das fotos. Confira o limite de upload do PHP.';
                    break;
                }
                if ($arquivo['tamanho'] <= 0 || $arquivo['tamanho'] > $limiteBytes) {
                    $erro = 'Cada foto precisa ter no máximo 3 MB.';
                    break;
                }
                $mime = $verificador->file($arquivo['temporario']);
                if (!isset($extensoes[$mime]) || @getimagesize($arquivo['temporario']) === false) {
                    $erro = 'Envie somente fotos válidas em JPG, PNG ou WebP.';
                    break;
                }
                $validos[] = ['temporario' => $arquivo['temporario'], 'extensao' => $extensoes[$mime]];
            }
        }

        if (!$erro) {
            $pasta = __DIR__ . '/../uploads/imoveis';
            if (!is_dir($pasta) && !mkdir($pasta, 0775, true)) {
                $erro = 'A pasta uploads/imoveis não pôde ser criada.';
            } elseif (!is_writable($pasta)) {
                $erro = 'A pasta uploads/imoveis não tem permissão de escrita.';
            } else {
                $movidos = [];
                try {
                    foreach ($validos as $arquivo) {
                        $nome = bin2hex(random_bytes(12)) . '.' . $arquivo['extensao'];
                        $destino = $pasta . DIRECTORY_SEPARATOR . $nome;
                        if (!move_uploaded_file($arquivo['temporario'], $destino)) {
                            throw new RuntimeException('Não foi possível salvar uma das imagens.');
                        }
                        $movidos[] = ['destino' => $destino, 'arquivo' => 'uploads/imoveis/' . $nome];
                    }
                    $banco->beginTransaction();
                    $inserir = $banco->prepare('INSERT INTO fotos_imoveis (id_imovel, arquivo, principal) VALUES (?, ?, ?)');
                    foreach ($movidos as $indice => $foto) {
                        $principal = (!$atuais && $indice === 0) ? 1 : 0;
                        $inserir->execute([$id, $foto['arquivo'], $principal]);
                    }
                    $banco->commit();
                    mensagem(count($movidos) === 1 ? 'Foto adicionada!' : 'Fotos adicionadas!');
                    redirecionar('painel/fotos-imovel.php?id=' . $id);
                } catch (Throwable $falha) {
                    if ($banco->inTransaction()) $banco->rollBack();
                    foreach ($movidos as $foto) {
                        if (is_file($foto['destino'])) unlink($foto['destino']);
                    }
                    $erro = 'Não foi possível salvar as fotos. Confira as permissões da pasta e tente novamente.';
                }
            }
        }
    } elseif ($acao === 'excluir' || $acao === 'capa') {
        $idFoto = filter_var($_POST['id_foto'] ?? null, FILTER_VALIDATE_INT);
        $consultaFoto = $banco->prepare('SELECT id_foto, arquivo, principal FROM fotos_imoveis WHERE id_foto = ? AND id_imovel = ?');
        $consultaFoto->execute([$idFoto ?: 0, $id]);
        $foto = $consultaFoto->fetch();
        if (!$foto) {
            $erro = 'Foto não encontrada.';
        } else {
            try {
                $banco->beginTransaction();
                if ($acao === 'capa') {
                    $banco->prepare('UPDATE fotos_imoveis SET principal = 0 WHERE id_imovel = ?')->execute([$id]);
                    $banco->prepare('UPDATE fotos_imoveis SET principal = 1 WHERE id_imovel = ? AND id_foto = ?')->execute([$id, $idFoto]);
                } else {
                    $banco->prepare('DELETE FROM fotos_imoveis WHERE id_foto = ? AND id_imovel = ?')->execute([$idFoto, $id]);
                    if ((int)$foto['principal'] === 1) {
                        $proxima = $banco->prepare('SELECT id_foto FROM fotos_imoveis WHERE id_imovel = ? ORDER BY id_foto LIMIT 1');
                        $proxima->execute([$id]);
                        $novoId = $proxima->fetchColumn();
                        if ($novoId) $banco->prepare('UPDATE fotos_imoveis SET principal = 1 WHERE id_foto = ?')->execute([$novoId]);
                    }
                }
                $banco->commit();
                if ($acao === 'excluir' && preg_match('~^uploads/imoveis/[a-f0-9]{24}\.(jpg|png|webp)$~', $foto['arquivo'])) {
                    $caminhoFisico = __DIR__ . '/../' . $foto['arquivo'];
                    if (is_file($caminhoFisico)) unlink($caminhoFisico);
                }
                mensagem($acao === 'capa' ? 'Foto de capa alterada!' : 'Foto excluída!');
                redirecionar('painel/fotos-imovel.php?id=' . $id);
            } catch (PDOException $falha) {
                if ($banco->inTransaction()) $banco->rollBack();
                $erro = 'Não foi possível alterar esta foto.';
            }
        }
    }
}

$fotos = $tabelaExiste ? fotos_imovel($id) : [];
$titulo = 'Fotos do imóvel';
require __DIR__ . '/_topo.php';
?>
<div class="bloco-branco area-fotos">
 <p><a class="voltar" href="<?= h(caminho('painel/imoveis.php')) ?>">← Voltar para imóveis</a></p>
 <h2>Fotos: <?= h($imovel['titulo']) ?></h2>
 <?php if (!$tabelaExiste): ?>
  <div class="alerta alerta-erro">Falta atualizar o banco de dados. No phpMyAdmin, selecione <strong>central_imovel</strong> e importe o arquivo <strong>migracao_fotos.sql</strong>.</div>
 <?php else: ?>
  <?php if ($erro): ?><div class="alerta alerta-erro"><?= h($erro) ?></div><?php endif; ?>
  <p>Este imóvel tem <strong><?= count($fotos) ?> de <?= $limiteTotal ?></strong> fotos na galeria. A foto de capa aparece primeiro no site.</p>
  <form method="post" enctype="multipart/form-data" class="form-enviar-fotos">
   <?php campo_token(); ?>
   <input type="hidden" name="acao" value="enviar">
   <label for="fotos">Escolha até 2 fotos por vez (JPG, PNG ou WebP, até 3 MB cada)</label>
   <input id="fotos" name="fotos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required>
   <button class="botao" type="submit" <?= count($fotos) >= $limiteTotal ? 'disabled' : '' ?>>Enviar fotos</button>
  </form>
  <?php if ($fotos): ?>
   <div class="grade-fotos-painel">
    <?php foreach ($fotos as $foto): ?>
    <div class="item-foto-painel">
     <img src="<?= h(caminho($foto['arquivo'])) ?>" alt="Foto do imóvel" loading="lazy">
     <?php if ((int)$foto['principal'] === 1): ?><p class="marca-capa">★ Foto de capa</p><?php endif; ?>
     <div class="acoes-foto">
      <?php if (!(int)$foto['principal']): ?>
       <form method="post"><?php campo_token(); ?><input type="hidden" name="acao" value="capa"><input type="hidden" name="id_foto" value="<?= (int)$foto['id_foto'] ?>"><button type="submit">Usar como capa</button></form>
      <?php endif; ?>
      <form method="post" onsubmit="return confirm('Excluir esta foto?')"><?php campo_token(); ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id_foto" value="<?= (int)$foto['id_foto'] ?>"><button type="submit" class="perigoso">Excluir</button></form>
     </div>
    </div>
    <?php endforeach; ?>
   </div>
  <?php else: ?><p>Nenhuma foto extra cadastrada ainda. O site continuará mostrando a imagem antiga ou uma ilustração.</p><?php endif; ?>
 <?php endif; ?>
</div>
<style>
.area-fotos {padding:24px;max-width:980px;margin:24px auto 42px}
.area-fotos h2 {margin:12px 0}
.form-enviar-fotos {display:grid;gap:12px;margin:20px 0;padding:18px;background:#f4f7f9;border-radius:12px}
.form-enviar-fotos label {font-weight:700}
.form-enviar-fotos input {width:100%;max-width:100%}
.form-enviar-fotos .botao {justify-self:start}
.grade-fotos-painel {display:grid;grid-template-columns:repeat(auto-fit,minmax(195px,1fr));gap:16px}
.item-foto-painel {border:1px solid #d7e0e5;padding:9px;border-radius:12px;background:#fff}
.item-foto-painel img {width:100%;height:150px;object-fit:cover;border-radius:7px}
.marca-capa {color:#0f766e;font-weight:700;margin:7px 0}
.acoes-foto {display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
.acoes-foto button {background:white;border:1px solid #cbd5e1;border-radius:7px;padding:7px 9px;cursor:pointer}
.acoes-foto .perigoso {color:#b42318}
</style>
<?php require __DIR__ . '/_rodape.php'; ?>
