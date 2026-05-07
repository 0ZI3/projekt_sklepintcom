<?php
/**
 * @fileoverview index.php
 *
 * @description
 * Publiczny widok strony glownej sklepu.
 * Plik renderuje interfejs home, nawigacje kategorii i sekcje produktowe,
 * korzystajac z danych przygotowanych przez warstwe API sklepu.
 *
 * @scope
 * - Dolaczenie logiki backendowej z api/store/index.php.
 * - Renderowanie nawigacji glownej i menu kategorii.
 * - Renderowanie sekcji promocyjnych i list produktow na stronie glownej.
 * - Obsluga stanu sesji klienta/pracownika w elementach nawigacji.
 * - Integracja widgetu koszyka i podgladu pozycji.
 *
 * @behavior
 * - Wejscie na strone: wyswietlenie danych startowych sklepu.
 * - Aktywna sesja: dostosowanie elementow nawigacyjnych do roli uzytkownika.
 * - Po zaladowaniu: widget koszyka odswieza licznik i podglad zawartosci.
 */

require_once '../api/store/index.php';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SklepIntCom - Najlepsza elektronika</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="flex flex-col min-h-full text-gray-900">

    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex-shrink-0 flex items-center">
                    <div class="bg-indigo-600 p-2 rounded-lg mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-gray-900">Sklep<span
                            class="text-indigo-600">IntCom</span></span>
                </div>

                <div class="hidden sm:flex flex-1 max-w-md mx-8">
                    <form action="products.php" method="GET" class="w-full relative">
                        <input type="text" name="q" placeholder="Szukaj produktów..."
                            class="w-full bg-gray-100 border-transparent rounded-full py-2.5 pl-12 pr-4 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm outline-none">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </form>
                </div>

                <div class="flex items-center space-x-6">
                    <?php if (isset($_SESSION['worker_name'])): ?>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">Pracownik: <b
                                    class="text-indigo-600"><?= htmlspecialchars($_SESSION['worker_name']) ?></b></span>
                            <a href="employee/employee_panel.php"
                                class="bg-indigo-50 text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm active:scale-95 border border-indigo-100"
                                aria-label="Panel pracownika" title="Panel pracownika">
                                <i class="fa-solid fa-user-gear"></i>
                            </a>
                            <a href="../api/auth/employee_logout.php"
                                class="bg-indigo-50 text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm active:scale-95 border border-indigo-100"
                                aria-label="Wyloguj panel" title="Wyloguj panel">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_name'])): ?>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">Zalogowano jako:
                                <b><?= htmlspecialchars($_SESSION['user_name']) ?></b></span>
                            <a href="customer/customer_panel.php"
                                class="bg-white text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-50 transition-all shadow-sm active:scale-95 border border-indigo-100"
                                aria-label="Panel klienta" title="Panel klienta">
                                <i class="fa-solid fa-user"></i>
                            </a>
                            <a href="../api/auth/logout.php"
                                class="bg-indigo-50 text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm active:scale-95 border border-indigo-100"
                                aria-label="Wyloguj się" title="Wyloguj się">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </a>
                        </div>
                    <?php elseif (!isset($_SESSION['worker_id'])): ?>
                        <a href="customer/login.php"
                            class="text-gray-600 hover:text-indigo-600 font-medium text-sm transition-colors">
                            Zaloguj się
                        </a>
                    <?php endif; ?>
                    <div class="relative group" id="cart-widget">
                        <a href="cart.php"
                            class="relative p-2 text-gray-600 hover:text-indigo-600 transition-colors block"
                            aria-label="Koszyk" title="Koszyk">
                            <i class="fa-solid fa-cart-shopping text-xl"></i>
                            <span id="cart-count-badge"
                                class="absolute top-0 right-0 bg-indigo-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">0</span>
                        </a>
                        <div id="cart-hover-panel"
                            class="absolute right-0 top-full mt-2 w-80 bg-white rounded-2xl border border-gray-100 shadow-2xl p-4 z-[70] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-150">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-bold text-gray-900">Podglad koszyka</p>
                                <span id="cart-hover-count" class="text-xs font-semibold text-indigo-600">0 szt.</span>
                            </div>
                            <div id="cart-hover-items" class="space-y-2 max-h-64 overflow-auto">
                                <p class="text-sm text-gray-400">Koszyk jest pusty.</p>
                            </div>
                            <div class="pt-3 mt-3 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-sm text-gray-500">Razem</span>
                                <span id="cart-hover-total" class="text-base font-black text-gray-900">0,00 zl</span>
                            </div>
                            <a href="cart.php"
                                class="mt-3 inline-flex w-full justify-center items-center bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-bold hover:bg-indigo-700 transition-colors">
                                Przejdz do koszyka
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sm:hidden pb-4">
                <form action="products.php" method="GET">
                    <input type="text" name="q" placeholder="Szukaj..."
                        class="w-full bg-gray-100 rounded-lg py-2 px-4 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                </form>
            </div>
        </div>
    </nav>

    <div class="bg-indigo-600 text-white relative">
        <div
            class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex space-x-8 py-3 text-sm font-medium uppercase tracking-wider overflow-x-visible">
            <?php foreach ($categoryTree as $kat): ?>
                <?php if ($kat['nazwa'] === 'Elektronika' && !empty($kat['podkategorie'])): ?>
                    <?php foreach ($kat['podkategorie'] as $podKat): ?>
                        <div class="relative group">
                            <a href="products.php?kategoria=<?= (int) $podKat['id'] ?>"
                                class="hover:text-indigo-200 transition-colors flex items-center gap-1">
                                <?= htmlspecialchars($podKat['nazwa']) ?>
                                <?php if (!empty($podKat['podkategorie'])): ?>
                                    <i class="fa-solid fa-chevron-down text-[10px] group-hover:rotate-180 transition-transform"></i>
                                <?php endif; ?>
                            </a>

                            <?php if (!empty($podKat['podkategorie'])): ?>
                                <div
                                    class="absolute left-0 mt-3 w-56 bg-white rounded-xl shadow-2xl border border-gray-100 py-3 text-gray-800 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-[60] transform origin-top -translate-y-2 group-hover:translate-y-0">
                                    <?php foreach ($podKat['podkategorie'] as $subSubKat): ?>
                                        <a href="products.php?kategoria=<?= (int) $subSubKat['id'] ?>"
                                            class="block px-4 py-2 hover:bg-indigo-50 hover:text-indigo-600 transition-colors text-xs font-bold lowercase first-letter:uppercase">
                                            <?= htmlspecialchars($subSubKat['nazwa']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php elseif ($kat['nazwa'] !== 'Elektronika'): ?>
                    <div class="relative group">
                        <a href="products.php?kategoria=<?= (int) $kat['id'] ?>"
                            class="hover:text-indigo-200 transition-colors flex items-center gap-1">
                            <?= htmlspecialchars($kat['nazwa']) ?>
                            <?php if (!empty($kat['podkategorie'])): ?>
                                <i class="fa-solid fa-chevron-down text-[10px] group-hover:rotate-180 transition-transform"></i>
                            <?php endif; ?>
                        </a>

                        <?php if (!empty($kat['podkategorie'])): ?>
                            <div
                                class="absolute left-0 mt-3 w-56 bg-white rounded-xl shadow-2xl border border-gray-100 py-3 text-gray-800 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-[60] transform origin-top -translate-y-2 group-hover:translate-y-0">
                                <?php foreach ($kat['podkategorie'] as $podKat): ?>
                                    <a href="products.php?kategoria=<?= (int) $podKat['id'] ?>"
                                        class="block px-4 py-2 hover:bg-indigo-50 hover:text-indigo-600 transition-colors text-xs font-bold lowercase first-letter:uppercase">
                                        <?= htmlspecialchars($podKat['nazwa']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <section class="relative bg-slate-900 h-[400px] overflow-hidden">
        <div
            class="absolute inset-0 opacity-60 bg-[url('https://images.unsplash.com/photo-1519389950473-47ba0277781c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80')] bg-cover bg-center">
        </div>
        <div class="absolute inset-0 bg-slate-950/40"></div>
        <div
            class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex flex-col justify-center items-start text-white">
            <h1 class="text-4xl sm:text-6xl font-extrabold mb-4 tracking-tight">Nowa fala technologii</h1>
            <p class="text-xl max-w-2xl mb-8 text-gray-200">Sprawdź naszą ofertę laptopów biznesowych i akcesoriów,
                które zrewolucjonizują Twoją pracę zdalną.</p>
            <div class="flex gap-4">
                <a href="#produkty"
                    class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-indigo-700 transition-all shadow-lg hover:shadow-indigo-500/30">Kup
                    teraz</a>
            </div>
        </div>
    </section>

    <main class="flex-grow max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-12 py-12" id="produkty">
        <div class="flex justify-between items-end mb-10 px-2 text-indigo-100">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Polecane Produkty</h2>
                <p class="text-gray-500 mt-2">Wybrane specjalnie dla Ciebie</p>
            </div>
            <div class="relative group" id="sort-dropdown">
                <button id="sort-button"
                    class="flex items-center gap-2 text-sm text-indigo-600 font-semibold cursor-pointer py-2 px-4 rounded-lg hover:bg-indigo-50 transition-all border border-transparent hover:border-indigo-100">
                    <span id="current-sort">Losowo</span> <i
                        class="fa-solid fa-chevron-down text-xs transition-transform duration-200 group-hover:rotate-180"
                        id="sort-icon"></i>
                </button>
                <div
                    class="absolute right-0 top-full pt-1 w-48 z-50 hidden group-hover:block animate-in fade-in slide-in-from-top-1 duration-200">
                    <div id="sort-menu"
                        class="bg-white border border-gray-100 rounded-xl shadow-xl py-2 overflow-hidden">
                        <button
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors sort-option"
                            data-sort="newest">Najnowsze</button>
                        <button
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors sort-option"
                            data-sort="price_asc">Najtańsze</button>
                        <button
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors sort-option"
                            data-sort="price_desc">Najdroższe</button>
                        <button
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors sort-option"
                            data-sort="random">Losowo</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-8 md:gap-10 items-stretch"
            id="product-container">
            <?php if (empty($products)): ?>
                <div class="col-span-full py-20 text-center">
                    <i class="fa-solid fa-box-open text-6xl text-gray-200 mb-4 block"></i>
                    <p class="text-gray-400">Brak dostępnych produktów.</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col product-card h-full min-w-0"
                        data-product-id="<?= $product['id'] ?>">
                        <?php $productUrl = 'product.php?id=' . (int) $product['id']; ?>
                        <div class="bg-gray-100 relative overflow-hidden flex-shrink-0" style="padding-top: 75%;">
                            <div class="absolute top-4 left-4 z-10 flex flex-col gap-2">
                                <?php if (isset($product['is_new']) && $product['is_new']): ?>
                                    <span
                                        class="bg-indigo-600 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-tighter shadow-lg">Nowość!</span>
                                <?php endif; ?>
                                <?php if (isset($product['promocja_proc']) && (float) $product['promocja_proc'] > 0): ?>
                                    <span
                                        class="bg-red-600 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-tighter shadow-lg">
                                        PROMOCJA -<?= round($product['promocja_proc']) ?>%</span>
                                <?php endif; ?>
                            </div>
                            <?php
                            $productImages = [];
                            if (!empty($product['nazwy_plikow'])) {
                                $productImages = array_values(array_filter(array_map('trim', explode(',', $product['nazwy_plikow']))));
                            } elseif (!empty($product['nazwa_pliku'])) {
                                $productImages = [$product['nazwa_pliku']];
                            }
                            ?>
                            <?php if (!empty($productImages)): ?>
                                <div class="absolute inset-0 product-slider" data-slide-count="<?= count($productImages) ?>">
                                    <?php foreach ($productImages as $index => $fileName): ?>
                                        <img src="assets/img/<?= rawurlencode($fileName) ?>"
                                            alt="<?= htmlspecialchars($product['nazwa']) ?>"
                                            class="product-slide absolute inset-0 h-full w-full object-cover transition-all duration-500 <?= $index === 0 ? 'opacity-100' : 'opacity-0' ?>"
                                            loading="lazy">
                                    <?php endforeach; ?>
                                </div>
                                <?php if (count($productImages) > 1): ?>
                                    <div
                                        class="absolute bottom-3 left-1/2 -translate-x-1/2 z-10 flex gap-1.5 px-2.5 py-1 rounded-full bg-gradient-to-r from-black/70 via-black/45 to-black/70 backdrop-blur-sm shadow-md">
                                        <?php foreach ($productImages as $index => $_): ?>
                                            <button type="button"
                                                class="product-slide-dot w-1.5 h-1.5 rounded-full <?= $index === 0 ? 'bg-white' : 'bg-white/50' ?>"
                                                data-slide-index="<?= $index ?>" aria-label="Zdjęcie <?= $index + 1 ?>"></button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="absolute inset-0 flex items-center justify-center text-gray-300">
                                    <i class="fa-solid fa-image text-4xl"></i>
                                </div>
                            <?php endif; ?>
                            <a href="<?= $productUrl ?>" class="absolute inset-0 z-[5]"
                                aria-label="Pokaż produkt <?= htmlspecialchars($product['nazwa']) ?>"></a>
                        </div>
                        <div class="p-6 flex flex-col flex-grow min-w-0">
                            <div class="h-5 mb-1 overflow-hidden min-w-0">
                                <p
                                    class="text-[10px] text-indigo-600 font-bold uppercase tracking-widest truncate block w-full">
                                    <?= !empty($product['sciezka_kategorii']) ? htmlspecialchars($product['sciezka_kategorii']) : 'Elektronika' ?>
                                </p>
                            </div>
                            <h3
                                class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-4 line-clamp-2 h-14 overflow-hidden min-w-0">
                                <a href="<?= $productUrl ?>"
                                    class="hover:text-indigo-600 transition-colors"><?= htmlspecialchars($product['nazwa']) ?></a>
                            </h3>
                            <div class="mt-auto pt-4 border-t border-gray-50 flex justify-between items-center min-w-0">
                                <?php if (isset($product['promocja_proc']) && (float) $product['promocja_proc'] > 0): ?>
                                    <div class="flex flex-col flex-shrink-0">
                                        <span
                                            class="text-xs text-red-500 font-bold line-through opacity-60"><?= $product['cena_formatted'] ?>
                                            zł</span>
                                        <span
                                            class="text-2xl font-black text-red-600 whitespace-nowrap tracking-tighter italic"><?= $product['cena_promocyjna_formatted'] ?>
                                            <span class="text-sm font-normal">zł</span></span>
                                    </div>
                                <?php else: ?>
                                    <div class="flex flex-col flex-shrink-0">
                                        <span
                                            class="text-2xl font-black text-gray-900 whitespace-nowrap"><?= number_format($product['cena'], 2, ',', ' ') ?>
                                            <span class="text-sm font-normal">zł</span></span>
                                    </div>
                                <?php endif; ?>
                                <button
                                    class="add-to-cart-btn bg-gray-100 text-gray-900 p-3 rounded-xl hover:bg-indigo-600 hover:text-white transition-all transform active:scale-90 flex-shrink-0 ml-2"
                                    data-product-id="<?= (int) $product['id'] ?>"
                                    aria-label="Dodaj do koszyka <?= htmlspecialchars($product['nazwa']) ?>">
                                    <i class="fa-solid fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="mt-16 text-center">
            <button id="btn-show-more"
                class="bg-white border-2 border-indigo-600 text-indigo-600 px-10 py-4 rounded-full font-bold hover:bg-indigo-600 hover:text-white transition-all shadow-lg active:scale-95">
                Pokaż więcej produktów
            </button>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnShowMore = document.getElementById('btn-show-more');
            const productContainer = document.getElementById('product-container');
            const cartCountBadge = document.getElementById('cart-count-badge');
            const cartHoverPanel = document.getElementById('cart-hover-panel');
            const cartHoverItems = document.getElementById('cart-hover-items');
            const cartHoverTotal = document.getElementById('cart-hover-total');
            const cartHoverCount = document.getElementById('cart-hover-count');
            let currentSort = 'random';

            function updateCartBadge(count) {
                if (!cartCountBadge) {
                    return;
                }

                const normalized = Number.isFinite(Number(count)) ? Math.max(0, parseInt(count, 10)) : 0;
                cartCountBadge.textContent = String(normalized);
            }

            function fetchCartCount() {
                fetch('../api/cart/count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.success) {
                            updateCartBadge(data.count);
                        }
                    })
                    .catch(error => {
                        console.error('Blad pobierania stanu koszyka:', error);
                    });
            }

            function formatPrice(value) {
                return Number(value || 0).toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' zl';
            }

            function renderCartHover(data) {
                if (!cartHoverItems || !cartHoverTotal || !cartHoverCount) {
                    return;
                }

                const previewItems = Array.isArray(data.preview_items) ? data.preview_items : [];
                const count = Number(data.count || 0);

                cartHoverCount.textContent = `${count} szt.`;
                cartHoverTotal.textContent = formatPrice(data.total || 0);

                if (previewItems.length === 0) {
                    cartHoverItems.innerHTML = '<p class="text-sm text-gray-400">Koszyk jest pusty.</p>';
                    return;
                }

                cartHoverItems.innerHTML = previewItems.map(item => {
                    const imageHtml = item.image
                        ? `<img src="assets/img/${encodeURIComponent(item.image)}" alt="${item.name}" class="w-12 h-12 rounded-lg object-cover bg-gray-100">`
                        : '<div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-gray-300"><i class="fa-solid fa-image"></i></div>';

                    return `
                    <a href="product.php?id=${item.product_id}" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors">
                        ${imageHtml}
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-gray-900 truncate">${item.name}</p>
                            <p class="text-[11px] text-gray-500">${item.qty} x ${formatPrice(item.unit_price)}</p>
                        </div>
                        <p class="text-xs font-bold text-gray-700 whitespace-nowrap">${formatPrice(item.line_total)}</p>
                    </a>
                `;
                }).join('');
            }

            function fetchCartDetails() {
                return fetch('../api/cart/details.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.success) {
                            updateCartBadge(data.count);
                            renderCartHover(data);
                        }
                    })
                    .catch(error => {
                        console.error('Blad pobierania szczegolow koszyka:', error);
                    });
            }

            function addToCart(productId, buttonElement) {
                if (!productId) {
                    return;
                }

                const button = buttonElement;
                const originalHtml = button ? button.innerHTML : '';

                if (button) {
                    button.disabled = true;
                    button.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
                }

                fetch('../api/cart/add.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ productId: Number(productId), quantity: 1 })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.success) {
                            updateCartBadge(data.count);
                            fetchCartDetails();

                            if (button) {
                                button.innerHTML = '<i class="fa-solid fa-check"></i>';
                                setTimeout(() => {
                                    button.innerHTML = originalHtml;
                                    button.disabled = false;
                                }, 700);
                            }

                            return;
                        }

                        if (button) {
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        }

                        if (data && data.message) {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Blad dodawania do koszyka:', error);
                        if (button) {
                            button.innerHTML = originalHtml;
                            button.disabled = false;
                        }
                    });
            }

            function initProductSlides(scope = document) {
                const sliders = scope.querySelectorAll('.product-slider:not([data-slider-init="1"])');

                sliders.forEach(slider => {
                    slider.dataset.sliderInit = '1';

                    const slides = Array.from(slider.querySelectorAll('.product-slide'));
                    if (slides.length <= 1) {
                        return;
                    }

                    const dots = Array.from(slider.parentElement.querySelectorAll('.product-slide-dot'));
                    let activeIndex = 0;

                    const setActiveSlide = (nextIndex) => {
                        activeIndex = nextIndex;
                        slides.forEach((slide, idx) => {
                            slide.classList.toggle('opacity-100', idx === activeIndex);
                            slide.classList.toggle('opacity-0', idx !== activeIndex);
                        });
                        dots.forEach((dot, idx) => {
                            dot.classList.toggle('bg-white', idx === activeIndex);
                            dot.classList.toggle('bg-white/50', idx !== activeIndex);
                        });
                    };

                    dots.forEach((dot, idx) => {
                        dot.addEventListener('click', function () {
                            setActiveSlide(idx);
                        });
                    });

                    setActiveSlide(0);
                });
            }

            function createProductCard(produkt) {
                const isPromotion = parseFloat(produkt.promocja_proc) > 0;
                const productUrl = `product.php?id=${encodeURIComponent(produkt.id)}`;
                const priceHtml = isPromotion ? `
                <div class="flex flex-col flex-shrink-0">
                    <span class="text-xs text-red-500 font-bold line-through opacity-60">${parseFloat(produkt.cena).toLocaleString('pl-PL', { minimumFractionDigits: 2 })} zł</span>
                    <span class="text-2xl font-black text-red-600 whitespace-nowrap tracking-tighter italic">
                        ${parseFloat(produkt.cena_promocyjna).toLocaleString('pl-PL', { minimumFractionDigits: 2 })} <span class="text-sm font-normal">zł</span>
                    </span>
                </div>
            ` : `
                <div class="flex flex-col flex-shrink-0">
                    <span class="text-2xl font-black text-gray-900 whitespace-nowrap">${parseFloat(produkt.cena).toLocaleString('pl-PL', { minimumFractionDigits: 2 })} <span class="text-sm font-normal">zł</span></span>
                </div>
            `;

                const badgesHtml = `
                <div class="absolute top-4 left-4 z-10 flex flex-col gap-2">
                    ${produkt.is_new ? '<span class="bg-indigo-600 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-tighter shadow-lg">Nowość!</span>' : ''}
                    ${isPromotion ? `<span class="bg-red-600 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-tighter shadow-lg"> PROMOCJA -${Math.round(produkt.promocja_proc)}%</span>` : ''}
                </div>
            `;

                const productImages = (
                    produkt.nazwy_plikow
                        ? String(produkt.nazwy_plikow).split(',')
                        : (produkt.nazwa_pliku ? [String(produkt.nazwa_pliku)] : [])
                ).map(item => item.trim()).filter(Boolean);

                let imageHtml = `<div class="absolute inset-0 flex items-center justify-center text-gray-300"><i class="fa-solid fa-image text-4xl"></i></div>`;
                if (productImages.length > 0) {
                    const slides = productImages.map((fileName, idx) => `
                    <img
                        src="assets/img/${encodeURIComponent(fileName)}"
                        alt="${produkt.nazwa}"
                        class="product-slide absolute inset-0 h-full w-full object-cover transition-all duration-500 ${idx === 0 ? 'opacity-100' : 'opacity-0'}"
                        loading="lazy"
                    >
                `).join('');

                    const dots = productImages.length > 1
                        ? `<div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-10 flex gap-1.5 px-2.5 py-1 rounded-full bg-gradient-to-r from-black/70 via-black/45 to-black/70 backdrop-blur-sm shadow-md">${productImages.map((_, idx) => `<button type="button" class="product-slide-dot w-1.5 h-1.5 rounded-full ${idx === 0 ? 'bg-white' : 'bg-white/50'}" data-slide-index="${idx}" aria-label="Zdjęcie ${idx + 1}"></button>`).join('')}</div>`
                        : '';

                    imageHtml = `<div class="absolute inset-0 product-slider" data-slide-count="${productImages.length}">${slides}</div>${dots}`;
                }

                return `
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col product-card h-full min-w-0" data-product-id="${produkt.id}">
                    <div class="bg-gray-100 relative overflow-hidden flex-shrink-0" style="padding-top: 75%;">
                        ${badgesHtml}
                        ${imageHtml}
                        <a href="${productUrl}" class="absolute inset-0 z-[5]" aria-label="Pokaż produkt ${produkt.nazwa}"></a>
                    </div>
                    <div class="p-6 flex flex-col flex-grow min-w-0">
                        <div class="h-5 mb-1 overflow-hidden min-w-0">
                            <p class="text-[10px] text-indigo-600 font-bold uppercase tracking-widest truncate block w-full">
                                ${produkt.sciezka_kategorii || 'Elektronika'}
                            </p>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-4 line-clamp-2 h-14 overflow-hidden min-w-0">
                            <a href="${productUrl}" class="hover:text-indigo-600 transition-colors">${produkt.nazwa}</a>
                        </h3>
                        <div class="mt-auto pt-4 border-t border-gray-50 flex justify-between items-center min-w-0">
                            ${priceHtml}
                            <button class="add-to-cart-btn bg-gray-100 text-gray-900 p-3 rounded-xl hover:bg-indigo-600 hover:text-white transition-all transform active:scale-90 flex-shrink-0 ml-2" data-product-id="${produkt.id}" aria-label="Dodaj do koszyka ${produkt.nazwa}">
                                <i class="fa-solid fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            }

            document.querySelectorAll('.sort-option').forEach(option => {
                option.addEventListener('click', function () {
                    const sortType = this.dataset.sort;
                    const sortLabel = this.textContent;

                    currentSort = sortType;
                    document.getElementById('current-sort').textContent = sortLabel;

                    productContainer.innerHTML = '<div class="col-span-full py-20 text-center"><i class="fa-solid fa-circle-notch fa-spin text-4xl text-indigo-600"></i></div>';

                    fetch('../api/fetch_more_products.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            excludeIds: [],
                            sort: currentSort
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            productContainer.innerHTML = '';
                            if (data.success && data.products.length > 0) {
                                data.products.forEach(produkt => {
                                    productContainer.insertAdjacentHTML('beforeend', createProductCard(produkt));
                                });
                                initProductSlides(productContainer);
                                btnShowMore.disabled = false;
                                btnShowMore.classList.remove('opacity-50', 'pointer-events-none');
                                btnShowMore.innerHTML = 'Pokaż więcej produktów';
                            } else {
                                productContainer.innerHTML = '<div class="col-span-full py-20 text-center text-gray-400">Brak dostępnych produktów.</div>';
                            }
                        });
                });
            });

            if (btnShowMore) {
                btnShowMore.addEventListener('click', function () {
                    const displayedIds = Array.from(document.querySelectorAll('.product-card'))
                        .map(el => el.dataset.productId);

                    btnShowMore.disabled = true;
                    btnShowMore.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Ładowanie...';

                    fetch('../api/fetch_more_products.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            excludeIds: displayedIds,
                            sort: currentSort
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.products.length > 0) {
                                data.products.forEach(produkt => {
                                    productContainer.insertAdjacentHTML('beforeend', createProductCard(produkt));
                                });
                                initProductSlides(productContainer);
                                btnShowMore.disabled = false;
                                btnShowMore.innerHTML = 'Pokaż więcej produktów';
                            } else {
                                btnShowMore.innerHTML = 'To już wszystkie produkty';
                                btnShowMore.classList.add('opacity-50', 'pointer-events-none');
                            }
                        })
                        .catch(error => {
                            console.error('Błąd:', error);
                            btnShowMore.disabled = false;
                            btnShowMore.innerHTML = 'Spróbuj ponownie';
                        });
                });
            }

            if (productContainer) {
                productContainer.addEventListener('click', function (event) {
                    const button = event.target.closest('.add-to-cart-btn');
                    if (!button) {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();

                    addToCart(button.dataset.productId, button);
                });
            }

            initProductSlides(document);
            fetchCartCount();
            fetchCartDetails();

            if (cartHoverPanel) {
                cartHoverPanel.addEventListener('mouseenter', fetchCartDetails);
            }
        });
    </script>

    <!-- Footer -->
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
