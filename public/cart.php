<?php
/**
 * @fileoverview cart.php
 *
 * @description
 * Publiczny widok koszyka zakupowego.
 * Plik pobiera aktualny stan koszyka z warstwy API, renderuje liste pozycji,
 * podsumowanie zamowienia oraz obsluguje interakcje klienta (zmiana ilosci,
 * usuwanie pozycji, odswiezanie danych).
 *
 * @scope
 * - Dolaczenie logiki backendowej z api/cart/helpers.php.
 * - Pobranie podsumowania koszyka (items, count, total).
 * - Renderowanie stanu pustego koszyka i stanu z pozycjami.
 * - Obsluga akcji frontendowych przez API: update/remove/details.
 * - Aktualizacja podsumowania i listy pozycji bez przeladowania strony.
 *
 * @behavior
 * - Pusty koszyk: wyswietlenie komunikatu i linku do produktow.
 * - Koszyk z pozycjami: wyswietlenie listy, ilosci, sum i przejscia do platnosci.
 * - Interakcje uzytkownika: dynamiczna synchronizacja zmian z API koszyka.
 */

require_once '../api/cart/helpers.php';

$cartId = cartGetOrCreateActiveId();
$summary = $cartId ? cartGetSummary($cartId) : ['items' => [], 'count' => 0, 'total' => 0.0];
$cartItems = $summary['items'];
$itemCount = (int) $summary['count'];
$totalValue = (float) $summary['total'];
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SklepIntCom - Koszyk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="flex flex-col min-h-full text-gray-900">
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="index.php" class="flex-shrink-0 flex items-center">
                    <div class="bg-indigo-600 p-2 rounded-lg mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-gray-900">Sklep<span
                            class="text-indigo-600">IntCom</span></span>
                </a>
                <div class="flex items-center gap-3">
                    <a href="products.php"
                        class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-indigo-600 transition-colors px-3 py-2 rounded-lg hover:bg-indigo-50">
                        <i class="fa-solid fa-box-open text-xs"></i>
                        Produkty
                    </a>
                    <a href="index.php"
                        class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-indigo-600 transition-colors px-3 py-2 rounded-lg hover:bg-indigo-50">
                        <i class="fa-solid fa-house text-xs"></i>
                        Strona glowna
                    </a>
                    <span
                        class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 bg-indigo-50 px-3 py-2 rounded-lg border border-indigo-100">
                        <i class="fa-solid fa-cart-shopping text-xs"></i>
                        Koszyk
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-6xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-end justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Twoj koszyk</h1>
                <p class="text-sm text-gray-500 mt-1">Lacznie produktow: <span id="cart-count"
                        class="font-semibold text-indigo-600"><?php echo $itemCount; ?></span></p>
            </div>
            <a href="products.php"
                class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                <i class="fa-solid fa-arrow-left"></i>
                Wroc do produktow
            </a>
        </div>

        <div id="cart-empty"
            class="<?php echo empty($cartItems) ? '' : 'hidden'; ?> bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
            <i class="fa-solid fa-cart-shopping text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 mb-6">Koszyk jest pusty.</p>
            <a href="products.php"
                class="inline-flex items-center gap-2 bg-indigo-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-indigo-700 transition-all">
                <i class="fa-solid fa-bag-shopping"></i>
                Przegladaj produkty
            </a>
        </div>

        <div id="cart-content"
            class="grid grid-cols-1 lg:grid-cols-3 gap-6 <?php echo empty($cartItems) ? 'hidden' : ''; ?>">
            <section class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div id="cart-items" class="divide-y divide-gray-100">
                    <?php foreach ($cartItems as $item): ?>
                        <article class="p-5 flex flex-col sm:flex-row sm:items-center gap-4 cart-item"
                            data-product-id="<?php echo (int) $item['product_id']; ?>">
                            <a href="product.php?id=<?php echo (int) $item['product_id']; ?>"
                                class="w-full sm:w-24 h-24 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0 flex items-center justify-center">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="assets/img/<?php echo rawurlencode($item['image']); ?>"
                                        alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="fa-solid fa-image text-gray-300 text-2xl"></i>
                                <?php endif; ?>
                            </a>

                            <div class="flex-grow min-w-0">
                                <a href="product.php?id=<?php echo (int) $item['product_id']; ?>"
                                    class="font-bold text-gray-900 hover:text-indigo-600 transition-colors line-clamp-2"><?php echo htmlspecialchars($item['name']); ?></a>
                                <p class="text-xs text-gray-500 mt-1">Cena szt.: <span
                                        class="font-semibold text-gray-700"><?php echo number_format((float) $item['unit_price'], 2, ',', ' '); ?>
                                        zl</span></p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button"
                                    class="qty-btn w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700"
                                    data-action="minus">-</button>
                                <input type="number" min="1" max="<?php echo (int) $item['stock']; ?>"
                                    value="<?php echo (int) $item['qty']; ?>"
                                    class="qty-input w-16 text-center rounded-lg border border-gray-200 px-2 py-1.5 text-sm font-semibold text-gray-900" />
                                <button type="button"
                                    class="qty-btn w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700"
                                    data-action="plus">+</button>
                            </div>

                            <div class="text-right min-w-[120px]">
                                <p class="text-xs text-gray-500">Suma</p>
                                <p class="line-total text-lg font-black text-gray-900">
                                    <?php echo number_format((float) $item['line_total'], 2, ',', ' '); ?> zl
                                </p>
                            </div>

                            <button type="button"
                                class="remove-btn w-10 h-10 rounded-lg bg-red-50 text-red-600 hover:bg-red-100"
                                aria-label="Usun pozycje">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <aside class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 h-fit">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Podsumowanie</h2>
                <div class="space-y-2 text-sm text-gray-600 mb-6">
                    <div class="flex justify-between">
                        <span>Produkty</span>
                        <span id="summary-count" class="font-semibold text-gray-900"><?php echo $itemCount; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Dostawa</span>
                        <span class="font-semibold text-gray-900">0,00 zl</span>
                    </div>
                    <div class="pt-3 border-t border-gray-100 flex justify-between text-base">
                        <span class="font-bold text-gray-900">Razem</span>
                        <span id="summary-total"
                            class="font-black text-indigo-600"><?php echo number_format($totalValue, 2, ',', ' '); ?>
                            zl</span>
                    </div>
                </div>
                <a href="payment.php"
                    class="block text-center w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition-colors">Przejdz
                    do zamowienia</a>
            </aside>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cartItemsContainer = document.getElementById('cart-items');
            const cartCount = document.getElementById('cart-count');
            const summaryCount = document.getElementById('summary-count');
            const summaryTotal = document.getElementById('summary-total');
            const cartEmpty = document.getElementById('cart-empty');
            const cartContent = document.getElementById('cart-content');

            function formatPrice(value) {
                const amount = Number(value) || 0;
                return amount.toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' zl';
            }

            function renderFromData(data) {
                const count = Number(data.count || 0);
                const total = Number(data.total || 0);
                const items = Array.isArray(data.items) ? data.items : [];

                cartCount.textContent = String(count);
                summaryCount.textContent = String(count);
                summaryTotal.textContent = formatPrice(total);

                if (items.length === 0) {
                    cartEmpty.classList.remove('hidden');
                    cartContent.classList.add('hidden');
                    return;
                }

                cartEmpty.classList.add('hidden');
                cartContent.classList.remove('hidden');

                const html = items.map(item => {
                    const imageHtml = item.image
                        ? `<img src="assets/img/${encodeURIComponent(item.image)}" alt="${item.name}" class="w-full h-full object-cover">`
                        : '<i class="fa-solid fa-image text-gray-300 text-2xl"></i>';

                    return `
                    <article class="p-5 flex flex-col sm:flex-row sm:items-center gap-4 cart-item" data-product-id="${item.product_id}">
                        <a href="product.php?id=${item.product_id}" class="w-full sm:w-24 h-24 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0 flex items-center justify-center">${imageHtml}</a>
                        <div class="flex-grow min-w-0">
                            <a href="product.php?id=${item.product_id}" class="font-bold text-gray-900 hover:text-indigo-600 transition-colors line-clamp-2">${item.name}</a>
                            <p class="text-xs text-gray-500 mt-1">Cena szt.: <span class="font-semibold text-gray-700">${formatPrice(item.unit_price)}</span></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="qty-btn w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700" data-action="minus">-</button>
                            <input type="number" min="1" max="${item.stock}" value="${item.qty}" class="qty-input w-16 text-center rounded-lg border border-gray-200 px-2 py-1.5 text-sm font-semibold text-gray-900" />
                            <button type="button" class="qty-btn w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700" data-action="plus">+</button>
                        </div>
                        <div class="text-right min-w-[120px]">
                            <p class="text-xs text-gray-500">Suma</p>
                            <p class="line-total text-lg font-black text-gray-900">${formatPrice(item.line_total)}</p>
                        </div>
                        <button type="button" class="remove-btn w-10 h-10 rounded-lg bg-red-50 text-red-600 hover:bg-red-100" aria-label="Usun pozycje">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </article>
                `;
                }).join('');

                cartItemsContainer.innerHTML = html;
            }

            function refreshCartDetails() {
                return fetch('../api/cart/details.php')
                    .then(response => response.json())
                    .then(data => {
                        if (!data || !data.success) {
                            return;
                        }
                        renderFromData(data);
                    });
            }

            function updateQuantity(productId, quantity) {
                return fetch('../api/cart/update.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ productId: Number(productId), quantity: Number(quantity) })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data || !data.success) {
                            if (data && data.message) {
                                alert(data.message);
                            }
                            return;
                        }
                        return refreshCartDetails();
                    });
            }

            function removeItem(productId) {
                return fetch('../api/cart/remove.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ productId: Number(productId) })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data || !data.success) {
                            if (data && data.message) {
                                alert(data.message);
                            }
                            return;
                        }
                        return refreshCartDetails();
                    });
            }

            if (cartItemsContainer) {
                cartItemsContainer.addEventListener('click', function (event) {
                    const item = event.target.closest('.cart-item');
                    if (!item) {
                        return;
                    }

                    const productId = Number(item.dataset.productId);
                    const input = item.querySelector('.qty-input');
                    const maxStock = Number(input.getAttribute('max') || 999999);
                    const currentQty = Number(input.value || 1);

                    const qtyButton = event.target.closest('.qty-btn');
                    if (qtyButton) {
                        const action = qtyButton.dataset.action;
                        const nextQty = action === 'plus' ? currentQty + 1 : currentQty - 1;
                        if (nextQty < 1 || nextQty > maxStock) {
                            return;
                        }
                        input.value = String(nextQty);
                        updateQuantity(productId, nextQty);
                        return;
                    }

                    const removeButton = event.target.closest('.remove-btn');
                    if (removeButton) {
                        removeItem(productId);
                    }
                });

                cartItemsContainer.addEventListener('change', function (event) {
                    const input = event.target.closest('.qty-input');
                    if (!input) {
                        return;
                    }

                    const item = input.closest('.cart-item');
                    if (!item) {
                        return;
                    }

                    const productId = Number(item.dataset.productId);
                    const maxStock = Number(input.getAttribute('max') || 999999);
                    let nextQty = Number(input.value || 1);

                    if (nextQty < 1) {
                        nextQty = 1;
                    }
                    if (nextQty > maxStock) {
                        nextQty = maxStock;
                    }

                    input.value = String(nextQty);
                    updateQuantity(productId, nextQty);
                });
            }
        });
    </script>

    <footer class="bg-white border-t border-gray-200 pt-16 pb-8">
        <div class="max-w-[1440px] mx-auto px-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div class="col-span-1 md:col-span-1">
                    <a href="index.php" class="flex items-center mb-6">
                        <div class="bg-indigo-600 p-2 rounded-lg mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <span class="text-xl font-bold tracking-tight text-gray-900">Sklep<span
                                class="text-indigo-600">IntCom</span></span>
                    </a>
                    <p class="text-gray-500 text-sm leading-relaxed">Twoj partner w swiecie technologii. Dostarczamy
                        najwyzszej jakosci sprzet IT dla profesjonalistow i hobbystow.</p>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 mb-6">Sklep</h4>
                    <ul class="space-y-4 text-sm text-gray-500">
                        <li><a href="products.php" class="hover:text-indigo-600 transition-colors">Wszystkie
                                produkty</a></li>
                        <li><a href="products.php?filtr=promocje"
                                class="hover:text-indigo-600 transition-colors">Promocje</a></li>
                        <li><a href="products.php?filtr=nowosci"
                                class="hover:text-indigo-600 transition-colors">Nowosci</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 mb-6">Informacje</h4>
                    <ul class="space-y-4 text-sm text-gray-500">
                        <li><a href="terms.php" class="hover:text-indigo-600 transition-colors">Regulamin</a></li>
                        <li><a href="privacy.php" class="hover:text-indigo-600 transition-colors">Polityka
                                prywatnosci</a></li>
                        <li><a href="customer/customer_panel.php" class="hover:text-indigo-600 transition-colors">Panel
                                klienta</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 mb-6">Kontakt</h4>
                    <ul class="space-y-4 text-sm text-gray-500">
                        <li>kontakt@sklepint.com</li>
                        <li>+48 123 456 789</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-100 pt-8 flex justify-center">
                <p class="text-xs text-gray-400">&copy; 2026 SklepIntCom. Wszelkie prawa zastrzezone.</p>
            </div>
        </div>
    </footer>
</body>
</html>
