<?php
/**
 * @fileoverview payment_success.php
 *
 * @description
 * Publiczny widok potwierdzenia przyjecia zamowienia po poprawnej platnosci.
 * Plik renderuje ekran sukcesu z podstawowymi informacjami o zamowieniu
 * oraz linkami nawigacyjnymi do dalszych akcji klienta.
 *
 * @scope
 * - Dolaczenie logiki backendowej z api/store/payment_success.php.
 * - Renderowanie komunikatu sukcesu po finalizacji checkoutu.
 * - Prezentacja numeru zamowienia, jesli jest dostepny.
 * - Udostepnienie nawigacji do produktow i panelu klienta.
 *
 * @behavior
 * - Wejscie na strone: wyswietlenie potwierdzenia przyjecia zamowienia.
 * - Dostepny orderId: pokazanie numeru zamowienia klientowi.
 * - Brak orderId: wyswietlenie ogolnego komunikatu sukcesu bez numeru.
 */

require_once '../api/store/payment_success.php';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SklepIntCom - Zamówienie przyjęte</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="min-h-full flex items-center justify-center px-4 py-10">
    <div class="max-w-xl w-full bg-white border border-gray-100 rounded-2xl shadow-sm p-8 text-center">
        <div
            class="mx-auto w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4">
            <i class="fa-solid fa-check text-2xl"></i>
        </div>

        <?php if (!empty($stripeError) && $orderId <= 0): ?>
            <h1 class="text-2xl font-bold text-gray-900">Nie udało się potwierdzić płatności</h1>
            <p class="text-gray-600 mt-2">Spróbuj odświeżyć stronę lub skontaktuj się z obsługą.</p>
        <?php else: ?>
            <h1 class="text-2xl font-bold text-gray-900">Dziękujemy za zakup</h1>
            <p class="text-gray-600 mt-2">Twoje zamówienie zostało przyjęte i przekazane do realizacji.</p>
        <?php endif; ?>

        <?php if (!empty($stripeError)): ?>
            <p class="mt-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-2">
                <?php echo htmlspecialchars($stripeError); ?>
            </p>
        <?php endif; ?>

        <?php if ($orderId > 0): ?>
            <p
                class="mt-4 inline-flex items-center gap-2 bg-indigo-50 text-indigo-700 px-4 py-2 rounded-lg text-sm font-semibold">
                Numer zamówienia: #<?php echo $orderId; ?>
            </p>
        <?php endif; ?>

        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="products.php"
                class="inline-flex justify-center items-center bg-indigo-600 text-white px-5 py-3 rounded-xl font-bold hover:bg-indigo-700 transition-colors">
                Wróć do zakupów
            </a>
            <a href="customer/customer_panel.php"
                class="inline-flex justify-center items-center bg-white text-gray-700 border border-gray-300 px-5 py-3 rounded-xl font-bold hover:bg-gray-50 transition-colors">
                Panel klienta
            </a>
        </div>
    </div>
</body>
</html>
