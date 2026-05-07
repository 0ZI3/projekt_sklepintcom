<?php
/**
 * @fileoverview access_denied.php
 *
 * @description
 * Wspolny renderer estetycznego ekranu 403 dla panelu pracownika.
 *
 * @scope
 * - Ustawienie kodu HTTP 403.
 * - Wyswietlenie spojnego, stylowanego komunikatu o braku uprawnien.
 * - Udostepnienie pojedynczego przycisku powrotu do panelu pracownika.
 *
 * @behavior
 * - Wejscie do niedozwolonego widoku: render strony bledu i zatrzymanie wykonania.
 */

if (!function_exists('renderEmployeeAccessDenied')) {
    function renderEmployeeAccessDenied(string $message = 'Nie masz uprawnien do tej sekcji panelu. Jesli uwazasz, ze to blad, skontaktuj sie z administratorem systemu.'): void
    {
        http_response_code(403);
        $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $panelLink = strpos($scriptName, '/api/') !== false ? '../../public/employee/employee_panel.php' : 'employee_panel.php';
        ?>
        <!DOCTYPE html>
        <html lang="pl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Brak dostepu - Panel pracownika</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        </head>
        <body class="min-h-screen bg-slate-50 text-slate-900">
            <main class="min-h-screen flex items-center justify-center px-4 py-10">
                <section class="w-full max-w-2xl rounded-2xl border border-rose-100 bg-white shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-rose-600 to-orange-500 text-white p-6 sm:p-8">
                        <p class="uppercase text-xs tracking-[0.2em] text-rose-100 mb-2">Kod 403</p>
                        <h1 class="text-2xl sm:text-3xl font-extrabold flex items-center gap-3">
                            <i class="fa-solid fa-shield-halved"></i>
                            Brak dostepu
                        </h1>
                        <p class="mt-3 text-rose-50/95">Dostep do tej sekcji jest ograniczony przez polityke uprawnien.</p>
                    </div>
                    <div class="p-6 sm:p-8">
                        <p class="text-slate-700 leading-relaxed"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-3 text-sm text-slate-500">W razie pytan skontaktuj sie z administratorem systemu.</p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="<?= htmlspecialchars($panelLink, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-white font-semibold hover:bg-indigo-700 transition-colors">
                                <i class="fa-solid fa-arrow-left"></i>
                                Powrot do panelu
                            </a>
                        </div>
                    </div>
                </section>
            </main>
        </body>
        </html>
        <?php
        exit();
    }
}
