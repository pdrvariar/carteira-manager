<?php
// app/views/project/view.php

use App\Core\Session;

$statusOptions = [
    'planning' => 'Planejamento',
    'started' => 'Em Andamento',
    'paused' => 'Pausado',
    'finished' => 'Concluído',
    'cancelled' => 'Cancelado'
];

$statusConfig = [
    'planning' => ['icon' => 'bi-clock', 'class' => 'bg-secondary bg-opacity-10 text-secondary'],
    'started' => ['icon' => 'bi-play-circle', 'class' => 'bg-primary bg-opacity-10 text-primary'],
    'paused' => ['icon' => 'bi-pause-circle', 'class' => 'bg-warning bg-opacity-10 text-warning'],
    'finished' => ['icon' => 'bi-check-circle', 'class' => 'bg-success bg-opacity-10 text-success'],
    'cancelled' => ['icon' => 'bi-x-circle', 'class' => 'bg-danger bg-opacity-10 text-danger']
];

$config = $statusConfig[$project['project_status']] ?? $statusConfig['planning'];

$title = 'Projeto: ' . htmlspecialchars($project['name']);
$additional_css = '<link rel="stylesheet" href="/css/project.css">';
ob_start();
?>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-4 border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="bi bi-kanban text-primary fs-4"></i>
                            </div>
                            <div>
                                <h4 class="mb-1 fw-bold"><?= htmlspecialchars($project['name']) ?></h4>
                                <p class="text-muted mb-0">
                                <span class="badge <?= $config['class'] ?> px-3 py-1 rounded-pill">
                                    <i class="bi <?= $config['icon'] ?> me-1"></i>
                                    <?= $statusOptions[$project['project_status']] ?? $project['project_status'] ?>
                                </span>
                                    • Criado em <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                                </p>
                            </div>
                        </div>

                        <div class="dropdown">
                            <button class="btn btn-outline-secondary border-0 rounded-circle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu shadow-sm border-0">
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
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('project') ?>">
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
                            <?php if (!empty($project['description'])): ?>
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-2">Descrição</h6>
                                    <div class="bg-light rounded-3 p-3">
                                        <?= nl2br(htmlspecialchars($project['description'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">Informações do Projeto</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="bg-light rounded-3 p-3">
                                            <small class="text-muted d-block">Status</small>
                                            <div class="mt-1 fw-bold">
                                            <span class="badge <?= $config['class'] ?> px-3 py-2 rounded-pill">
                                                <i class="bi <?= $config['icon'] ?> me-1"></i>
                                                <?= $statusOptions[$project['project_status']] ?? $project['project_status'] ?>
                                            </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="bg-light rounded-3 p-3">
                                            <small class="text-muted d-block">Proprietário</small>
                                            <div class="d-flex align-items-center mt-1">
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 0.7rem;">
                                                    <?= strtoupper(substr($project['username'], 0, 1)) ?>
                                                </div>
                                                <span class="fw-bold"><?= htmlspecialchars($project['username']) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="bg-light rounded-3 p-3">
                                            <small class="text-muted d-block">Data de Início</small>
                                            <div class="mt-1 fw-bold">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?= date('d/m/Y', strtotime($project['project_start_date'])) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="bg-light rounded-3 p-3">
                                            <small class="text-muted d-block">Data de Término</small>
                                            <div class="mt-1 fw-bold">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?= $project['project_end_date'] ? date('d/m/Y', strtotime($project['project_end_date'])) : '<span class="text-muted">Não definida</span>' ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="bg-light rounded-3 p-3">
                                            <small class="text-muted d-block">Criado em</small>
                                            <div class="mt-1 fw-bold">
                                                <i class="bi bi-plus-circle me-1"></i>
                                                <?= date('d/m/Y H:i', strtotime($project['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="bg-light rounded-3 p-3">
                                            <small class="text-muted d-block">Última Atualização</small>
                                            <div class="mt-1 fw-bold">
                                                <i class="bi bi-arrow-clockwise me-1"></i>
                                                <?= date('d/m/Y H:i', strtotime($project['updated_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Timeline visual -->
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">Timeline do Projeto</h6>
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <div class="timeline-marker <?= $config['class'] ?>"></div>
                                        <div class="timeline-content">
                                            <h6 class="fw-bold">Status Atual: <?= $statusOptions[$project['project_status']] ?></h6>
                                            <p class="small text-muted mb-2">Desde: <?= date('d/m/Y', strtotime($project['updated_at'])) ?></p>
                                            <?php if ($project['project_status'] == 'finished' && $project['project_end_date']): ?>
                                                <p class="small text-success">
                                                    <i class="bi bi-check-circle me-1"></i>
                                                    Projeto concluído na data prevista
                                                </p>
                                            <?php elseif ($project['project_status'] == 'started'): ?>
                                                <p class="small text-primary">
                                                    <i class="bi bi-play-circle me-1"></i>
                                                    Projeto em andamento
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card border-0 bg-light rounded-4 h-100">
                                <div class="card-body d-flex flex-column justify-content-center p-4">
                                    <div class="text-center mb-4">
                                        <div class="<?= $config['class'] ?> rounded-circle p-4 d-inline-block mb-3">
                                            <i class="bi bi-kanban <?= str_replace('bg-', 'text-', explode(' ', $config['class'])[0]) ?>" style="font-size: 2.5rem;"></i>
                                        </div>
                                        <h5 class="fw-bold mb-2">Controle do Projeto</h5>
                                        <p class="text-muted small">Gerencie seu projeto</p>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <a href="/index.php?url=<?= obfuscateUrl('project/edit/' . $project['id']) ?>" class="btn btn-outline-primary rounded-pill">
                                            <i class="bi bi-pencil me-2"></i> Editar Projeto
                                        </a>
                                        <a href="/index.php?url=<?= obfuscateUrl('project/delete/' . $project['id']) ?>"
                                           class="btn btn-outline-danger rounded-pill"
                                           onclick="return confirm('Tem certeza que deseja arquivar este projeto?')">
                                            <i class="bi bi-archive me-2"></i> Arquivar
                                        </a>
                                        <a href="/index.php?url=<?= obfuscateUrl('project') ?>" class="btn btn-outline-secondary rounded-pill">
                                            <i class="bi bi-arrow-left me-2"></i> Voltar
                                        </a>
                                    </div>

                                    <?php if ($project['deleted_at']): ?>
                                        <div class="mt-3 text-center">
                                            <a href="/index.php?url=<?= obfuscateUrl('project/restore/' . $project['id']) ?>"
                                               class="btn btn-success btn-sm rounded-pill"
                                               onclick="return confirm('Restaurar este projeto?')">
                                                <i class="bi bi-arrow-clockwise me-1"></i> Restaurar Projeto
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
            <?php if ($project['project_status'] == 'finished'): ?>
                <div class="alert alert-success border-0 rounded-4 bg-opacity-10">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Projeto Concluído</h6>
                            <p class="mb-0">Este projeto foi marcado como concluído. Parabéns!</p>
                        </div>
                    </div>
                </div>
            <?php elseif ($project['project_status'] == 'started'): ?>
                <div class="alert alert-primary border-0 rounded-4 bg-opacity-10">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-play-circle-fill text-primary fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Projeto em Andamento</h6>
                            <p class="mb-0">Este projeto está ativo e em desenvolvimento.</p>
                        </div>
                    </div>
                </div>
            <?php elseif ($project['project_status'] == 'paused'): ?>
                <div class="alert alert-warning border-0 rounded-4 bg-opacity-10">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-pause-circle-fill text-warning fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Projeto Pausado</h6>
                            <p class="mb-0">Este projeto está temporariamente pausado. Considere retomá-lo em breve.</p>
                        </div>
                    </div>
                </div>
            <?php elseif ($project['project_status'] == 'cancelled'): ?>
                <div class="alert alert-danger border-0 rounded-4 bg-opacity-10">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-x-circle-fill text-danger fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Projeto Cancelado</h6>
                            <p class="mb-0">Este projeto foi cancelado. Considere analisar os motivos para futuros projetos.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline-marker {
            position: absolute;
            left: -30px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 3px solid #fff;
        }

        .timeline-content {
            padding-left: 10px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
    </style>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>