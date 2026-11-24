<?php
require_once __DIR__ . '/config/funcoes.php';
if (esta_logado()) redirecionar(e_visitante() ? 'minha-conta.php' : 'painel/index.php');
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_token();
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $senha = (string)($_POST['senha'] ?? '');
    $busca = db()->prepare('SELECT id_usuario,nome,email,senha,tipo FROM usuarios WHERE email=? LIMIT 1');
    $busca->execute([$email]);
    $conta = $busca->fetch();
    if ($conta && $conta['tipo'] === 'visitante' && password_verify($senha, $conta['senha'])) {
        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id' => $conta['id_usuario'], 'nome' => $conta['nome'],
            'email' => $conta['email'], 'tipo' => 'visitante'
        ];
        redirecionar('minha-conta.php');
    }
    $erro = 'E-mail ou senha incorretos para uma conta de visitante.';
}
cabecalho('Entrar como visitante');
?>
<main class="container pagina">
 <section class="login-card">
  <span class="sobretitulo verde">ACESSO DO VISITANTE</span>
  <h1>Entrar como visitante</h1>
  <p>Acesse sua conta para procurar imóveis e solicitar atendimento.</p>
  <?php mostrar_mensagem(); ?>
  <?php if ($erro): ?><div class="alerta alerta-erro"><?= h($erro) ?></div><?php endif; ?>
  <form method="post">
   <?php campo_token(); ?>
   <label for="email">E-mail</label>
   <input id="email" name="email" type="email" maxlength="150" autocomplete="username" required>
   <label for="senha">Senha</label>
   <input id="senha" name="senha" type="password" autocomplete="current-password" required>
   <button type="submit" class="botao largura-total">Entrar</button>
  </form>
  <p>Primeira vez por aqui? <a href="<?= h(caminho('cadastro-visitante.php')) ?>">Criar conta de visitante</a>.</p>
  <p class="dica-login">Faz parte da equipe? <a href="<?= h(caminho('login.php')) ?>">Acesse o login do administrador/corretor</a>.</p>
 </section>
</main>
<?php rodape(); ?>
