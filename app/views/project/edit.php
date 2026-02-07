<?php
// app/views/project/edit.php

use App\Core\Session;

$statusOptions = [
    'planning' => 'Planejamento',
    'started' => 'Em Andamento',
    'paused' => 'Pausado',
    'finished' => 'Concluído',
    'cancelled' => 'Cancelado'
];

$statusConfig = [
    'planning' => ['class' => 'bg-secondary bg-opacity-10 text-secondary'],
    'started' => ['class' => 'bg-primary bg-opacity-10 text-primary'],
    'paused' => ['class' => 'bg-warning bg-opacity-10 text-warning'],
    'finished' => ['class' => 'bg-success bg-opacity-10 text-success'],
    'cancelled' => ['class' => 'bg-danger bg-opacity-10 text-danger']
];

$title = 'Editar Projeto: ' . htmlspecialchars($project['name']);
$additional_css = '<link rel="stylesheet" href="/css/project.css">';
ob_start();
?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-4 border-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="bi bi-kanban text-primary fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold">Editar Projeto</h4>
                            <p class="text-muted mb-0">Atualize as informações do seu projeto.</p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="/index.php?url=<?php echo obfuscateUrl('project/update/' . $project['id']); ?>" id="projectForm">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">

                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">
                                Nome do Projeto <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-lg border-0 bg-light rounded-3"
                                   id="name"
                                   name="name"
                                   value="<?= htmlspecialchars($project['name']) ?>"
                                   required>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Descrição</label>
                            <textarea class="form-control border-0 bg-light rounded-3"
                                      id="description"
                                      name="description"
                                      rows="4"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="project_status" class="form-label fw-bold">Status</label>
                                <select class="form-select border-0 bg-light rounded-3" id="project_status" name="project_status">
                                    <?php foreach ($statusOptions as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $value == ($project['project_status'] ?? 'planning') ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="project_start_date" class="form-label fw-bold">
                                    Data de Início <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                       class="form-control border-0 bg-light rounded-3"
                                       id="project_start_date"
                                       name="project_start_date"
                                       required
                                       value="<?= $project['project_start_date'] ?>">
                                <div class="form-text text-danger small" id="startDateError"></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="project_end_date" class="form-label fw-bold">Data de Término Prevista</label>
                            <input type="date"
                                   class="form-control border-0 bg-light rounded-3"
                                   id="project_end_date"
                                   name="project_end_date"
                                   value="<?= !empty($project['project_end_date']) ? $project['project_end_date'] : '' ?>"
                                   placeholder="Selecione uma data (opcional)">
                            <div class="form-text">Deixe em branco se não houver data definida</div>
                            <div class="form-text text-danger small" id="endDateError"></div>
                        </div>

                        <div class="d-flex justify-content-between pt-3 border-top">
                            <div>
                                <a href="/index.php?url=<?= obfuscateUrl('project/view/' . $project['id']) ?>" class="btn btn-outline-secondary px-4 rounded-pill me-2">
                                    <i class="bi bi-x-lg me-1"></i> Cancelar
                                </a>
                                <a href="/index.php?url=<?= obfuscateUrl('project/delete/' . $project['id']) ?>"
                                   class="btn btn-outline-danger px-4 rounded-pill"
                                   onclick="return confirm('Tem certeza que deseja arquivar este projeto?')">
                                    <i class="bi bi-archive me-1"></i> Arquivar
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
                                <small class="text-muted d-block">Status Atual</small>
                                <div class="mt-1">
                                    <?php $config = $statusConfig[$project['project_status']] ?? $statusConfig['planning']; ?>
                                    <span class="badge <?= $config['class'] ?> px-3 py-2 rounded-pill">
                                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                    <?= $statusOptions[$project['project_status']] ?? $project['project_status'] ?>
                                </span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Data de Criação</small>
                                <div class="fw-bold">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($project['created_at'])) ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Última Atualização</small>
                                <div class="fw-bold">
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($project['updated_at'])) ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">ID do Projeto</small>
                                <div class="fw-bold">
                                    <i class="bi bi-hash me-1"></i>
                                    <?= $project['id'] ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Proprietário</small>
                                <div class="fw-bold">
                                    <i class="bi bi-person me-1"></i>
                                    <?= htmlspecialchars($project['username']) ?>
                                </div>
                            </div>
                            <?php if ($project['deleted_at']): ?>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Arquivado em</small>
                                    <div class="fw-bold text-danger">
                                        <i class="bi bi-archive me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($project['deleted_at'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('projectForm');
            const startDateInput = document.getElementById('project_start_date');
            const endDateInput = document.getElementById('project_end_date');
            const startDateError = document.getElementById('startDateError');
            const endDateError = document.getElementById('endDateError');

            // Configurar validação de datas
            startDateInput.addEventListener('change', function() {
                startDateError.textContent = '';

                // Se a data final existe e é anterior à data inicial, ajustar
                if (endDateInput.value && endDateInput.value < this.value) {
                    endDateInput.value = this.value;
                }
                // Definir data mínima para data final
                endDateInput.min = this.value;
            });

            endDateInput.addEventListener('change', function() {
                endDateError.textContent = '';

                // Validar se data final é anterior à data inicial
                if (this.value && this.value < startDateInput.value) {
                    endDateError.textContent = 'A data final não pode ser anterior à data de início.';
                    this.value = startDateInput.value;
                }
            });

            // Validação do formulário
            form.addEventListener('submit', function(e) {
                let isValid = true;

                // Validar data de início
                if (!startDateInput.value) {
                    startDateError.textContent = 'A data de início é obrigatória.';
                    isValid = false;
                }

                // Validar se data final é válida (se preenchida)
                if (endDateInput.value && endDateInput.value < startDateInput.value) {
                    endDateError.textContent = 'A data final não pode ser anterior à data de início.';
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    return false;
                }

                // Limpar campo de data final se estiver vazio
                if (!endDateInput.value || endDateInput.value.trim() === '') {
                    endDateInput.disabled = true; // Desabilitar para não enviar valor vazio
                }
            });
        });
    </script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>