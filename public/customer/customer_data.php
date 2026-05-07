<?php
/**
 * @fileoverview customer_data.php
 *
 * @description
 * Plik odpowiada za renderowanie widoku danych klienta w panelu uzytkownika.
 * Korzysta z warstwy API do pobrania i aktualizacji danych, a nastepnie wyswietla
 * formularz edycji lub podglad danych wraz z walidacja po stronie interfejsu.
 *
 * @scope
 * - Dolaczenie logiki backendowej z api/customer/data.php.
 * - Renderowanie widoku danych klienta (tryb podgladu i tryb edycji).
 * - Wyswietlanie komunikatow sukcesu i bledow walidacyjnych.
 * - Integracja formularza z walidacja i formatowaniem danych adresowych.
 * - Obsluga widgetu koszyka oraz elementow nawigacji panelu klienta.
 *
 * @behavior
 * - Tryb standardowy: prezentacja zapisanych danych klienta.
 * - Tryb edit=1: wyswietlenie formularza edycji i obsluga zapisu.
 * - Aktualizacja danych: prezentacja komunikatu o powodzeniu operacji.
 */

require_once '../../api/customer/data.php';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moje dane - SklepIntCom</title>
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

    <main class="flex-grow max-w-4xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-12">
        <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
                Dane klienta zostaly zaktualizowane.
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="bg-indigo-600 px-8 py-7 text-white">
                <h1 class="text-3xl font-extrabold tracking-tight">Moje dane</h1>
                <p class="text-indigo-100 mt-2">Poniżej znajdziesz dane pobrane z Twojego konta klienta.</p>
            </div>

            <?php if ($isEditMode): ?>
                <div class="p-8">
                    <form class="space-y-6" action="customer_data.php?edit=1" method="POST">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="imie" class="block text-sm font-semibold text-gray-700">Imie</label>
                                <div class="mt-1">
                                    <input id="imie" name="imie" type="text" required
                                        value="<?= e($_POST['imie'] ?? (string) ($clientData['imie'] ?? '')) ?>"
                                        class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                                </div>
                            </div>

                            <div>
                                <label for="nazwisko" class="block text-sm font-semibold text-gray-700">Nazwisko</label>
                                <div class="mt-1">
                                    <input id="nazwisko" name="nazwisko" type="text" required
                                        value="<?= e($_POST['nazwisko'] ?? (string) ($clientData['nazwisko'] ?? '')) ?>"
                                        class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700">Email</label>
                            <div class="mt-1">
                                <input id="email" type="email" disabled
                                    value="<?= e((string) ($clientData['email'] ?? '')) ?>"
                                    class="block w-full appearance-none rounded-lg border border-gray-200 bg-gray-100 px-4 py-3 text-gray-500 sm:text-sm">
                            </div>
                        </div>

                        <div>
                            <label for="telefon" class="block text-sm font-semibold text-gray-700">Numer telefonu</label>
                            <div class="mt-1">
                                <input id="telefon" name="telefon" type="tel" required pattern="^\+?[0-9\s\-()]{7,20}$"
                                    title="Uzyj formatu np. +48 500 100 100"
                                    value="<?= e($_POST['telefon'] ?? (string) ($clientData['telefon'] ?? '')) ?>"
                                    class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                            </div>
                        </div>

                        <div>
                            <label for="ulica" class="block text-sm font-semibold text-gray-700">Ulica</label>
                            <div class="mt-1">
                                <input id="ulica" name="ulica" type="text" required data-capitalize-first="true"
                                    pattern="^[^<>]{2,150}$" title="Podaj poprawna nazwe ulicy"
                                    value="<?= e($_POST['ulica'] ?? (string) ($clientData['ulica'] ?? '')) ?>"
                                    class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="dom" class="block text-sm font-semibold text-gray-700">Numer domu</label>
                                <div class="mt-1">
                                    <input id="dom" name="dom" type="text" required data-house-uppercase-last="true"
                                        pattern="^[0-9A-Za-z\-\/]{1,20}$" title="Podaj poprawny numer domu"
                                        value="<?= e($_POST['dom'] ?? (string) ($clientData['dom'] ?? '')) ?>"
                                        class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                                </div>
                            </div>

                            <div>
                                <label for="numer" class="block text-sm font-semibold text-gray-700">Numer lokalu</label>
                                <div class="mt-1">
                                    <input id="numer" name="numer" type="text" pattern="^[0-9A-Za-z\-\/]{1,20}$"
                                        title="Podaj poprawny numer lokalu"
                                        value="<?= e($_POST['numer'] ?? (string) ($clientData['numer'] ?? '')) ?>"
                                        class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="miasto" class="block text-sm font-semibold text-gray-700">Miasto</label>
                                <div class="mt-1">
                                    <input id="miasto" name="miasto" type="text" required data-capitalize-first="true"
                                        pattern="^[^<>]{2,120}$" title="Podaj poprawna nazwe miasta"
                                        value="<?= e($_POST['miasto'] ?? (string) ($clientData['miasto'] ?? '')) ?>"
                                        class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                                </div>
                            </div>

                            <div>
                                <label for="kod_pocztowy" class="block text-sm font-semibold text-gray-700">Kod
                                    pocztowy</label>
                                <div class="mt-1">
                                    <input id="kod_pocztowy" name="kod_pocztowy" type="text" required
                                        pattern="^[0-9]{2}-[0-9]{3}$" title="Uzyj formatu 00-000"
                                        value="<?= e($_POST['kod_pocztowy'] ?? (string) ($clientData['kod_pocztowy'] ?? '')) ?>"
                                        class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="kraj" class="block text-sm font-semibold text-gray-700">Kraj</label>
                            <div class="mt-1">
                                <input id="kraj" name="kraj" type="text" required data-capitalize-first="true"
                                    pattern="^[^<>]{2,120}$" title="Podaj poprawna nazwe kraju"
                                    value="<?= e($_POST['kraj'] ?? (string) ($clientData['kraj'] ?? 'Polska')) ?>"
                                    class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit"
                                class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-3 rounded-lg font-bold text-sm hover:bg-indigo-700 transition-all">
                                <i class="fa-solid fa-floppy-disk"></i> Zapisz zmiany
                            </button>
                            <a href="customer_data.php"
                                class="inline-flex items-center gap-2 bg-gray-100 text-gray-800 px-5 py-3 rounded-lg font-bold text-sm hover:bg-gray-200 transition-all">
                                <i class="fa-solid fa-xmark"></i> Anuluj
                            </a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Imie</p>
                        <p class="text-lg font-semibold text-gray-900"><?= displayValue($clientData['imie'] ?? null) ?></p>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Nazwisko</p>
                        <p class="text-lg font-semibold text-gray-900"><?= displayValue($clientData['nazwisko'] ?? null) ?>
                        </p>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-5 md:col-span-2">
                        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Email</p>
                        <p class="text-lg font-semibold text-gray-900 break-all">
                            <?= displayValue($clientData['email'] ?? null) ?></p>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Telefon</p>
                        <p class="text-lg font-semibold text-gray-900"><?= displayValue($clientData['telefon'] ?? null) ?>
                        </p>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Miasto</p>
                        <p class="text-lg font-semibold text-gray-900"><?= displayValue($clientData['miasto'] ?? null) ?>
                        </p>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-5 md:col-span-2">
                        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Adres</p>
                        <p class="text-lg font-semibold text-gray-900">
                            <?= displayValue(trim((string) ($clientData['ulica'] ?? ''))) ?>,
                            dom <?= displayValue(trim((string) ($clientData['dom'] ?? ''))) ?>,
                            lokal <?= displayValue(trim((string) ($clientData['numer'] ?? ''))) ?>
                        </p>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Kod pocztowy</p>
                        <p class="text-lg font-semibold text-gray-900">
                            <?= displayValue($clientData['kod_pocztowy'] ?? null) ?></p>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Kraj</p>
                        <p class="text-lg font-semibold text-gray-900"><?= displayValue($clientData['kraj'] ?? null) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="px-8 py-6 border-t border-gray-100 flex flex-wrap gap-3">
                <a href="customer_panel.php"
                    class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-3 rounded-lg font-bold text-sm hover:bg-indigo-700 transition-all">
                    <i class="fa-solid fa-arrow-left"></i> Wroc do panelu
                </a>
                <?php if ($isEditMode): ?>
                    <a href="customer_data.php"
                        class="inline-flex items-center gap-2 bg-gray-100 text-gray-800 px-5 py-3 rounded-lg font-bold text-sm hover:bg-gray-200 transition-all">
                        <i class="fa-solid fa-eye"></i> Zobacz dane
                    </a>
                <?php else: ?>
                    <a href="customer_data.php?edit=1"
                        class="inline-flex items-center gap-2 bg-gray-100 text-gray-800 px-5 py-3 rounded-lg font-bold text-sm hover:bg-gray-200 transition-all">
                        <i class="fa-solid fa-pen"></i> Edytuj dane
                    </a>
                <?php endif; ?>
            </div>
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

    <script src="../../api/auth/address_autocapitalize.js"></script>
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
