<?php
// app/routers/web.php

use App\Core\Router;

function setupRoutes(Router $router) {
    // --- Rotas Públicas ---
    $router->add('', ['controller' => 'home', 'action' => 'index']);
    $router->add('login', ['controller' => 'auth', 'action' => 'login']);
    $router->add('register', ['controller' => 'auth', 'action' => 'register']);
    $router->add('logout', ['controller' => 'auth', 'action' => 'logout']);
    $router->add('verify', ['controller' => 'auth', 'action' => 'verify']);

    // --- Rotas de Autenticação Google (CORRIGIDAS) ---
    // Ajustadas para o padrão de Array compatível com o seu Router
    $router->add('google-auth', ['controller' => 'auth', 'action' => 'googleLogin']);
    $router->add('google/callback', ['controller' => 'auth', 'action' => 'googleCallback']);
    

    //Rotas de Portfólio (ADICIONAR AS QUE FALTAM) ---
    $router->add('portfolio', ['controller' => 'portfolio', 'action' => 'index']);
    $router->add('portfolio/create', ['controller' => 'portfolio', 'action' => 'create']);
    $router->add('portfolio/view/{id:\d+}', ['controller' => 'portfolio', 'action' => 'view']);
    $router->add('portfolio/run/{id:\d+}', ['controller' => 'portfolio', 'action' => 'runSimulation']);
    $router->add('portfolio/quick-update/{id:\d+}', ['controller' => 'portfolio', 'action' => 'quickUpdate']);
    
    // NOVAS ROTAS OBRIGATÓRIAS:
    $router->add('portfolio/edit/{id:\d+}', ['controller' => 'portfolio', 'action' => 'edit']);
    $router->add('portfolio/update/{id:\d+}', ['controller' => 'portfolio', 'action' => 'update']);
    $router->add('portfolio/delete/{id:\d+}', ['controller' => 'portfolio', 'action' => 'delete']);
    $router->add('portfolio/clone/{id:\d+}', ['controller' => 'portfolio', 'action' => 'clone']);    
    
    // --- Rotas de Ativos ---
    $router->add('assets/view/{id:\d+}', ['controller' => 'asset', 'action' => 'view']);
    $router->add('assets', ['controller' => 'asset', 'action' => 'index']);
    $router->add('assets/import', ['controller' => 'asset', 'action' => 'import']);
    $router->add('api/assets/update', ['controller' => 'asset', 'action' => 'updateApi']);  
    $router->add('api/assets/{id:\d+}', ['controller' => 'asset', 'action' => 'getAssetApi']); 
    $router->add('api/assets/benchmark/{id:\d+}', ['controller' => 'asset', 'action' => 'getBenchmarkData']);
    
    
    // --- Rotas de Perfil ---
    $router->add('profile', ['controller' => 'profile', 'action' => 'index']);
    $router->add('profile/update', ['controller' => 'profile', 'action' => 'update']);
    $router->add('profile/change-password', ['controller' => 'profile', 'action' => 'changePassword']);
    $router->add('portfolio/toggle-system/{id:\d+}', ['controller' => 'portfolio', 'action' => 'toggleSystem']);    

    // --- Recuperação de Senha ---
    $router->add('forgot-password', ['controller' => 'auth', 'action' => 'forgotPassword']);
    $router->add('reset-password', ['controller' => 'auth', 'action' => 'resetPassword']);
    
    // --- Admin ---
    $router->add('admin', ['controller' => 'admin', 'action' => 'dashboard']);
    $router->add('admin/dashboard', ['controller' => 'admin', 'action' => 'dashboard']);
    $router->add('admin/users', ['controller' => 'admin', 'action' => 'users']);
    $router->add('admin/users/edit/{id:\d+}', ['controller' => 'admin', 'action' => 'editUser']);
    $router->add('admin/users/update/{id:\d+}', ['controller' => 'admin', 'action' => 'updateUser']);
    $router->add('admin/assets', ['controller' => 'admin', 'action' => 'assets']);
    $router->add('admin/create-default-portfolios', ['controller' => 'admin', 'action' => 'createDefaultPortfolios']);

    // --- Rotas de Carteiras (Wallets) ---
    $router->add('wallet', ['controller' => 'wallet', 'action' => 'index']);
    $router->add('wallet/create', ['controller' => 'wallet', 'action' => 'create']);
    $router->add('wallet/view/{id:\d+}', ['controller' => 'wallet', 'action' => 'view']);
    $router->add('wallet/edit/{id:\d+}', ['controller' => 'wallet', 'action' => 'edit']);
    $router->add('wallet/update/{id:\d+}', ['controller' => 'wallet', 'action' => 'update']);
    $router->add('wallet/delete/{id:\d+}', ['controller' => 'wallet', 'action' => 'delete']);

    // CORREÇÃO: Usar 'wallet-stock' (kebab-case) para que o Router converta corretamente para WalletStockController
    $router->add('wallet_stocks/index/{wallet_id:\d+}', ['controller' => 'wallet-stock', 'action' => 'index']);
    $router->add('wallet_stocks/create/{wallet_id:\d+}', ['controller' => 'wallet-stock', 'action' => 'create']);
    $router->add('wallet_stocks/edit/{id:\d+}', ['controller' => 'wallet-stock', 'action' => 'edit']);
    $router->add('wallet_stocks/update/{id:\d+}', ['controller' => 'wallet-stock', 'action' => 'update']);
    $router->add('wallet_stocks/delete/{id:\d+}', ['controller' => 'wallet-stock', 'action' => 'delete']);
    $router->add('wallet_stocks/update_prices/{wallet_id:\d+}', ['controller' => 'wallet-stock', 'action' => 'updatePrices']);

    $router->add('project', ['controller' => 'project', 'action' => 'index']);
    $router->add('project/create', ['controller' => 'project', 'action' => 'create']);
    $router->add('project/view/{id:\d+}', ['controller' => 'project', 'action' => 'view']);
    $router->add('project/edit/{id:\d+}', ['controller' => 'project', 'action' => 'edit']);
    $router->add('project/update/{id:\d+}', ['controller' => 'project', 'action' => 'update']);
    $router->add('project/delete/{id:\d+}', ['controller' => 'project', 'action' => 'delete']);
    $router->add('project/restore/{id:\d+}', ['controller' => 'project', 'action' => 'restore']);

    // --- Dashboard ---
    $router->add('dashboard', ['controller' => 'home', 'action' => 'index']);
}
