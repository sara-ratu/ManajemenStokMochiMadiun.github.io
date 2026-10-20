import './bootstrap';

document.querySelectorAll('[data-live-clock]').forEach((clock) => {
    const dateFormatter = new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        timeZone: 'Asia/Jakarta',
        weekday: 'long',
        year: 'numeric',
    });
    const timeFormatter = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        hourCycle: 'h23',
        minute: '2-digit',
        timeZone: 'Asia/Jakarta',
    });

    const updateClock = () => {
        const now = new Date();

        clock.textContent = `${dateFormatter.format(now)} · ${timeFormatter.format(now).replace(':', '.')}`;
        clock.dateTime = now.toISOString();
    };

    updateClock();
    window.setInterval(updateClock, 1000);
});

document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const password = document.querySelector('#password');
        const isVisible = password.type === 'text';

        password.type = isVisible ? 'password' : 'text';
        toggle.setAttribute('aria-pressed', String(!isVisible));
        toggle.setAttribute('aria-label', isVisible ? 'Tampilkan password' : 'Sembunyikan password');
    });
});

document.querySelectorAll('[data-category-form]').forEach((form) => {
    const search = form.querySelector('[data-category-search]');
    const select = form.querySelector('[data-category-select]');
    const idInput = form.querySelector('[data-category-id-input]');

    if (!(search instanceof HTMLInputElement) || !(select instanceof HTMLSelectElement)) {
        return;
    }

    const getOptionById = (id) => Array.from(select.options).find((option) => option.value === id);
    const initialOption = select.selectedOptions[0];
    if (initialOption?.value) {
        if (idInput instanceof HTMLInputElement) {
            idInput.value = initialOption.value;
        }
        if (!search.value) {
            search.value = initialOption.textContent.trim();
        }
    }

    const filterCategories = () => {
        const query = search.value.trim().toLocaleLowerCase('id-ID');
        const options = Array.from(select.options).slice(1);

        options.forEach((option) => {
            option.hidden = query !== '' && !option.textContent.trim().toLocaleLowerCase('id-ID').includes(query);
        });

        if (select.selectedOptions[0]?.hidden) {
            select.value = '';
        }
    };

    search.addEventListener('input', () => {
        select.value = '';
        if (idInput instanceof HTMLInputElement) {
            idInput.value = '';
        }
        filterCategories();
    });

    select.addEventListener('change', () => {
        const option = select.selectedOptions[0];
        if (option?.value) {
            if (idInput instanceof HTMLInputElement) {
                idInput.value = option.value;
            }
            search.value = option.textContent.trim();
        }
        filterCategories();
    });

    if (idInput instanceof HTMLInputElement) {
        idInput.addEventListener('input', () => {
            const option = getOptionById(idInput.value.trim());
            select.value = option?.value ?? '';
            search.value = option?.textContent.trim() ?? '';
            filterCategories();
        });
    }

    filterCategories();
});

document.querySelectorAll('[data-stock-product-search]').forEach((search) => {
    const form = search.closest('form');
    const select = form?.querySelector('[data-stock-product-select]');

    if (!(select instanceof HTMLSelectElement)) {
        return;
    }

    search.addEventListener('input', () => {
        const query = search.value.trim().toLocaleLowerCase('id-ID');

        Array.from(select.options).slice(1).forEach((option) => {
            option.hidden = query !== '' && !option.textContent.trim().toLocaleLowerCase('id-ID').includes(query);
        });

        if (select.selectedOptions[0]?.hidden) {
            select.value = '';
        }
    });
});

