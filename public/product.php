<?php
/**
 * @fileoverview product.php
 *
 * @description
 * Publiczny widok szczegolow pojedynczego produktu.
 * Plik renderuje karte produktu, galerie zdjec, dane cenowe i magazynowe,
 * breadcrumb kategorii oraz sekcje produktow powiazanych.
 *
 * @scope
 * - Dolaczenie logiki backendowej z api/store/product.php.
 * - Renderowanie stanu produktu i fallbacku dla braku produktu.
 * - Renderowanie galerii zdjec i sekcji "sprawdz rowniez".
 * - Integracja widgetu koszyka i akcji "dodaj do koszyka".
 * - Obsluga elementow nawigacji i stopki sklepu.
 *
 * @behavior
 * - Produkt istnieje: wyswietlenie pelnych szczegolow i akcji zakupowych.
 * - Brak produktu: wyswietlenie komunikatu i linku powrotu do listy produktow.
 * - Po zaladowaniu: skrypty aktualizuja podglad koszyka i interakcje galerii.
 */

require_once '../api/store/product.php';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
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
                                aria-label="Panel pracownika" title="Panel pracownika"><i
                                    class="fa-solid fa-user-gear"></i></a>
                            <a href="../api/auth/employee_logout.php"
                                class="bg-indigo-50 text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm active:scale-95 border border-indigo-100"
                                aria-label="Wyloguj panel" title="Wyloguj panel"><i
                                    class="fa-solid fa-right-from-bracket"></i></a>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_name'])): ?>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">Zalogowano jako:
                                <b><?= htmlspecialchars($_SESSION['user_name']) ?></b></span>
                            <a href="customer/customer_panel.php"
                                class="bg-white text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-50 transition-all shadow-sm active:scale-95 border border-indigo-100"
                                aria-label="Panel klienta" title="Panel klienta"><i class="fa-solid fa-user"></i></a>
                            <a href="../api/auth/logout.php"
                                class="bg-indigo-50 text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm active:scale-95 border border-indigo-100"
                                aria-label="Wyloguj się" title="Wyloguj się"><i
                                    class="fa-solid fa-right-from-bracket"></i></a>
                        </div>
                    <?php elseif (!isset($_SESSION['worker_id'])): ?>
                        <a href="customer/login.php"
                            class="text-gray-600 hover:text-indigo-600 font-medium text-sm transition-colors">Zaloguj
                            się</a>
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
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <main class="flex-grow max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-12 py-10">
        <?php if (!$product): ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
                <i class="fa-solid fa-box-open text-5xl text-gray-300 mb-4"></i>
                <h1 class="text-2xl font-bold text-gray-900 mb-3">Nie znaleziono produktu</h1>
                <p class="text-gray-500 mb-8">Wybrany produkt nie istnieje lub został usunięty.</p>
                <a href="products.php"
                    class="inline-flex items-center gap-2 bg-indigo-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-indigo-700 transition-all">
                    <i class="fa-solid fa-arrow-left"></i>
                    Wróć do produktów
                </a>
            </div>
        <?php else: ?>
            <div class="mb-6 text-sm text-gray-500">
                <a href="index.php" class="hover:text-indigo-600">Strona główna</a>
                <?php if (!empty($categoryBreadcrumb)): ?>
                    <?php foreach ($categoryBreadcrumb as $crumb): ?>
                        <span class="mx-2">/</span>
                        <a href="products.php?kategoria=<?= (int) $crumb['id'] ?>"
                            class="hover:text-indigo-600"><?= htmlspecialchars($crumb['nazwa']) ?></a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="mx-2">/</span>
                    <a href="products.php" class="hover:text-indigo-600">Produkty</a>
                <?php endif; ?>
                <span class="mx-2">/</span>
                <span class="text-gray-700"><?= htmlspecialchars($product['nazwa']) ?></span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div>
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                        <div class="bg-gray-100 rounded-xl relative overflow-hidden" style="padding-top: 75%;">
                            <?php if (!empty($productImages)): ?>
                                <?php foreach ($productImages as $idx => $img): ?>
                                    <img src="assets/img/<?= rawurlencode($img) ?>" alt="<?= htmlspecialchars($product['nazwa']) ?>"
                                        class="detail-slide absolute inset-0 h-full w-full object-cover transition-opacity duration-300 <?= $idx === 0 ? 'opacity-100' : 'opacity-0' ?>"
                                        data-slide-index="<?= $idx ?>">
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="absolute inset-0 flex items-center justify-center text-gray-300">
                                    <i class="fa-solid fa-image text-5xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (count($productImages) > 1): ?>
                            <div class="mt-4 grid grid-cols-5 sm:grid-cols-6 gap-2" id="detail-thumbs">
                                <?php foreach ($productImages as $idx => $img): ?>
                                    <button type="button"
                                        class="detail-thumb border rounded-lg overflow-hidden <?= $idx === 0 ? 'border-indigo-600' : 'border-gray-200' ?>"
                                        data-slide-index="<?= $idx ?>" aria-label="Miniatura <?= $idx + 1 ?>">
                                        <img src="assets/img/<?= rawurlencode($img) ?>" alt="Miniatura <?= $idx + 1 ?>"
                                            class="w-full h-16 object-cover">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 h-full">
                        <p class="text-[11px] text-indigo-600 font-bold uppercase tracking-widest mb-3">
                            <?= !empty($product['sciezka_kategorii']) ? htmlspecialchars($product['sciezka_kategorii']) : 'Elektronika' ?>
                        </p>
                        <h1 class="text-3xl font-bold text-gray-900 leading-tight mb-4">
                            <?= htmlspecialchars($product['nazwa']) ?></h1>

                        <div class="flex items-center gap-3 mb-6">
                            <?php if ($product['is_new']): ?>
                                <span
                                    class="bg-indigo-600 text-white text-[11px] font-bold px-3 py-1 rounded-full uppercase tracking-tight">Nowość</span>
                            <?php endif; ?>
                            <?php if ((float) $product['promocja_proc'] > 0): ?>
                                <span
                                    class="bg-red-600 text-white text-[11px] font-bold px-3 py-1 rounded-full uppercase tracking-tight">Promocja
                                    -<?= round((float) $product['promocja_proc']) ?>%</span>
                            <?php endif; ?>
                        </div>

                        <?php if ((float) $product['promocja_proc'] > 0): ?>
                            <div class="mb-8">
                                <p class="text-sm text-red-500 line-through font-bold">
                                    <?= htmlspecialchars($product['cena_formatted']) ?> zł</p>
                                <p class="text-4xl font-black text-red-600">
                                    <?= htmlspecialchars($product['cena_promocyjna_formatted']) ?> <span
                                        class="text-base font-semibold">zł</span></p>
                            </div>
                        <?php else: ?>
                            <div class="mb-8">
                                <p class="text-4xl font-black text-gray-900"><?= htmlspecialchars($product['cena_formatted']) ?>
                                    <span class="text-base font-semibold">zł</span></p>
                            </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-2 gap-4 mb-8 text-sm">
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                <p class="text-gray-500 mb-1">SKU</p>
                                <p class="font-bold text-gray-900"><?= htmlspecialchars($product['sku']) ?></p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                <p class="text-gray-500 mb-1">Dostępność</p>
                                <p
                                    class="font-bold <?= (int) $product['stan_magazynowy'] > 0 ? 'text-emerald-600' : 'text-red-600' ?>">
                                    <?= (int) $product['stan_magazynowy'] > 0 ? ('W magazynie: ' . (int) $product['stan_magazynowy'] . ' szt.') : 'Brak w magazynie' ?>
                                </p>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h2 class="text-lg font-bold text-gray-900 mb-3">Opis produktu</h2>
                            <p class="text-gray-600 leading-relaxed whitespace-pre-line">
                                <?= !empty($product['opis']) ? htmlspecialchars($product['opis']) : 'Brak opisu dla tego produktu.' ?>
                            </p>
                        </div>

                        <button type="button"
                            class="add-to-cart-btn w-full sm:w-auto bg-indigo-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-indigo-700 transition-all shadow-sm"
                            data-product-id="<?= (int) $product['id'] ?>"
                            aria-label="Dodaj do koszyka <?= htmlspecialchars($product['nazwa']) ?>">
                            <i class="fa-solid fa-cart-plus mr-2"></i>
                            Dodaj do koszyka
                        </button>
                    </div>
                </div>
            </div>

            <?php if (!empty($relatedProducts)): ?>
                <section class="mt-14">
                    <div class="flex justify-between items-end mb-8">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Sprawdź również</h2>
                            <p class="text-gray-500 mt-1">Inne produkty z tej samej kategorii</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                        <?php foreach ($relatedProducts as $item): ?>
                            <?php
                            $itemImages = [];
                            if (!empty($item['nazwy_plikow'])) {
                                $itemImages = array_values(array_filter(array_map('trim', explode(',', $item['nazwy_plikow']))));
                            } elseif (!empty($item['nazwa_pliku'])) {
                                $itemImages = [$item['nazwa_pliku']];
                            }
                            $itemImage = !empty($itemImages) ? $itemImages[0] : '';
                            ?>
                            <div
                                class="bg-white rounded-2xl shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col min-w-0">
                                <div class="bg-gray-100 relative overflow-hidden" style="padding-top: 75%;">
                                    <?php if ($itemImage !== ''): ?>
                                        <img src="assets/img/<?= rawurlencode($itemImage) ?>"
                                            alt="<?= htmlspecialchars($item['nazwa']) ?>"
                                            class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                            loading="lazy">
                                    <?php else: ?>
                                        <div class="absolute inset-0 flex items-center justify-center text-gray-300">
                                            <i class="fa-solid fa-image text-4xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    <a href="product.php?id=<?= (int) $item['id'] ?>" class="absolute inset-0"
                                        aria-label="Pokaż produkt <?= htmlspecialchars($item['nazwa']) ?>"></a>
                                </div>

                                <div class="p-5 flex flex-col flex-grow min-w-0">
                                    <p
                                        class="text-[10px] text-indigo-600 font-bold uppercase tracking-widest truncate block w-full mb-2">
                                        <?= !empty($item['sciezka_kategorii']) ? htmlspecialchars($item['sciezka_kategorii']) : 'Elektronika' ?>
                                    </p>
                                    <h3
                                        class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition-colors h-14 overflow-hidden">
                                        <a href="product.php?id=<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['nazwa']) ?></a>
                                    </h3>

                                    <div class="mt-auto pt-4 border-t border-gray-50">
                                        <?php if ((float) $item['promocja_proc'] > 0): ?>
                                            <p class="text-xs text-red-500 font-bold line-through opacity-60">
                                                <?= htmlspecialchars($item['cena_formatted']) ?> zł</p>
                                            <p class="text-2xl font-black text-red-600">
                                                <?= htmlspecialchars($item['cena_promocyjna_formatted']) ?> <span
                                                    class="text-sm font-normal">zł</span></p>
                                        <?php else: ?>
                                            <p class="text-2xl font-black text-gray-900">
                                                <?= htmlspecialchars($item['cena_formatted']) ?> <span
                                                    class="text-sm font-normal">zł</span></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>

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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cartCountBadge = document.getElementById('cart-count-badge');
            const cartHoverPanel = document.getElementById('cart-hover-panel');
            const cartHoverItems = document.getElementById('cart-hover-items');
            const cartHoverTotal = document.getElementById('cart-hover-total');
            const cartHoverCount = document.getElementById('cart-hover-count');
            const slides = Array.from(document.querySelectorAll('.detail-slide'));
            const thumbs = Array.from(document.querySelectorAll('.detail-thumb'));
            const addToCartButton = document.querySelector('.add-to-cart-btn');

            function updateCartBadge(count) {
                if (!cartCountBadge) {
                    return;
                }

                const normalized = Number.isFinite(Number(count)) ? Math.max(0, parseInt(count, 10)) : 0;
                cartCountBadge.textContent = String(normalized);
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

            if (slides.length > 1 && thumbs.length > 0) {
                const setActiveSlide = (activeIndex) => {
                    slides.forEach((slide, idx) => {
                        slide.classList.toggle('opacity-100', idx === activeIndex);
                        slide.classList.toggle('opacity-0', idx !== activeIndex);
                    });

                    thumbs.forEach((thumb, idx) => {
                        thumb.classList.toggle('border-indigo-600', idx === activeIndex);
                        thumb.classList.toggle('border-gray-200', idx !== activeIndex);
                    });
                };

                thumbs.forEach((thumb, idx) => {
                    thumb.addEventListener('click', function () {
                        setActiveSlide(idx);
                    });
                });
            }

            if (addToCartButton) {
                addToCartButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    addToCart(addToCartButton.dataset.productId, addToCartButton);
                });
            }

            fetchCartCount();
            fetchCartDetails();

            if (cartHoverPanel) {
                cartHoverPanel.addEventListener('mouseenter', fetchCartDetails);
            }
        });
    </script>
</body>
</html>
