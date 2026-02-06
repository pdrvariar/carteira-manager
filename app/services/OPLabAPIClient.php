<?php
// app/services/OPLabAPIClient.php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Env; // Importa a classe Env do namespace global

class OPLabAPIClient {
    private $client;
    private $accessToken;
    private $baseUrl = "https://api.oplab.com.br/v3/";

    public function __construct(string $accessToken = null) {
        // Inicializa o Env
        $this->loadEnv();

        // Se não passar token, tenta pegar do .env
        if ($accessToken === null) {
            $accessToken = Env::get('OPLAB_TOKEN');

            if (!$accessToken) {
                throw new \Exception("Token da API OPLab não configurado. Adicione OPLAB_TOKEN no arquivo .env");
            }
        }

        $this->accessToken = $accessToken;
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30.0,
            'headers' => [
                'Access-Token' => $this->accessToken,
                'Accept' => 'application/json',
            ]
        ]);
    }

    private function loadEnv() {
        // Garante que o Env está carregado apenas uma vez
        static $loaded = false;
        if (!$loaded) {
            try {
                // Verifica se a classe Env existe antes de chamar
                if (class_exists('Env')) {
                    Env::load();
                    $loaded = true;
                }
            } catch (\Exception $e) {
                error_log("Erro ao carregar .env: " . $e->getMessage());
            }
        }
    }

    // Faz uma única requisição ao endpoint e normaliza a resposta
    private function requestOnce(string $symbol) {
        $url = "market/stocks/{$symbol}";
        try {
            $response = $this->client->get($url);
            $status = $response->getStatusCode();
            $headers = $response->getHeaders();
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            // Normalizar preço a partir de possíveis chaves
            $price = null;
            if (is_array($data)) {
                $candidates = ['price', 'last', 'close', 'preco', 'venda', 'ask', 'bid', 'price_last', 'last_price'];
                foreach ($candidates as $key) {
                    if (isset($data[$key])) {
                        $price = $data[$key];
                        break;
                    }
                }

                if ($price === null) {
                    $nested_paths = [
                        ['data','price'], ['data','last'], ['ticker','last'], ['ticker','price'], ['results','price']
                    ];
                    foreach ($nested_paths as $path) {
                        $cursor = $data;
                        foreach ($path as $p) {
                            if (is_array($cursor) && isset($cursor[$p])) {
                                $cursor = $cursor[$p];
                            } else {
                                $cursor = null;
                                break;
                            }
                        }
                        if ($cursor !== null) {
                            $price = $cursor;
                            break;
                        }
                    }
                }

                if (is_array($price)) {
                    $subCandidates = ['raw','value','preco','last'];
                    foreach ($subCandidates as $sc) {
                        if (isset($price[$sc])) {
                            $price = $price[$sc];
                            break;
                        }
                    }
                }

                if (is_numeric($price)) {
                    $price = (float)$price;
                } else {
                    $price = null;
                }
            }

            return [
                'queried_symbol' => $symbol,
                'price' => $price,
                'raw' => $data,
                'status' => $status,
                'headers' => $headers,
                'url' => $this->baseUrl . $url,
                'body' => $body,
                'error' => null
            ];
        } catch (RequestException $e) {
            $resp = $e->hasResponse() ? $e->getResponse() : null;
            $status = $resp ? $resp->getStatusCode() : null;
            $headers = $resp ? $resp->getHeaders() : null;
            $body = null;
            if ($resp) {
                try {
                    $body = $resp->getBody()->getContents();
                } catch (\Exception $ex) {
                    $body = null;
                }
            }
            $errorMsg = $e->getMessage();
            error_log("OPLab RequestException for {$symbol}: status={$status}, error={$errorMsg}");

            return [
                'queried_symbol' => $symbol,
                'price' => null,
                'raw' => null,
                'status' => $status,
                'headers' => $headers,
                'url' => $this->baseUrl . $url,
                'body' => $body,
                'error' => $errorMsg
            ];
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("OPLab Exception for {$symbol}: {$errorMsg}");
            return [
                'queried_symbol' => $symbol,
                'price' => null,
                'raw' => null,
                'status' => null,
                'headers' => null,
                'url' => $this->baseUrl . $url,
                'body' => null,
                'error' => $errorMsg
            ];
        }
    }

    // Public: tenta a symbol original e uma variante (sem/a .SA) para maior compatibilidade
    public function getStockData(string $symbol) {
        // Tenta com o símbolo exatamente como informado
        $first = $this->requestOnce($symbol);
        // Se obtivemos um preço válido ou status 200 com corpo, retornamos
        if (($first['price'] !== null) || ($first['status'] === 200 && $first['raw'] !== null)) {
            return $first;
        }

        // Caso não tenha, tenta uma alternativa:
        $alt = null;
        if (strpos($symbol, '.') !== false) {
            // Remove sufixo após o ponto: PETR4.SA -> PETR4
            $parts = explode('.', $symbol);
            $alt = $parts[0];
        } else {
            // Tenta acrescentar .SA (se API usar esse formato)
            $alt = $symbol . '.SA';
        }

        // Evita re-tentar o mesmo símbolo
        if ($alt && $alt !== $symbol) {
            $second = $this->requestOnce($alt);
            // Se o segundo trouxe preço ou conteúdo válido, devolve
            if (($second['price'] !== null) || ($second['status'] === 200 && $second['raw'] !== null)) {
                return $second;
            }
            // Caso contrário, devolve o primeiro (mais informativo) com um campo adicional 'attempts'
            $first['attempts'] = [$first['queried_symbol'], $second['queried_symbol']];
            return $first;
        }

        // Sem alternativa, retorna o primeiro
        return $first;
    }

    // Conveniência: retorna apenas o preço numérico (float) ou null
    public function getPrice(string $symbol) {
        $res = $this->getStockData($symbol);
        if (is_array($res) && array_key_exists('price', $res)) {
            return $res['price'];
        }
        return null;
    }
}