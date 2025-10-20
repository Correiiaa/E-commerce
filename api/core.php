<?php


/**
 * Cria uma ligação a uma base de dados e devolve um objeto PDO com a ligação
 * @param Array $db 
 *        Array com definição de host, dbname, port, charset, username e password
 * @return PDO Objeto PDO com a ligação à Base de Dados
 */
# Inserir a ligação à base de dados
function connectDB($db)
{
    try {
        $pdo = new PDO(
            'mysql:host=' . $db['host'] . ';port=' . $db['port'] . ';
                dbname=' . $db['dbname'] . ';charset=' . $db['charset'],
            $db['username'],
            $db['password']
        );
    } catch (PDOException $e) {
        die('Erro: ' . $e->getMessage());
    }

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}


/**
 * Verifica se o modo DEBUG está definido e ativo e escreve na consola do browser
 * @param mixed $info
 * @param sting $type [log, error, info]
 * @return bool
 */
function debug($info = '', $type = 'log')
{
    if (defined('DEBUG') && DEBUG) {
        echo "<script>console.$type(" . json_encode($info) . ");</script>";
        return true;
    }
    return false;
}
