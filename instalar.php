<?php
require_once __DIR__ . '/config/funcoes.php';
// O instalador só permite criar o primeiro administrador.
$quantidade = (int) db()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
if ($quantidade > 0) { cabecalho('Instalação concluída'); echo '<main class="container pagina"><h1>O administrador já foi criado.</h1><p>Por segurança, exclua o arquivo instalar.php e <a href="' . h(caminho('login.php')) . '">entre no sistema</a>.</p></main>'; rodape(); exit; }
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 verificar_token();
 $nome = trim((string)($_POST['nome'] ?? ''));
 $email = trim((string)($_POST['email'] ?? ''));
 $senha = (string)($_POST['senha'] ?? '');
 if (mb_strlen($nome)<2 || mb_strlen($nome)>120 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($senha)<8) $erro = 'Informe nome, e-mail válido e senha de pelo menos 8 caracteres.';
 else {
   try {
    $salvar = db()->prepare("INSERT INTO usuarios (nome,email,senha,tipo) VALUES (?,?,?,'administrador')");
    $salvar->execute([$nome,$email,password_hash($senha,PASSWORD_DEFAULT)]);
    mensagem('Administrador cadastrado. Faça login para acessar o painel.');
    redirecionar('login.php');
   } catch (PDOException $e) { $erro = 'Erro ao criar a conta. Verifique o banco.'; }
 }
}
cabecalho('Primeiro acesso'); ?>
<main class="container pagina"><section class="login-card"><span class="sobretitulo verde">PRIMEIRO ACESSO</span><h1>Cadastro inicial</h1><p>Crie o administrador do TCC. Este formulário só aparece enquanto não existe nenhum usuário cadastrado.</p>
<?php if ($erro): ?><div class="alerta alerta-erro"><?= h($erro) ?></div><?php endif; ?>
<form method="post"><?php campo_token(); ?><label for="nome">Nome</label><input id="nome" name="nome" required maxlength="120"><label for="email">E-mail</label><input id="email" name="email" type="email" required><label for="senha">Senha (mínimo 8 caracteres)</label><input id="senha" name="senha" type="password" minlength="8" required><button class="botao largura-total" type="submit">Criar administrador</button></form>
<p class="dica-login">Depois do cadastro, exclua o arquivo <code>instalar.php</code>.</p></section></main>
<?php rodape(); ?>
