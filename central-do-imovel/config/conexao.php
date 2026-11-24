<?php
// Configure estas informações conforme seu XAMPP.
const DB_HOST = 'localhost';
const DB_NOME = 'central_imovel';
const DB_USUARIO = 'root';
const DB_SENHA = '';
const BASE_URL = '/central-do-imovel';

function db(): PDO
{
    static $conexao = null;
    if ($conexao === null) {
        try {
            $conexao = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NOME . ';charset=utf8mb4',
                DB_USUARIO,
                DB_SENHA,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                 PDO::ATTR_EMULATE_PREPARES => false]
            );
        } catch (PDOException $erro) {
            http_response_code(500);
            exit('Erro na conexão com o banco. Verifique o XAMPP, o banco central_imovel e config/conexao.php.');
        }
    }
    return $conexao;
}
