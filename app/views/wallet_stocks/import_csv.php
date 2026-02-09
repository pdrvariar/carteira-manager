<?php
// app/views/wallet_stocks/import_csv.php

use App\Core\Session;

$title = 'Importar CSV - ' . htmlspecialchars($wallet['name']);
$additional_css = '
    <link rel="stylesheet" href="/css/wallet_stocks.css">
    <style>
        .drop-zone {
            border: 3px dashed #6c757d;
            border-radius: 15px;
            padding: 60px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            position: relative;
            overflow: hidden;
        }
        
        .drop-zone:hover {
            border-color: #0d6efd;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.1);
        }
        
        .drop-zone.dragover {
            border-color: #198754;
            background: linear-gradient(135deg, #d1e7dd 0%, #a3cfbb 100%);
            animation: pulse-green 1.5s infinite;
        }
        
        .drop-zone-icon {
            font-size: 64px;
            margin-bottom: 20px;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        
        .drop-zone:hover .drop-zone-icon {
            color: #0d6efd;
            transform: scale(1.1);
        }
        
        .drop-zone.dragover .drop-zone-icon {
            color: #198754;
            animation: bounce 0.5s infinite alternate;
        }
        
        .drop-zone-text h5 {
            font-weight: 700;
            margin-bottom: 10px;
            color: #343a40;
        }
        
        .drop-zone-text p {
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .file-info {
            margin-top: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 10px;
            border: 2px solid #dee2e6;
            display: none;
        }
        
        .file-info.show {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        .file-details {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .file-icon {
            font-size: 36px;
            color: #28a745;
        }
        
        .file-name {
            font-weight: 600;
            color: #343a40;
            word-break: break-all;
        }
        
        .file-size {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(25, 135, 84, 0); }
            100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
        }
        
        @keyframes bounce {
            from { transform: translateY(0px); }
            to { transform: translateY(-10px); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .drag-instructions {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-left: 4px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .drag-instructions h6 {
            color: #856404;
            font-weight: 700;
        }
        
        .drag-instructions ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        
        .drag-instructions li {
            color: #856404;
            margin-bottom: 5px;
        }
        
        .example-container {
            background: #1a1a1a;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .example-header {
            background: #2d2d2d;
            padding: 10px 15px;
            border-bottom: 1px solid #404040;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .example-header h6 {
            color: #f8f9fa;
            margin: 0;
            font-weight: 600;
        }
        
        .example-header .badge {
            background: #495057;
            color: #f8f9fa;
        }
        
        .example-code {
            padding: 15px;
            margin: 0;
            color: #20c997;
            font-family: "Consolas", "Monaco", "Courier New", monospace;
            font-size: 0.9rem;
            line-height: 1.5;
            overflow-x: auto;
        }
        
        .btn-browse {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-browse:hover {
            background: linear-gradient(135deg, #495057 0%, #343a40 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
';

ob_start();
?>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-4 border-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="bi bi-file-earmark-arrow-up text-primary fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold">Importar Composição via CSV</h4>
                            <p class="text-muted mb-0">
                                <?= htmlspecialchars($wallet['name']) ?> - Envie seu arquivo CSV para importar ações
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Instruções de Arrastar e Soltar -->
                    <div class="drag-instructions">
                        <h6><i class="bi bi-lightbulb me-2"></i>Como importar:</h6>
                        <ul>
                            <li><strong>Arraste e solte</strong> seu arquivo CSV na área abaixo, ou</li>
                            <li><strong>Clique para navegar</strong> e selecionar o arquivo do seu computador</li>
                            <li>Formato exigido: TICKER;QUANTIDADE;PRECO MEDIO;% ALOCAÇÃO</li>
                        </ul>
                    </div>

                    <form method="POST" action="/index.php?url=<?= obfuscateUrl('wallet_stocks/import_csv/' . $wallet['id']) ?>" enctype="multipart/form-data" id="csvImportForm">
                        <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken(); ?>">

                        <!-- Área de Arrastar e Soltar -->
                        <div class="drop-zone mb-4" id="dropZone">
                            <input type="file"
                                   class="d-none"
                                   id="csv_file"
                                   name="csv_file"
                                   accept=".csv"
                                   required>

                            <div class="drop-zone-icon">
                                <i class="bi bi-cloud-arrow-up"></i>
                            </div>

                            <div class="drop-zone-text">
                                <h5>Arraste e solte seu arquivo CSV aqui</h5>
                                <p>ou clique para selecionar o arquivo</p>
                                <button type="button" class="btn-browse" onclick="document.getElementById('csv_file').click()">
                                    <i class="bi bi-folder2-open me-2"></i>Procurar arquivo
                                </button>
                            </div>

                            <div class="text-muted small mt-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Tamanho máximo: 5MB | Formato: .csv
                            </div>
                        </div>

                        <!-- Informações do arquivo selecionado -->
                        <div class="file-info" id="fileInfo">
                            <div class="file-details">
                                <div class="file-icon">
                                    <i class="bi bi-filetype-csv"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="file-name" id="fileName">Nenhum arquivo selecionado</div>
                                    <div class="file-size" id="fileSize">-</div>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearFile()">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Exemplo do formato -->
                        <div class="mt-4">
                            <h5 class="fw-bold mb-3">Formato do arquivo CSV</h5>
                            <div class="example-container">
                                <div class="example-header">
                                    <h6><i class="bi bi-file-earmark-code me-2"></i>Estrutura do CSV</h6>
                                    <span class="badge">Exemplo</span>
                                </div>
                                <pre class="example-code">TICKER;QUANTIDADE;PRECO MEDIO POR ACAO;% ALOCAÇÂO ATIVO
PETR4;100;17.50;5.24566
VALE3;114;17.53;5.15566
ITSA4;200;8.75;10.0
BBDC4;150;15.20;7.5</pre>
                            </div>

                            <div class="alert alert-info border-0 bg-opacity-10 rounded-3 mt-3">
                                <div class="d-flex">
                                    <i class="bi bi-exclamation-circle-fill text-info fs-5 me-3"></i>
                                    <div>
                                        <h6 class="fw-bold mb-2">Observações importantes:</h6>
                                        <ul class="mb-0">
                                            <li>Use <strong>ponto (.)</strong> como separador decimal (ex: 17.50)</li>
                                            <li>Separador de colunas: <strong>ponto e vírgula (;)</strong></li>
                                            <li>A porcentagem de alocação pode ser informada como <strong>5.24566</strong> (já em %) ou <strong>0.0524566</strong> (decimal)</li>
                                            <li><strong>Ações já existentes serão atualizadas</strong> com os novos valores</li>
                                            <li>Arquivos muito grandes podem levar alguns segundos para processar</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botões de ação -->
                        <div class="d-flex justify-content-between pt-4 mt-4 border-top">
                            <a href="/index.php?url=<?= obfuscateUrl('wallet_stocks/index/' . $wallet['id']) ?>"
                               class="btn btn-outline-secondary px-4 rounded-pill">
                                <i class="bi bi-arrow-left me-1"></i> Voltar
                            </a>

                            <div>
                                <button type="reset" class="btn btn-outline-danger px-4 rounded-pill me-2" onclick="clearFile()">
                                    <i class="bi bi-trash me-1"></i> Limpar
                                </button>
                                <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm" id="submitBtn" disabled>
                                    <i class="bi bi-upload me-1"></i> Iniciar Importação
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('csv_file');
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('csvImportForm');

            // Função para exibir informações do arquivo
            function showFileInfo(file) {
                fileName.textContent = file.name;

                // Formatar tamanho do arquivo
                const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
                fileSize.textContent = `${sizeInMB} MB`;

                fileInfo.classList.add('show');
                submitBtn.disabled = false;
            }

            // Função para limpar arquivo selecionado
            window.clearFile = function() {
                fileInput.value = '';
                fileInfo.classList.remove('show');
                fileName.textContent = 'Nenhum arquivo selecionado';
                fileSize.textContent = '-';
                submitBtn.disabled = true;
                dropZone.classList.remove('dragover');
            };

            // Evento de clique na área de drop
            dropZone.addEventListener('click', function(e) {
                if (e.target !== fileInput) {
                    fileInput.click();
                }
            });

            // Evento de mudança no input de arquivo
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const file = this.files[0];

                    // Validação de tipo de arquivo
                    if (!file.name.toLowerCase().endsWith('.csv')) {
                        alert('Por favor, selecione um arquivo CSV válido.');
                        clearFile();
                        return;
                    }

                    // Validação de tamanho (5MB)
                    const maxSize = 5 * 1024 * 1024;
                    if (file.size > maxSize) {
                        alert('O arquivo é muito grande. Tamanho máximo: 5MB');
                        clearFile();
                        return;
                    }

                    showFileInfo(file);
                }
            });

            // Prevenir comportamentos padrão de drag and drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            // Highlight da área de drop
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });

            function highlight() {
                dropZone.classList.add('dragover');
            }

            function unhighlight() {
                dropZone.classList.remove('dragover');
            }

            // Processar arquivo solto
            dropZone.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length > 0) {
                    const file = files[0];

                    // Validações
                    if (!file.name.toLowerCase().endsWith('.csv')) {
                        alert('Por favor, solte apenas arquivos CSV.');
                        return;
                    }

                    const maxSize = 5 * 1024 * 1024;
                    if (file.size > maxSize) {
                        alert('O arquivo é muito grande. Tamanho máximo: 5MB');
                        return;
                    }

                    // Criar um novo DataTransfer para atribuir ao input
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    fileInput.files = dataTransfer.files;

                    // Disparar evento change
                    const event = new Event('change');
                    fileInput.dispatchEvent(event);
                }
            });

            // Feedback visual durante envio
            form.addEventListener('submit', function(e) {
                const file = fileInput.files[0];
                if (!file) {
                    e.preventDefault();
                    alert('Por favor, selecione um arquivo CSV.');
                    return;
                }

                // Mostrar loading
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Importando...';
                submitBtn.disabled = true;

                // Feedback visual
                // IMPORTANTE: Não altere o dropZone.innerHTML diretamente pois contém o fileInput
                // Em vez disso, oculte os elementos internos ou use um overlay
                const dropZoneContent = dropZone.querySelector('.drop-zone-text');
                const dropZoneIcon = dropZone.querySelector('.drop-zone-icon');
                const fileInfoElement = document.getElementById('fileInfo');
                
                if (dropZoneContent) dropZoneContent.style.display = 'none';
                if (dropZoneIcon) dropZoneIcon.style.display = 'none';
                if (fileInfoElement) fileInfoElement.style.display = 'none';

                const loadingDiv = document.createElement('div');
                loadingDiv.className = 'text-center py-5';
                loadingDiv.innerHTML = `
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <h5 class="fw-bold">Processando arquivo...</h5>
                    <p class="text-muted">Isso pode levar alguns instantes</p>
                    <div class="progress mt-3 mx-auto" style="height: 10px; max-width: 300px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                    </div>
                `;
                dropZone.appendChild(loadingDiv);
            });
        });
    </script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>