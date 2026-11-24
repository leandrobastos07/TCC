<?php
require_once __DIR__ . '/config/funcoes.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirecionar('index.php');
verificar_token();
$id = filter_var($_POST['id_imovel'] ?? null, FILTER_VALIDATE_INT);
$nome = trim((string)($_POST['nome'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$telefone = trim((string)($_POST['telefone'] ?? ''));
$texto = trim((string)($_POST['mensagem'] ?? ''));
if (e_visitante()) {
    $conta = db()->prepare("SELECT nome,email FROM usuarios WHERE id_usuario=? AND tipo='visitante' LIMIT 1");
    $conta->execute([(int) usuario()['id']]);
    $dadosConta = $conta->fetch();
    if (!$dadosConta) { http_response_code(403); exit('Conta de visitante invalida.'); }
    $nome = $dadosConta['nome'];
    $email = $dadosConta['email'];
}
if (!$id) redirecionar('index.php');
if (mb_strlen($nome) < 2 || mb_strlen($nome) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150 || mb_strlen($telefone) > 30 || $texto === '' || mb_strlen($texto) > 1500) {
    mensagem('Preencha os dados corretamente para enviar o pedido.', 'erro');
    redirecionar('imovel.php?id=' . $id);
}
$existe = db()->prepare('SELECT id_imovel FROM imoveis WHERE id_imovel = ?');
$existe->execute([$id]);
if (!$existe->fetch()) redirecionar('index.php');
try {
    db()->beginTransaction();
    $cliente = db()->prepare('SELECT id_cliente FROM clientes WHERE email = ? LIMIT 1');
    $cliente->execute([$email]);
    $idCliente = $cliente->fetchColumn();
    if ($idCliente) {
        $atualizar = db()->prepare('UPDATE clientes SET nome=?, telefone=? WHERE id_cliente=?');
        $atualizar->execute([$nome,$telefone,$idCliente]);
    } else {
        $inserir = db()->prepare('INSERT INTO clientes (nome,email,telefone) VALUES (?,?,?)');
        $inserir->execute([$nome,$email,$telefone]);
        $idCliente = db()->lastInsertId();
    }
    // Apenas o visitante conectado consegue acompanhar este pedido em Minha conta.
    // Pedidos feitos sem login continuam funcionando, mas nao ficam vinculados a conta.
    $idUsuario = e_visitante() ? (int) usuario()['id'] : null;
    $novo = db()->prepare('INSERT INTO atendimentos (id_cliente,id_imovel,id_usuario,data,mensagem) VALUES (?,?,?,CURDATE(),?)');
    $novo->execute([$idCliente,$id,$idUsuario,$texto]);
    db()->commit();
    mensagem('Solicitação enviada! A equipe poderá consultá-la no painel.');
} catch (Throwable $erro) {
    if (db()->inTransaction()) db()->rollBack();
    mensagem('Não foi possível registrar a solicitação. Tente novamente.', 'erro');
}
redirecionar('imovel.php?id=' . $id);
