<?php
// app/views/project/index.php

use App\Core\Session;

$title = 'Meus Projetos';
$additional_css = '<link rel="stylesheet" href="/css/project.css">';
ob_start();

// Mapeamento de status
$statusConfig = [
        'planning' => ['label' => 'Planejamento', 'class' => 'bg-secondary bg-opacity-10 text-secondary'],
        'started' => ['label' => 'Em Andamento', 'class' => 'bg-primary bg-opacity-10 text-primary'],
        'paused' => ['label' => 'Pausado', 'class' => 'bg-warning bg-opacity-10 text-warning'],
        'finished' => ['label' => 'Concluído', 'class' => 'bg-success bg-opacity-10 text-success'],
        'cancelled' => ['label' => 'Cancelado', 'class' => 'bg-danger bg-opacity-10 text-danger']
];
?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Meus Projetos</h2>
            <p class="text-muted small mb-0">Gerencie seus projetos e acompanhe o progresso.</p>
        </div>
        <a href="/index.php?url=<?= obfuscateUrl('project/create') ?>" class="btn btn-primary shadow-sm rounded-pill px-4">
            <i class="bi bi-plus-lg me-1"></i> Novo Projeto
        </a>
    </div>

    <!-- Estatísticas -->
<?php if (!empty($stats)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-3">
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($stats as $stat):
                            $config = $statusConfig[$stat['project_status']] ?? $statusConfig['planning'];
                            ?>
                            <div class="d-flex align-items-center">
                        <span class="badge <?= $config['class'] ?> px-3 py-2 rounded-pill">
                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                            <?= $stat['status_label'] ?>: <?= $stat['count'] ?>
                        </span>
                            </div>
                        <?php endforeach; ?>
                        <div class="ms-auto">
                        <span class="text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Total: <?= array_sum(array_column($stats, 'count')) ?> projetos
                        </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($projects)): ?>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body text-center py-5">
            <div class="mb-3">
                <i class="bi bi-kanban text-muted" style="font-size: 3rem;"></i>
            </div>
            <h5 class="text-muted mb-3">Nenhum projeto encontrado</h5>
            <p class="text-muted mb-4">Crie seu primeiro projeto para começar a organizar suas atividades.</p>
            <a href="/index.php?url=<?= obfuscateUrl('project/create') ?>" class="btn btn-primary px-4">
                <i class="bi bi-plus-lg me-1"></i> Criar Primeiro Projeto
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($projects as $project):
            $config = $statusConfig[$project['project_status']] ?? $statusConfig['planning'];
            $progressClass = $project['project_status'] == 'finished' ? 'bg-success' :
                    ($project['project_status'] == 'started' ? 'bg-primary' : 'bg-secondary');
            ?>
            <div class="col">
                <div class="card h-100 border-0 shadow-sm rounded-4 hover-shadow transition-all">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                                        <i class="bi bi-kanban text-primary"></i>
                                    </div>
                                    <h5 class="card-title mb-0 fw-bold text-truncate"><?= htmlspecialchars($project['name']) ?></h5>
                                </div>

                                <span class="badge <?= $config['class'] ?> px-3 py-1 rounded-pill">
                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                            <?= $config['label'] ?>
                        </span>
                            </div>

                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary border-0" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu shadow-sm border-0">
                                    <li>
                                        <a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('project/view/' . $project['id']) ?>">
                                            <i class="bi bi-eye me-2"></i> Visualizar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('project/edit/' . $project['id']) ?>">
                                            <i class="bi bi-pencil me-2"></i> Editar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-danger"
                                           href="/index.php?url=<?= obfuscateUrl('project/delete/' . $project['id']) ?>"
                                           onclick="return confirm('Tem certeza que deseja arquivar este projeto?')">
                                            <i class="bi bi-archive me-2"></i> Arquivar
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <?php if (!empty($project['description'])): ?>
                            <p class="card-text text-muted small mb-4" style="min-height: 60px;">
                                <?= htmlspecialchars(substr($project['description'], 0, 100)) . (strlen($project['description']) > 100 ? '...' : '') ?>
                            </p>
                        <?php else: ?>
                            <p class="card-text text-muted small mb-4" style="min-height: 60px;">
                                <em>Sem descrição</em>
                            </p>
                        <?php endif; ?>

                        <!-- Timeline do projeto -->
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Período</small>
                            <div class="d-flex align-items-center small">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= date('d/m/Y', strtotime($project['project_start_date'])) ?>
                                <?php if ($project['project_end_date']): ?>
                                    <i class="bi bi-arrow-right mx-2"></i>
                                    <?= date('d/m/Y', strtotime($project['project_end_date'])) ?>
                                <?php else: ?>
                                    <span class="ms-2 text-muted">(Sem data final)</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-top pt-3">
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>
                                <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                            </small>
                            <a href="/index.php?url=<?= obfuscateUrl('project/view/' . $project['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
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
        Total de <?= count($projects) ?> projeto(s) encontrado(s)
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>