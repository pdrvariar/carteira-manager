<?php
// app/test_api.php

// 1. Carrega o Autoloader do Composer (para GuzzleHttp, etc.)
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Carrega a classe Env (necessária para ler o .env)
require_once __DIR__ . '/core/Env.php';

// 3. Carrega a classe OPLabAPIClient manualmente (pois não está no PSR-4 do composer)
require_once __DIR__ . '/services/OPLabAPIClient.php';

use App\Services\OPLabAPIClient;

try {
    // Carrega as variáveis de ambiente explicitamente
    Env::load(__DIR__ . '/../.env');

    echo "Inicializando cliente OPLab...\n";
    $client = new OPLabAPIClient();
    echo "Cliente OPLab inicializado com sucesso!\n";

    // Teste com um ativo
    $symbol = 'PETR4';
    echo "Consultando preço de $symbol...\n";
    
    $price = $client->getPrice($symbol);

    if ($price !== null) {
        echo "Preço de $symbol: R$ " . number_format($price, 2, ',', '.') . "\n";
    } else {
        echo "Não foi possível obter o preço de $symbol\n";

        // Tenta obter dados mais detalhados para debug
        $data = $client->getStockData($symbol);
        echo "Dados brutos retornados:\n";
        print_r($data);
    }

} catch (Exception $e) {
    echo "\n[ERRO] " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
