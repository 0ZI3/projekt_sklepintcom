<!--
    @fileoverview register.php

    @description
    Publiczny widok rejestracji klienta sklepu.
    Plik renderuje formularz tworzenia konta i przekazuje dane do endpointu
    API odpowiedzialnego za rejestracje, wraz z walidacja po stronie klienta.

    @scope
    - Renderowanie formularza rejestracji (dane osobowe, email, haslo).
    - Udostepnienie akceptacji regulaminu i polityki prywatnosci.
    - Integracja z endpointem api/auth/register.php.
    - Dolaczenie skryptu walidacji hasla i potwierdzenia hasla.
    - Prezentacja linkow nawigacyjnych do logowania i strony glownej.

    @behavior
    - Wejscie na strone: wyswietlenie formularza tworzenia konta.
    - Submit formularza: wyslanie danych rejestracyjnych do API.
    - Walidacja klientowa: dynamiczne komunikaty o poprawnosci hasla.
-->
    
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja - SklepIntCom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>
</head>
<body class="h-full">
    <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div class="inline-flex items-center justify-center p-3 bg-indigo-600 rounded-lg shadow-lg mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold tracking-tight text-gray-900">Utwórz nowe konto</h2>
            <p class="mt-2 text-sm text-gray-600">
                Masz już konto?
                <a href="login.php" class="font-semibold text-indigo-600 hover:text-indigo-500">zaloguj się tutaj</a>
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-xl sm:rounded-xl sm:px-10 border border-gray-100">
                <form class="space-y-6" action="../../api/auth/register.php" method="POST">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="imie" class="block text-sm font-semibold text-gray-700">Imię</label>
                            <div class="mt-1">
                                <input id="imie" name="imie" type="text" required 
                                    class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                            </div>
                        </div>

                        <div>
                            <label for="nazwisko" class="block text-sm font-semibold text-gray-700">Nazwisko</label>
                            <div class="mt-1">
                                <input id="nazwisko" name="nazwisko" type="text" required 
                                    class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700">Adres e-mail</label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" autocomplete="email" required 
                                class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700">Hasło</label>
                            <div class="mt-1">
                                <input id="password" name="password" type="password" required 
                                    class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                            </div>
                            <p id="password-hint" class="mt-2 text-xs text-gray-500">Hasło musi mieć minimum 8 znaków i przynajmniej 1 znak specjalny.</p>
                        </div>

                        <div>
                            <label for="password_confirm" class="block text-sm font-semibold text-gray-700">Potwierdź hasło</label>
                            <div class="mt-1">
                                <input id="password_confirm" name="password_confirm" type="password" required 
                                    class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm transition-all">
                            </div>
                            <p id="password-match" class="mt-2 text-xs text-gray-500">Wpisz to samo hasło ponownie.</p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input id="terms" name="terms" type="checkbox" required 
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        <label for="terms" class="ml-2 block text-sm text-gray-700 cursor-pointer">
                            Akceptuję <a href="../terms.php" target="_blank" class="font-medium text-indigo-600 hover:text-indigo-500">regulamin</a> i <a href="../privacy.php" target="_blank" class="font-medium text-indigo-600 hover:text-indigo-500">politykę prywatności</a>
                        </label>
                    </div>

                    <div>
                        <button type="submit" 
                            class="flex w-full justify-center rounded-lg border border-transparent bg-indigo-600 py-3 px-4 text-sm font-bold text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all active:scale-[0.98]">
                            Zarejestruj się
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

                    <div class="mt-6 text-center space-y-3">
                        <a href="../employee/login.php" 
                            class="inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-600 shadow-sm hover:bg-gray-50 hover:text-indigo-600 transition-all">
                            Logowanie panel pracownika
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

    <script src="../../api/auth/register_validation.js"></script>
</body>
</html>
