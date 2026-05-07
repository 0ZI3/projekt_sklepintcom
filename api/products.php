<?php
/**
 * @fileoverview products.php
 *
 * @description
 * Plik zawiera funkcje domenowe do pobierania i przygotowania danych produktow.
 * Odpowiada za filtrowanie i sortowanie list produktowych, obsluge relacji
 * kategorii, budowanie sciezki kategorii oraz wyliczanie cen promocyjnych.
 *
 * @scope
 * - Pobieranie mapy i drzewa zaleznosci kategorii.
 * - Budowanie sciezki kategorii (breadcrumb) dla produktu.
 * - Pobieranie list produktow z filtrami, sortowaniem i wykluczeniami ID.
 * - Oznaczanie nowosci i formatowanie cen do prezentacji.
 * - Pobieranie pojedynczego produktu po ID z danymi obrazow.
 *
 * @behavior
 * - Filtry i sortowanie: dynamiczne budowanie warunkow SQL i ORDER BY.
 * - Brak wynikow: zwrot pustej listy lub null (dla pojedynczego produktu).
 * - Dostepne dane: zwrot gotowych struktur do wykorzystania w widokach sklepu.
 */

require_once __DIR__ . '/../db/connection.php';

function getCategoryDescendantIds($categoryId)
{
    global $conn;

    $categoryId = (int) $categoryId;
    if ($categoryId <= 0) {
        return [];
    }

    $result = mysqli_query($conn, "SELECT id, nadrzedna_id FROM kategorie");
    if (!$result) {
        return [$categoryId];
    }

    $childrenMap = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $parentId = $row['nadrzedna_id'] !== null ? (int) $row['nadrzedna_id'] : 0;
        if (!isset($childrenMap[$parentId])) {
            $childrenMap[$parentId] = [];
        }
        $childrenMap[$parentId][] = (int) $row['id'];
    }

    $allIds = [];
    $queue = [$categoryId];
    while (!empty($queue)) {
        $currentId = array_shift($queue);
        if (in_array($currentId, $allIds, true)) {
            continue;
        }

        $allIds[] = $currentId;
        if (!empty($childrenMap[$currentId])) {
            foreach ($childrenMap[$currentId] as $childId) {
                $queue[] = $childId;
            }
        }
    }

    return $allIds;
}

function getCategoryMap()
{
    global $conn;

    $map = [];
    $result = mysqli_query($conn, "SELECT id, nazwa, nadrzedna_id FROM kategorie");
    if (!$result) {
        return $map;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $map[(int) $row['id']] = [
            'nazwa' => $row['nazwa'],
            'nadrzedna_id' => $row['nadrzedna_id'] !== null ? (int) $row['nadrzedna_id'] : null
        ];
    }

    return $map;
}

function buildCategoryPath($categoryId, $categoryMap)
{
    $categoryId = (int) $categoryId;
    if ($categoryId <= 0 || empty($categoryMap[$categoryId])) {
        return '';
    }

    $parts = [];
    $visited = [];
    $currentId = $categoryId;

    while ($currentId !== null && isset($categoryMap[$currentId])) {
        if (isset($visited[$currentId])) {
            break;
        }

        $visited[$currentId] = true;
        $parts[] = $categoryMap[$currentId]['nazwa'];
        $currentId = $categoryMap[$currentId]['nadrzedna_id'];
    }

    $parts = array_reverse($parts);
    return implode(' / ', $parts);
}


/**
 * @param int $limit
 * @param string $sort
 * @param array $filters
 * @return array
 */

