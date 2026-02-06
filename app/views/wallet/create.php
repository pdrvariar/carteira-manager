<?php
// app/views/wallet/create.php

$title = 'Nova Carteira';
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
                            <h4 class="mb-1 fw-bold">Criar Nova Carteira</h4>
                            <p class="text-muted mb-0">Organize seus investimentos em carteiras personalizadas.</p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="/index.php?url=<?php echo obfuscateUrl('wallet/create'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">

                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">
                                Nome da Carteira <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-lg border-0 bg-light rounded-3"
                                   id="name"
                                   name="name"
                                   required
                                   placeholder="Ex: Carteira Conservadora, Renda Variável, etc.">
                            <div class="form-text">Dê um nome descritivo para sua carteira</div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Descrição</label>
                            <textarea class="form-control border-0 bg-light rounded-3"
                                      id="description"
                                      name="description"
                                      rows="4"
                                      placeholder="Descreva os objetivos desta carteira, estratégias, ou observações importantes..."></textarea>
                            <div class="form-text">Opcional - Adicione detalhes sobre esta carteira</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Status Inicial</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="status_active" value="active" checked>
                                    <label class="form-check-label" for="status_active">
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-3">
                                        <i class="bi bi-check-circle me-1"></i> Ativa
                                    </span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="status_disabled" value="disabled">
                                    <label class="form-check-label" for="status_disabled">
                                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-3">
                                        <i class="bi bi-x-circle me-1"></i> Desativada
                                    </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between pt-3 border-top">
                            <a href="/index.php?url=<?= obfuscateUrl('wallet') ?>" class="btn btn-outline-secondary px-4 rounded-pill">
                                <i class="bi bi-arrow-left me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> Criar Carteira
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-4">
                <div class="alert alert-info border-0 rounded-4 bg-opacity-10">
                    <div class="d-flex">
                        <i class="bi bi-lightbulb text-info fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Dicas para organizar suas carteiras:</h6>
                            <ul class="mb-0 ps-3">
                                <li class="small">Crie carteiras por tipo de investimento (Renda Fixa, Ações, ETFs)</li>
                                <li class="small">Separe por objetivos (Aposentadoria, Educação, Curto Prazo)</li>
                                <li class="small">Use carteiras para diferentes níveis de risco (Conservadora, Moderada, Agressiva)</li>
                            </ul>
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