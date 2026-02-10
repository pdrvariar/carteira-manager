<?php
// app/views/order/create.php

use App\Core\Session;

$statusOptions = [
    'pending' => 'Pendente',
    'processing' => 'Processando',
    'shipped' => 'Enviado',
    'delivered' => 'Entregue',
    'cancelled' => 'Cancelado'
];

$title = 'Novo Pedido';
$additional_css = '
    <link rel="stylesheet" href="/css/order.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
';
$additional_js = '
    <script src="/js/order.js" defer></script>
';
ob_start();
?>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-4 border-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="bi bi-receipt text-primary fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold">Criar Novo Pedido</h4>
                            <p class="text-muted mb-0">Registre um novo pedido com cliente e itens.</p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="/index.php?url=<?php echo obfuscateUrl('order/create'); ?>" id="orderForm">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">

                        <!-- Seção: Dados do Cliente -->
                        <div class="mb-5">
                            <h5 class="fw-bold mb-3 text-primary">
                                <i class="bi bi-person-badge me-2"></i>
                                Dados do Cliente
                            </h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="customer_name" class="form-label fw-bold">
                                        Nome do Cliente <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control border-0 bg-light rounded-3"
                                           id="customer_name"
                                           name="customer_name"
                                           required
                                           placeholder="Ex: João da Silva">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="customer_email" class="form-label fw-bold">E-mail</label>
                                    <input type="email"
                                           class="form-control border-0 bg-light rounded-3"
                                           id="customer_email"
                                           name="customer_email"
                                           placeholder="cliente@exemplo.com">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="customer_phone" class="form-label fw-bold">Telefone</label>
                                    <input type="tel"
                                           class="form-control border-0 bg-light rounded-3"
                                           id="customer_phone"
                                           name="customer_phone"
                                           placeholder="(11) 99999-9999">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="order_date" class="form-label fw-bold">
                                        Data do Pedido <span class="text-danger">*</span>
                                    </label>
                                    <input type="date"
                                           class="form-control border-0 bg-light rounded-3"
                                           id="order_date"
                                           name="order_date"
                                           required
                                           value="<?= date('Y-m-d') ?>">
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="customer_address" class="form-label fw-bold">Endereço de Entrega</label>
                                    <textarea class="form-control border-0 bg-light rounded-3"
                                              id="customer_address"
                                              name="customer_address"
                                              rows="2"
                                              placeholder="Rua, número, bairro, cidade - Estado"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Seção: Itens do Pedido -->
                        <div class="mb-5">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0 text-primary">
                                    <i class="bi bi-cart me-2"></i>
                                    Itens do Pedido
                                </h5>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" id="addItemBtn">
                                    <i class="bi bi-plus-circle me-1"></i> Adicionar Item
                                </button>
                            </div>

                            <div id="itemsContainer">
                                <!-- Itens serão adicionados aqui via JavaScript -->
                            </div>

                            <div class="card border-0 bg-light mt-3">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="fw-bold">Total do Pedido:</span>
                                            <div class="text-muted small" id="totalItems">0 itens</div>
                                        </div>
                                        <div class="text-end">
                                            <h4 class="fw-bold text-success mb-0" id="orderTotal">R$ 0,00</h4>
                                            <small class="text-muted" id="totalCalculated">Valor calculado automaticamente</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Seção: Informações Adicionais -->
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3 text-primary">
                                <i class="bi bi-info-circle me-2"></i>
                                Informações Adicionais
                            </h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label fw-bold">Status do Pedido</label>
                                    <select class="form-select border-0 bg-light rounded-3" id="status" name="status">
                                        <?php foreach ($statusOptions as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $value == 'pending' ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="delivery_date" class="form-label fw-bold">Data de Entrega Prevista</label>
                                    <input type="date"
                                           class="form-control border-0 bg-light rounded-3"
                                           id="delivery_date"
                                           name="delivery_date">
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="notes" class="form-label fw-bold">Observações</label>
                                    <textarea class="form-control border-0 bg-light rounded-3"
                                              id="notes"
                                              name="notes"
                                              rows="3"
                                              placeholder="Informações adicionais sobre o pedido..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between pt-4 border-top">
                            <a href="/index.php?url=<?= obfuscateUrl('order') ?>" class="btn btn-outline-secondary px-4 rounded-pill">
                                <i class="bi bi-arrow-left me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> Criar Pedido
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
                            <h6 class="fw-bold mb-1">Dicas para criar pedidos:</h6>
                            <ul class="mb-0 ps-3">
                                <li class="small">Preencha os dados do cliente corretamente para facilitar a comunicação</li>
                                <li class="small">Adicione todos os itens do pedido com quantidades e preços precisos</li>
                                <li class="small">Atualize o status conforme o pedido evolui (processando → enviado → entregue)</li>
                                <li class="small">Use as observações para informações importantes sobre entrega ou pagamento</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template para novo item (usado pelo JavaScript) -->
    <template id="itemTemplate">
        <div class="item-row card border-0 bg-light mb-3">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-md-4 mb-2">
                        <input type="text"
                               class="form-control border-0 bg-white"
                               name="items[ITEM_INDEX][product_name]"
                               placeholder="Nome do produto"
                               required>
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="text"
                               class="form-control border-0 bg-white"
                               name="items[ITEM_INDEX][product_code]"
                               placeholder="Código">
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="number"
                               class="form-control border-0 bg-white quantity"
                               name="items[ITEM_INDEX][quantity]"
                               min="1"
                               value="1"
                               required>
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="text"
                               class="form-control border-0 bg-white price"
                               name="items[ITEM_INDEX][unit_price]"
                               placeholder="0,00"
                               required>
                    </div>
                    <div class="col-md-2 mb-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="col-12">
                    <textarea class="form-control border-0 bg-white mt-2"
                              name="items[ITEM_INDEX][description]"
                              rows="1"
                              placeholder="Descrição do item (opcional)"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('orderForm');
            const itemsContainer = document.getElementById('itemsContainer');
            const addItemBtn = document.getElementById('addItemBtn');
            const orderTotal = document.getElementById('orderTotal');
            const totalItems = document.getElementById('totalItems');
            const itemTemplate = document.getElementById('itemTemplate');

            let itemCount = 0;

            // Adicionar primeiro item
            addNewItem();

            // Configurar máscara de moeda
            function setupCurrencyMask() {
                document.querySelectorAll('.price').forEach(input => {
                    input.addEventListener('input', function(e) {
                        let value = e.target.value.replace(/\D/g, '');
                        value = (value / 100).toFixed(2) + '';
                        value = value.replace(".", ",");
                        value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
                        value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
                        e.target.value = value;
                        calculateTotal();
                    });
                });
            }

            // Calcular total do pedido
            function calculateTotal() {
                let total = 0;
                let items = 0;

                document.querySelectorAll('.item-row').forEach(row => {
                    const quantity = parseInt(row.querySelector('.quantity').value) || 0;
                    const price = parseFloat(row.querySelector('.price').value.replace(/\./g, '').replace(',', '.')) || 0;

                    if (quantity > 0 && price > 0) {
                        const itemTotal = quantity * price;
                        total += itemTotal;
                        items += quantity;

                        // Atualizar total da linha (opcional)
                        const totalCell = row.querySelector('.item-total');
                        if (totalCell) {
                            totalCell.textContent = 'R$ ' + itemTotal.toFixed(2).replace('.', ',');
                        }
                    }
                });

                orderTotal.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
                totalItems.textContent = items + ' item(ns)';
            }

            // Adicionar novo item
            function addNewItem() {
                const template = itemTemplate.innerHTML.replace(/ITEM_INDEX/g, itemCount);
                const div = document.createElement('div');
                div.innerHTML = template;
                div.classList.add('item-row');

                itemsContainer.appendChild(div);

                // Configurar eventos
                div.querySelector('.remove-item').addEventListener('click', function() {
                    if (document.querySelectorAll('.item-row').length > 1) {
                        div.remove();
                        calculateTotal();
                    } else {
                        alert('O pedido deve ter pelo menos um item.');
                    }
                });

                div.querySelector('.quantity').addEventListener('change', calculateTotal);
                div.querySelector('.quantity').addEventListener('input', calculateTotal);

                itemCount++;
                setupCurrencyMask();
                calculateTotal();
            }

            // Evento para adicionar item
            addItemBtn.addEventListener('click', addNewItem);

            // Validação do formulário
            form.addEventListener('submit', function(e) {
                let isValid = true;

                // Validar cliente
                if (!form.customer_name.value.trim()) {
                    alert('Nome do cliente é obrigatório.');
                    isValid = false;
                }

                // Validar data do pedido
                if (!form.order_date.value) {
                    alert('Data do pedido é obrigatória.');
                    isValid = false;
                }

                // Validar itens
                const items = document.querySelectorAll('.item-row');
                if (items.length === 0) {
                    alert('Adicione pelo menos um item ao pedido.');
                    isValid = false;
                }

                // Validar cada item
                items.forEach(item => {
                    const productName = item.querySelector('[name*="product_name"]').value.trim();
                    const quantity = item.querySelector('.quantity').value;
                    const price = item.querySelector('.price').value;

                    if (!productName || !quantity || !price) {
                        alert('Preencha todos os campos obrigatórios dos itens.');
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                }
            });

            // Inicializar cálculos
            calculateTotal();
        });
    </script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>