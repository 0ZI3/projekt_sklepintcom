<!--
    @fileoverview login.php

    @description
    Publiczny widok logowania pracownika do panelu administracyjnego.
    Plik renderuje formularz logowania, obsluguje komunikat o blednych danych
    oraz udostepnia nawigacje do pozostalych sciezek logowania.

    @scope
    - Renderowanie formularza logowania pracownika.
    - Wyswietlanie komunikatu bledu invalid_credentials na podstawie parametru GET.
    - Integracja z endpointem api/auth/employee_login.php.
    - Udostepnienie opcji "zapamietaj mnie" dla sesji pracownika.
    - Prezentacja linkow nawigacyjnych do logowania klienta i strony glownej.

    @behavior
    - Wejscie na strone: wyswietlenie formularza logowania panelu.
    - Parametr error=invalid_credentials: wyswietlenie komunikatu o blednych danych.
    - Submit formularza: przekazanie danych do endpointu logowania pracownika.
-->
    
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Pracownika - Logowanie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>
</head>
<body class="h-full">
    <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div class="inline-flex items-center justify-center p-3 bg-indigo-600 rounded-lg shadow-lg mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold tracking-tight text-gray-900">Zaloguj się do panelu</h2>
            <p class="mt-2 text-sm text-gray-600">Zaloguj się, aby zarządzać systemem</p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-xl sm:rounded-xl sm:px-10 border border-gray-100">
                <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_credentials'): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-medium">
                        Błędny e-mail lub hasło.
                    </div>
                <?php endif; ?>
                <form class="space-y-6" action="../../api/auth/employee_login.php" method="POST">
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700">Adres e-mail służbowy</label>
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
                        <a href="../customer/login.php" 
                            class="inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-600 shadow-sm hover:bg-gray-50 hover:text-indigo-600 transition-all">
                            Logowanie - klient
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
