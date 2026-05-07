<?php
/**
 * @fileoverview remove.php
 *
 * @description
 * Endpoint API odpowiada za usuwanie produktu z aktywnego koszyka.
 * Odbiera dane z zadania POST, odczytuje identyfikator produktu i deleguje
 * operacje usuniecia pozycji do funkcji pomocniczych modulu koszyka.
 *
 * @scope
 * - Obsluga wyłącznie metody HTTP POST.
 * - Odczyt parametru productId z danych wejsciowych.
 * - Usuniecie produktu z koszyka przez funkcje cartRemoveProduct.
 * - Zwrot odpowiedzi JSON z odpowiednim kodem HTTP.
 *
 * @behavior
 * - Metoda inna niz POST: odpowiedz 405 (Method not allowed).
 * - Sukces usuniecia: odpowiedz 200 z wynikiem operacji.
 * - Blad usuniecia: odpowiedz 400 z informacja o problemie.
 */

require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cartSendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = cartGetRequestData();
$productId = isset($input['productId']) ? (int)$input['productId'] : 0;

$result = cartRemoveProduct($productId);
$statusCode = !empty($result['success']) ? 200 : 400;

cartSendJson($result, $statusCode);
