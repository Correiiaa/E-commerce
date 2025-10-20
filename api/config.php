<?php

/* * * * * * * * * * * * * * *
 * C O N F I G U R A Ç Ã O
 * 
 */
// Autor
define('AUTHOR', 'PREENCHER COM O SEU NOME');
define('ANO_LETIVO', 'PREENCHER COM O ANO LETIVO');

use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

/* * * * * * * * * * * * * * *
 * B A S E   D E   D A D O S
 */
# ALTERAR GRUPO

// Ler valores do ambiente com valores por omissãogit checkout -b MinhaNovaBrach

$guru = getenv('DB_GURU') ?: '32';
$host = getenv('DB_HOST') ?: 'mysql-sa.mgmt.ua.pt';
$port = getenv('DB_PORT') ?: '3306';
$charset = getenv('DB_CHARSET') ?: 'utf8';
$dbname_prefix = getenv('DB_NAME_PREFIX') ?: 'esan-dsg';

$dbname = $dbname_prefix . $guru;

$dsg_dbo = [
    'host' => $host,
    'port' => $port,
    'charset' => $charset,
    'dbname' => $dbname,
    'username' => $dbname_prefix . $guru . '-dbo',
    'password' => getenv('DB_DBO_PASSWORD') ?: ''
];

$dsg_web = [
    'host' => $host,
    'port' => $port,
    'charset' => $charset,
    'dbname' => $dbname,
    'username' => $dbname_prefix . $guru . '-web',
    'password' => getenv('DB_WEB_PASSWORD') ?: ''
];
/** @var Array $db['host','port','charset','dbname','username','password'] */
# Descomentar utilizador DBO ou WEB
#$db = $dsg_dbo;
$db = $dsg_web;



/* * * * * * * * * *
 * D E B U G
 */
define('DEBUG', true);

if (defined('DEBUG') && DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $_DEBUG = '';
}