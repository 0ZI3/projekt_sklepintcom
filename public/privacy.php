<!--
    @fileoverview privacy.php

    @description
    Publiczny widok polityki prywatnosci serwisu SklepIntCom.
    Plik prezentuje informacje dotyczace przetwarzania danych osobowych,
    celow przetwarzania, praw uzytkownika oraz zasad cookies.

    @scope
    - Renderowanie tresci polityki prywatnosci w warstwie publicznej.
    - Prezentacja sekcji informacyjnych zgodnych z RODO.
    - Udostepnienie podstawowej nawigacji i stopki serwisu.
    - Zapewnienie czytelnej formy dokumentu dla klienta.

    @behavior
    - Wejscie na strone: wyswietlenie statycznej tresci polityki prywatnosci.
    - Brak logiki biznesowej: strona pelni funkcje informacyjna.
-->

<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Polityka Prywatności - SklepIntCom</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white shadow-xl rounded-2xl p-8 sm:p-12 border border-gray-100">
            <h1 class="text-3xl font-extrabold text-gray-900 mb-8 border-b pb-4">Polityka Prywatności Serwisu
                SklepIntCom</h1>

            <div class="prose prose-indigo max-w-none text-gray-600 space-y-6">
                <section>
                    <h2 class="text-xl font-bold text-gray-900">1. Przetwarzanie i ochrona danych osobowych</h2>
                    <p>Administratorem danych osobowych Klientów jest SklepIntCom. Zgodnie z Rozporządzeniem Parlamentu
                        Europejskiego i Rady (UE) 2016/679 (RODO), przetwarzamy Twoje dane w celu realizacji zamówień i
                        świadczenia usług cyfrowych.</p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-gray-900">2. Cel zbierania danych</h2>
                    <p>Dane są zbierane wyłącznie w celu:</p>
                    <ul class="list-disc pl-5 space-y-2">
                        <li>Realizacji i wysyłki Twojego zamówienia.</li>
                        <li>Obsługi procesu reklamacji i zwrotów.</li>
                        <li>Komunikacji z Tobą związanej z Twoim kontem i zakupami.</li>
                        <li>Umożliwienia Ci korzystania z pełnego panelu klienta.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-gray-900">3. Przekazywanie danych</h2>
                    <p>Twoje dane mogą być przekazywane firmom kurierskim (DPD, DHL, InPost) wyłącznie w zakresie
                        niezbędnym do dostarczenia Twojej przesyłki oraz operatorom płatności w celu sfinalizowania
                        Twojej transakcji.</p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-gray-900">4. Prawa Klienta</h2>
                    <p>W każdej chwili masz prawo do dostępu do swoich danych, ich poprawienia, usunięcia lub
                        ograniczenia przetwarzania. Swoje uprawnienia możesz realizować kontaktując się z nami pod
                        adresem: admin@sklepint.com.</p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-gray-900">5. Pliki Cookies</h2>
                    <p>Serwis używa plików cookies w celu dostosowania zawartości strony do preferencji użytkownika,
                        utrzymania sesji użytkownika (po zalogowaniu) oraz zbierania anonimowych statystyk.</p>
                </section>
            </div>
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
</html>
