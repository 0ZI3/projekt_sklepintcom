<?php
/**
 * @fileoverview fill_details.php
 *
 * @description
 * Plik odpowiada za renderowanie formularza uzupelnienia danych adresowych klienta
 * przed finalizacja zamowienia. Odczytuje kody bledow i parametry redirect,
 * wyswietla odpowiednie komunikaty oraz przekazuje dane do endpointu aktualizacji.
 *
 * @scope
 * - Odczyt parametrow GET (error, redirect) i ich podstawowa walidacja.
 * - Renderowanie formularza danych kontaktowo-adresowych.
 * - Wyswietlanie komunikatow bledow walidacyjnych.
 * - Integracja z endpointem api/user/update_details.php.
 * - Dolaczenie skryptu auto-formatowania pol adresowych.
 *
 * @behavior
 * - Parametr error: wyswietlenie dedykowanego komunikatu dla uzytkownika.
 * - Parametr redirect z adresem zewnetrznym: fallback do bezpiecznej sciezki lokalnej.
 * - Submit formularza: przekazanie danych do aktualizacji i kontynuacja checkoutu.
 */

$errorCode = $_GET['error'] ?? '';
$redirect = $_GET['redirect'] ?? '../../public/index.php';

if (strpos($redirect, 'http://') === 0 || strpos($redirect, 'https://') === 0) {
    $redirect = '../index.php';
}
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uzupełnij dane - SklepIntCom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>
</head>
<body class="h-full">
    <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div class="inline-flex items-center justify-center p-3 bg-indigo-600 rounded-lg shadow-lg mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold tracking-tight text-gray-900">Uzupełnij swoje dane</h2>
            <p class="mt-2 text-sm text-gray-600">
                To Twoje pierwsze zamówienie. Potrzebujemy tych informacji, aby dostarczyć Twoją przesyłkę.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-xl sm:rounded-xl sm:px-10 border border-gray-100">
                <?php if ($errorCode === 'missing_fields'): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-medium">
                        Uzupełnij wszystkie pola formularza.
                    </div>
                <?php elseif ($errorCode === 'invalid_phone'): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-medium">
                        Podaj poprawny numer telefonu.
                    </div>
                <?php elseif ($errorCode === 'invalid_postcode'): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-medium">
                        Podaj poprawny kod pocztowy (np. 00-000).
                    </div>
                <?php elseif ($errorCode === 'invalid_address'): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-medium">
                        Podaj poprawne dane adresowe.
                    </div>
                <?php elseif ($errorCode === 'save_failed'): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-medium">
                        Nie udało się zapisać danych. Spróbuj ponownie.
                    </div>
                <?php endif; ?>

                <form class="space-y-6" action="../../api/user/update_details.php" method="POST">
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                    <div>
                        <label for="telefon" class="block text-sm font-semibold text-gray-700">Numer telefonu</label>
                        <div class="mt-1">
                            <input id="telefon" name="telefon" type="tel" required placeholder="+48 000 000 000"
                                pattern="^\+?[0-9\s\-()]{7,20}$" title="Uzyj formatu np. +48 500 100 100"
                                class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                        </div>
                    </div>

                    <div>
                        <label for="ulica" class="block text-sm font-semibold text-gray-700">Ulica</label>
                        <div class="mt-1">
                            <input id="ulica" name="ulica" type="text" required placeholder="np. Marszalkowska"
                                data-capitalize-first="true"
                                pattern="^[^<>]{2,150}$" title="Podaj poprawna nazwe ulicy"
                                class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="dom" class="block text-sm font-semibold text-gray-700">Numer domu</label>
                            <div class="mt-1">
                                <input id="dom" name="dom" type="text" required placeholder="np. 12A"
                                    data-house-uppercase-last="true"
                                    pattern="^[0-9A-Za-z\-\/]{1,20}$" title="Podaj poprawny numer domu"
                                    class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                            </div>
                        </div>

                        <div>
                            <label for="numer" class="block text-sm font-semibold text-gray-700">Numer lokalu</label>
                            <div class="mt-1">
                                <input id="numer" name="numer" type="text" placeholder="np. 8 (opcjonalnie)"
                                    pattern="^[0-9A-Za-z\-\/]{1,20}$" title="Podaj poprawny numer lokalu"
                                    class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="miasto" class="block text-sm font-semibold text-gray-700">Miasto</label>
                            <div class="mt-1">
                                <input id="miasto" name="miasto" type="text" required 
                                    data-capitalize-first="true"
                                    pattern="^[^<>]{2,120}$" title="Podaj poprawna nazwe miasta"
                                    class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                            </div>
                        </div>

                        <div>
                            <label for="kod_pocztowy" class="block text-sm font-semibold text-gray-700">Kod pocztowy</label>
                            <div class="mt-1">
                                <input id="kod_pocztowy" name="kod_pocztowy" type="text" required placeholder="00-000"
                                    pattern="^[0-9]{2}-[0-9]{3}$" title="Uzyj formatu 00-000"
                                    class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="kraj" class="block text-sm font-semibold text-gray-700">Kraj</label>
                        <div class="mt-1">
                            <input id="kraj" name="kraj" type="text" required value="Polska"
                                data-capitalize-first="true"
                                pattern="^[^<>]{2,120}$" title="Podaj poprawna nazwe kraju"
                                class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input id="save_default" name="save_default" type="checkbox" checked
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        <label for="save_default" class="ml-2 block text-sm text-gray-700 cursor-pointer">
                            Zapisz jako domyślny adres dostawy
                        </label>
                    </div>

                    <div>
                        <button type="submit" 
                            class="flex w-full justify-center rounded-lg border border-transparent bg-indigo-600 py-3 px-4 text-sm font-bold text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all active:scale-[0.98]">
                            Kontynuuj do zamówienia
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <a href="javascript:history.back()" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                        Wróć do koszyka
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
<script src="../../api/auth/address_autocapitalize.js"></script>
</html>
