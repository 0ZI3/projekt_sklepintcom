<?php
/**
 * @fileoverview fetch_more_products.php
 *
 * @description
 * Endpoint API odpowiada za dynamiczne dogrywanie kolejnych produktow
 * (np. przy przycisku "zaladuj wiecej" lub infinite scroll).
 * Odczytuje parametry filtrowania i sortowania z payloadu JSON,
 * a nastepnie zwraca kolejna paczke produktow w odpowiedzi JSON.
 *
 * @scope
 * - Ustawienie odpowiedzi jako application/json.
 * - Odczyt danych wejsciowych z php://input (excludeIds, sort, filtry).
 * - Przygotowanie filtrow: category_id, query, special_filter.
 * - Pobranie produktow przez funkcje getRandomProducts.
 * - Zwrot odpowiedzi JSON ze statusem success i danymi produktow.
 *
 * @behavior
 * - Sukces pobrania: odpowiedz z success=true i lista products.
 * - Blad pobrania: odpowiedz z success=false i komunikatem bledu.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/products.php';

$input = json_decode(file_get_contents('php://input'), true);
$excludeIds = isset($input['excludeIds']) ? $input['excludeIds'] : [];
$sort = isset($input['sort']) ? $input['sort'] : 'random';
$filters = [
    'category_id' => isset($input['categoryId']) ? (int)$input['categoryId'] : 0,
    'query' => isset($input['query']) ? trim((string)$input['query']) : '',
    'special_filter' => isset($input['specialFilter']) ? trim((string)$input['specialFilter']) : ''
];

$products = getRandomProducts(8, $excludeIds, $sort, $filters);

if ($products !== false) {
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Błąd podczas pobierania produktów'
    ]);
}