function getRandomProducts($limit = 4, $excludeIds = [], $sort = 'random', $filters = [])
{
    global $conn;

    $whereParts = ['p.aktywny = 1'];

    if (!empty($excludeIds)) {
        $safeIds = array_map('intval', $excludeIds);
        $whereParts[] = "p.id NOT IN (" . implode(',', $safeIds) . ")";
    }

    $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : 0;
    if ($categoryId > 0) {
        $categoryIds = getCategoryDescendantIds($categoryId);
        if (!empty($categoryIds)) {
            $safeCategoryIds = array_map('intval', $categoryIds);
            $whereParts[] = "p.kategoria_id IN (" . implode(',', $safeCategoryIds) . ")";
        }
    }

    $query = isset($filters['query']) ? trim((string) $filters['query']) : '';
    if ($query !== '') {
        $escapedQuery = mysqli_real_escape_string($conn, $query);
        $whereParts[] = "(p.nazwa LIKE '%$escapedQuery%' OR p.opis LIKE '%$escapedQuery%')";
    }

    $specialFilter = isset($filters['special_filter']) ? trim((string) $filters['special_filter']) : '';
    if ($specialFilter === 'promocje') {
        $whereParts[] = 'p.promocja_proc > 0';
    } elseif ($specialFilter === 'nowosci') {
        $whereParts[] = 'p.id IN (SELECT id FROM (SELECT id FROM produkty ORDER BY data_dodania DESC, id DESC LIMIT 5) AS latest)';
    }

    $whereClause = !empty($whereParts) ? ('WHERE ' . implode(' AND ', $whereParts) . ' ') : '';

    $orderBy = "RAND()";
    switch ($sort) {
        case 'newest':
            $orderBy = "data_dodania DESC, id DESC";
            break;
        case 'price_asc':
            $orderBy = "cena ASC";
            break;
        case 'price_desc':
            $orderBy = "cena DESC";
            break;
    }

    $limit = (int) $limit;
    $sql = "SELECT p.*, 
            z.nazwa_pliku,
                z.nazwy_plikow
            FROM produkty p
            LEFT JOIN (
                SELECT 
                    zp.produkt_id,
                    SUBSTRING_INDEX(GROUP_CONCAT(zp.nazwa_pliku ORDER BY zp.nazwa_pliku SEPARATOR ','), ',', 1) AS nazwa_pliku,
                    GROUP_CONCAT(zp.nazwa_pliku ORDER BY zp.nazwa_pliku SEPARATOR ',') AS nazwy_plikow
                FROM zdjecia_produktow zp
                GROUP BY zp.produkt_id
            ) z ON z.produkt_id = p.id
            $whereClause ORDER BY $orderBy LIMIT $limit";

    $result = mysqli_query($conn, $sql);
    $products = [];
    $categoryMap = getCategoryMap();

    $latestIds = [];
    $latestResult = mysqli_query($conn, "SELECT id FROM produkty ORDER BY data_dodania DESC, id DESC LIMIT 5");
    if ($latestResult) {
        while ($lRow = mysqli_fetch_assoc($latestResult)) {
            $latestIds[] = (int) $lRow['id'];
        }
    }

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['is_new'] = in_array((int) $row['id'], $latestIds);
            $row['sciezka_kategorii'] = buildCategoryPath((int) $row['kategoria_id'], $categoryMap);

            $cena_brutto = (float) $row['cena'];
            $promocja_proc = (float) $row['promocja_proc'];
            $cena_promocyjna = $cena_brutto;

            if ($promocja_proc > 0) {
                $cena_promocyjna = $cena_brutto * (1 - ($promocja_proc / 100));
            }

            $row['cena_formatted'] = number_format($cena_brutto, 2, ',', ' ');
            $row['cena_promocyjna_formatted'] = number_format($cena_promocyjna, 2, ',', ' ');
            $row['cena_promocyjna'] = $cena_promocyjna;

            $products[] = $row;
        }
    }

    return $products;
}


/**
 * @param int $productId
 * @return array|null
 */

function getProductById($productId)
{
    global $conn;

    $productId = (int) $productId;
    if ($productId <= 0) {
        return null;
    }

    $sql = "SELECT p.*,
            z.nazwa_pliku,
            z.nazwy_plikow
            FROM produkty p
            LEFT JOIN (
                SELECT
                    zp.produkt_id,
                    SUBSTRING_INDEX(GROUP_CONCAT(zp.nazwa_pliku ORDER BY zp.nazwa_pliku SEPARATOR ','), ',', 1) AS nazwa_pliku,
                    GROUP_CONCAT(zp.nazwa_pliku ORDER BY zp.nazwa_pliku SEPARATOR ',') AS nazwy_plikow
                FROM zdjecia_produktow zp
                GROUP BY zp.produkt_id
            ) z ON z.produkt_id = p.id
            WHERE p.id = $productId AND p.aktywny = 1
            LIMIT 1";

    $result = mysqli_query($conn, $sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }

    $product = mysqli_fetch_assoc($result);
    $categoryMap = getCategoryMap();
    $product['sciezka_kategorii'] = buildCategoryPath((int) $product['kategoria_id'], $categoryMap);

    $latestIds = [];
    $latestResult = mysqli_query($conn, "SELECT id FROM produkty ORDER BY data_dodania DESC, id DESC LIMIT 5");
    if ($latestResult) {
        while ($lRow = mysqli_fetch_assoc($latestResult)) {
            $latestIds[] = (int) $lRow['id'];
        }
    }
    $product['is_new'] = in_array((int) $product['id'], $latestIds, true);

    $cenaBrutto = (float) $product['cena'];
    $promocjaProc = (float) $product['promocja_proc'];
    $cenaPromocyjna = $cenaBrutto;
    if ($promocjaProc > 0) {
        $cenaPromocyjna = $cenaBrutto * (1 - ($promocjaProc / 100));
    }

    $product['cena_formatted'] = number_format($cenaBrutto, 2, ',', ' ');
    $product['cena_promocyjna_formatted'] = number_format($cenaPromocyjna, 2, ',', ' ');
    $product['cena_promocyjna'] = $cenaPromocyjna;

    return $product;
}
