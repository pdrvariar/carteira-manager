<?php
// app/views/order/view.php

use App\Core\Session;

$statusOptions = [
    'pending' => 'Pendente',
    'processing' => 'Processando',
    'shipped' => 'Enviado',
    'delivered' => 'Entregue',
    'cancelled' => 'Cancelado'
];

$statusConfig = [
    'pending' => ['icon' => 'bi-clock', 'class' => 'bg-warning bg-opacity-10 text-warning'],
    'processing' => ['icon' => 'bi-gear', 'class' => 'bg-primary bg-opacity-10 text-primary'],
    'shipped' => ['icon' => 'bi-truck', 'class' => 'bg-info bg-opacity-10 text-info'],
    'delivered' => ['icon' => 'bi-check-circle', 'class' => 'bg-success bg-opacity-10 text-success'],
    'cancelled' => ['icon' => 'bi-x-circle', 'class' => 'bg-danger bg-opacity-10 text-danger']
];

$config = $statusConfig[$order['status']] ?? $statusConfig['pending'];

$title = 'Pedido: ' . htmlspecialchars($order['order_number'] . ' - ' . $order['customer_name']);
$additional_css = '<link rel="stylesheet" href="/css/order.css">';
ob_start();
?>

    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-4 border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="bi bi-receipt text-primary fs-4"></i>
                            </div>
                            <div>
                                <h4 class="mb-1 fw-bold">Pedido <?= $order['order_number'] ?></h4>
                                <p class="text-muted mb-0">
                                <span class="badge <?= $config['class'] ?> px-3 py-1 rounded-pill">
                                    <i class="bi <?= $config['icon'] ?> me-1"></i>
                                    <?= $statusOptions[$order['status']] ?? $order['status'] ?>
                                </span>
                                    • Criado em <?= date('d/m/Y', strtotime($order['created_at'])) ?>
                                </p>
                            </div>
                        </div>

                        <div class="dropdown">
                            <button class="btn btn-outline-secondary border-0 rounded-circle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu shadow-sm border-0">
                                <li>
                                    <a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('order/edit/' . $order['id']) ?>">
                                        <i class="bi bi-pencil me-2"></i> Editar
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('order') ?>">
                                        <i class="bi bi-printer me-2"></i> Imprimir
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger"
                                       href="/index.php?url=<?= obfuscateUrl('order/delete/' . $order['id']) ?>"
                                       onclick="return confirm('Tem certeza que deseja arquivar este pedido?')">
                                        <i class="bi bi-archive me-2"></i> Arquivar
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('order') ?>">
                                        <i class="bi bi-arrow-left me-2"></i> Voltar para lista
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Informações do Cliente -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">Informações do Cliente</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="bg-light rounded-3 p-3">
                                            <small class="text-muted d-block">Cliente</small>
                                            <div class="mt-1 fw-bold">
                                                <i class="bi bi-person-circle me-1"></i>
                                                <?= htmlspecialchars($order['customer_name']) ?>
                                            </div>
                                            <?php if ($order['customer_email']): ?>
                                                <small class="text-muted"><?= htmlspecialchars($order['customer_email']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="bg-light rounded-3 p-3">
                                            <small class="text-muted d-block">Contato</small>
                                            <div class="mt-1 fw-bold">
                                                <i class="bi bi-telephone me-1"></i>
                                                <?= htmlspecialchars($order['customer_phone'] ?? 'Não informado') ?>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($order['customer_address']): ?>
                                        <div class="col-12">
                                            <div class="bg-light rounded-3 p-3">
                                                <small class="text-muted d-block">Endereço de Entrega</small>
                                                <div class="mt-1">
                                                    <?= nl2br(htmlspecialchars($order['customer_address'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Itens do Pedido -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">Itens do Pedido</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Produto</th>
                                            <th>Código</th>
                                            <th class="text-center">Quantidade</th>
                                            <th class="text-end">Preço Unit.</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php if (!empty($order['items'])): ?>
                                            <?php foreach ($order['items'] as $item): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></div>
                                                        <?php if ($item['description']): ?>
                                                            <small class="text-muted"><?= htmlspecialchars($item['description']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($item['product_code'] ?? '-') ?></td>
                                                    <td class="text-center"><?= $item['quantity'] ?></td>
                                                    <td class="text-end">R$ <?= number_format($item['unit_price'], 2, ',', '.') ?></td>
                                                    <td class="text-end fw-bold">R$ <?= number_format($item['total_price'], 2, ',', '.') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">Nenhum item encontrado</td>
                                            </tr>
                                        <?php endif; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                        <tr>
                                            <td colspan="4" class="text-end fw-bold">Total do Pedido:</td>
                                            <td class="text-end fw-bold text-success">R$ <?= number_format($order['total_amount'], 2, ',', '.') ?></td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Observações -->
                            <?php if ($order['notes']): ?>
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-2">Observações</h6>
                                    <div class="bg-light rounded-3 p-3">
                                        <?= nl2br(htmlspecialchars($order['notes'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <div class="card border-0 bg-light rounded-4 h-100">
                                <div class="card-body d-flex flex-column justify-content-center p-4">
                                    <div class="text-center mb-4">
                                        <div class="<?= $config['class'] ?> rounded-circle p-4 d-inline-block mb-3">
                                            <i class="bi bi-receipt <?= str_replace('bg-', 'text-', explode(' ', $config['class'])[0]) ?>" style="font-size: 2.5rem;"></i>
                                        </div>
                                        <h5 class="fw-bold mb-2">Resumo do Pedido</h5>
                                        <p class="text-muted small">Detalhes e ações</p>
                                    </div>

                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Status:</span>
                                            <span class="badge <?= $config['class'] ?>"><?= $statusOptions[$order['status']] ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Data do Pedido:</span>
                                            <span class="fw-bold"><?= date('d/m/Y', strtotime($order['order_date'])) ?></span>
                                        </div>
                                        <?php if ($order['delivery_date']): ?>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">Entrega Prevista:</span>
                                                <span class="fw-bold"><?= date('d/m/Y', strtotime($order['delivery_date'])) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Itens:</span>
                                            <span class="fw-bold"><?= count($order['items'] ?? []) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-muted">Valor Total:</span>
                                            <span class="fw-bold text-success">R$ <?= number_format($order['total_amount'], 2, ',', '.') ?></span>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <a href="/index.php?url=<?= obfuscateUrl('order/edit/' . $order['id']) ?>" class="btn btn-outline-primary rounded-pill">
                                            <i class="bi bi-pencil me-2"></i> Editar Pedido
                                        </a>
                                        <a href="/index.php?url=<?= obfuscateUrl('order/delete/' . $order['id']) ?>"
                                           class="btn btn-outline-danger rounded-pill"
                                           onclick="return confirm('Tem certeza que deseja arquivar este pedido?')">
                                            <i class="bi bi-archive me-2"></i> Arquivar
                                        </a>
                                        <a href="/index.php?url=<?= obfuscateUrl('order') ?>" class="btn btn-outline-secondary rounded-pill">
                                            <i class="bi bi-arrow-left me-2"></i> Voltar
                                        </a>
                                    </div>

                                    <?php if ($order['deleted_at']): ?>
                                        <div class="mt-3 text-center">
                                            <a href="/index.php?url=<?= obfuscateUrl('order/restore/' . $order['id']) ?>"
                                               class="btn btn-success btn-sm rounded-pill"
                                               onclick="return confirm('Restaurar este pedido?')">
                                                <i class="bi bi-arrow-clockwise me-1"></i> Restaurar Pedido
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas baseados no status -->
            <?php if ($order['status'] == 'pending'): ?>
                <div class="alert alert-warning border-0 rounded-4 bg-opacity-10">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-clock-fill text-warning fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Pedido Pendente</h6>
                            <p class="mb-0">Este pedido está aguardando processamento. Atualize o status quando começar a preparar.</p>
                        </div>
                    </div>
                </div>
            <?php elseif ($order['status'] == 'processing'): ?>
                <div class="alert alert-primary border-0 rounded-4 bg-opacity-10">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-gear-fill text-primary fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Pedido em Processamento</h6>
                            <p class="mb-0">Este pedido está sendo preparado. Atualize para "Enviado" quando despachar.</p>
                        </div>
                    </div>
                </div>
            <?php elseif ($order['status'] == 'shipped'): ?>
                <div class="alert alert-info border-0 rounded-4 bg-opacity-10">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-truck text-info fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Pedido Enviado</h6>
                            <p class="mb-0">Este pedido foi despachado. Atualize para "Entregue" quando o cliente receber.</p>
                        </div>
                    </div>
                </div>
            <?php elseif ($order['status'] == 'delivered'): ?>
                <div class="alert alert-success border-0 rounded-4 bg-opacity-10">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Pedido Entregue</h6>
                            <p class="mb-0">Este pedido foi entregue ao cliente. Parabéns!</p>
                        </div>
                    </div>
                </div>
            <?php elseif ($order['status'] == 'cancelled'): ?>
                <div class="alert alert-danger border-0 rounded-4 bg-opacity-10">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-x-circle-fill text-danger fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Pedido Cancelado</h6>
                            <p class="mb-0">Este pedido foi cancelado. Considere analisar os motivos para melhorias.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .order-table th {
            font-weight: 600;
            background-color: #f8f9fa;
        }
        .order-table tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>