document.querySelectorAll('[data-sales-checkout]').forEach((checkout) => {
    const cart = checkout.closest('.sales-cart-panel')?.querySelector('[data-sale-cart]');
    const totalOutput = checkout.querySelector('[data-cart-total]');
    const countOutput = checkout.querySelector('[data-cart-count]');
    const emptyNotice = cart?.querySelector('[data-cart-empty]');
    const paidInput = checkout.querySelector('[data-amount-paid]');
    const changeOutput = checkout.querySelector('[data-change-amount]');
    const submitButton = checkout.querySelector('[data-submit-sale]');
    const clearButton = checkout.closest('.sales-cart-panel')?.querySelector('[data-clear-sale-cart]');
    const paymentMethod = checkout.querySelector('#sales-payment-method');

    if (!(cart instanceof HTMLElement) || !(paidInput instanceof HTMLInputElement)) {
        return;
    }

    const products = new Map();
    document.querySelectorAll('[data-add-sale-product]').forEach((button) => {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        products.set(button.dataset.productId, {
            id: button.dataset.productId,
            name: button.dataset.productName,
            price: Number(button.dataset.productPrice),
            stock: Number(button.dataset.productStock),
        });
    });

    const items = new Map();
    const formatPrice = (amount) => `Rp${new Intl.NumberFormat('id-ID').format(amount)}`;

    const renderCart = (resetPaid = false) => {
        const values = Array.from(items.values());
        const total = values.reduce((sum, item) => sum + item.price * item.quantity, 0);

        cart.querySelectorAll('.sales-cart-item').forEach((item) => item.remove());
        if (emptyNotice) {
            emptyNotice.hidden = values.length > 0;
        }

        values.forEach((item, index) => {
            const row = document.createElement('article');
            row.className = 'sales-cart-item';
            row.innerHTML = `<div class="sales-cart-item-info"><strong></strong><span></span></div><div class="sales-cart-item-controls"><button type="button" aria-label="Kurangi jumlah">−</button><span></span><button type="button" aria-label="Tambah jumlah">+</button><button type="button" class="sales-cart-remove" aria-label="Hapus produk dari keranjang">×</button></div><strong class="sales-cart-item-subtotal"></strong>`;
            row.querySelector('.sales-cart-item-info strong').textContent = item.name;
            row.querySelector('.sales-cart-item-info span').textContent = formatPrice(item.price);
            row.querySelector('.sales-cart-item-controls span').textContent = item.quantity;
            row.querySelector('.sales-cart-item-subtotal').textContent = formatPrice(item.price * item.quantity);

            const [decreaseButton, increaseButton, removeButton] = row.querySelectorAll('button');
            decreaseButton.disabled = item.quantity <= 1;
            increaseButton.disabled = item.quantity >= item.stock;
            decreaseButton.addEventListener('click', () => {
                item.quantity -= 1;
                renderCart();
            });
            increaseButton.addEventListener('click', () => {
                if (item.quantity < item.stock) {
                    item.quantity += 1;
                    renderCart();
                }
            });
            removeButton.addEventListener('click', () => {
                items.delete(item.id);
                renderCart();
            });

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = `items[${index}][id_produk]`;
            idInput.value = item.id;
            const quantityInput = document.createElement('input');
            quantityInput.type = 'hidden';
            quantityInput.name = `items[${index}][jumlah]`;
            quantityInput.value = item.quantity;
            row.append(idInput, quantityInput);
            cart.append(row);
        });

        if (totalOutput) {
            totalOutput.textContent = formatPrice(total);
        }
        if (countOutput) {
            countOutput.textContent = values.reduce((sum, item) => sum + item.quantity, 0);
        }
        if (submitButton instanceof HTMLButtonElement) {
            submitButton.disabled = values.length === 0 || total > 2147483647;
        }
        if (clearButton instanceof HTMLButtonElement) {
            clearButton.disabled = values.length === 0;
        }

        if (resetPaid || paymentMethod?.value !== 'Tunai' || Number(paidInput.value) < total) {
            paidInput.value = String(total);
        }
        updateChange();
    };

    const updateChange = () => {
        const total = Array.from(items.values()).reduce((sum, item) => sum + item.price * item.quantity, 0);
        const paid = Number(paidInput.value || 0);
        const change = Math.max(paid - total, 0);
        if (changeOutput) {
            changeOutput.textContent = formatPrice(change);
        }
        if (submitButton instanceof HTMLButtonElement) {
            submitButton.disabled = items.size === 0 || total > 2147483647 || paid < total;
        }
    };

    document.querySelectorAll('[data-add-sale-product]').forEach((button) => {
        button.addEventListener('click', () => {
            const product = products.get(button.dataset.productId);
            if (!product || product.stock < 1) {
                return;
            }

            const item = items.get(product.id) ?? { ...product, quantity: 0 };
            if (item.quantity < item.stock) {
                item.quantity += 1;
                items.set(item.id, item);
                renderCart(true);
            }
        });
    });

    clearButton?.addEventListener('click', () => {
        items.clear();
        renderCart(true);
    });

    paidInput.addEventListener('input', updateChange);
    paymentMethod?.addEventListener('change', () => renderCart(true));

    const initialItems = JSON.parse(cart.dataset.initialItems || '[]');
    if (Array.isArray(initialItems)) {
        initialItems.forEach((initialItem) => {
            const id = String(initialItem.id_produk ?? '');
            const product = products.get(id);
            const quantity = Number(initialItem.jumlah);
            if (product && Number.isInteger(quantity) && quantity > 0 && quantity <= product.stock) {
                items.set(id, { ...product, quantity });
            }
        });
    }

    renderCart();
});

document.querySelectorAll('[data-delete-dialog]').forEach((dialog) => {
    if (!(dialog instanceof HTMLDialogElement)) {
        return;
    }

    let pendingForm = null;
    const confirmButton = dialog.querySelector('[data-confirm-delete-action]');

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    dialog.querySelectorAll('[data-close-delete-dialog]').forEach((button) => {
        button.addEventListener('click', () => dialog.close());
    });

    dialog.addEventListener('close', () => {
        pendingForm = null;
    });

    document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed !== 'true') {
                event.preventDefault();
                pendingForm = form;
                dialog.showModal();
            } else {
                delete form.dataset.confirmed;
            }
        });
    });

    confirmButton?.addEventListener('click', () => {
        if (pendingForm instanceof HTMLFormElement) {
            const form = pendingForm;
            pendingForm = null;
            form.dataset.confirmed = 'true';
            form.requestSubmit();
        }

        dialog.close();
    });
});

