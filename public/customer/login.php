<?php
/**
 * @fileoverview login.php
 *
 * @description
 * Publiczny widok logowania klienta.
 * Plik renderuje formularz logowania oraz komunikaty statusu zwracane
 * po rejestracji lub nieudanym logowaniu, korzystajac z flag ustawionych
 * w warstwie API.
 *
 * @scope
 * - Dolaczenie logiki backendowej z api/customer/login.php.
 * - Renderowanie formularza logowania klienta.
 * - Wyswietlanie komunikatow: sukces rejestracji, blad rejestracji, bledne dane.
 * - Udostepnienie linkow pomocniczych (rejestracja, reset hasla, logowanie pracownika).
 *
 * @behavior
 * - Wejscie na strone: wyswietlenie formularza logowania.
 * - Ustawione flagi statusu: wyswietlenie odpowiednich komunikatow nad formularzem.
 * - Submit formularza: wyslanie danych do api/auth/login.php.
 */

require_once '../../api/customer/login.php';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - SklepIntCom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>
</head>
<body class="h-full">
    <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div class="inline-flex items-center justify-center p-3 bg-indigo-600 rounded-lg shadow-lg mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold tracking-tight text-gray-900">Zaloguj się do swojego konta</h2>
            <p class="mt-2 text-sm text-gray-600">
                Lub
                <a href="register.php" class="font-semibold text-indigo-600 hover:text-indigo-500">zarejestruj się za darmo</a>
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-xl sm:rounded-xl sm:px-10 border border-gray-100">
                <?php if ($showRegistered): ?>
                    <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 text-sm font-medium">
                        Konto zostało utworzone. Możesz się teraz zalogować.
                    </div>
                <?php endif; ?>
                <?php if ($showRegistrationFailed): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-medium">
                        Nie udało się utworzyć konta. Spróbuj ponownie.
                    </div>
                <?php endif; ?>
                <?php if ($showInvalidCredentials): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-medium animate-pulse">
                        Błędny e-mail lub hasło. Spróbuj ponownie.
                    </div>
                <?php endif; ?>
                <form class="space-y-6" action="../../api/auth/login.php" method="POST">
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700">Adres e-mail</label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" autocomplete="email" required 
                                class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700">Hasło</label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" autocomplete="current-password" required 
                                class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember-me" type="checkbox" 
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <label for="remember-me" class="ml-2 block text-sm text-gray-700 cursor-pointer">Zapamiętaj mnie</label>
                        </div>

                        <div class="text-sm">
                            <a href="forgot_password.php" class="font-semibold text-indigo-600 hover:text-indigo-500">Zapomniałeś hasła?</a>
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                            class="flex w-full justify-center rounded-lg border border-transparent bg-indigo-600 py-3 px-4 text-sm font-bold text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all active:scale-[0.98]">
                            Zaloguj się
                        </button>
                    </div>
                </form>

                <div class="mt-8">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="bg-white px-2 text-gray-400 uppercase tracking-wider font-medium">Inne opcje</span>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        <a href="../employee/login.php" 
                            class="inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-600 shadow-sm hover:bg-gray-50 hover:text-indigo-600 transition-all">
                            Logowanie - pracownik
                        </a>
                        <a href="../index.php" 
                            class="inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-600 shadow-sm hover:bg-gray-50 hover:text-indigo-600 transition-all">
                            Powrót do strony głównej
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
