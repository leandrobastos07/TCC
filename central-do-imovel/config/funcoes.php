<?php
require_once __DIR__ . '/conexao.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function caminho(string $pagina = ''): string { return BASE_URL . ($pagina ? '/' . ltrim($pagina, '/') : ''); }
function h($valor): string { return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8'); }
function dinheiro($valor): string { return 'R$ ' . number_format((float)$valor, 2, ',', '.'); }
function redirecionar(string $pagina): never { header('Location: ' . caminho($pagina)); exit; }
function usuario(): ?array { return $_SESSION['usuario'] ?? null; }
function esta_logado(): bool { return usuario() !== null; }
function e_admin(): bool { return (usuario()['tipo'] ?? '') === 'administrador'; }
function e_visitante(): bool { return (usuario()['tipo'] ?? '') === 'visitante'; }
function e_equipe(): bool { return in_array((usuario()['tipo'] ?? ''), ['administrador','corretor'], true); }
function exigir_login(): void { if (!esta_logado()) redirecionar('login.php'); }
function exigir_equipe(): void {
    if (!esta_logado()) redirecionar('login.php');
    if (!e_equipe()) { http_response_code(403); exit('Acesso reservado a administradores e corretores.'); }
}
function exigir_visitante(): void {
    if (!esta_logado()) redirecionar('login-visitante.php');
    if (!e_visitante()) { http_response_code(403); exit('Esta página é exclusiva para visitantes.'); }
}
function exigir_admin(): void { exigir_login(); if (!e_admin()) { http_response_code(403); exit('Acesso permitido apenas ao administrador.'); } }
function mensagem(string $texto, string $tipo = 'sucesso'): void { $_SESSION['mensagem'] = [$texto, $tipo]; }
function mostrar_mensagem(): void {
    if (empty($_SESSION['mensagem'])) return;
    [$texto, $tipo] = $_SESSION['mensagem']; unset($_SESSION['mensagem']);
    echo '<div class="alerta alerta-' . h($tipo) . '">' . h($texto) . '</div>';
}
function token(): string {
    if (empty($_SESSION['token'])) $_SESSION['token'] = bin2hex(random_bytes(24));
    return $_SESSION['token'];
}
function campo_token(): void { echo '<input type="hidden" name="token" value="' . h(token()) . '">'; }
function verificar_token(): void {
    if (!isset($_POST['token']) || !hash_equals(token(), (string)$_POST['token'])) {
        http_response_code(403); exit('Sessão inválida. Atualize a página e tente novamente.');
    }
}
function marca(): array {
    // ÚNICO LOCAL para os valores padrão da identidade visual.
    $padrao = [
        'nome'=>'Central do Imóvel', 'slogan'=>'Seu próximo endereço começa aqui',
        'local'=>'Colombo • Paraná', 'logo'=>'', 'sobreposicao'=>'',
        'banner'=>'', 'mostrar_nome'=>true, 'cor_principal'=>'#0f766e'
    ];
    $arquivo = __DIR__ . '/marca.json';
    $personalizado = is_file($arquivo) ? json_decode((string)file_get_contents($arquivo), true) : [];
    return array_merge($padrao, is_array($personalizado) ? $personalizado : []);
}
function imagem_marca(string $nome): string {
    $marca = marca();
    $arquivo = (string)($marca[$nome] ?? '');
    if (preg_match('~^uploads/marca/[a-z0-9_-]+\.(png|jpg|webp)$~', $arquivo)) {
        return caminho($arquivo);
    }
    return '';
}
function logo_marca(): void {
    $m = marca(); $logo = imagem_marca('logo'); $selo = imagem_marca('sobreposicao');
    echo '<span class="marca-imagens">';
    if ($logo) echo '<img class="logo-principal" src="' . h($logo) . '" alt="">';
    else echo '<span class="logo-padrao" aria-hidden="true">⌂</span>';
    if ($selo) echo '<img class="logo-sobreposicao" src="' . h($selo) . '" alt="">';
    echo '</span>';
    if (!empty($m['mostrar_nome'])) {
        echo '<span class="marca-textos"><strong>' . h($m['nome']) . '</strong><small>' . h($m['local']) . '</small></span>';
    }
}
// Fotos extras cadastradas no painel. Sem fotos extras, mantemos a imagem antiga.
function fotos_imovel(int $id): array {
    if ($id <= 0) return [];
    try {
        $consulta = db()->prepare('SELECT id_foto, arquivo, principal FROM fotos_imoveis WHERE id_imovel = ? ORDER BY principal DESC, id_foto ASC');
        $consulta->execute([$id]);
        $fotos = $consulta->fetchAll();
    } catch (PDOException $erro) {
        // A vitrine antiga continua abrindo enquanto a migração SQL não for importada.
        if ((int)($erro->errorInfo[1] ?? 0) === 1146) return [];
        throw $erro;
    }
    return array_values(array_filter($fotos, function (array $foto): bool {
        return (bool) preg_match('~^uploads/imoveis/[a-f0-9]{24}\.(jpg|png|webp)$~', (string)$foto['arquivo']);
    }));
}
function foto_imovel(array $imovel): string {
    $fotos = fotos_imovel((int)($imovel['id_imovel'] ?? 0));
    if ($fotos) return caminho($fotos[0]['arquivo']);
    $foto = (string)($imovel['imagem'] ?? '');
    if ($foto && preg_match('/^uploads\/[a-zA-Z0-9._-]+$/', $foto)) return caminho($foto);
    $tipo = mb_strtolower((string)($imovel['tipo'] ?? 'casa'));
    if (str_contains($tipo, 'apartamento')) return caminho('assets/img/apartamento.svg');
    if (str_contains($tipo, 'terreno')) return caminho('assets/img/terreno.svg');
    if (str_contains($tipo, 'comercial')) return caminho('assets/img/comercial.svg');
    return caminho('assets/img/casa.svg');
}
function cabecalho(string $titulo = 'Encontre seu imóvel'): void {
    $m = marca();
    $cor = preg_match('/^#[0-9a-fA-F]{6}$/', (string)$m['cor_principal']) ? $m['cor_principal'] : '#0f766e';
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= h($m['nome']) ?> - encontre casas, apartamentos e terrenos para compra e aluguel em Colombo - PR.">
    <meta name="theme-color" content="<?= h($cor) ?>">
    <title><?= h($titulo) ?> | <?= h($m['nome']) ?></title>
    <link rel="icon" href="<?= h(imagem_marca('logo') ?: caminho('assets/img/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= h(caminho('assets/css/estilo.css')) ?>">
    <style>:root{--cor-principal:<?= h($cor) ?>}</style>
    <script src="<?= h(caminho('assets/js/app.js')) ?>" defer></script>
</head>
<body>
<header class="topo"><div class="container topo-conteudo">
    <a class="marca" href="<?= h(caminho('index.php')) ?>" aria-label="Início: <?= h($m['nome']) ?>"><?php logo_marca(); ?></a>
    <button class="menu-botao" id="menu-botao" type="button" aria-label="Abrir navegação" aria-expanded="false">☰</button>
    <nav class="menu" id="menu">
        <a href="<?= h(caminho('index.php')) ?>">Ver imóveis</a>
        <?php if (e_equipe()): ?>
            <a href="<?= h(caminho('painel/index.php')) ?>">Painel</a>
            <a class="botao botao-pequeno botao-borda" href="<?= h(caminho('logout.php')) ?>">Sair</a>
        <?php elseif (e_visitante()): ?>
            <a href="<?= h(caminho('minha-conta.php')) ?>">Minha conta</a>
            <a class="botao botao-pequeno botao-borda" href="<?= h(caminho('logout.php')) ?>">Sair</a>
        <?php else: ?>
            <a class="botao botao-pequeno" href="<?= h(caminho('acesso.php')) ?>">Entrar / Criar conta</a>
        <?php endif; ?>
    </nav>
</div></header>
<?php }
function rodape(): void { $m = marca(); ?>
<footer class="rodape">
    <div class="container rodape-conteudo">
        <div class="rodape-marca">
            <a class="marca marca-rodape" href="<?= h(caminho('index.php')) ?>"><?php logo_marca(); ?></a>
            <p>Casas, apartamentos, terrenos e outros imóveis em <?= h($m['local']) ?>.</p>
            <p class="rodape-copyright">&copy; <?= date('Y') ?> <?= h($m['nome']) ?>.</p>
        </div>
        <nav class="rodape-navegacao" aria-label="Links úteis">
            <strong>Links úteis</strong>
            <div class="rodape-links">
                <a href="<?= h(caminho('index.php?finalidade=Venda#imoveis')) ?>">Imóveis à venda</a>
                <a href="<?= h(caminho('index.php?finalidade=Aluguel#imoveis')) ?>">Imóveis para alugar</a>
                <?php if (e_equipe()): ?>
                    <a href="<?= h(caminho('painel/index.php')) ?>">Área da equipe</a>
                <?php elseif (e_visitante()): ?>
                    <a href="<?= h(caminho('minha-conta.php')) ?>">Minha conta</a>
                <?php else: ?>
                    <a href="<?= h(caminho('acesso.php')) ?>">Entrar ou cadastrar</a>
                <?php endif; ?>
            </div>
            <p>Para pedir informações, abra o anúncio e utilize o formulário de atendimento.</p>
        </nav>
    </div>
</footer>
</body></html>
<?php }
