<?php
require_once __DIR__ . '/config/funcoes.php';
if (esta_logado()) redirecionar(e_visitante() ? 'minha-conta.php' : 'painel/index.php');
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 verificar_token();
 $email = trim((string)($_POST['email'] ?? ''));
 $senha = (string)($_POST['senha'] ?? '');
 $busca = db()->prepare('SELECT id_usuario,nome,email,senha,tipo FROM usuarios WHERE email=? LIMIT 1');
 $busca->execute([$email]);
 $conta = $busca->fetch();
 if ($conta && in_array($conta['tipo'], ['administrador','corretor'], true) && password_verify($senha, $conta['senha'])) {
    session_regenerate_id(true);
    $_SESSION['usuario'] = ['id' => $conta['id_usuario'], 'nome' => $conta['nome'], 'email' => $conta['email'], 'tipo' => $conta['tipo']];
    redirecionar('painel/index.php');
 }
 $erro = 'E-mail ou senha incorretos.';
}
cabecalho('Entrar'); ?>
<main class="container pagina"><section class="login-card"><span class="sobretitulo verde">ACESSO DA EQUIPE</span><h1>Entrar no sistema</h1><p>Área destinada aos administradores e corretores.</p>
<?php mostrar_mensagem(); ?>
<?php if ($erro): ?><div class="alerta alerta-erro"><?= h($erro) ?></div><?php endif; ?>
<form method="post"><?php campo_token(); ?><label for="email">E-mail</label><input id="email" name="email" type="email" autocomplete="username" required><label for="senha">Senha</label><input id="senha" name="senha" type="password" autocomplete="current-password" required><button class="botao largura-total" type="submit">Acessar painel</button></form>
<p class="dica-login">Quer procurar imoveis com uma conta pessoal? <a href="<?= h(caminho('login-visitante.php')) ?>">Entrar como visitante</a>.</p></section></main>
<?php rodape(); ?>
