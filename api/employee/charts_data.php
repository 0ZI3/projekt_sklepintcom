<?php
/**
 * @fileoverview charts_data.php
 *
 * @description
 * Endpoint API odpowiada za dostarczanie danych analitycznych do wykresow panelu
 * administracyjnego. Pobiera dane sprzedazowe, koszykowe, kategorie przychodow
 * oraz top produkty, a nastepnie zwraca je w formacie JSON.
 *
 * @scope
 * - Autoryzacja dostepu: tylko zalogowany pracownik z rola admin.
 * - Odczyt danych z widokow raportowych dla ostatnich 90 dni.
 * - Uzupelnienie brakujacych dni zerowymi wartosciami (szkielet 90 dni).
 * - Mapowanie i normalizacja typow danych dla odpowiedzi API.
 * - Zwrot struktur: sales90, categoryRevenue90, topProducts90, carts90.
 *
 * @behavior
 * - Brak sesji pracownika: odpowiedz 401.
 * - Brak uprawnien admina: odpowiedz 403.
 * - Sukces: odpowiedz 200 z pelnym payloadem danych wykresow.
 * - Blad runtime/bazy: odpowiedz 500 z komunikatem bledu.
 */

session_start();
require_once __DIR__ . '/../../db/connection.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['worker_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Brak autoryzacji.']);
    exit();
}

$workerRole = $_SESSION['worker_role'] ?? '';
if (!in_array($workerRole, ['admin', 'magazyn'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Brak uprawnien.']);
    exit();
}

function getRows(mysqli $conn, string $sql): array
{
    $rows = [];
    $result = @mysqli_query($conn, $sql);
    if ($result === false) {
        throw new RuntimeException((string)mysqli_error($conn));
    }
    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $rows[] = $row;
    }

    return $rows;
}

function buildLast90DaysSkeleton(): array
{
    $data = [];
    $today = new DateTimeImmutable('today');

    for ($i = 89; $i >= 0; $i--) {
        $day = $today->sub(new DateInterval('P' . $i . 'D'))->format('Y-m-d');
        $data[$day] = [
            'dzien' => $day,
            'przychod' => 0.0,
            'sprzedane_sztuki' => 0,
            'aktywne_koszyki' => 0,
            'porzucone_koszyki' => 0,
        ];
    }

    return $data;
}

try {
    $salesRows = getRows(
        $conn,
        'SELECT dzien, przychod, sprzedane_sztuki
         FROM vw_admin_wykres_sprzedaz_dzienna_90
         ORDER BY dzien ASC'
    );

    $cartsRows = getRows(
        $conn,
        'SELECT dzien, aktywne_koszyki, porzucone_koszyki
         FROM vw_admin_wykres_koszyki_dzienne_90
         ORDER BY dzien ASC'
    );

    $days = buildLast90DaysSkeleton();

    foreach ($salesRows as $row) {
        $day = (string)($row['dzien'] ?? '');
        if (!isset($days[$day])) {
            continue;
        }
        $days[$day]['przychod'] = (float)($row['przychod'] ?? 0);
        $days[$day]['sprzedane_sztuki'] = (int)($row['sprzedane_sztuki'] ?? 0);
    }

    foreach ($cartsRows as $row) {
        $day = (string)($row['dzien'] ?? '');
        if (!isset($days[$day])) {
            continue;
        }
        $days[$day]['aktywne_koszyki'] = (int)($row['aktywne_koszyki'] ?? 0);
        $days[$day]['porzucone_koszyki'] = (int)($row['porzucone_koszyki'] ?? 0);
    }

    $dailySales = array_map(
        static function (array $row): array {
            return [
                'dzien' => $row['dzien'],
                'przychod' => (float)$row['przychod'],
                'sprzedane_sztuki' => (int)$row['sprzedane_sztuki'],
            ];
        },
        array_values($days)
    );

    $dailyCarts = array_map(
        static function (array $row): array {
            return [
                'dzien' => $row['dzien'],
                'aktywne_koszyki' => (int)$row['aktywne_koszyki'],
                'porzucone_koszyki' => (int)$row['porzucone_koszyki'],
            ];
        },
        array_values($days)
    );

    $categories = getRows(
        $conn,
        'SELECT kategoria, przychod, sprzedane_sztuki
         FROM vw_admin_wykres_przychod_kategorie_90
         ORDER BY przychod DESC, sprzedane_sztuki DESC'
    );

    $categoryRevenue = array_map(
        static function (array $row): array {
            return [
                'kategoria' => (string)$row['kategoria'],
                'przychod' => (float)($row['przychod'] ?? 0),
                'sprzedane_sztuki' => (int)($row['sprzedane_sztuki'] ?? 0),
            ];
        },
        $categories
    );

    $topProductsRows = getRows(
        $conn,
        'SELECT produkt_id, nazwa, sku, sprzedane_sztuki, przychod
         FROM vw_admin_wykres_top_produkty_90
         ORDER BY sprzedane_sztuki DESC, przychod DESC
         LIMIT 10'
    );

    $topProducts = array_map(
        static function (array $row): array {
            return [
                'produkt_id' => (int)($row['produkt_id'] ?? 0),
                'nazwa' => (string)$row['nazwa'],
                'sku' => (string)$row['sku'],
                'sprzedane_sztuki' => (int)($row['sprzedane_sztuki'] ?? 0),
                'przychod' => (float)($row['przychod'] ?? 0),
            ];
        },
        $topProductsRows
    );

    echo json_encode([
        'sales90' => $dailySales,
        'categoryRevenue90' => $categoryRevenue,
        'topProducts90' => $topProducts,
        'carts90' => $dailyCarts,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Nie udalo sie pobrac danych wykresow.',
        'details' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
