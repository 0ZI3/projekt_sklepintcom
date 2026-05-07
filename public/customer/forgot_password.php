<!--
    @fileoverview forgot_password.php

    @description
    Publiczny widok informacyjny dotyczacy odzyskiwania hasla klienta.
    Strona komunikuje, ze automatyczny reset hasla jest obecnie niedostepny
    i kieruje uzytkownika do kontaktu z administratorem serwisu.

    @scope
    - Renderowanie komunikatu o niedostepnej funkcji resetu hasla.
    - Prezentacja danych kontaktowych administratora (adres email).
    - Udostepnienie przycisku powrotu do strony logowania klienta.

    @behavior
    - Wejscie na strone: wyswietlenie stalego komunikatu informacyjnego.
    - Akcja uzytkownika: mozliwosc powrotu do formularza logowania.
-->

<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zapomniałem hasła - SklepIntCom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>
</head>
<body class="h-full">
    <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div class="inline-flex items-center justify-center p-3 bg-indigo-600 rounded-lg shadow-lg mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m4-6a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold tracking-tight text-gray-900">Odzyskiwanie hasła</h2>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-xl sm:rounded-xl sm:px-10 border border-gray-100">
                <div class="rounded-lg bg-yellow-50 p-4 border border-yellow-100 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625l6.28-10.875zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-bold text-yellow-800">Funkcjonalność niedostępna</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Automatyczne resetowanie hasła jest aktualnie wyłączone.</p>
                                <p class="mt-2 text-sm font-semibold">Skontaktuj się z administratorem serwisu:</p>
                                <a href="mailto:admin@sklepint.com" class="font-bold text-indigo-600 hover:text-indigo-500">admin@sklepint.com</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="login.php" 
                        class="flex w-full justify-center rounded-lg border border-transparent bg-indigo-600 py-3 px-4 text-sm font-bold text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all active:scale-[0.98]">
                        Wróć do logowania
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