document.querySelectorAll('[data-product-dialog]').forEach((dialog) => {
    if (!(dialog instanceof HTMLDialogElement)) {
        return;
    }

    const openButton = document.querySelector(`[data-open-product-dialog="${dialog.id}"]`);
    openButton?.addEventListener('click', () => {
        dialog.showModal();
        dialog.querySelector('input:not([type="hidden"])')?.focus();
    });

    dialog.querySelectorAll('[data-close-product-dialog]').forEach((button) => {
        button.addEventListener('click', () => dialog.close());
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    if (dialog.hasAttribute('data-open-on-load')) {
        dialog.showModal();
        dialog.querySelector('.form-field-error')?.scrollIntoView({ block: 'nearest' });
    }
});

document.querySelectorAll('[data-customer-order-form]').forEach((form) => {
    const itemInputs = form.querySelector('[data-customer-order-items]');
    const totalOutput = form.querySelector('[data-customer-order-total]');
    const submitButton = form.querySelector('[data-customer-order-submit]');
    const quantities = Array.from(form.querySelectorAll('[data-customer-product-quantity]'));

    if (!(itemInputs instanceof HTMLElement) || !(submitButton instanceof HTMLButtonElement)) {
        return;
    }

    const formatPrice = (amount) => `Rp${new Intl.NumberFormat('id-ID').format(amount)}`;
    const renderOrder = () => {
        itemInputs.replaceChildren();
        let total = 0;
        let itemCount = 0;

        quantities.forEach((input) => {
            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            const quantity = Number(input.value);
            if (!Number.isInteger(quantity) || quantity <= 0) {
                return;
            }

            itemCount += 1;
            total += quantity * Number(input.dataset.productPrice);

            const id = document.createElement('input');
            id.type = 'hidden';
            id.name = `items[${itemCount - 1}][id_produk]`;
            id.value = input.dataset.productId;

            const amount = document.createElement('input');
            amount.type = 'hidden';
            amount.name = `items[${itemCount - 1}][jumlah]`;
            amount.value = String(quantity);
            itemInputs.append(id, amount);
        });

        if (totalOutput) {
            totalOutput.textContent = formatPrice(total);
        }
        submitButton.disabled = itemCount === 0;
    };

    quantities.forEach((input) => input.addEventListener('input', renderOrder));
    renderOrder();
});

document.querySelectorAll('[data-customer-order-status]').forEach((statusCard) => {
    const statusUrl = statusCard.dataset.statusUrl;
    const statusLabel = statusCard.querySelector('[data-status-label]');
    const statusMessage = statusCard.querySelector('[data-status-message]');
    const messages = {
        Menunggu: 'Menunggu konfirmasi dari kasir.',
        Diproses: 'Pesanan diterima. Silakan lakukan pembayaran kepada kasir.',
        Ditolak: 'Maaf, kasir belum dapat menerima pesanan ini. Silakan hubungi kasir.',
        Selesai: 'Pesanan selesai. Terima kasih sudah berbelanja!',
    };

    if (!statusUrl || !(statusLabel instanceof HTMLElement) || !(statusMessage instanceof HTMLElement)) {
        return;
    }

    const updateStatus = async () => {
        const response = await fetch(statusUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });
        if (!response.ok) {
            throw new Error(`Pembaruan status pesanan gagal (${response.status}).`);
        }

        const order = await response.json();
        statusLabel.textContent = order.status;
        statusLabel.className = `customer-order-status-pill customer-order-status-${order.status.toLocaleLowerCase('id-ID')}`;
        statusMessage.textContent = messages[order.status] ?? 'Status pesanan diperbarui.';

        if (['Ditolak', 'Selesai'].includes(order.status)) {
            window.clearInterval(statusPoll);
        }
    };

    const statusPoll = window.setInterval(() => {
        updateStatus().catch((error) => console.error(error));
    }, 8000);

    if (['Ditolak', 'Selesai'].includes(statusLabel.textContent.trim())) {
        window.clearInterval(statusPoll);
    }
});

document.querySelectorAll('[data-cashier-order-poll]').forEach((queue) => {
    const notificationsUrl = queue.dataset.notificationsUrl;
    const alert = queue.querySelector('[data-new-order-alert]');
    const refreshButton = queue.querySelector('[data-refresh-order-queue]');
    let latestOrderId = Number(queue.dataset.latestOrderId ?? 0);

    if (!notificationsUrl || !(alert instanceof HTMLElement)) {
        return;
    }

    refreshButton?.addEventListener('click', () => window.location.reload());

    const pollOrders = async () => {
        const response = await fetch(notificationsUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });
        if (!response.ok) {
            throw new Error(`Pemeriksaan order baru gagal (${response.status}).`);
        }

        const result = await response.json();
        queue.querySelectorAll('[data-pending-count]').forEach((count) => {
            count.textContent = new Intl.NumberFormat('id-ID').format(result.pending_count);
        });

        if (result.latest_order_id > latestOrderId) {
            latestOrderId = result.latest_order_id;
            alert.hidden = false;
        }
    };

    window.setInterval(() => {
        pollOrders().catch((error) => console.error(error));
    }, 8000);
});
