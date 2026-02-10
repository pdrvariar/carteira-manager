// public/js/order.js - Funcionalidades específicas para pedidos

class OrderManager {
    constructor() {
        this.itemCount = 0;
        this.itemsContainer = null;
        this.orderTotal = null;
        this.totalItems = null;
        this.itemTemplate = null;

        this.init();
    }

    init() {
        this.setupDOM();
        this.setupEventListeners();
        this.calculateTotal();
    }

    setupDOM() {
        this.itemsContainer = document.getElementById('itemsContainer');
        this.orderTotal = document.getElementById('orderTotal');
        this.totalItems = document.getElementById('totalItems');
        this.itemTemplate = document.getElementById('itemTemplate');

        // Se não houver itens, adicionar um
        if (this.itemsContainer && this.itemTemplate && this.itemsContainer.children.length === 0) {
            this.addNewItem();
        }

        // Atualizar itemCount inicial com base nos itens existentes (para edição)
        if (this.itemsContainer) {
            this.itemCount = this.itemsContainer.querySelectorAll('.item-row').length;
        }
    }

    setupEventListeners() {
        // Botão para adicionar item
        const addBtn = document.getElementById('addItemBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.addNewItem());
        }

        // Configurar máscara de preços existentes e eventos de remoção
        this.setupInitialItems();

        // Calcular total quando houver mudanças nas quantidades
        this.itemsContainer.addEventListener('input', (e) => {
            if (e.target.classList.contains('quantity')) {
                this.calculateTotal();
            }
        });

        // Calcular total quando houver mudanças nos preços (após a máscara agir)
        this.itemsContainer.addEventListener('input', (e) => {
            if (e.target.classList.contains('price')) {
                // Pequeno delay para garantir que a máscara de moeda já atualizou o valor
                setTimeout(() => this.calculateTotal(), 0);
            }
        });
    }

    setupInitialItems() {
        if (!this.itemsContainer) return;

        this.itemsContainer.querySelectorAll('.item-row').forEach(row => {
            // Máscara de preço
            const priceInput = row.querySelector('.price');
            if (priceInput) {
                this.setupCurrencyMask(priceInput);
            }

            // Evento de remoção
            const removeBtn = row.querySelector('.remove-item');
            if (removeBtn) {
                removeBtn.addEventListener('click', () => this.removeItem(row));
            }
        });
    }

    addNewItem() {
        if (!this.itemsContainer || !this.itemTemplate) return;

        const template = this.itemTemplate.innerHTML.replace(/ITEM_INDEX/g, this.itemCount);
        const div = document.createElement('div');
        div.innerHTML = template;
        
        // Extrair o conteúdo do template que agora é uma div.item-row
        const itemRow = div.firstElementChild;
        itemRow.style.opacity = '0';
        itemRow.style.transform = 'translateY(10px)';

        this.itemsContainer.appendChild(itemRow);

        // Animar entrada
        setTimeout(() => {
            itemRow.style.transition = 'all 0.3s ease';
            itemRow.style.opacity = '1';
            itemRow.style.transform = 'translateY(0)';
        }, 10);

        // Configurar eventos do novo item
        const removeBtn = itemRow.querySelector('.remove-item');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => this.removeItem(itemRow));
        }

        // Configurar máscara de moeda
        const priceInput = itemRow.querySelector('.price');
        if (priceInput) {
            this.setupCurrencyMask(priceInput);
        }

        this.itemCount++;
        this.calculateTotal();
    }

    removeItem(itemElement) {
        if (this.itemsContainer.children.length <= 1) {
            alert('O pedido deve ter pelo menos um item.');
            return;
        }

        // Animar saída
        itemElement.style.opacity = '0';
        itemElement.style.transform = 'translateX(20px)';

        setTimeout(() => {
            itemElement.remove();
            this.calculateTotal();
        }, 300);
    }


    setupCurrencyMask(input) {
        // Remover listener antigo se houver (para evitar duplicidade se chamado múltiplas vezes, 
        // embora aqui usemos listener anônimo, o ideal é garantir que o evento 'input' principal 
        // de cálculo não seja o mesmo da máscara)
        
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (!value) {
                e.target.value = '';
                return;
            }
            value = (value / 100).toFixed(2) + '';
            value = value.replace(".", ",");
            value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
            value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
            e.target.value = value;
        });
    }

    calculateTotal() {
        if (!this.orderTotal || !this.totalItems) return;

        let total = 0;
        let items = 0;

        // Usar um set para evitar processar a mesma linha duas vezes se houver algum bug no DOM
        const processedRows = new Set();

        this.itemsContainer.querySelectorAll('.item-row').forEach(row => {
            if (processedRows.has(row)) return;
            processedRows.add(row);

            const quantityInput = row.querySelector('.quantity');
            const priceInput = row.querySelector('.price');

            if (quantityInput && priceInput) {
                const quantity = parseInt(quantityInput.value) || 0;
                const price = this.parseCurrency(priceInput.value);

                if (quantity > 0 && price > 0) {
                    const itemTotal = quantity * price;
                    total += itemTotal;
                    items += quantity;

                    // Atualizar total da linha se existir elemento
                    const totalCell = row.querySelector('.item-total');
                    if (totalCell) {
                        totalCell.textContent = this.formatCurrency(itemTotal);
                    }
                }
            }
        });

        this.orderTotal.textContent = this.formatCurrency(total);
        this.totalItems.textContent = `${items} item(ns)`;
    }

    parseCurrency(value) {
        if (!value) return 0;
        return parseFloat(value.replace(/\./g, '').replace(',', '.'));
    }

    formatCurrency(value) {
        return 'R$ ' + value.toFixed(2).replace('.', ',');
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    new OrderManager();

    // Validação de formulário
    const form = document.getElementById('orderForm');
    if (form) {
        form.addEventListener('submit', (e) => {
            // Validar itens
            const items = document.querySelectorAll('.item-row');
            if (items.length === 0) {
                e.preventDefault();
                alert('Adicione pelo menos um item ao pedido.');
                return;
            }

            // Validar cada item
            let hasErrors = false;
            items.forEach((item, index) => {
                const productName = item.querySelector('[name*="product_name"]');
                const quantity = item.querySelector('.quantity');
                const price = item.querySelector('.price');

                if (!productName.value.trim() || !quantity.value || !price.value) {
                    hasErrors = true;
                    item.classList.add('border', 'border-danger');
                } else {
                    item.classList.remove('border', 'border-danger');
                }
            });

            if (hasErrors) {
                e.preventDefault();
                alert('Preencha todos os campos obrigatórios dos itens.');
            }
        });
    }
});