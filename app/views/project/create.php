<?php
// app/views/project/create.php

use App\Core\Session;

$statusOptions = [
    'planning' => 'Planejamento',
    'started' => 'Em Andamento',
    'paused' => 'Pausado',
    'finished' => 'Concluído',
    'cancelled' => 'Cancelado'
];

$title = 'Novo Projeto';
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
                            <h4 class="mb-1 fw-bold">Criar Novo Projeto</h4>
                            <p class="text-muted mb-0">Organize suas atividades e acompanhe o progresso.</p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="/index.php?url=<?php echo obfuscateUrl('project/create'); ?>" id="projectForm">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">

                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">
                                Nome do Projeto <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-lg border-0 bg-light rounded-3"
                                   id="name"
                                   name="name"
                                   required
                                   placeholder="Ex: Desenvolvimento App, Site Corporativo, etc.">
                            <div class="form-text">Dê um nome claro e objetivo para seu projeto</div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Descrição</label>
                            <textarea class="form-control border-0 bg-light rounded-3"
                                      id="description"
                                      name="description"
                                      rows="4"
                                      placeholder="Descreva os objetivos, escopo, entregáveis e observações importantes..."></textarea>
                            <div class="form-text">Opcional - Detalhe seu projeto para melhor organização</div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="project_status" class="form-label fw-bold">Status</label>
                                <select class="form-select border-0 bg-light rounded-3" id="project_status" name="project_status">
                                    <?php foreach ($statusOptions as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $value == 'planning' ? 'selected' : '' ?>>
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
                                       value="<?= date('Y-m-d') ?>">
                                <div class="form-text text-danger small" id="startDateError"></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="project_end_date" class="form-label fw-bold">Data de Término Prevista</label>
                            <input type="date"
                                   class="form-control border-0 bg-light rounded-3"
                                   id="project_end_date"
                                   name="project_end_date"
                                   placeholder="Selecione uma data (opcional)">
                            <div class="form-text">Opcional - Deixe em branco se não houver data definida</div>
                            <div class="form-text text-danger small" id="endDateError"></div>
                        </div>

                        <div class="d-flex justify-content-between pt-3 border-top">
                            <a href="/index.php?url=<?= obfuscateUrl('project') ?>" class="btn btn-outline-secondary px-4 rounded-pill">
                                <i class="bi bi-arrow-left me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> Criar Projeto
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
                            <h6 class="fw-bold mb-1">Dicas para gerenciar projetos:</h6>
                            <ul class="mb-0 ps-3">
                                <li class="small">Defina datas realistas para início e término</li>
                                <li class="small">Atualize o status conforme o projeto evolui</li>
                                <li class="small">Use descrições detalhadas para acompanhar o escopo</li>
                                <li class="small">Revisite projetos pausados regularmente</li>
                            </ul>
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

            // Configurar data mínima para hoje
            const today = new Date().toISOString().split('T')[0];
            startDateInput.min = today;

            // Configurar validação de datas
            startDateInput.addEventListener('change', function() {
                // Limpar mensagens de erro
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