<?php
/**
 * @fileoverview customer_panel.php
 *
 * @description
 * Plik odpowiada za renderowanie glownego panelu klienta w warstwie publicznej.
 * Laduje dane sesyjne z warstwy API i prezentuje skróty do najwazniejszych sekcji:
 * danych klienta, historii zamowien oraz listy produktow sklepu.
 *
 * @scope
 * - Dolaczenie logiki backendowej z api/customer/panel.php.
 * - Renderowanie interfejsu panelu klienta i nawigacji.
 * - Prezentacja kart szybkiego dostepu do kluczowych funkcji konta.
 * - Integracja widgetu koszyka i podgladu pozycji.
 * - Obsluga czesci wspolnych UI: naglowek, stopka, skrypty klienta.
 *
 * @behavior
 * - Aktywna sesja klienta: wyswietlenie panelu z danymi personalizowanymi.
 * - Brak sesji: kontrola dostepu realizowana przez dolaczona warstwe API.
 * - Po zaladowaniu strony: pobranie szczegolow koszyka i aktualizacja widgetu.
 */

require_once '../../api/customer/panel.php';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel klienta - SklepIntCom</title>
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

                <div class="hidden sm:flex flex-1 max-w-md mx-8">
                    <form action="../products.php" method="GET" class="w-full relative">
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

                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600 hidden md:inline">Zalogowano jako:
                        <b><?= htmlspecialchars($userName) ?></b></span>
                    <a href="../../api/auth/logout.php"
                        class="bg-indigo-50 text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm active:scale-95 border border-indigo-100"
                        aria-label="Wyloguj się" title="Wyloguj się">
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

    <section class="relative bg-slate-900 h-[320px] overflow-hidden">
        <div
            class="absolute inset-0 opacity-60 bg-[url('https://images.unsplash.com/photo-1519389950473-47ba0277781c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80')] bg-cover bg-center">
        </div>
        <div class="absolute inset-0 bg-slate-950/45"></div>
        <div
            class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex flex-col justify-center items-start text-white">
            <h1 class="text-4xl sm:text-5xl font-extrabold mb-3 tracking-tight">Panel klienta</h1>
            <p class="text-lg max-w-2xl text-gray-200">Witaj, <?= htmlspecialchars($userName) ?>. Zarządzaj swoimi
                danymi i szybko wróć do zakupów.</p>
        </div>
    </section>

    <main class="flex-grow max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-12 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-10 items-stretch">
            <a href="customer_data.php"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col h-full min-w-0">
                <div class="bg-gray-100 relative overflow-hidden flex-shrink-0" style="padding-top: 45%;">
                    <div class="absolute inset-0 flex items-center justify-center text-indigo-600/20">
                        <i
                            class="fa-solid fa-user-pen text-6xl group-hover:scale-110 transition-transform duration-500"></i>
                    </div>
                </div>
                <div class="p-8 flex flex-col flex-grow min-w-0 text-center">
                    <h3
                        class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-4 uppercase tracking-tighter">
                        Moje dane</h3>
                    <p class="text-gray-500 text-sm mb-6">Uzupełnij i zaktualizuj dane kontaktowe oraz adres dostawy.
                    </p>
                    <div class="mt-auto pt-6 border-t border-gray-50">
                        <span
                            class="w-full bg-gray-100 text-gray-900 py-3 rounded-xl font-bold group-hover:bg-indigo-600 group-hover:text-white transition-all inline-flex items-center justify-center gap-2">
                            <i class="fa-solid fa-id-card"></i> Otwórz sekcję
                        </span>
                    </div>
                </div>
            </a>

            <a href="orders.php"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col h-full min-w-0">
                <div class="bg-gray-100 relative overflow-hidden flex-shrink-0" style="padding-top: 45%;">
                    <div class="absolute inset-0 flex items-center justify-center text-indigo-600/20">
                        <i
                            class="fa-solid fa-receipt text-6xl group-hover:scale-110 transition-transform duration-500"></i>
                    </div>
                </div>
                <div class="p-8 flex flex-col flex-grow min-w-0 text-center">
                    <h3
                        class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-4 uppercase tracking-tighter">
                        Moje zamowienia</h3>
                    <p class="text-gray-500 text-sm mb-6">Sprawdz historie zakupow, status platnosci i dostawy.</p>
                    <div class="mt-auto pt-6 border-t border-gray-50">
                        <span
                            class="w-full bg-gray-100 text-gray-900 py-3 rounded-xl font-bold group-hover:bg-indigo-600 group-hover:text-white transition-all inline-flex items-center justify-center gap-2">
                            <i class="fa-solid fa-box"></i> Zobacz zamowienia
                        </span>
                    </div>
                </div>
            </a>

            <a href="../products.php"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col h-full min-w-0">
                <div class="bg-gray-100 relative overflow-hidden flex-shrink-0" style="padding-top: 45%;">
                    <div class="absolute inset-0 flex items-center justify-center text-indigo-600/20">
                        <i
                            class="fa-solid fa-bag-shopping text-6xl group-hover:scale-110 transition-transform duration-500"></i>
                    </div>
                </div>
                <div class="p-8 flex flex-col flex-grow min-w-0 text-center">
                    <h3
                        class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-4 uppercase tracking-tighter">
                        Sklep</h3>
                    <p class="text-gray-500 text-sm mb-6">Przeglądaj produkty, promocje i nowości dostępne w ofercie.
                    </p>
                    <div class="mt-auto pt-6 border-t border-gray-50">
                        <span
                            class="w-full bg-gray-100 text-gray-900 py-3 rounded-xl font-bold group-hover:bg-indigo-600 group-hover:text-white transition-all inline-flex items-center justify-center gap-2">
                            <i class="fa-solid fa-store"></i> Przejdź do sklepu
                        </span>
                    </div>
                </div>
            </a>
        </div>
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
