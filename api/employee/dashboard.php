<?php
/**
 * @fileoverview dashboard.php
 *
 * @description
 * Plik odpowiada za przygotowanie danych panelu glownego pracownika.
 * Pobiera i agreguje kluczowe wskazniki (KPI) oraz listy operacyjne
 * (zamowienia, klienci, stany magazynowe i koszyki), ktore sa wykorzystywane
 * do renderowania dashboardu administracyjnego.
 *
 * @scope
 * - Weryfikacja sesji pracownika i dostepu do panelu.
 * - Pobranie drzewa kategorii dla elementow widoku.
 * - Agregacja KPI: zamowienia, obrot, klienci, aktywne koszyki.
 * - Pobranie zestawien: top klienci, najnowsze zamowienia, niski stan, aktywne koszyki.
 * - Przygotowanie danych w strukturach tablicowych dla warstwy prezentacji.
 *
 * @behavior
 * - Brak sesji: przekierowanie do logowania pracownika.
 * - Dostepna sesja: zbudowanie pelnego kontekstu danych dla dashboardu.
 * - Brak danych czastkowych: fallback do wartosci domyslnych bez przerywania widoku.
 */

session_start();
require_once __DIR__ . '/../../db/connection.php';
require_once __DIR__ . '/access_denied.php';

if (!isset($_SESSION['worker_id'])) {
    header('Location: login.php');
    exit();
}

$worker_name = $_SESSION['worker_name'];
$worker_role = $_SESSION['worker_role'];

if (!in_array($worker_role, ['admin', 'magazyn'], true)) {
    renderEmployeeAccessDenied('Nie masz uprawnien do panelu pracownika.');
}

require_once __DIR__ . '/../categories.php';
$categoryTree = getCategoryTree();

function fetchAllAssoc(mysqli $conn, string $sql): array
{
    $rows = [];
    $result = mysqli_query($conn, $sql);
    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $rows[] = $row;
    }

    return $rows;
}

$kpi = [
    'zamowienia' => 0,
    'obrot' => 0.0,
    'klienci' => 0,
    'aktywne_koszyki' => 0,
];

$kpiOrdersResult = mysqli_query($conn, 'SELECT COUNT(*) AS cnt, COALESCE(SUM(kwota_brutto), 0) AS total FROM vw_admin_zamowienia_pelne');
if ($kpiOrdersResult && ($row = mysqli_fetch_assoc($kpiOrdersResult))) {
    $kpi['zamowienia'] = (int)$row['cnt'];
    $kpi['obrot'] = (float)$row['total'];
}

$kpiCustomersResult = mysqli_query($conn, 'SELECT COUNT(*) AS cnt FROM vw_admin_klienci_statystyki_zamowien');
if ($kpiCustomersResult && ($row = mysqli_fetch_assoc($kpiCustomersResult))) {
    $kpi['klienci'] = (int)$row['cnt'];
}

$kpiCartsResult = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM vw_admin_aktywne_koszyki WHERE status = 'aktywny' AND wartosc_koszyka > 0");
if ($kpiCartsResult && ($row = mysqli_fetch_assoc($kpiCartsResult))) {
    $kpi['aktywne_koszyki'] = (int)$row['cnt'];
}

$topCustomers = fetchAllAssoc(
    $conn,
    'SELECT klient, email, liczba_zamowien, suma_wydatkow
    FROM vw_admin_klienci_statystyki_zamowien
     ORDER BY suma_wydatkow DESC, liczba_zamowien DESC
     LIMIT 5'
);

$latestOrders = fetchAllAssoc(
    $conn,
    'SELECT zamowienie_id, data_zamowienia, klient, status_zamowienia, kwota_brutto
    FROM vw_admin_zamowienia_pelne
     ORDER BY zamowienie_id DESC
     LIMIT 5'
);

$lowStock = fetchAllAssoc(
    $conn,
    'SELECT produkt_id, nazwa, sku, stan_magazynowy, kategoria, sprzedane_sztuki
    FROM vw_admin_produkty_stan_sprzedaz
     WHERE stan_magazynowy <= 10
     ORDER BY stan_magazynowy ASC, sprzedane_sztuki DESC
     LIMIT 5'
);

$activeCarts = fetchAllAssoc(
    $conn,
    "SELECT koszyk_id, klient, status, liczba_pozycji, wartosc_koszyka, zaktualizowano_at
    FROM vw_admin_aktywne_koszyki
    WHERE wartosc_koszyka > 0
     ORDER BY zaktualizowano_at DESC
     LIMIT 5"
);
