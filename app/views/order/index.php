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

// Garantir que as variáveis do controller existam
$filters = $filters ?? ['search' => '', 'status' => '', 'date_from' => '', 'date_to' => '', 'sort' => 'created_at', 'order' => 'desc'];
$limit = $limit ?? 10;
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;

// Funções Auxiliares para URL e Ordenação (Movidas para o topo)
if (!function_exists('buildSortUrl')) {
    function buildSortUrl($column, $filters, $limit) {
        $params = $filters;
        $params['sort'] = $column;
        $params['order'] = ($filters['sort'] == $column && $filters['order'] == 'asc') ? 'desc' : 'asc';
        $params['limit'] = $limit;
        $params['url'] = $_GET['url'] ?? '';
        return '/index.php?' . http_build_query($params);
    }
}

if (!function_exists('getSortIcon')) {
    function getSortIcon($column, $filters) {
        if ($filters['sort'] != $column) return '<i class="bi bi-arrow-down-up smaller opacity-25"></i>';
        return $filters['order'] == 'asc' ? '<i class="bi bi-sort-up text-primary"></i>' : '<i class="bi bi-sort-down text-primary"></i>';
    }
}

if (!function_exists('buildPaginationUrl')) {
    function buildPaginationUrl($p, $filters, $limit) {
        $params = $filters;
        $params['page'] = $p;
        $params['limit'] = $limit;
        $params['url'] = $_GET['url'] ?? '';
        return '/index.php?' . http_build_query($params);
    }
}
?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-0">Meus Pedidos</h2>
            <p class="text-muted small mb-0">Gerencie seus pedidos e acompanhe o status em tempo real.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary rounded-pill px-3" type="button" data-bs-toggle="collapse" data-bs-target="#advancedSearch">
                <i class="bi bi-filter me-1"></i> Filtros
            </button>
            <a href="/index.php?url=<?= obfuscateUrl('order/create') ?>" class="btn btn-primary shadow-sm rounded-pill px-4">
                <i class="bi bi-plus-lg me-1"></i> Novo Pedido
            </a>
        </div>
    </div>

    <!-- Busca e Filtros Avançados -->
    <div class="collapse <?= !empty(array_filter($filters)) ? 'show' : '' ?> mb-4" id="advancedSearch">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form action="/index.php" method="GET" class="row g-3">
                    <input type="hidden" name="url" value="<?= $_GET['url'] ?? '' ?>">
                    
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Pesquisa Geral</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control bg-light border-start-0" 
                                   placeholder="Cliente ou Nº do pedido..." value="<?= htmlspecialchars($filters['search']) ?>">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" class="form-select bg-light">
                            <option value="">Todos</option>
                            <?php foreach ($statusConfig as $key => $config): ?>
                                <option value="<?= $key ?>" <?= $filters['status'] == $key ? 'selected' : '' ?>>
                                    <?= $config['label'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Desde</label>
                        <input type="date" name="date_from" class="form-control bg-light" value="<?= $filters['date_from'] ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Até</label>
                        <input type="date" name="date_to" class="form-control bg-light" value="<?= $filters['date_to'] ?>">
                    </div>

                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100 rounded-3">
                            Filtrar
                        </button>
                        <a href="/index.php?url=<?= $_GET['url'] ?? '' ?>" class="btn btn-outline-secondary rounded-3" title="Limpar">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
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
            <p class="text-muted mb-4">Ajuste seus filtros ou crie um novo pedido.</p>
            <a href="/index.php?url=<?= obfuscateUrl('order/create') ?>" class="btn btn-primary px-4">
                <i class="bi bi-plus-lg me-1"></i> Criar Novo Pedido
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4 py-3">
                        <a href="<?= buildSortUrl('order_number', $filters, $limit) ?>" class="text-decoration-none text-muted">
                            Pedido <?= getSortIcon('order_number', $filters) ?>
                        </a>
                    </th>
                    <th class="py-3">
                        <a href="<?= buildSortUrl('customer_name', $filters, $limit) ?>" class="text-decoration-none text-muted">
                            Cliente <?= getSortIcon('customer_name', $filters) ?>
                        </a>
                    </th>
                    <th class="py-3">
                        <a href="<?= buildSortUrl('status', $filters, $limit) ?>" class="text-decoration-none text-muted">
                            Status <?= getSortIcon('status', $filters) ?>
                        </a>
                    </th>
                    <th class="py-3">
                        <a href="<?= buildSortUrl('total_amount', $filters, $limit) ?>" class="text-decoration-none text-muted">
                            Total <?= getSortIcon('total_amount', $filters) ?>
                        </a>
                    </th>
                    <th class="py-3">
                        <a href="<?= buildSortUrl('order_date', $filters, $limit) ?>" class="text-decoration-none text-muted">
                            Data <?= getSortIcon('order_date', $filters) ?>
                        </a>
                    </th>
                    <th class="pe-4 py-3 text-end">Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order):
                    $config = $statusConfig[$order['status']] ?? $statusConfig['pending'];
                    $totalAmount = $order['calculated_total'] ?? $order['total_amount'];
                    ?>
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold text-dark"><?= $order['order_number'] ?></span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-2 d-none d-sm-flex">
                                    <i class="bi bi-person small"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($order['customer_name']) ?></div>
                                    <div class="text-muted smaller"><?= htmlspecialchars($order['customer_email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= $config['class'] ?> rounded-pill px-3 py-1">
                                <i class="bi <?= $config['icon'] ?> me-1"></i>
                                <?= $config['label'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold text-success">R$ <?= number_format($totalAmount, 2, ',', '.') ?></span>
                            <div class="smaller text-muted"><?= $order['items_count'] ?> item(ns)</div>
                        </td>
                        <td>
                            <div class="text-dark small"><?= date('d/m/Y', strtotime($order['order_date'])) ?></div>
                            <div class="text-muted smaller"><?= date('H:i', strtotime($order['created_at'])) ?></div>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="btn-group shadow-sm">
                                <a href="/index.php?url=<?= obfuscateUrl('order/view/' . $order['id']) ?>" class="btn btn-sm btn-white border px-2" title="Ver Detalhes">
                                    <i class="bi bi-eye text-primary"></i>
                                </a>
                                <a href="/index.php?url=<?= obfuscateUrl('order/edit/' . $order['id']) ?>" class="btn btn-sm btn-white border px-2" title="Editar">
                                    <i class="bi bi-pencil text-warning"></i>
                                </a>
                                <a href="/index.php?url=<?= obfuscateUrl('order/clone/' . $order['id']) ?>" class="btn btn-sm btn-white border px-2" title="Clonar Pedido">
                                    <i class="bi bi-copy text-info"></i>
                                </a>
                                <a href="/index.php?url=<?= obfuscateUrl('order/delete/' . $order['id']) ?>" 
                                   class="btn btn-sm btn-white border px-2" 
                                   title="Arquivar"
                                   onclick="return confirm('Tem certeza que deseja arquivar este pedido?')">
                                    <i class="bi bi-archive text-danger"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <div class="card-footer bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Mostrar</span>
                <select class="form-select form-select-sm w-auto rounded-3" onchange="window.location.href = this.value">
                    <?php foreach ([5, 10, 25, 50, 100] as $l): ?>
                        <option value="<?= buildPaginationUrl(1, $filters, $l) ?>" <?= $limit == $l ? 'selected' : '' ?>>
                            <?= $l ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="text-muted small">por página</span>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination pagination-sm justify-content-end mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link rounded-start-pill px-3" href="<?= buildPaginationUrl($page - 1, $filters, $limit) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= buildPaginationUrl(1, $filters, $limit) ?>">1</a></li>
                            <?php if ($startPage > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif;
                        endif;

                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= buildPaginationUrl($i, $filters, $limit) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor;

                        if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item"><a class="page-link" href="<?= buildPaginationUrl($totalPages, $filters, $limit) ?>"><?= $totalPages ?></a></li>
                        <?php endif; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link rounded-end-pill px-3" href="<?= buildPaginationUrl($page + 1, $filters, $limit) ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>