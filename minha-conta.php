<?php
// Área do visitante: permite consultar dados, editar nome e trocar senha.
require_once __DIR__ . '/config/funcoes.php';
exigir_visitante();

$idUsuario = (int) usuario()['id'];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_token();
    $acao = (string) ($_POST['acao'] ?? '');

    if ($acao === 'salvar_nome') {
        $novoNome = trim((string) ($_POST['nome'] ?? ''));
        if (mb_strlen($novoNome) < 2 || mb_strlen($novoNome) > 120) {
            $erro = 'O nome precisa ter entre 2 e 120 caracteres.';
        } else {
            $alterar = db()->prepare("UPDATE usuarios SET nome = ? WHERE id_usuario = ? AND tipo = 'visitante'");
            $alterar->execute([$novoNome, $idUsuario]);
            $_SESSION['usuario']['nome'] = $novoNome;
            mensagem('Nome atualizado com sucesso!');
            redirecionar('minha-conta.php');
        }
    } elseif ($acao === 'trocar_senha') {
        $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
        $novaSenha = (string) ($_POST['nova_senha'] ?? '');
        $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');

        $consulta = db()->prepare("SELECT senha FROM usuarios WHERE id_usuario = ? AND tipo = 'visitante' LIMIT 1");
        $consulta->execute([$idUsuario]);
        $senhaSalva = $consulta->fetchColumn();

        if (!$senhaSalva || !password_verify($senhaAtual, (string) $senhaSalva)) {
            $erro = 'A senha atual está incorreta.';
        } elseif (strlen($novaSenha) < 8 || strlen($novaSenha) > 72) {
            $erro = 'A nova senha deve ter entre 8 e 72 caracteres.';
        } elseif ($novaSenha !== $confirmarSenha) {
            $erro = 'A confirmação da nova senha não corresponde.';
        } else {
            $senhaCriptografada = password_hash($novaSenha, PASSWORD_DEFAULT);
            $alterar = db()->prepare("UPDATE usuarios SET senha = ? WHERE id_usuario = ? AND tipo = 'visitante'");
            $alterar->execute([$senhaCriptografada, $idUsuario]);
            mensagem('Senha alterada com sucesso!');
            redirecionar('minha-conta.php');
        }
    } else {
        $erro = 'Ação inválida.';
    }
}

$consulta = db()->prepare("SELECT nome, email FROM usuarios WHERE id_usuario = ? AND tipo = 'visitante' LIMIT 1");
$consulta->execute([$idUsuario]);
$conta = $consulta->fetch();
if (!$conta) redirecionar('logout.php');

// Mostra somente os pedidos vinculados ao ID do visitante conectado.
$buscarPedidos = db()->prepare(
    'SELECT a.id_atendimento, a.id_imovel, a.data, a.status, a.mensagem, i.titulo
     FROM atendimentos a
     JOIN imoveis i ON i.id_imovel = a.id_imovel
     WHERE a.id_usuario = ?
     ORDER BY a.id_atendimento DESC'
);
$buscarPedidos->execute([$idUsuario]);
$meusPedidos = $buscarPedidos->fetchAll();

cabecalho('Minha conta');
?>
<main class="container pagina">
    <section class="login-card" style="max-width: 650px;">
        <span class="sobretitulo verde">ÁREA DO VISITANTE</span>
        <h1>Olá, <?= h($conta['nome']) ?>!</h1>
        <p>Aqui você pode visualizar seus dados e atualizar sua conta.</p>

        <?php mostrar_mensagem(); ?>
        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= h($erro) ?></div>
        <?php endif; ?>

        <h2>Meus dados</h2>
        <p><strong>E-mail:</strong> <?= h($conta['email']) ?></p>
        <form method="post" action="<?= h(caminho('minha-conta.php')) ?>">
            <?php campo_token(); ?>
            <input type="hidden" name="acao" value="salvar_nome">
            <label for="nome">Nome completo</label>
            <input type="text" id="nome" name="nome" minlength="2" maxlength="120"
                   value="<?= h($conta['nome']) ?>" autocomplete="name" required>
            <button class="botao largura-total" type="submit">Salvar nome</button>
        </form>

        <hr>

        <h2>Alterar minha senha</h2>
        <form method="post" action="<?= h(caminho('minha-conta.php')) ?>">
            <?php campo_token(); ?>
            <input type="hidden" name="acao" value="trocar_senha">
            <label for="senha_atual">Senha atual</label>
            <input type="password" id="senha_atual" name="senha_atual" autocomplete="current-password" required>

            <label for="nova_senha">Nova senha (mínimo de 8 caracteres)</label>
            <input type="password" id="nova_senha" name="nova_senha" minlength="8" maxlength="72"
                   autocomplete="new-password" required>

            <label for="confirmar_senha">Confirmar nova senha</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" minlength="8" maxlength="72"
                   autocomplete="new-password" required>

            <button class="botao botao-borda largura-total" type="submit">Alterar senha</button>
        </form>

        <hr>

        <h2>Minhas solicitações</h2>
        <p>Aqui aparecem os pedidos de atendimento enviados quando você estava conectado nesta conta.</p>
        <?php if (!$meusPedidos): ?>
            <p>Você ainda não enviou nenhuma solicitação com esta conta.</p>
        <?php else: ?>
            <?php foreach ($meusPedidos as $pedido): ?>
                <article class="bloco-branco" style="padding: 18px; margin: 14px 0;">
                    <h3 style="margin: 0 0 8px;">
                        <a href="<?= h(caminho('imovel.php?id=' . (int)$pedido['id_imovel'])) ?>">
                            <?= h($pedido['titulo']) ?>
                        </a>
                    </h3>
                    <p><strong>Data:</strong> <?= h(date('d/m/Y', strtotime($pedido['data']))) ?></p>
                    <p><strong>Situação:</strong>
                        <span class="etiqueta <?= $pedido['status'] === 'Novo' ? 'etiqueta-novo' : '' ?>">
                            <?= h($pedido['status']) ?>
                        </span>
                    </p>
                    <p class="mensagem-cliente"><?= nl2br(h($pedido['mensagem'])) ?></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

        <hr>

        <p>Quer procurar casas, apartamentos ou terrenos disponíveis?</p>
        <p><a class="botao largura-total" href="<?= h(caminho('index.php')) ?>">Ver imóveis disponíveis</a></p>
        <p><a href="<?= h(caminho('logout.php')) ?>">Sair da minha conta</a></p>
    </section>
</main>
<?php rodape(); ?>
