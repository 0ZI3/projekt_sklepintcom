<?php
/**
 * @fileoverview orders.php
 *
 * @description
 * Publiczny widok administracyjny do obsługi zamówień.
 * Plik renderuje listę zamówień oraz formularz zarządzania szczegółami wybranego
 * zamówienia (statusy, przypisania, dostawa, notatki) na podstawie danych z API.
 *
 * @scope
 * - Dolaczenie logiki backendowej z api/employee/orders.php.
 * - Renderowanie tabeli zamowien i opcji wyszukiwania.
 * - Renderowanie formularza edycji zamowienia w trybie edit.
 * - Prezentacja pozycji zamowienia, statusow platnosci i dostawy.
 * - Wyswietlanie komunikatow sukcesu i bledow walidacyjnych.
 *
 * @behavior
 * - Wejscie na widok: prezentacja listy zamowien i akcji administracyjnych.
 * - Tryb edycji (parametr edit): wyswietlenie formularza zarzadzania zamowieniem.
 * - Aktualizacja danych: obsluga komunikatow statusowych po zapisaniu zmian.
 */

require_once '../../api/employee/orders.php';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zamówienia - Panel Administratora</title>
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
                <h1 class="text-3xl font-bold text-gray-900">Zarzadzanie zamówieniami</h1>
                <p class="text-gray-500 mt-2">Przeglądaj i aktualizuj statusy zamówień, płatności oraz dostaw.</p>
            </div>
            <form action="orders.php" method="GET" class="w-full md:w-auto flex gap-3">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Szukaj po ID, kliencie lub email" class="w-full md:w-80 bg-white border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-all">Szukaj</button>
            </form>
        </div>

        <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
                Zamówienie zostało zaktualizowane.
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($editingOrder): ?>
            <section class="mb-8 bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-indigo-600 text-white flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold">Edycja zamówienia #<?= (int)$editingOrder['id'] ?></h2>
                    <a href="orders.php" class="text-sm font-semibold bg-white/20 px-3 py-1.5 rounded-lg hover:bg-white/30 transition-all">Zamknij</a>
                </div>
                <form action="orders.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <input type="hidden" name="action" value="update_order">
                    <input type="hidden" name="order_id" value="<?= (int)$editingOrder['id'] ?>">

                    <div class="md:col-span-2 rounded-xl border border-indigo-100 bg-indigo-50/50 px-4 py-3 text-sm text-indigo-900">
                        <p><b>Klient:</b> <?= e((string)$editingOrder['imie'] . ' ' . (string)$editingOrder['nazwisko']) ?> (<?= e((string)$editingOrder['email']) ?>)</p>
                        <p><b>Data:</b> <?= e((string)$editingOrder['data_zamowienia']) ?> | <b>Kwota:</b> <?= number_format((float)$editingOrder['kwota_brutto'], 2, ',', ' ') ?> PLN</p>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-sm font-semibold text-gray-800 mb-2">Co zostalo zamowione</p>
                        <?php if ($editingOrderItems): ?>
                            <ul class="space-y-2 text-sm text-gray-700">
                                <?php foreach ($editingOrderItems as $item): ?>
                                    <?php
                                        $qty = (int)$item['ilosc'];
                                        $unitPrice = (float)$item['cena_jednostkowa'];
                                        $discount = (float)$item['rabat_proc'];
                                        $lineTotal = $qty * $unitPrice * (1 - ($discount / 100));
                                    ?>
                                    <li class="flex flex-col md:flex-row md:items-center md:justify-between gap-1 border-b border-gray-200 last:border-b-0 pb-2 last:pb-0">
                                        <span>
                                            <b><?= e((string)$item['produkt_nazwa']) ?></b>
                                            <?php if (!empty($item['sku'])): ?>
                                                <span class="text-xs text-gray-500">(SKU: <?= e((string)$item['sku']) ?>)</span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="text-gray-600">
                                            <?= $qty ?> x <?= number_format($unitPrice, 2, ',', ' ') ?> PLN
                                            <?php if ($discount > 0): ?>
                                                <span class="text-xs">(rabat <?= number_format($discount, 2, ',', ' ') ?>%)</span>
                                            <?php endif; ?>
                                            = <b><?= number_format($lineTotal, 2, ',', ' ') ?> PLN</b>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">Brak pozycji dla tego zamówienia.</p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="status_id">Status zamówienia</label>
                        <?php $currentOrderStatus = (int)($_POST['status_id'] ?? $editingOrder['status_id']); ?>
                        <select id="status_id" name="status_id" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <?php foreach ($orderStatuses as $statusId => $statusName): ?>
                                <option value="<?= $statusId ?>" <?= $currentOrderStatus === $statusId ? 'selected' : '' ?>><?= e($statusName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="assigned_worker_id">Przypisany pracownik</label>
                        <?php $currentAssignedWorker = (int)($_POST['assigned_worker_id'] ?? ($editingOrder['pracownik_id'] ?? 0)); ?>
                        <select id="assigned_worker_id" name="assigned_worker_id" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="0" <?= $currentAssignedWorker === 0 ? 'selected' : '' ?>>Brak przypisania</option>
                            <?php foreach ($workersById as $id => $worker): ?>
                                <option value="<?= $id ?>" <?= $currentAssignedWorker === $id ? 'selected' : '' ?>>
                                    <?= e((string)$worker['imie'] . ' ' . (string)$worker['nazwisko'] . ' (' . (string)$worker['rola'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="payment_status_id">Status platnosci</label>
                        <?php $currentPaymentStatus = (int)($_POST['payment_status_id'] ?? $editingOrder['payment_status_id']); ?>
                        <select id="payment_status_id" name="payment_status_id" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <?php foreach ($paymentStatuses as $statusId => $statusName): ?>
                                <option value="<?= $statusId ?>" <?= $currentPaymentStatus === $statusId ? 'selected' : '' ?>><?= e($statusName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="delivery_status_id">Status dostawy</label>
                        <?php $currentDeliveryStatus = (int)($_POST['delivery_status_id'] ?? $editingOrder['delivery_status_id']); ?>
                        <select id="delivery_status_id" name="delivery_status_id" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <?php foreach ($deliveryStatuses as $statusId => $statusName): ?>
                                <option value="<?= $statusId ?>" <?= $currentDeliveryStatus === $statusId ? 'selected' : '' ?>><?= e($statusName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="kurier_id">Kurier</label>
                        <?php $currentCourierId = (int)($_POST['kurier_id'] ?? ($editingOrder['kurier_id'] ?? 0)); ?>
                        <select id="kurier_id" name="kurier_id" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="0" <?= $currentCourierId === 0 ? 'selected' : '' ?>>Wybierz kuriera</option>
                            <?php foreach ($couriersById as $courier): ?>
                                <option value="<?= (int)$courier['id'] ?>" <?= $currentCourierId === (int)$courier['id'] ? 'selected' : '' ?>>
                                    <?= e((string)$courier['nazwa']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="numer_przesylki">Numer przesylki</label>
                        <input id="numer_przesylki" name="numer_przesylki" type="text" value="<?= e((string)($_POST['numer_przesylki'] ?? ($editingOrder['numer_przesylki'] ?? ''))) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="uwagi">Uwagi</label>
                        <textarea id="uwagi" name="uwagi" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= e((string)($_POST['uwagi'] ?? ($editingOrder['uwagi'] ?? ''))) ?></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1" for="notatki_wewnetrzne">Notatki wewnetrzne</label>
                        <textarea id="notatki_wewnetrzne" name="notatki_wewnetrzne" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= e((string)($_POST['notatki_wewnetrzne'] ?? ($editingOrder['notatki_wewnetrzne'] ?? ''))) ?></textarea>
                        <p class="mt-1 text-xs text-gray-500">Widoczne tylko dla pracowników panelu.</p>
                    </div>

                    <div class="md:col-span-2 flex gap-3">
                        <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-all">Zapisz zmiany</button>
                        <a href="orders.php" class="bg-gray-100 text-gray-700 px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-gray-200 transition-all">Anuluj</a>
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
                            <th class="px-4 py-3 text-left">Klient</th>
                            <th class="px-4 py-3 text-left">Data</th>
                            <th class="px-4 py-3 text-left">Kwota</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Platnosc</th>
                            <th class="px-4 py-3 text-left">Dostawa</th>
                            <th class="px-4 py-3 text-left">Pracownik</th>
                            <th class="px-4 py-3 text-left">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (!$orders): ?>
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-gray-500">Brak zamówień do wyświetlenia.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50/70">
                                <td class="px-4 py-3 text-gray-700">#<?= (int)$order['id'] ?></td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900"><?= e((string)$order['imie'] . ' ' . (string)$order['nazwisko']) ?></p>
                                    <p class="text-xs text-gray-500"><?= e((string)$order['email']) ?></p>
                                </td>
                                <td class="px-4 py-3 text-gray-700"><?= e((string)$order['data_zamowienia']) ?></td>
                                <td class="px-4 py-3 text-gray-700"><?= number_format((float)$order['kwota_brutto'], 2, ',', ' ') ?> PLN</td>
                                <td class="px-4 py-3 text-gray-700"><?= e((string)($order['status_nazwa'] ?? '-')) ?></td>
                                <td class="px-4 py-3 text-gray-700"><?= e((string)($order['payment_status_nazwa'] ?? '-')) ?></td>
                                <td class="px-4 py-3 text-gray-700"><?= e((string)($order['delivery_status_nazwa'] ?? '-')) ?></td>
                                <td class="px-4 py-3 text-gray-700">
                                    <?php if (!empty($order['worker_imie'])): ?>
                                        <?= e((string)$order['worker_imie'] . ' ' . (string)$order['worker_nazwisko']) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="orders.php?edit=<?= (int)$order['id'] ?>" class="inline-flex items-center gap-2 rounded-lg bg-indigo-50 text-indigo-700 px-3 py-1.5 font-semibold hover:bg-indigo-100 transition-all">
                                        <i class="fa-solid fa-pen-to-square"></i> Zarzadzaj
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
