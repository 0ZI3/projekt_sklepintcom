<?php
/**
 * @fileoverview payment.php
 *
 * @description
 * Publiczny widok finalizacji zamowienia i platnosci.
 * Plik renderuje podsumowanie koszyka, dane wysylki klienta, wybor dostawy
 * i metody platnosci oraz formularz checkout oparty o dane przygotowane przez API.
 *
 * @scope
 * - Dolaczenie logiki backendowej z api/store/payment.php.
 * - Renderowanie danych wysylki i podsumowania kosztow zamowienia.
 * - Renderowanie opcji dostawy i platnosci wraz z walidacja formularza.
 * - Wyswietlanie bledow backendowych i frontendowych checkoutu.
 * - Integracja skryptu walidacji platnosci po stronie klienta.
 *
 * @behavior
 * - Wejscie na strone: prezentacja aktualnego stanu checkoutu.
 * - Zmiana opcji dostawy/platnosci: dynamiczna aktualizacja podsumowania.
 * - Submit formularza: przekazanie danych do finalizacji zamowienia.
 */

require_once '../api/store/payment.php';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SklepIntCom - Płatność</title>
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
                <div class="flex items-center gap-3">
                    <a href="cart.php"
                        class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-indigo-600 transition-colors px-3 py-2 rounded-lg hover:bg-indigo-50">
                        <i class="fa-solid fa-cart-shopping text-xs"></i>
                        Koszyk
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-6xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-end justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Płatność i potwierdzenie</h1>
                <p class="text-sm text-gray-500 mt-1">Finalizujesz zamówienie: <span
                        class="font-semibold text-indigo-600"><?php echo $itemCount; ?> szt.</span></p>
            </div>
            <a href="cart.php"
                class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                <i class="fa-solid fa-arrow-left"></i>
                Wróć do koszyka
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div id="client-validation-error"
            class="hidden mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"></div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Dane wysyłki</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
                    <div>
                        <p class="text-gray-500">Imię i nazwisko</p>
                        <p class="font-semibold">
                            <?php echo htmlspecialchars($customer['imie'] . ' ' . $customer['nazwisko']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">E-mail</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($customer['email']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Telefon</p>
                        <p id="ship-phone" class="font-semibold"><?php echo htmlspecialchars($customer['telefon']); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500">Ulica</p>
                        <p id="ship-street" class="font-semibold"><?php echo htmlspecialchars($customer['ulica']); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500">Numer domu</p>
                        <p id="ship-house" class="font-semibold"><?php echo htmlspecialchars($customer['dom']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Numer lokalu</p>
                        <p id="ship-flat" class="font-semibold"><?php echo htmlspecialchars($customer['numer']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Miasto</p>
                        <p id="ship-city" class="font-semibold"><?php echo htmlspecialchars($customer['miasto']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Kod pocztowy</p>
                        <p id="ship-postcode" class="font-semibold">
                            <?php echo htmlspecialchars($customer['kod_pocztowy']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Kraj</p>
                        <p id="ship-country" class="font-semibold"><?php echo htmlspecialchars($customer['kraj']); ?>
                        </p>
                    </div>
                </div>

                <a href="customer/fill_details.php?redirect=../../public/payment.php"
                    class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                    <i class="fa-solid fa-pen"></i>
                    Edytuj dane wysyłki
                </a>

                <h2 class="text-lg font-bold text-gray-900 mt-8 mb-4">Wybierz kuriera</h2>
                <form id="payment-form" method="POST" class="space-y-3">
                    <label
                        class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 p-3 hover:border-indigo-300 hover:bg-indigo-50/40 transition-colors cursor-pointer">
                        <div class="flex items-center gap-3 min-w-0">
                            <input type="radio" name="delivery_method" value="pickup" <?php echo $selectedDelivery == 'pickup' ? 'checked' : ''; ?>
                                class="text-indigo-600 focus:ring-indigo-500" data-cost="0">
                            <span class="font-medium text-gray-800 truncate">Odbiór w sklepie</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 whitespace-nowrap" data-display-cost>0,00
                            zł</span>
                    </label>
                    <?php foreach ($deliveryMethods as $key => $delivery): ?>
                        <label
                            class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 p-3 hover:border-indigo-300 hover:bg-indigo-50/40 transition-colors cursor-pointer">
                            <div class="flex items-center gap-3 min-w-0">
                                <input type="radio" name="delivery_method" value="<?php echo htmlspecialchars($key); ?>"
                                    <?php echo $selectedDelivery == $key ? 'checked' : ''; ?>
                                    class="text-indigo-600 focus:ring-indigo-500"
                                    data-cost="<?php echo htmlspecialchars((string) $delivery['cost']); ?>">
                                <span
                                    class="font-medium text-gray-800 truncate"><?php echo htmlspecialchars($delivery['label']); ?></span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 whitespace-nowrap"
                                data-display-cost><?php echo number_format((float) $delivery['cost'], 2, ',', ' '); ?>
                                zł</span>
                        </label>
                    <?php endforeach; ?>

                    <h2 class="text-lg font-bold text-gray-900 mt-8 mb-4">Metoda płatności</h2>
                    <?php foreach ($paymentMethods as $key => $label): ?>
                        <label
                            class="flex items-center gap-3 rounded-xl border border-gray-200 p-3 hover:border-indigo-300 hover:bg-indigo-50/40 transition-colors cursor-pointer">
                            <input type="radio" name="payment_method" value="<?php echo htmlspecialchars($key); ?>" <?php echo $selectedPayment === $key ? 'checked' : ''; ?>
                                class="text-indigo-600 focus:ring-indigo-500">
                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($label); ?></span>
                        </label>
                    <?php endforeach; ?>

                    <div class="mt-6">
                        <label for="order-note" class="block text-sm font-semibold text-gray-900 mb-2">
                            Uwagi do zamówienia (opcjonalnie)
                        </label>
                        <textarea id="order-note" name="order_note" rows="4" maxlength="225"
                            class="w-full rounded-xl border border-gray-200 p-3 text-sm text-gray-700 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Np. zostaw paczkę u sąsiada, prośba o fakturę">
<?php echo htmlspecialchars($orderNoteValue ?? ''); ?></textarea>
                        <p class="mt-1 text-xs text-gray-500">Maksymalnie 225 znaków.</p>
                    </div>

                    <button type="submit"
                        class="w-full mt-4 bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition-colors">
                        Zapłać i złóż zamówienie
                    </button>
                </form>
            </section>

            <aside class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 h-fit">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Twoje produkty</h2>
                <div class="space-y-3 mb-6">
                    <?php foreach ($summary['items'] as $item): ?>
                        <div class="flex justify-between text-sm text-gray-600 gap-4">
                            <span class="truncate"><?php echo htmlspecialchars($item['name']); ?> x
                                <?php echo (int) $item['qty']; ?></span>
                            <span
                                class="font-semibold text-gray-900 whitespace-nowrap"><?php echo number_format((float) $item['line_total'], 2, ',', ' '); ?>
                                zł</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="space-y-2 text-sm text-gray-600 mb-6" id="order-summary"
                    data-products-total="<?php echo htmlspecialchars((string) $productsTotalValue); ?>">
                    <div class="flex justify-between">
                        <span>Produkty</span>
                        <span class="font-semibold text-gray-900"
                            id="products-total"><?php echo number_format((float) $productsTotalValue, 2, ',', ' '); ?>
                            zł</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Dostawa</span>
                        <span class="font-semibold text-gray-900"
                            id="delivery-cost"><?php echo number_format((float) $deliveryCost, 2, ',', ' '); ?> zł</span>
                    </div>
                    <div class="pt-3 border-t border-gray-100 flex justify-between text-base">
                        <span class="font-bold text-gray-900">Razem</span>
                        <span class="font-black text-indigo-600"
                            id="total-value"><?php echo number_format($totalValue, 2, ',', ' '); ?> zł</span>
                    </div>
                </div>
            </aside>
        </div>
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
</body>
<script src="../api/auth/payment_validation.js"></script>
</html>
