<?php
/**
 * @fileoverview orders.php
 *
 * @description
 * Publiczny widok historii zamowien klienta.
 * Plik renderuje liste zamowien wraz z pozycjami, statusami platnosci i dostawy,
 * korzystajac z danych przygotowanych przez warstwe API.
 *
 * @scope
 * - Dolaczenie logiki backendowej z api/customer/orders.php.
 * - Renderowanie listy zamowien i szczegolow pozycji.
 * - Prezentacja statusow: zamowienie, platnosc, dostawa, kurier.
 * - Obsluga stanu pustej historii zamowien.
 * - Integracja widgetu koszyka i elementow nawigacji widoku.
 *
 * @behavior
 * - Brak zamowien: wyswietlenie komunikatu i przycisku przejscia do produktow.
 * - Dostepne zamowienia: wyswietlenie kart zamowien z danymi szczegolowymi.
 * - Po zaladowaniu strony: pobranie danych koszyka i aktualizacja widgetu.
 */

require_once '../../api/customer/orders.php';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moje zamowienia - SklepIntCom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="flex flex-col min-h-full text-gray-900">
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="../index.php" class="flex-shrink-0 flex items-center">
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

                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600 hidden md:inline">Zalogowano jako:
                        <b><?= e((string) $userName) ?></b></span>
                    <a href="../../api/auth/logout.php"
                        class="bg-indigo-50 text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm active:scale-95 border border-indigo-100"
                        aria-label="Wyloguj sie" title="Wyloguj sie">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                    <div class="relative group" id="cart-widget">
                        <a href="../cart.php"
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
                            <a href="../cart.php"
                                class="mt-3 inline-flex w-full justify-center items-center bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-bold hover:bg-indigo-700 transition-colors">
                                Przejdz do koszyka
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-6xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-end justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Moje zamowienia</h1>
                <p class="text-sm text-gray-500 mt-1">Historia Twoich zamowien i status realizacji.</p>
            </div>
            <a href="customer_panel.php"
                class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                <i class="fa-solid fa-arrow-left"></i>
                Wroc do panelu
            </a>
        </div>

        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
                <i class="fa-solid fa-box-open text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 mb-6">Nie masz jeszcze zadnych zamowien.</p>
                <a href="../products.php"
                    class="inline-flex items-center gap-2 bg-indigo-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-indigo-700 transition-all">
                    <i class="fa-solid fa-bag-shopping"></i>
                    Przegladaj produkty
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($orders as $order): ?>
                    <?php $orderId = (int) $order['id']; ?>
                    <?php $items = $orderItemsByOrder[$orderId] ?? []; ?>
                    <article class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <header
                            class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <p class="text-sm text-gray-500">Zamowienie</p>
                                <p class="text-lg font-black text-gray-900">#<?= $orderId ?></p>
                            </div>
                            <div class="text-sm text-gray-600">
                                <p><span class="font-semibold text-gray-800">Data:</span>
                                    <?= e((string) $order['data_zamowienia']) ?></p>
                                <p><span class="font-semibold text-gray-800">Status:</span>
                                    <?= e((string) ($order['status_zamowienia'] ?? '-')) ?></p>
                            </div>
                        </header>

                        <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <section class="lg:col-span-2">
                                <h2 class="text-sm font-bold text-gray-900 mb-3 uppercase tracking-wide">Pozycje</h2>
                                <div class="space-y-2">
                                    <?php foreach ($items as $item): ?>
                                        <?php
                                        $qty = (int) $item['ilosc'];
                                        $unit = (float) $item['cena_jednostkowa'];
                                        $discount = (float) $item['rabat_proc'];
                                        $lineTotal = $qty * $unit * (1 - ($discount / 100));
                                        ?>
                                        <div
                                            class="flex justify-between items-center gap-4 rounded-xl border border-gray-100 px-4 py-3 text-sm">
                                            <div class="min-w-0">
                                                <p class="font-semibold text-gray-900 truncate">
                                                    <?= e((string) $item['produkt_nazwa']) ?></p>
                                                <p class="text-gray-500"><?= $qty ?> x <?= number_format($unit, 2, ',', ' ') ?>
                                                    zl<?php if ($discount > 0): ?> (rabat
                                                        <?= number_format($discount, 2, ',', ' ') ?>%)<?php endif; ?></p>
                                            </div>
                                            <p class="font-bold text-gray-900 whitespace-nowrap">
                                                <?= number_format($lineTotal, 2, ',', ' ') ?> zl</p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>

                            <aside class="rounded-xl border border-gray-100 p-4 bg-gray-50 text-sm text-gray-700 h-fit">
                                <h2 class="text-sm font-bold text-gray-900 mb-3 uppercase tracking-wide">Szczegoly</h2>
                                <div class="space-y-2">
                                    <p><span class="font-semibold text-gray-800">Platnosc:</span>
                                        <?= e((string) ($order['typ_platnosci'] ?? '-')) ?></p>
                                    <p><span class="font-semibold text-gray-800">Status platnosci:</span>
                                        <?= e((string) ($order['status_platnosci'] ?? '-')) ?></p>
                                    <p><span class="font-semibold text-gray-800">Kurier:</span>
                                        <?= e((string) ($order['kurier_nazwa'] ?? '-')) ?></p>
                                    <p><span class="font-semibold text-gray-800">Status dostawy:</span>
                                        <?= e((string) ($order['status_dostawy'] ?? '-')) ?></p>
                                    <p><span class="font-semibold text-gray-800">Numer przesylki:</span>
                                        <?= e((string) ($order['numer_przesylki'] ?? '-')) ?></p>
                                    <p><span class="font-semibold text-gray-800">Przewidywana dostawa:</span>
                                        <?= e((string) ($order['przewidywana_dostawa'] ?? '-')) ?></p>
                                    <p><span class="font-semibold text-gray-800">Koszt dostawy:</span>
                                        <?= number_format((float) ($order['koszt_dostawy'] ?? 0), 2, ',', ' ') ?> zl</p>
                                </div>
                                <div class="mt-4 pt-3 border-t border-gray-200 flex justify-between text-base">
                                    <span class="font-bold text-gray-900">Razem</span>
                                    <span
                                        class="font-black text-indigo-600"><?= number_format((float) $order['kwota_brutto'], 2, ',', ' ') ?>
                                        zl</span>
                                </div>
                            </aside>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-white border-t border-gray-200 pt-16 pb-8">
        <div class="max-w-[1440px] mx-auto px-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div class="col-span-1 md:col-span-1">
                    <a href="../index.php" class="flex items-center mb-6">
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
                        <li><a href="../products.php" class="hover:text-indigo-600 transition-colors">Wszystkie
                                produkty</a></li>
                        <li><a href="../products.php?filtr=promocje"
                                class="hover:text-indigo-600 transition-colors">Promocje</a></li>
                        <li><a href="../products.php?filtr=nowosci"
                                class="hover:text-indigo-600 transition-colors">Nowosci</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 mb-6">Informacje</h4>
                    <ul class="space-y-4 text-sm text-gray-500">
                        <li><a href="../terms.php" class="hover:text-indigo-600 transition-colors">Regulamin</a></li>
                        <li><a href="../privacy.php" class="hover:text-indigo-600 transition-colors">Polityka
                                prywatnosci</a></li>
                        <li><a href="../customer/customer_panel.php"
                                class="hover:text-indigo-600 transition-colors">Panel klienta</a></li>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cartCountBadge = document.getElementById('cart-count-badge');
            const cartHoverItems = document.getElementById('cart-hover-items');
            const cartHoverTotal = document.getElementById('cart-hover-total');
            const cartHoverCount = document.getElementById('cart-hover-count');

            if (!cartCountBadge) {
                return;
            }

            function updateCartBadge(count) {
                const normalized = Number.isFinite(Number(count)) ? Math.max(0, parseInt(count, 10)) : 0;
                cartCountBadge.textContent = String(normalized);
                cartCountBadge.classList.toggle('hidden', normalized <= 0);
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

                cartHoverCount.textContent = count + ' szt.';
                cartHoverTotal.textContent = formatPrice(data.total || 0);

                if (previewItems.length === 0) {
                    cartHoverItems.innerHTML = '<p class="text-sm text-gray-400">Koszyk jest pusty.</p>';
                    return;
                }

                cartHoverItems.innerHTML = previewItems.map(function (item) {
                    const imageHtml = item.image
                        ? '<img src="../assets/img/' + encodeURIComponent(item.image) + '" alt="' + item.name + '" class="w-12 h-12 rounded-lg object-cover bg-gray-100">'
                        : '<div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-gray-300"><i class="fa-solid fa-image"></i></div>';

                    return '' +
                        '<a href="../product.php?id=' + item.product_id + '" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors">' +
                        imageHtml +
                        '<div class="min-w-0 flex-1">' +
                        '<p class="text-xs font-semibold text-gray-900 truncate">' + item.name + '</p>' +
                        '<p class="text-[11px] text-gray-500">' + item.qty + ' x ' + formatPrice(item.unit_price) + '</p>' +
                        '</div>' +
                        '<p class="text-xs font-bold text-gray-700 whitespace-nowrap">' + formatPrice(item.line_total) + '</p>' +
                        '</a>';
                }).join('');
            }

            fetch('../../api/cart/details.php')
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (!data || !data.success) {
                        return;
                    }

                    updateCartBadge(data.count);
                    renderCartHover(data);
                })
                .catch(function () {
                });
        });
    </script>
</body>
</html>
