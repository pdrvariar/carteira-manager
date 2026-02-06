<?php
// app/views/wallet/edit.php

use App\Core\Session;

$title = 'Editar Carteira: ' . htmlspecialchars($wallet['name']);
ob_start();
?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-4 border-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="bi bi-wallet text-primary fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold">Editar Carteira</h4>
                            <p class="text-muted mb-0">Atualize as informações da sua carteira.</p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="/index.php?url=<?php echo obfuscateUrl('wallet/update/' . $wallet['id']); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">

                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">
                                Nome da Carteira <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-lg border-0 bg-light rounded-3"
                                   id="name"
                                   name="name"
                                   value="<?= htmlspecialchars($wallet['name']) ?>"
                                   required
                                   placeholder="Ex: Carteira Conservadora, Renda Variável, etc.">
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Descrição</label>
                            <textarea class="form-control border-0 bg-light rounded-3"
                                      id="description"
                                      name="description"
                                      rows="4"
                                      placeholder="Descreva os objetivos desta carteira, estratégias, ou observações importantes..."><?= htmlspecialchars($wallet['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Status</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="status_active" value="active" <?= $wallet['status'] == 'active' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="status_active">
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-3">
                                        <i class="bi bi-check-circle me-1"></i> Ativa
                                    </span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="status_disabled" value="disabled" <?= $wallet['status'] == 'disabled' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="status_disabled">
                                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-3">
                                        <i class="bi bi-x-circle me-1"></i> Desativada
                                    </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between pt-3 border-top">
                            <div>
                                <a href="/index.php?url=<?= obfuscateUrl('wallet/view/' . $wallet['id']) ?>" class="btn btn-outline-secondary px-4 rounded-pill me-2">
                                    <i class="bi bi-x-lg me-1"></i> Cancelar
                                </a>
                                <a href="/index.php?url=<?= obfuscateUrl('wallet/delete/' . $wallet['id']) ?>"
                                   class="btn btn-outline-danger px-4 rounded-pill"
                                   onclick="return confirm('Tem certeza que deseja desativar esta carteira?')">
                                    <i class="bi bi-trash me-1"></i> Desativar
                                </a>
                            </div>
                            <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">
                                <i class="bi bi-save me-1"></i> Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-4">
                <div class="card border-0 bg-light rounded-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">Informações do Sistema</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Data de Criação</small>
                                <div class="fw-bold">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($wallet['created_at'])) ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Última Atualização</small>
                                <div class="fw-bold">
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($wallet['updated_at'])) ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">ID da Carteira</small>
                                <div class="fw-bold">
                                    <i class="bi bi-hash me-1"></i>
                                    <?= $wallet['id'] ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Proprietário</small>
                                <div class="fw-bold">
                                    <i class="bi bi-person me-1"></i>
                                    <?= htmlspecialchars($wallet['username']) ?>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>