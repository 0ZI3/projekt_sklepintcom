<?php
/**
 * @fileoverview products.php
 *
 * @description
 * Publiczny widok administracyjny do zarzadzania produktami sklepu.
 * Plik renderuje formularz dodawania/edycji produktu, listę produktów oraz akcje
 * operacyjne (zmiana statusu, usunięcie), korzystając z danych warstwy API.
 *
 * @scope
 * - Dolaczenie logiki backendowej z api/employee/products.php.
 * - Renderowanie formularza produktu (tworzenie i edycja).
 * - Obsługa uploadu zdjęć oraz parametrów asortymentu.
 * - Renderowanie tabeli produktów i akcji administracyjnych.
 * - Wyświetlanie komunikatów sukcesu i błędów walidacyjnych.
 *
 * @behavior
 * - Wejście na widok: prezentacja listy produktów i formularza operacyjnego.
 * - Tryb edycji (parametr edit): prewypelnienie formularza danymi produktu.
 * - Akcje POST: realizowane przez API, a wynik prezentowany komunikatami w widoku.
 */

require_once '../../api/employee/products.php';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produkty - Panel Administratora</title>
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
                <h1 class="text-3xl font-bold text-gray-900">Zarzadzanie produktami</h1>
                <p class="text-gray-500 mt-2">Dodawaj, edytuj i kontroluj produkty widoczne na stronie sklepu.</p>
            </div>
            <form action="products.php" method="GET" class="w-full md:w-auto flex gap-3">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Szukaj po nazwie lub SKU" class="w-full md:w-80 bg-white border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-all">Szukaj</button>
            </form>
        </div>

        <?php if (isset($_GET['created']) && $_GET['created'] === '1'): ?>
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
                Produkt zostal dodany.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
                Dane produktu zostaly zaktualizowane.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === '1'): ?>
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
                Status produktu zostal zmieniony.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted']) && $_GET['deleted'] === '1'): ?>
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
                Produkt zostal usuniety.
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php
            $isEditing = $editingProduct !== null;
            $formTitle = $isEditing ? ('Edycja produktu #' . (int)$editingProduct['id']) : 'Dodaj nowy produkt';
        ?>
        <section class="mb-8 bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-indigo-600 text-white flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold"><?= e($formTitle) ?></h2>
                <?php if ($isEditing): ?>
                    <a href="products.php" class="text-sm font-semibold bg-white/20 px-3 py-1.5 rounded-lg hover:bg-white/30 transition-all">Zamknij edycje</a>
                <?php endif; ?>
            </div>
            <form action="products.php" method="POST" enctype="multipart/form-data" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <input type="hidden" name="action" value="save_product">
                <input type="hidden" name="product_id" value="<?= $isEditing ? (int)$editingProduct['id'] : 0 ?>">

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="nazwa">Nazwa produktu</label>
                    <input id="nazwa" name="nazwa" type="text" required value="<?= e(productInput('nazwa', $editingProduct ?? [])) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="kategoria_id">Kategoria</label>
                    <?php $currentCategoryId = (int)productInput('kategoria_id', $editingProduct ?? []); ?>
                    <select id="kategoria_id" name="kategoria_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Wybierz kategorię</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int)$category['id'] ?>" <?= $currentCategoryId === (int)$category['id'] ? 'selected' : '' ?>>
                                <?= e((string)$category['nazwa']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="sku">SKU</label>
                    <input id="sku" name="sku" type="text" required value="<?= e(productInput('sku', $editingProduct ?? [])) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="cena">Cena (PLN)</label>
                    <input id="cena" name="cena" type="number" min="0" step="0.01" required value="<?= e(productInput('cena', $editingProduct ?? [])) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="stan_magazynowy">Stan magazynowy</label>
                    <input id="stan_magazynowy" name="stan_magazynowy" type="number" min="0" step="1" required value="<?= e(productInput('stan_magazynowy', $editingProduct ?? [])) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="promocja_proc">Promocja (%)</label>
                    <input id="promocja_proc" name="promocja_proc" type="number" min="0" max="100" step="0.01" required value="<?= e(productInput('promocja_proc', $editingProduct ?? [])) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="aktywny">Dostepnosc na stronie</label>
                    <?php $aktywnyValue = (int)productInput('aktywny', $editingProduct ?? []); ?>
                    <select id="aktywny" name="aktywny" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="1" <?= $aktywnyValue === 1 ? 'selected' : '' ?>>aktywny</option>
                        <option value="0" <?= $aktywnyValue === 0 ? 'selected' : '' ?>>nieaktywny</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="opis">Opis</label>
                    <textarea id="opis" name="opis" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= e(productInput('opis', $editingProduct ?? [])) ?></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1" for="zdjecia">Zdjecia produktu</label>
                    <input id="zdjecia" name="zdjecia[]" type="file" accept=".webp,.jpg,.jpeg,.png,image/webp,image/jpeg,image/png" multiple class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    <p class="text-xs text-gray-500 mt-2">Pliki zostaną automatycznie skonwertowane do WebP i nazwane w formacie: idProduktu-numerZdjecia.webp, np. 55-1.webp.</p>
                </div>

                <div class="md:col-span-2 flex gap-3">
                    <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-all">
                        <?= $isEditing ? 'Zapisz zmiany' : 'Dodaj produkt' ?>
                    </button>
                    <?php if ($isEditing): ?>
                        <a href="products.php" class="bg-gray-100 text-gray-700 px-5 py-2.5 rounded-lg text-sm font-bold hover:bg-gray-200 transition-all">Anuluj</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Nazwa</th>
                            <th class="px-4 py-3 text-left">SKU</th>
                            <th class="px-4 py-3 text-left">Kategoria</th>
                            <th class="px-4 py-3 text-left">Cena</th>
                            <th class="px-4 py-3 text-left">Magazyn</th>
                            <th class="px-4 py-3 text-left">Promocja</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (!$products): ?>
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-gray-500">Brak produktów do wyświetlenia.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($products as $product): ?>
                            <tr class="hover:bg-gray-50/70">
                                <td class="px-4 py-3 text-gray-700"><?= (int)$product['id'] ?></td>
                                <td class="px-4 py-3 font-medium text-gray-900"><?= e((string)$product['nazwa']) ?></td>
                                <td class="px-4 py-3 text-gray-700"><?= e((string)$product['sku']) ?></td>
                                <td class="px-4 py-3 text-gray-700"><?= e((string)($product['kategoria_nazwa'] ?? '-')) ?></td>
                                <td class="px-4 py-3 text-gray-700"><?= number_format((float)$product['cena'], 2, ',', ' ') ?> PLN</td>
                                <td class="px-4 py-3 text-gray-700"><?= (int)$product['stan_magazynowy'] ?></td>
                                <td class="px-4 py-3 text-gray-700"><?= number_format((float)$product['promocja_proc'], 2, ',', ' ') ?>%</td>
                                <td class="px-4 py-3">
                                    <?php if ((int)$product['aktywny'] === 1): ?>
                                        <span class="inline-flex rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-1 text-xs font-semibold">aktywny</span>
                                    <?php else: ?>
                                        <span class="inline-flex rounded-full bg-red-100 text-red-700 px-2.5 py-1 text-xs font-semibold">nieaktywny</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="products.php?edit=<?= (int)$product['id'] ?>" class="inline-flex items-center gap-2 rounded-lg bg-indigo-50 text-indigo-700 px-3 py-1.5 font-semibold hover:bg-indigo-100 transition-all">
                                            <i class="fa-solid fa-pen-to-square"></i> Edytuj
                                        </a>
                                        <form action="products.php" method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= (int)$product['aktywny'] === 1 ? 0 : 1 ?>">
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 font-semibold transition-all <?= (int)$product['aktywny'] === 1 ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' ?>">
                                                <?php if ((int)$product['aktywny'] === 1): ?>
                                                    <i class="fa-solid fa-eye-slash"></i> Ukryj
                                                <?php else: ?>
                                                    <i class="fa-solid fa-eye"></i> Pokaz
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                        <form action="products.php" method="POST" class="inline" onsubmit="return confirm('Czy na pewno usunac ten produkt?');">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-50 text-red-700 px-3 py-1.5 font-semibold hover:bg-red-100 transition-all">
                                                <i class="fa-solid fa-trash"></i> Usun
                                            </button>
                                        </form>
                                    </div>
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
                    <p class="text-gray-500 text-sm leading-relaxed">Twój partner w świecie technologii. Dostarczamy najwyższej jakości sprzęt IT dla profesjonalistów i hobbystów.</p>
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
                        <li><a href="../privacy.php" class="hover:text-indigo-600 transition-colors">Polityka prywatności</a></li>
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
                <p class="text-xs text-gray-400">&copy; 2026 SklepIntCom. Wszelkie prawa zastrzeżone.</p>
            </div>
        </div>
    </footer>
</body>
</html>
