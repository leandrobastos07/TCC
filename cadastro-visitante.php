<?php
require_once __DIR__ . '/config/funcoes.php';
if (esta_logado()) redirecionar(e_visitante() ? 'minha-conta.php' : 'painel/index.php');
$erro = '';
$nome = '';
$email = '';
$telefone = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_token();
    $nome = trim((string)($_POST['nome'] ?? ''));
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $telefone = trim((string)($_POST['telefone'] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');
    $confirmacao = (string)($_POST['confirmacao'] ?? '');
    if (mb_strlen($nome) < 2 || mb_strlen($nome) > 120 ||
        mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        mb_strlen($telefone) > 30 || strlen($senha) < 8 || strlen($senha) > 72) {
        $erro = 'Preencha os dados corretamente. A senha precisa ter de 8 a 72 caracteres.';
    } elseif ($senha !== $confirmacao) {
        $erro = 'As duas senhas não são iguais.';
    } else {
        try {
            db()->beginTransaction();
            // Todos os cadastros públicos possuem exatamente o perfil visitante.
            $novoUsuario = db()->prepare("INSERT INTO usuarios (nome,email,senha,tipo) VALUES (?,?,?,'visitante')");
            $novoUsuario->execute([$nome,$email,password_hash($senha, PASSWORD_DEFAULT)]);
            $idUsuario = (int) db()->lastInsertId();

            // Aparece tambem na lista Clientes do painel, sem duplicar e-mail antigo.
            $procuraCliente = db()->prepare('SELECT id_cliente FROM clientes WHERE email=? LIMIT 1');
            $procuraCliente->execute([$email]);
            if (!$procuraCliente->fetchColumn()) {
                $novoCliente = db()->prepare('INSERT INTO clientes (nome,email,telefone) VALUES (?,?,?)');
                $novoCliente->execute([$nome,$email,$telefone]);
            }
            db()->commit();
            session_regenerate_id(true);
            $_SESSION['usuario'] = ['id'=>$idUsuario, 'nome'=>$nome, 'email'=>$email, 'tipo'=>'visitante'];
            mensagem('Sua conta de visitante foi criada com sucesso!');
            redirecionar('minha-conta.php');
        } catch (PDOException $e) {
            if (db()->inTransaction()) db()->rollBack();
            $erro = 'Não foi possível criar a conta. Verifique se este e-mail já está cadastrado.';
        }
    }
}
cabecalho('Cadastrar visitante');
?>
<main class="container pagina">
 <section class="login-card">
  <span class="sobretitulo verde">CADASTRO GRATUITO</span>
  <h1>Criar conta de visitante</h1>
  <p>Cadastre-se para pesquisar imóveis e entrar em contato com a imobiliária.</p>
  <?php if ($erro): ?><div class="alerta alerta-erro"><?= h($erro) ?></div><?php endif; ?>
  <form method="post">
   <?php campo_token(); ?>
   <label for="nome">Nome completo</label>
   <input id="nome" name="nome" maxlength="120" required value="<?= h($nome) ?>" autocomplete="name">
   <label for="email">E-mail</label>
   <input id="email" name="email" type="email" maxlength="150" required value="<?= h($email) ?>" autocomplete="email">
   <label for="telefone">Telefone (opcional)</label>
   <input id="telefone" name="telefone" maxlength="30" value="<?= h($telefone) ?>" autocomplete="tel">
   <label for="senha">Senha (mínimo 8 caracteres)</label>
   <input id="senha" name="senha" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
   <label for="confirmacao">Confirme sua senha</label>
   <input id="confirmacao" name="confirmacao" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
   <button class="botao largura-total" type="submit">Criar minha conta</button>
  </form>
  <p>Já tem conta? <a href="<?= h(caminho('login-visitante.php')) ?>">Entre aqui</a>.</p>
 </section>
</main>
<?php rodape(); ?>
