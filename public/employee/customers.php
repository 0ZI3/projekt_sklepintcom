<?php
/**
 * @fileoverview customers.php
 *
 * @description
 * Publiczny widok administracyjny do zarzadzania klientami.
 * Plik renderuje liste klientow, formularz wyszukiwania oraz formularz edycji
 * danych klienta na podstawie danych dostarczonych przez warstwe API.
 *
 * @scope
 * - Dolaczenie logiki backendowej z api/employee/customers.php.
 * - Renderowanie tabeli klientow i stanu pustej listy.
 * - Renderowanie formularza wyszukiwania i filtrowania klientow.
 * - Renderowanie formularza edycji klienta oraz komunikatow statusu/bledow.
 * - Obsluga elementow nawigacyjnych panelu pracownika.
 *
 * @behavior
 * - Wejscie na widok: prezentacja listy klientow i opcji administracyjnych.
 * - Tryb edycji (parametr edit): wyswietlenie formularza zmian dla wskazanego klienta.
 * - Sukces/blad operacji: prezentacja komunikatow zwrotnych nad trescia.
 */

require_once '../../api/employee/customers.php';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klienci - Panel Administratora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="flex flex-col min-h-full text-gray-900">
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="employee_panel.php" class="flex-shrink-0 flex items-center">
                    <div class="bg-indigo-600 p-2 rounded-lg mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-gray-900">Sklep<span class="text-indigo-600">IntCom</span></span>
                </a>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600 hidden md:inline">Pracownik: <b class="text-indigo-600"><?= e($workerName) ?></b></span>
                    <a href="employee_panel.php" class="bg-indigo-50 text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all border border-indigo-100" aria-label="Panel administratora" title="Panel administratora"><i class="fa-solid fa-user-gear"></i></a>
                    <a href="../../api/auth/employee_logout.php" class="bg-indigo-50 text-indigo-600 p-2.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all border border-indigo-100" aria-label="Wyloguj panel" title="Wyloguj panel"><i class="fa-solid fa-right-from-bracket"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-[1440px] mx-auto w-full px-4 sm:px-6 lg:px-12 py-10">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Przeglad klientow</h1>
                <p class="text-gray-500 mt-2">Lista wszystkich klientow z mozliwoscia edytowania danych.</p>
            </div>
            <form action="customers.php" method="GET" class="w-full md:w-auto flex gap-3">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Szukaj po imieniu, nazwisku, email" class="w-full md:w-80 bg-white border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-all">Szukaj</button>
            </form>
        </div>

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

        <?php if ($editingClient): ?>
            <section class="mb-8 bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-indigo-600 text-white flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold">Edycja klienta #<?= (int)$editingClient['id'] ?></h2>
                    <a href="customers.php" class="text-sm font-semibold bg-white/20 px-3 py-1.5 rounded-lg hover:bg-white/30 transition-all">Zamknij</a>
                </div>
                <form action="customers.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <input type="hidden" name="action" value="update_client">
                    <input type="hidden" name="client_id" value="<?= (int)$editingClient['id'] ?>">

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="imie">Imie</label>
                        <input id="imie" name="imie" type="text" required value="<?= e($_POST['imie'] ?? (string)$editingClient['imie']) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="nazwisko">Nazwisko</label>
                        <input id="nazwisko" name="nazwisko" type="text" required value="<?= e($_POST['nazwisko'] ?? (string)$editingClient['nazwisko']) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="email">Email</label>
                        <input id="email" name="email" type="email" required value="<?= e($_POST['email'] ?? (string)$editingClient['email']) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="telefon">Telefon</label>
                        <input id="telefon" name="telefon" type="text" value="<?= e($_POST['telefon'] ?? (string)($editingClient['telefon'] ?? '')) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="miasto">Miasto</label>
                        <input id="miasto" name="miasto" type="text" value="<?= e($_POST['miasto'] ?? (string)($editingClient['miasto'] ?? '')) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="kod_pocztowy">Kod pocztowy</label>
                        <input id="kod_pocztowy" name="kod_pocztowy" type="text" value="<?= e($_POST['kod_pocztowy'] ?? (string)($editingClient['kod_pocztowy'] ?? '')) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="kraj">Kraj</label>
                        <input id="kraj" name="kraj" type="text" required value="<?= e($_POST['kraj'] ?? (string)$editingClient['kraj']) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="status">Status</label>
                        <?php $statusValue = $_POST['status'] ?? (string)$editingClient['status']; ?>
                        <select id="status" name="status" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="aktywny" <?= $statusValue === 'aktywny' ? 'selected' : '' ?>>aktywny</option>
                            <option value="zablokowany" <?= $statusValue === 'zablokowany' ? 'selected' : '' ?>>zablokowany</option>
                        </select>
                    </div>

                    <div class="md:col-span-2 flex gap-3">
                        <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-all">Zapisz zmiany</button>
                        <a href="customers.php" class="bg-gray-100 text-gray-700 px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-gray-200 transition-all">Anuluj</a>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <section class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Imie i nazwisko</th>
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">Telefon</th>
                            <th class="px-4 py-3 text-left">Miasto</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Rejestracja</th>
                            <th class="px-4 py-3 text-left">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (!$clients): ?>
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-gray-500">Brak klientow do wyswietlenia.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($clients as $client): ?>
                            <tr class="hover:bg-gray-50/70">
                                <td class="px-4 py-3 text-gray-700"><?= (int)$client['id'] ?></td>
                                <td class="px-4 py-3 font-medium text-gray-900"><?= e($client['imie'] . ' ' . $client['nazwisko']) ?></td>
                                <td class="px-4 py-3 text-gray-700"><?= e($client['email']) ?></td>
                                <td class="px-4 py-3 text-gray-700"><?= e((string)($client['telefon'] ?? '')) ?></td>
                                <td class="px-4 py-3 text-gray-700"><?= e((string)($client['miasto'] ?? '')) ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($client['status'] === 'aktywny'): ?>
                                        <span class="inline-flex rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-1 text-xs font-semibold">aktywny</span>
                                    <?php else: ?>
                                        <span class="inline-flex rounded-full bg-red-100 text-red-700 px-2.5 py-1 text-xs font-semibold">zablokowany</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-gray-700"><?= e((string)$client['data_rejestracji']) ?></td>
                                <td class="px-4 py-3">
                                    <a href="customers.php?edit=<?= (int)$client['id'] ?>" class="inline-flex items-center gap-2 rounded-lg bg-indigo-50 text-indigo-700 px-3 py-1.5 font-semibold hover:bg-indigo-100 transition-all">
                                        <i class="fa-solid fa-pen-to-square"></i> Edytuj
                                    </a>
                                </td>
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
</body>
</html>
