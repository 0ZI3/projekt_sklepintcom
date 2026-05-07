<?php
/**
 * @fileoverview employee_panel.php
 *
 * @description
 * Publiczny widok glownego panelu administratora/pracownika.
 * Plik renderuje dashboard operacyjny z KPI, listami analitycznymi i kartami
 * nawigacyjnymi do kluczowych modulow zarzadzania systemem.
 *
 * @scope
 * - Dolaczenie logiki backendowej z api/employee/dashboard.php.
 * - Renderowanie metryk KPI i sekcji podsumowan (klienci, zamowienia, koszyki).
 * - Renderowanie kart nawigacyjnych do modulow: produkty, zamowienia, klienci, logi.
 * - Udostepnienie przejscia do panelu wykresow analitycznych.
 * - Obsluga elementow wspolnych UI: nawigacja, hero, stopka.
 *
 * @behavior
 * - Wejscie na strone: wyswietlenie panelu administracyjnego z danymi operacyjnymi.
 * - Brak autoryzacji/uprawnien: kontrola dostepu realizowana przez dolaczone API.
 * - Dostepne dane: prezentacja aktualnych metryk i list dla administratora.
 */

require_once '../../api/employee/dashboard.php';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administratora - SklepIntCom</title>
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
                    <div class="w-full relative">
                        <input type="text" placeholder="Szukaj w panelu..."
                            class="w-full bg-gray-100 border-transparent rounded-full py-2.5 pl-12 pr-4 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm outline-none">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-6">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">Pracownik: <b
                                class="text-indigo-600"><?= htmlspecialchars($worker_name) ?></b></span>
                        <a href="../index.php"
                            class="bg-indigo-50 text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm active:scale-95 border border-indigo-100"
                            aria-label="Strona glowna" title="Strona glowna">
                            <i class="fa-solid fa-house"></i>
                        </a>
                        <a href="../../api/auth/employee_logout.php"
                            class="bg-indigo-50 text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm active:scale-95 border border-indigo-100"
                            aria-label="Wyloguj panel" title="Wyloguj panel">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <section class="relative bg-slate-900 h-[400px] overflow-hidden">
        <div
            class="absolute inset-0 opacity-60 bg-[url('https://images.unsplash.com/photo-1519389950473-47ba0277781c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80')] bg-cover bg-center">
        </div>
        <div class="absolute inset-0 bg-slate-950/40"></div>
        <div
            class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex flex-col justify-center items-start text-white">
            <h1 class="text-4xl sm:text-6xl font-extrabold mb-4 tracking-tight">Panel Administratora</h1>
            <p class="text-xl max-w-2xl mb-8 text-gray-200">Zarządzaj asortymentem, zamówieniami i użytkownikami
                SklepIntCom.</p>
        </div>
    </section>

    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-12 -mt-16 relative z-10">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
            <article class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <p class="text-xs uppercase tracking-wide text-gray-500">Zamowienia</p>
                <p class="text-3xl font-extrabold text-gray-900 mt-2"><?= (int) $kpi['zamowienia'] ?></p>
            </article>
            <article class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <p class="text-xs uppercase tracking-wide text-gray-500">Obrot brutto</p>
                <p class="text-3xl font-extrabold text-gray-900 mt-2">
                    <?= number_format((float) $kpi['obrot'], 2, ',', ' ') ?> PLN</p>
            </article>
            <article class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <p class="text-xs uppercase tracking-wide text-gray-500">Klienci</p>
                <p class="text-3xl font-extrabold text-gray-900 mt-2"><?= (int) $kpi['klienci'] ?></p>
            </article>
            <article class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <p class="text-xs uppercase tracking-wide text-gray-500">Aktywne koszyki</p>
                <p class="text-3xl font-extrabold text-gray-900 mt-2"><?= (int) $kpi['aktywne_koszyki'] ?></p>
            </article>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-12">
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900">Top klienci</h3>
                </div>
                <div class="p-5 space-y-3">
                    <?php if (!$topCustomers): ?>
                        <p class="text-sm text-gray-500">Brak danych.</p>
                    <?php endif; ?>
                    <?php foreach ($topCustomers as $row): ?>
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 truncate">
                                    <?= htmlspecialchars((string) $row['klient']) ?></p>
                                <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars((string) $row['email']) ?></p>
                            </div>
                            <div class="text-right whitespace-nowrap">
                                <p class="text-xs text-gray-500"><?= (int) $row['liczba_zamowien'] ?> zam.</p>
                                <p class="font-bold text-indigo-600">
                                    <?= number_format((float) $row['suma_wydatkow'], 2, ',', ' ') ?> PLN</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900">Ostatnie zamowienia</h3>
                </div>
                <div class="p-5 space-y-3">
                    <?php if (!$latestOrders): ?>
                        <p class="text-sm text-gray-500">Brak danych.</p>
                    <?php endif; ?>
                    <?php foreach ($latestOrders as $row): ?>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gray-900">#<?= (int) $row['zamowienie_id'] ?> -
                                    <?= htmlspecialchars((string) $row['klient']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars((string) $row['status_zamowienia']) ?>
                                    | <?= htmlspecialchars((string) $row['data_zamowienia']) ?></p>
                            </div>
                            <p class="font-bold text-indigo-600 whitespace-nowrap">
                                <?= number_format((float) $row['kwota_brutto'], 2, ',', ' ') ?> PLN</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900">Niski stan magazynowy</h3>
                </div>
                <div class="p-5 space-y-3">
                    <?php if (!$lowStock): ?>
                        <p class="text-sm text-gray-500">Brak danych.</p>
                    <?php endif; ?>
                    <?php foreach ($lowStock as $row): ?>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gray-900"><?= htmlspecialchars((string) $row['nazwa']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars((string) $row['sku']) ?> |
                                    <?= htmlspecialchars((string) $row['kategoria']) ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-rose-600"><?= (int) $row['stan_magazynowy'] ?> szt.</p>
                                <p class="text-xs text-gray-500">sprzedane: <?= (int) $row['sprzedane_sztuki'] ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900">Aktywne i porzucone koszyki</h3>
                </div>
                <div class="p-5 space-y-3">
                    <?php if (!$activeCarts): ?>
                        <p class="text-sm text-gray-500">Brak danych.</p>
                    <?php endif; ?>
                    <?php foreach ($activeCarts as $row): ?>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gray-900">Koszyk
                                    #<?= (int) $row['koszyk_id'] ?><?= !empty($row['klient']) ? ' - ' . htmlspecialchars((string) $row['klient']) : '' ?>
                                </p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars((string) $row['status']) ?> |
                                    <?= htmlspecialchars((string) $row['zaktualizowano_at']) ?></p>
                            </div>
                            <div class="text-right whitespace-nowrap">
                                <p class="text-xs text-gray-500"><?= (int) $row['liczba_pozycji'] ?> poz.</p>
                                <p class="font-bold text-indigo-600">
                                    <?= number_format((float) $row['wartosc_koszyka'], 2, ',', ' ') ?> PLN</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <div class="mb-12 flex justify-center">
            <a href="charts.php"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                <i class="fa-solid fa-chart-column"></i>
                Pokaz wykresy
            </a>
        </div>
    </section>

    <main class="flex-grow max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-12 py-12" id="produkty">
        <div class="flex justify-between items-end mb-10 px-2 text-indigo-100">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 uppercase">Zarządzanie systemem</h2>
                <p class="text-gray-500 mt-2">Wybierz moduł do edycji</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-8 md:gap-10 items-stretch">
            <div
                class="bg-white rounded-2xl shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col h-full min-w-0">
                <div class="bg-gray-100 relative overflow-hidden flex-shrink-0" style="padding-top: 50%;">
                    <div class="absolute inset-0 flex items-center justify-center text-indigo-600/20">
                        <i class="fa-solid fa-box text-6xl group-hover:scale-110 transition-transform duration-500"></i>
                    </div>
                </div>
                <div class="p-8 flex flex-col flex-grow min-w-0 text-center">
                    <h3
                        class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-4 uppercase tracking-tighter">
                        Produkty
                    </h3>
                    <p class="text-gray-500 text-sm mb-6">Zarządzaj stanami magazynowymi, cenami i opisami asortymentu.
                    </p>
                    <div class="mt-auto pt-6 border-t border-gray-50">
                        <a href="products.php"
                            class="w-full bg-gray-100 text-gray-900 py-3 rounded-xl font-bold hover:bg-indigo-600 hover:text-white transition-all transform active:scale-95 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-list-check"></i> Otwórz listę
                        </a>
                    </div>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col h-full min-w-0">
                <div class="bg-gray-100 relative overflow-hidden flex-shrink-0" style="padding-top: 50%;">
                    <div class="absolute inset-0 flex items-center justify-center text-indigo-600/20">
                        <i
                            class="fa-solid fa-cart-shopping text-6xl group-hover:scale-110 transition-transform duration-500"></i>
                    </div>
                </div>
                <div class="p-8 flex flex-col flex-grow min-w-0 text-center">
                    <h3
                        class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-4 uppercase tracking-tighter">
                        Zamówienia
                    </h3>
                    <p class="text-gray-500 text-sm mb-6">Przeglądaj, realizuj i aktualizuj statusy zamówień klientów.
                    </p>
                    <div class="mt-auto pt-6 border-t border-gray-50">
                        <a href="orders.php"
                            class="w-full bg-gray-100 text-gray-900 py-3 rounded-xl font-bold hover:bg-indigo-600 hover:text-white transition-all transform active:scale-95 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-truck-fast"></i> Otwórz panel
                        </a>
                    </div>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col h-full min-w-0">
                <div class="bg-gray-100 relative overflow-hidden flex-shrink-0" style="padding-top: 50%;">
                    <div class="absolute inset-0 flex items-center justify-center text-indigo-600/20">
                        <i
                            class="fa-solid fa-users text-6xl group-hover:scale-110 transition-transform duration-500"></i>
                    </div>
                </div>
                <div class="p-8 flex flex-col flex-grow min-w-0 text-center">
                    <h3
                        class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-4 uppercase tracking-tighter">
                        Klienci
                    </h3>
                    <p class="text-gray-500 text-sm mb-6">Zarządzaj bazą użytkowników, adresami i dostępem do kont.</p>
                    <div class="mt-auto pt-6 border-t border-gray-50">
                        <a href="customers.php"
                            class="w-full bg-gray-100 text-gray-900 py-3 rounded-xl font-bold hover:bg-indigo-600 hover:text-white transition-all transform active:scale-95 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-user-gear"></i> Przegladaj baze
                        </a>
                    </div>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl shadow-sm border border-gray-100 group hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col h-full min-w-0">
                <div class="bg-gray-100 relative overflow-hidden flex-shrink-0" style="padding-top: 50%;">
                    <div class="absolute inset-0 flex items-center justify-center text-indigo-600/20">
                        <i
                            class="fa-solid fa-microchip text-6xl group-hover:scale-110 transition-transform duration-500"></i>
                    </div>
                </div>
                <div class="p-8 flex flex-col flex-grow min-w-0 text-center">
                    <h3
                        class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-4 uppercase tracking-tighter">
                        Logi systemowe
                    </h3>
                    <p class="text-gray-500 text-sm mb-6">Przegladaj wpisy systemowe zapisywane automatycznie przez
                        mechanizmy bazy danych.</p>
                    <div class="mt-auto pt-6 border-t border-gray-50">
                        <a href="triggers.php"
                            class="w-full bg-gray-100 text-gray-900 py-3 rounded-xl font-bold hover:bg-indigo-600 hover:text-white transition-all transform active:scale-95 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-scroll"></i> Otworz podglad
                        </a>
                    </div>
                </div>
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
</body>
</html>
