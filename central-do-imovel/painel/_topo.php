<?php
require_once __DIR__ . '/../config/funcoes.php';
exigir_equipe();
cabecalho($titulo ?? 'Painel');
?>
<main class="container pagina painel">
 <div class="painel-cabecalho">
  <div>
   <span class="sobretitulo verde"><?= e_admin() ? 'ÁREA DO ADMINISTRADOR' : 'ÁREA DO CORRETOR' ?></span>
   <h1><?= h($titulo ?? 'Painel') ?></h1>
   <p>Olá, <?= h(usuario()['nome']) ?>! Você entrou como <?= e_admin() ? 'administrador' : 'corretor' ?>.</p>
  </div>
  <a class="botao botao-borda" href="<?= h(caminho('index.php')) ?>">Ver site →</a>
 </div>
 <nav class="painel-menu" aria-label="Menu do painel">
  <a href="<?= h(caminho('painel/index.php')) ?>">Resumo</a>
  <a href="<?= h(caminho('painel/imoveis.php')) ?>">Imóveis</a>
  <a href="<?= h(caminho('painel/registros.php?tipo=clientes')) ?>">Clientes</a>
  <a href="<?= h(caminho('painel/atendimentos.php')) ?>">Atendimentos</a>
  <?php if (e_admin()): ?>
   <a href="<?= h(caminho('painel/registros.php?tipo=corretores')) ?>">Corretores</a>
   <a href="<?= h(caminho('painel/usuarios.php')) ?>">Usuários</a>
   <a href="<?= h(caminho('painel/marca.php')) ?>">Personalizar marca</a>
  <?php endif; ?>
 </nav>
 <?php mostrar_mensagem(); ?>
