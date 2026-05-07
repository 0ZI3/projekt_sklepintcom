<?php
/**
 * @fileoverview update.php
 *
 * @description
 * Endpoint API odpowiada za aktualizacje ilosci produktu w aktywnym koszyku.
 * Odczytuje dane z zadania POST i deleguje operacje zmiany ilosci do funkcji
 * pomocniczej modulu koszyka, a nastepnie zwraca wynik w formacie JSON.
 *
 * @scope
 * - Obsluga wyłącznie metody HTTP POST.
 * - Odczyt parametrow productId i quantity z danych wejsciowych.
 * - Aktualizacja ilosci produktu przez funkcje cartSetProductQuantity.
 * - Zwrot odpowiedzi JSON z odpowiednim kodem HTTP.
 *
 * @behavior
 * - Metoda inna niz POST: odpowiedz 405 (Method not allowed).
 * - Sukces aktualizacji: odpowiedz 200 z wynikiem operacji.
 * - Blad aktualizacji: odpowiedz 400 z informacja o problemie.
 */

require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cartSendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = cartGetRequestData();
$productId = isset($input['productId']) ? (int)$input['productId'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 0;

$result = cartSetProductQuantity($productId, $quantity);
$statusCode = !empty($result['success']) ? 200 : 400;

cartSendJson($result, $statusCode);
