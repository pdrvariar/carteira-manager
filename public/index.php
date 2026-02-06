<?php
// public/index.php

use App\Core\Env;
use App\Core\Router;
use App\Core\Session;

ob_start();

// 1. O Autoload do Composer é a prioridade zero
$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // Log sênior para ajudar no debug do Docker
    error_log("Composer Autoload não encontrado em: " . $autoloadPath);
    die("Erro Crítico: Bibliotecas não instaladas. Execute 'composer require' no container.");
}

// 2. Carregar variáveis de ambiente
try {
    Env::load(__DIR__ . '/../.env');
} catch (Exception $e) {
    // Se não achar o .env, segue a vida (pode estar usando variáveis do sistema/Docker)
}

$isDev = ($_ENV['APP_ENV'] ?? 'production') === 'development';
error_reporting($isDev ? E_ALL : 0);
ini_set('display_errors', $isDev ? 1 : 0);

// 4. Segurança de Sessão Profissional
Session::start(); 

// Headers para evitar cache de dados sensíveis (SaaS Financeiro)
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 5. Sistema de Rotas
$router = new Router();
$baseDir = dirname(__DIR__) . '/app';
$routesPath = $baseDir . '/routers/web.php';

if (file_exists($routesPath)) {
    require_once $routesPath;
    setupRoutes($router);
}

// 6. Captura e Tratamento da URL
$url = $_GET['url'] ?? '';
if (empty($url)) {
    $url = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
}

// 7. Despacho da Requisição
try {
    $router->dispatch($url);
} catch (Exception $e) {
    http_response_code($e->getCode() == 404 ? 404 : 500);
    if ($isDev) {
        echo "<div style='padding:20px; border:5px solid red;'>";
        echo "<h2>Erro Sénior: " . $e->getMessage() . "</h2>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
    } else {
        // Em produção, mostra uma página de erro genérica e bonita
        $errorFile = __DIR__ . '/../app/views/errors/500.php';
        if (file_exists($errorFile)) {
            require_once $errorFile;
        } else {
            echo "Erro interno do servidor.";
        }
    }
}

ob_end_flush();
