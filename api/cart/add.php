<?php
/**
 * @fileoverview add.php
 *
 * @description
 * Endpoint API odpowiada za dodawanie produktu do koszyka.
 * Odbiera dane z zadania POST, normalizuje parametry wejsciowe i deleguje logike
 * dodania pozycji do warstwy pomocniczej koszyka, a nastepnie zwraca odpowiedz JSON.
 *
 * @scope
 * - Obsluga wyłącznie metody HTTP POST.
 * - Odczyt danych wejsciowych productId i quantity.
 * - Dodanie produktu do koszyka przez funkcje cartAddProduct.
 * - Zwrot odpowiedzi JSON wraz z odpowiednim kodem HTTP.
 *
 * @behavior
 * - Metoda inna niz POST: odpowiedz 405 (Method not allowed).
 * - Sukces dodania: odpowiedz 200 z wynikiem operacji.
 * - Blad dodania: odpowiedz 400 z informacja o problemie.
 */

require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cartSendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = cartGetRequestData();
$productId = isset($input['productId']) ? (int)$input['productId'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;

$result = cartAddProduct($productId, $quantity);
$statusCode = !empty($result['success']) ? 200 : 400;

cartSendJson($result, $statusCode);