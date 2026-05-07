<?php
/**
 * @fileoverview triggers.php
 *
 * @description
 * Publiczny widok administracyjny do podgladu logow systemowych.
 * Plik renderuje tabele logow oraz formularz filtrowania po typie i frazie,
 * korzystajac z danych dostarczonych przez warstwe API pracownika.
 *
 * @scope
 * - Dolaczenie logiki backendowej z api/employee/triggers.php.
 * - Renderowanie formularza filtrowania logow (typ + wyszukiwarka).
 * - Renderowanie tabeli wpisow logow i stanu pustych wynikow.
 * - Prezentacja metadanych logow: typ, opis, klient, pracownik, data.
 * - Obsluga elementow nawigacji panelu administratora.
 *
 * @behavior
 * - Wejscie na widok: wyswietlenie listy logow systemowych.
 * - Zastosowanie filtrow: odswiezenie widoku z przefiltrowanymi wpisami.
 * - Brak wynikow: prezentacja komunikatu o braku logow dla podanych filtrow.
 */

require_once '../../api/employee/triggers.php';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logi systemowe - Panel Administratora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="flex flex-col min-h-full text-gray-900">
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="employee_panel.php" class="flex-shrink-0 flex items-center">
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
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600 hidden md:inline">Pracownik: <b
                            class="text-indigo-600"><?= e($workerName) ?></b></span>
                    <a href="employee_panel.php"
                        class="bg-indigo-50 text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all border border-indigo-100"
                        aria-label="Panel administratora" title="Panel administratora"><i
                            class="fa-solid fa-user-gear"></i></a>
                    <a href="../../api/auth/employee_logout.php"
                        class="bg-indigo-50 text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all border border-indigo-100"
                        aria-label="Wyloguj panel" title="Wyloguj panel"><i
                            class="fa-solid fa-right-from-bracket"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-[1440px] mx-auto w-full px-4 sm:px-6 lg:px-12 py-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Logi systemowe</h1>
            <p class="text-gray-500 mt-2">Podglad ostatnich wpisow z logow.</p>
        </div>

        <section class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">Logi</h2>
            </div>

            <form action="triggers.php" method="GET"
                class="px-6 py-4 border-b border-gray-100 bg-gray-50 grid grid-cols-1 md:grid-cols-[1fr_auto_auto] gap-3">
                <input type="text" name="q" value="<?= e($search) ?>"
                    placeholder="Szukaj po tresci logu, id logu, kliencie lub pracowniku"
                    class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">

                <select name="typ"
                    class="bg-white border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="all" <?= $typeFilter === 'all' ? 'selected' : '' ?>>Wszystkie typy</option>
                    <option value="info" <?= $typeFilter === 'info' ? 'selected' : '' ?>>info</option>
                    <option value="warning" <?= $typeFilter === 'warning' ? 'selected' : '' ?>>warning</option>
                    <option value="error" <?= $typeFilter === 'error' ? 'selected' : '' ?>>error</option>
                    <option value="security" <?= $typeFilter === 'security' ? 'selected' : '' ?>>security</option>
                </select>

                <button type="submit"
                    class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-all">Filtruj</button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Typ</th>
                            <th class="px-4 py-3 text-left">Opis</th>
                            <th class="px-4 py-3 text-left">Klient</th>
                            <th class="px-4 py-3 text-left">Pracownik</th>
                            <th class="px-4 py-3 text-left">Data zdarzenia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (!$logs): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-gray-500">Brak logow dla podanych
                                    filtrow.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-gray-50/70 align-top">
                                <td class="px-4 py-3 text-gray-700"><?= (int) $log['id'] ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($log['typ'] === 'warning'): ?>
                                        <span
                                            class="inline-flex rounded-full bg-amber-100 text-amber-700 px-2.5 py-1 text-xs font-semibold">warning</span>
                                    <?php elseif ($log['typ'] === 'error'): ?>
                                        <span
                                            class="inline-flex rounded-full bg-red-100 text-red-700 px-2.5 py-1 text-xs font-semibold">error</span>
                                    <?php elseif ($log['typ'] === 'security'): ?>
                                        <span
                                            class="inline-flex rounded-full bg-violet-100 text-violet-700 px-2.5 py-1 text-xs font-semibold">security</span>
                                    <?php else: ?>
                                        <span
                                            class="inline-flex rounded-full bg-indigo-100 text-indigo-700 px-2.5 py-1 text-xs font-semibold">info</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-gray-700 max-w-[460px] break-words"><?= e((string) $log['opis']) ?>
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    <?= !empty($log['klient_nazwa']) ? e((string) $log['klient_nazwa']) : (isset($log['klient_id']) ? '#' . (int) $log['klient_id'] : '-') ?>
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    <?= !empty($log['pracownik_nazwa']) ? e((string) $log['pracownik_nazwa']) : (isset($log['pracownik_id']) ? '#' . (int) $log['pracownik_id'] : '-') ?>
                                </td>
                                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                                    <?= e((string) $log['data_zdarzenia']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
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
