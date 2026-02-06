<?php
// app/views/wallet/index.php

$title = 'Minhas Carteiras';
ob_start();
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Minhas Carteiras</h2>
            <p class="text-muted small mb-0">Gerencie suas carteiras de investimento.</p>
        </div>
        <a href="/index.php?url=<?= obfuscateUrl('wallet/create') ?>" class="btn btn-primary shadow-sm rounded-pill px-4">
            <i class="bi bi-plus-lg me-1"></i> Nova Carteira
        </a>
    </div>

<?php if (empty($wallets)): ?>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body text-center py-5">
            <div class="mb-3">
                <i class="bi bi-wallet text-muted" style="font-size: 3rem;"></i>
            </div>
            <h5 class="text-muted mb-3">Nenhuma carteira encontrada</h5>
            <p class="text-muted mb-4">Crie sua primeira carteira para começar a organizar seus investimentos.</p>
            <a href="/index.php?url=<?= obfuscateUrl('wallet/create') ?>" class="btn btn-primary px-4">
                <i class="bi bi-plus-lg me-1"></i> Criar Primeira Carteira
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($wallets as $wallet): ?>
            <div class="col">
                <div class="card h-100 border-0 shadow-sm rounded-4 hover-shadow transition-all">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                                        <i class="bi bi-wallet text-primary"></i>
                                    </div>
                                    <h5 class="card-title mb-0 fw-bold text-truncate"><?= htmlspecialchars($wallet['name']) ?></h5>
                                </div>

                                <span class="badge rounded-pill <?= $wallet['status'] == 'active' ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' ?> px-3 py-1">
                                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                    <?= $wallet['status'] == 'active' ? 'Ativa' : 'Desativada' ?>
                                </span>
                            </div>

                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary border-0" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu shadow-sm border-0">
                                    <li>
                                        <a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('wallet/view/' . $wallet['id']) ?>">
                                            <i class="bi bi-eye me-2"></i> Visualizar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('wallet/edit/' . $wallet['id']) ?>">
                                            <i class="bi bi-pencil me-2"></i> Editar
                                        </a>
                                    </li>
                                    <?php if ($wallet['status'] == 'active'): ?>
                                        <li>
                                            <a class="dropdown-item text-danger"
                                               href="/index.php?url=<?= obfuscateUrl('wallet/delete/' . $wallet['id']) ?>"
                                               onclick="return confirm('Tem certeza que deseja desativar esta carteira?')">
                                                <i class="bi bi-trash me-2"></i> Desativar
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <?php if (!empty($wallet['description'])): ?>
                            <p class="card-text text-muted small mb-4" style="min-height: 40px;">
                                <?= htmlspecialchars($wallet['description']) ?>
                            </p>
                        <?php else: ?>
                            <p class="card-text text-muted small mb-4" style="min-height: 40px;">
                                <em>Sem descrição</em>
                            </p>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center border-top pt-3">
                            <small class="text-muted">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= date('d/m/Y', strtotime($wallet['created_at'])) ?>
                            </small>
                            <a href="/index.php?url=<?= obfuscateUrl('wallet/view/' . $wallet['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                Ver Detalhes <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4 text-muted small">
        <i class="bi bi-info-circle me-1"></i>
        Total de <?= count($wallets) ?> carteira(s) encontrada(s)
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>