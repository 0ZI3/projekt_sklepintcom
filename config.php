<?php
/**
 * @fileoverview config.php
 *
 * @description
 * Centralny plik konfiguracji aplikacji SklepIntCom.
 * Zawiera kluczowe parametry srodowiskowe wykorzystywane przez warstwe backend,
 * w tym ustawienia polaczenia z baza, bezpieczenstwa hasel i integracji Stripe.
 *
 * @scope
 * - Konfiguracja polaczenia z baza danych (db_host, db_name, db_user, db_pass).
 * - Konfiguracja mechanizmu hashowania hasel (pepper).
 * - Konfiguracja platnosci Stripe (secret key i waluta).
 * - Udostepnienie jednolitego zrodla ustawien dla endpointow API.
 *
 * @behavior
 * - Zaladowanie pliku: zwrot tablicy asocjacyjnej z parametrami konfiguracji.
 * - Brak logiki biznesowej: plik pelni role statycznej konfiguracji aplikacji.
 */

return [
    'db_host' => 'localhost',
    'db_name' => 'SklepIntCom',
    'db_user' => 'root',
    'db_pass' => '',
    'pepper' => '7b8d9d6b8f4f4c7b9a2f1b0a5f9c3d4e6a7c8e9f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5061728394a5b6c7d8e9f0',
    'stripe_secret_key' => getenv('sk_test_51TKagkIbaHOyZLuexhLLkalLRTa8tfUBfwQk2AM4XJ6W3vCuWgJ4M3dTYtSjYi4gQPy7itKk3JPf7OxD4D53VHLv00gvZnZDqR') ?: 'sk_test_51TKagkIbaHOyZLuexhLLkalLRTa8tfUBfwQk2AM4XJ6W3vCuWgJ4M3dTYtSjYi4gQPy7itKk3JPf7OxD4D53VHLv00gvZnZDqR',
    'stripe_currency' => 'pln'
];
?>