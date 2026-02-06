<?php
// app/views/wallet/view.php

$title = 'Carteira: ' . htmlspecialchars($wallet['name']);
ob_start();
?>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-4 border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="bi bi-wallet text-primary fs-4"></i>
                            </div>
                            <div>
                                <h4 class="mb-1 fw-bold"><?= htmlspecialchars($wallet['name']) ?></h4>
                                <p class="text-muted mb-0">
                                <span class="badge rounded-pill <?= $wallet['status'] == 'active' ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' ?> px-3 py-1">
                                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                    <?= $wallet['status'] == 'active' ? 'Ativa' : 'Desativada' ?>
                                </span>
                                    • Criada em <?= date('d/m/Y', strtotime($wallet['created_at'])) ?>
                                </p>
                            </div>
                        </div>

                        <div class="dropdown">
                            <button class="btn btn-outline-secondary border-0 rounded-circle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu shadow-sm border-0">
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
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('wallet') ?>">
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
                            <?php if (!empty($wallet['description'])): ?>
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-2">Descrição</h6>
                                    <div class="bg-light rounded-3 p-3">
                                        <?= nl2br(htmlspecialchars($wallet['description'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">Informações da Carteira</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="bg-light rounded-3 p-3">
                                            <small class="text-muted d-block">Proprietário</small>
                                            <div class="d-flex align-items-center mt-1">
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 0.7rem;">
                                                    <?= strtoupper(substr($wallet['username'], 0, 1)) ?>
                                                </div>
                                                <span class="fw-bold"><?= htmlspecialchars($wallet['username']) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="bg-light rounded-3 p-3">
                                            <small class="text-muted d-block">Status</small>
                                            <div class="mt-1">
                                            <span class="badge <?= $wallet['status'] == 'active' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' ?> px-3 py-2 rounded-pill">
                                                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                                <?= $wallet['status'] == 'active' ? 'Ativa' : 'Desativada' ?>
                                            </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="bg-light rounded-3 p-3">
                                            <small class="text-muted d-block">Data de Criação</small>
                                            <div class="mt-1 fw-bold">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?= date('d/m/Y', strtotime($wallet['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="bg-light rounded-3 p-3">
                                            <small class="text-muted d-block">Última Atualização</small>
                                            <div class="mt-1 fw-bold">
                                                <i class="bi bi-arrow-clockwise me-1"></i>
                                                <?= date('d/m/Y', strtotime($wallet['updated_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card border-0 bg-light rounded-4 h-100">
                                <div class="card-body d-flex flex-column justify-content-center p-4">
                                    <div class="text-center mb-4">
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-4 d-inline-block mb-3">
                                            <i class="bi bi-wallet text-primary" style="font-size: 2.5rem;"></i>
                                        </div>
                                        <h5 class="fw-bold mb-2">Carteira</h5>
                                        <p class="text-muted small">Gerenciamento de investimentos</p>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <a href="/index.php?url=<?= obfuscateUrl('wallet/edit/' . $wallet['id']) ?>" class="btn btn-outline-primary rounded-pill">
                                            <i class="bi bi-pencil me-2"></i> Editar Carteira
                                        </a>
                                        <?php if ($wallet['status'] == 'active'): ?>
                                            <a href="/index.php?url=<?= obfuscateUrl('wallet/delete/' . $wallet['id']) ?>"
                                               class="btn btn-outline-danger rounded-pill"
                                               onclick="return confirm('Tem certeza que deseja desativar esta carteira?')">
                                                <i class="bi bi-trash me-2"></i> Desativar
                                            </a>
                                        <?php endif; ?>
                                        <a href="/index.php?url=<?= obfuscateUrl('wallet') ?>" class="btn btn-outline-secondary rounded-pill">
                                            <i class="bi bi-arrow-left me-2"></i> Voltar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($wallet['status'] == 'active'): ?>
                <div class="alert alert-success border-0 rounded-4 bg-opacity-10">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Carteira Ativa</h6>
                            <p class="mb-0">Esta carteira está ativa e pronta para uso. Você pode vinculá-la a portfólios ou transações.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning border-0 rounded-4 bg-opacity-10">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Carteira Desativada</h6>
                            <p class="mb-0">Esta carteira está desativada. Para reativá-la, edite a carteira e altere o status para "Ativa".</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>