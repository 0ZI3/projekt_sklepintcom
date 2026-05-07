<?php
/**
 * @fileoverview charts.php
 *
 * @description
 * Publiczny widok analityczny panelu administratora.
 * Plik renderuje kontenery wykresow oraz interfejs strony, korzystajac z danych
 * i autoryzacji przygotowanych w warstwie API pracownika.
 *
 * @scope
 * - Dolaczenie logiki backendowej z api/employee/charts.php.
 * - Renderowanie struktury strony z sekcjami wykresow.
 * - Integracja bibliotek frontendowych (Tailwind, Font Awesome, D3).
 * - Udostepnienie kontenerow dla wykresow sprzedazy, kategorii, produktow i koszykow.
 * - Dolaczenie skryptu klienckiego api/employee/charts.js.
 *
 * @behavior
 * - Wejscie na strone: wyswietlenie panelu analitycznego administratora.
 * - Brak autoryzacji/uprawnien: kontrola dostepu realizowana w dolaczonym API.
 * - Po zaladowaniu: skrypt JS pobiera dane i renderuje wykresy dynamicznie.
 */

require_once '../../api/employee/charts.php';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wykresy - Panel Administratora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/d3@7"></script>
</head>
<body class="flex flex-col min-h-full text-gray-900">
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="../index.php" class="flex-shrink-0 flex items-center">
                    <div class="bg-indigo-600 p-2 rounded-lg mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-gray-900">Sklep<span class="text-indigo-600">IntCom</span></span>
                </a>

                <div class="flex items-center space-x-6">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">Pracownik: <b class="text-indigo-600"><?= e($workerName) ?></b></span>
                        <a href="employee_panel.php" class="bg-indigo-50 text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm active:scale-95 border border-indigo-100" aria-label="Panel administratora" title="Panel administratora">
                            <i class="fa-solid fa-user-gear"></i>
                        </a>
                        <a href="../../api/auth/employee_logout.php" class="bg-indigo-50 text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm active:scale-95 border border-indigo-100" aria-label="Wyloguj panel" title="Wyloguj panel">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-[1440px] mx-auto w-full px-4 sm:px-6 lg:px-12 py-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Wykresy sprzedazy i koszykow (90 dni)</h1>
            <p class="text-gray-500 mt-2">Panel analityczny oparty o dane z ostatnich 90 dni.</p>
        </div>

        <div id="charts-error" class="hidden mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"></div>

        <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <article class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h2 class="font-bold text-gray-900">Sprzedaz dzienna: przychod i ilosc sztuk</h2>
                        <p class="text-xs text-gray-500 mt-1">Wykres liniowy z odczytem konkretnego dnia.</p>
                    </div>
                </div>
                <div id="sales-line-chart" class="w-full min-h-[360px]"></div>
            </article>

            <article class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h2 class="font-bold text-gray-900">Przychod wedlug kategorii</h2>
                        <p class="text-xs text-gray-500 mt-1">Wykres kolowy: udzial procentowy i wartosc.</p>
                    </div>
                </div>
                <div id="category-pie-chart" class="w-full min-h-[360px]"></div>
            </article>

            <article class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 xl:col-span-2">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h2 class="font-bold text-gray-900">Top 10 produktow po sprzedanych sztukach</h2>
                        <p class="text-xs text-gray-500 mt-1">Wykres slupkowy dla ostatnich 90 dni.</p>
                    </div>
                </div>
                <div id="top-products-bar-chart" class="w-full min-h-[420px]"></div>
            </article>

            <article class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 xl:col-span-2">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h2 class="font-bold text-gray-900">Koszyki aktywne vs porzucone</h2>
                        <p class="text-xs text-gray-500 mt-1">Wykres liniowy z odczytem konkretnych dni.</p>
                    </div>
                </div>
                <div id="carts-line-chart" class="w-full min-h-[360px]"></div>
            </article>
        </section>
    </main>

    <footer class="bg-white border-t border-gray-200 pt-16 pb-8">
        <div class="max-w-[1440px] mx-auto px-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div class="col-span-1 md:col-span-1">
                    <a href="../index.php" class="flex items-center mb-6">
                        <div class="bg-indigo-600 p-2 rounded-lg mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <span class="text-xl font-bold tracking-tight text-gray-900">Sklep<span class="text-indigo-600">IntCom</span></span>
                    </a>
                    <p class="text-gray-500 text-sm leading-relaxed">Twoj partner w swiecie technologii. Dostarczamy najwyzszej jakosci sprzet IT dla profesjonalistow i hobbystow.</p>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 mb-6">Sklep</h4>
                    <ul class="space-y-4 text-sm text-gray-500">
                        <li><a href="../products.php" class="hover:text-indigo-600 transition-colors">Wszystkie produkty</a></li>
                        <li><a href="../products.php?filtr=promocje" class="hover:text-indigo-600 transition-colors">Promocje</a></li>
                        <li><a href="../products.php?filtr=nowosci" class="hover:text-indigo-600 transition-colors">Nowosci</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 mb-6">Informacje</h4>
                    <ul class="space-y-4 text-sm text-gray-500">
                        <li><a href="../terms.php" class="hover:text-indigo-600 transition-colors">Regulamin</a></li>
                        <li><a href="../privacy.php" class="hover:text-indigo-600 transition-colors">Polityka prywatnosci</a></li>
                        <li><a href="../customer/customer_panel.php" class="hover:text-indigo-600 transition-colors">Panel klienta</a></li>
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
    <script src="../../api/employee/charts.js"></script>
</body>
</html>
