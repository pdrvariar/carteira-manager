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
    }

    setupEventListeners() {
        // Botão para adicionar item
        const addBtn = document.getElementById('addItemBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.addNewItem());
        }

        // Configurar máscara de preços existentes
        this.setupCurrencyMasks();

        // Calcular total quando houver mudanças
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('quantity') || e.target.classList.contains('price')) {
                this.calculateTotal();
            }
        });
    }

    addNewItem() {
        if (!this.itemsContainer || !this.itemTemplate) return;

        const template = this.itemTemplate.innerHTML.replace(/ITEM_INDEX/g, this.itemCount);
        const div = document.createElement('div');
        div.innerHTML = template;
        div.classList.add('item-row');

        // Adicionar animação
        div.style.opacity = '0';
        div.style.transform = 'translateY(10px)';

        this.itemsContainer.appendChild(div);

        // Animar entrada
        setTimeout(() => {
            div.style.transition = 'all 0.3s ease';
            div.style.opacity = '1';
            div.style.transform = 'translateY(0)';
        }, 10);

        // Configurar eventos do novo item
        const removeBtn = div.querySelector('.remove-item');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => this.removeItem(div));
        }

        // Configurar máscara de moeda
        const priceInput = div.querySelector('.price');
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

    setupCurrencyMasks() {
        document.querySelectorAll('.price').forEach(input => {
            this.setupCurrencyMask(input);
        });
    }

    setupCurrencyMask(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
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

        document.querySelectorAll('.item-row').forEach(row => {
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