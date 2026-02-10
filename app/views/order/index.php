<?php
// app/views/order/index.php

use App\Core\Session;

$title = 'Meus Pedidos';
$additional_css = '<link rel="stylesheet" href="/css/order.css">';
ob_start();

// Mapeamento de status
$statusConfig = [
    'pending' => ['label' => 'Pendente', 'class' => 'bg-warning bg-opacity-10 text-warning', 'icon' => 'bi-clock'],
    'processing' => ['label' => 'Processando', 'class' => 'bg-primary bg-opacity-10 text-primary', 'icon' => 'bi-gear'],
    'shipped' => ['label' => 'Enviado', 'class' => 'bg-info bg-opacity-10 text-info', 'icon' => 'bi-truck'],
    'delivered' => ['label' => 'Entregue', 'class' => 'bg-success bg-opacity-10 text-success', 'icon' => 'bi-check-circle'],
    'cancelled' => ['label' => 'Cancelado', 'class' => 'bg-danger bg-opacity-10 text-danger', 'icon' => 'bi-x-circle']
];
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Meus Pedidos</h2>
            <p class="text-muted small mb-0">Gerencie seus pedidos e acompanhe o status.</p>
        </div>
        <a href="/index.php?url=<?= obfuscateUrl('order/create') ?>" class="btn btn-primary shadow-sm rounded-pill px-4">
            <i class="bi bi-plus-lg me-1"></i> Novo Pedido
        </a>
    </div>

    <!-- Estatísticas -->
<?php if (!empty($stats)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-3">
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <?php foreach ($stats as $stat):
                            $config = $statusConfig[$stat['status']] ?? $statusConfig['pending'];
                            ?>
                            <div class="d-flex align-items-center">
                            <span class="badge <?= $config['class'] ?> px-3 py-2 rounded-pill">
                                <i class="bi <?= $config['icon'] ?> me-1"></i>
                                <?= $stat['status_label'] ?>: <?= $stat['count'] ?>
                            </span>
                                <?php if ($stat['total_value'] > 0): ?>
                                    <small class="ms-2 text-muted">(<?= $stat['total_value_formatted'] ?>)</small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <div class="ms-auto">
                            <?php
                            $totalOrders = array_sum(array_column($stats, 'count'));
                            $totalValue = array_sum(array_column($stats, 'total_value'));
                            ?>
                            <span class="text-muted small">
                            <i class="bi bi-graph-up me-1"></i>
                            Total: <?= $totalOrders ?> pedidos • R$ <?= number_format($totalValue, 2, ',', '.') ?>
                        </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($orders)): ?>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body text-center py-5">
            <div class="mb-3">
                <i class="bi bi-receipt text-muted" style="font-size: 3rem;"></i>
            </div>
            <h5 class="text-muted mb-3">Nenhum pedido encontrado</h5>
            <p class="text-muted mb-4">Crie seu primeiro pedido para começar a gerenciar suas vendas.</p>
            <a href="/index.php?url=<?= obfuscateUrl('order/create') ?>" class="btn btn-primary px-4">
                <i class="bi bi-plus-lg me-1"></i> Criar Primeiro Pedido
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($orders as $order):
            $config = $statusConfig[$order['status']] ?? $statusConfig['pending'];
            $itemsCount = $order['items_count'] ?? 0;
            $totalAmount = $order['calculated_total'] ?? $order['total_amount'];
            ?>
            <div class="col">
                <div class="card h-100 border-0 shadow-sm rounded-4 hover-shadow transition-all">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                                        <i class="bi bi-receipt text-primary"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-0 fw-bold text-truncate">
                                            <?= htmlspecialchars($order['customer_name']) ?>
                                        </h5>
                                        <small class="text-muted"><?= $order['order_number'] ?></small>
                                    </div>
                                </div>

                                <span class="badge <?= $config['class'] ?> px-3 py-1 rounded-pill">
                                    <i class="bi <?= $config['icon'] ?> me-1"></i>
                                    <?= $config['label'] ?>
                                </span>
                            </div>

                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary border-0" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu shadow-sm border-0">
                                    <li>
                                        <a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('order/view/' . $order['id']) ?>">
                                            <i class="bi bi-eye me-2"></i> Visualizar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('order/edit/' . $order['id']) ?>">
                                            <i class="bi bi-pencil me-2"></i> Editar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-danger"
                                           href="/index.php?url=<?= obfuscateUrl('order/delete/' . $order['id']) ?>"
                                           onclick="return confirm('Tem certeza que deseja arquivar este pedido?')">
                                            <i class="bi bi-archive me-2"></i> Arquivar
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Valor Total</small>
                                <span class="fw-bold text-success">R$ <?= number_format($totalAmount, 2, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Itens</small>
                                <span class="badge bg-light text-dark"><?= $itemsCount ?> item(ns)</span>
                            </div>
                        </div>

                        <!-- Informações do cliente -->
                        <div class="mb-4">
                            <small class="text-muted d-block mb-1">Cliente</small>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-circle me-2 text-muted"></i>
                                <div>
                                    <div class="small fw-bold"><?= htmlspecialchars($order['customer_name']) ?></div>
                                    <?php if ($order['customer_email']): ?>
                                        <div class="small text-muted"><?= htmlspecialchars($order['customer_email']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Datas -->
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Datas</small>
                            <div class="d-flex justify-content-between small">
                                <div>
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?= date('d/m/Y', strtotime($order['order_date'])) ?>
                                </div>
                                <?php if ($order['delivery_date']): ?>
                                    <div>
                                        <i class="bi bi-truck me-1"></i>
                                        <?= date('d/m/Y', strtotime($order['delivery_date'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-top pt-3">
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>
                                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                            </small>
                            <a href="/index.php?url=<?= obfuscateUrl('order/view/' . $order['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                Detalhes <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4 text-muted small">
        <i class="bi bi-info-circle me-1"></i>
        Total de <?= count($orders) ?> pedido(s) encontrado(s)
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>