<?php
// app/controllers/OrderController.php

namespace App\Controllers;

use App\Models\Order;
use App\Core\Session;
use App\Core\Auth;

class OrderController {
    private $orderModel;
    private $params;

    public function __construct($params = []) {
        $this->orderModel = new Order();
        $this->params = $params;
        Session::start();
    }

    public function index() {
        Auth::checkAuthentication();

        $userId = Auth::getCurrentUserId();
        $orders = $this->orderModel->getUserOrders($userId, true);
        $stats = $this->orderModel->getOrderStats($userId);
        $monthlySummary = $this->orderModel->getMonthlySummary($userId);

        require_once __DIR__ . '/../views/order/index.php';
    }

    public function create() {
        Auth::checkAuthentication();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                redirectBack('/index.php?url=' . obfuscateUrl('order/create'));
            }

            // Processar dados do pedido
            $orderData = [
                'user_id' => $_SESSION['user_id'],
                'customer_name' => trim($_POST['customer_name'] ?? ''),
                'customer_email' => trim($_POST['customer_email'] ?? ''),
                'customer_phone' => trim($_POST['customer_phone'] ?? ''),
                'customer_address' => trim($_POST['customer_address'] ?? ''),
                'status' => $_POST['status'] ?? 'pending',
                'order_date' => !empty($_POST['order_date']) ? $_POST['order_date'] : date('Y-m-d'),
                'delivery_date' => !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null,
                'notes' => trim($_POST['notes'] ?? '')
            ];

            // Processar itens do pedido
            $items = [];
            if (isset($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    $productName = $item['product_name'] ?? '';
                    $quantity = $item['quantity'] ?? '';
                    $unitPrice = $item['unit_price'] ?? '';

                    if (!empty($productName)) {
                        $items[] = [
                            'product_name' => trim($productName),
                            'product_code' => trim($item['product_code'] ?? ''),
                            'quantity' => max(1, (int)$quantity),
                            'unit_price' => (float)str_replace(['.', ','], ['', '.'], $unitPrice),
                            'description' => trim($item['description'] ?? '')
                        ];
                    }
                }
            }

            if (empty($orderData['customer_name'])) {
                Session::setFlash('error', 'Nome do cliente é obrigatório.');
                require_once __DIR__ . '/../views/order/create.php';
                return;
            }

            if (empty($items)) {
                Session::setFlash('error', 'O pedido deve ter pelo menos um item.');
                require_once __DIR__ . '/../views/order/create.php';
                return;
            }

            try {
                $orderId = $this->orderModel->create($orderData, $items);

                if ($orderId) {
                    Session::setFlash('success', 'Pedido criado com sucesso!');
                    header('Location: /index.php?url=' . obfuscateUrl('order/view/' . $orderId));
                    exit;
                } else {
                    Session::setFlash('error', 'Erro ao criar pedido.');
                }
            } catch (\Exception $e) {
                Session::setFlash('error', 'Erro ao criar pedido: ' . $e->getMessage());
            }
        }

        require_once __DIR__ . '/../views/order/create.php';
    }

    public function view() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        if (!$id) {
            header('Location: /index.php?url=' . obfuscateUrl('order'));
            exit;
        }

        $order = $this->orderModel->findWithItems($id);

        // Verificar permissões
        if (!$order || $order['user_id'] != $_SESSION['user_id']) {
            Session::setFlash('error', 'Acesso negado.');
            header('Location: /index.php?url=' . obfuscateUrl('order'));
            exit;
        }

        require_once __DIR__ . '/../views/order/view.php';
    }

    public function edit() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        $order = $this->orderModel->findWithItems($id);

        // Verificar permissões
        if (!$order || $order['user_id'] != $_SESSION['user_id']) {
            Session::setFlash('error', 'Acesso negado.');
            header('Location: /index.php?url=' . obfuscateUrl('order'));
            exit;
        }

        require_once __DIR__ . '/../views/order/edit.php';
    }

    public function update() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                redirectBack('/index.php?url=' . obfuscateUrl('order/edit/' . $id));
            }

            // Processar dados do pedido
            $orderData = [
                'user_id' => $_SESSION['user_id'],
                'customer_name' => trim($_POST['customer_name'] ?? ''),
                'customer_email' => trim($_POST['customer_email'] ?? ''),
                'customer_phone' => trim($_POST['customer_phone'] ?? ''),
                'customer_address' => trim($_POST['customer_address'] ?? ''),
                'status' => $_POST['status'] ?? 'pending',
                'order_date' => !empty($_POST['order_date']) ? $_POST['order_date'] : date('Y-m-d'),
                'delivery_date' => !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null,
                'notes' => trim($_POST['notes'] ?? '')
            ];

            // Processar itens do pedido
            $items = [];
            if (isset($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['product_name'])) {
                        $items[] = [
                            'product_name' => trim($item['product_name']),
                            'product_code' => trim($item['product_code'] ?? ''),
                            'quantity' => max(1, (int)($item['quantity'] ?? 1)),
                            'unit_price' => (float)str_replace(['.', ','], ['', '.'], $item['unit_price'] ?? '0'),
                            'description' => trim($item['description'] ?? '')
                        ];
                    }
                }
            }

            if (empty($orderData['customer_name'])) {
                Session::setFlash('error', 'Nome do cliente é obrigatório.');
                header('Location: /index.php?url=' . obfuscateUrl('order/edit/' . $id));
                exit;
            }

            if (empty($items)) {
                Session::setFlash('error', 'O pedido deve ter pelo menos um item.');
                header('Location: /index.php?url=' . obfuscateUrl('order/edit/' . $id));
                exit;
            }

            // Calcular total
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }
            $orderData['total_amount'] = $totalAmount;

            try {
                if ($this->orderModel->update($id, $orderData, $items)) {
                    Session::setFlash('success', 'Pedido atualizado com sucesso!');
                    header('Location: /index.php?url=' . obfuscateUrl('order/view/' . $id));
                    exit;
                } else {
                    Session::setFlash('error', 'Erro ao atualizar pedido.');
                    header('Location: /index.php?url=' . obfuscateUrl('order/edit/' . $id));
                    exit;
                }
            } catch (\Exception $e) {
                Session::setFlash('error', 'Erro ao atualizar pedido: ' . $e->getMessage());
                header('Location: /index.php?url=' . obfuscateUrl('order/edit/' . $id));
                exit;
            }
        }

        header('Location: /index.php?url=' . obfuscateUrl('order'));
        exit;
    }

    public function delete() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        if ($id) {
            if ($this->orderModel->delete($id, $_SESSION['user_id'])) {
                Session::setFlash('success', 'Pedido arquivado com sucesso.');
            } else {
                Session::setFlash('error', 'Erro ao arquivar pedido.');
            }
        }

        header('Location: /index.php?url=' . obfuscateUrl('order'));
        exit;
    }

    public function restore() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        if ($id) {
            if ($this->orderModel->restore($id, $_SESSION['user_id'])) {
                Session::setFlash('success', 'Pedido restaurado com sucesso.');
            } else {
                Session::setFlash('error', 'Erro ao restaurar pedido.');
            }
        }

        header('Location: /index.php?url=' . obfuscateUrl('order'));
        exit;
    }
}