<?php
/**
 * @fileoverview details.php
 *
 * @description
 * Endpoint API odpowiada za zwracanie szczegolowych danych aktywnego koszyka.
 * Odczytuje podsumowanie koszyka oraz liste podgladowa pozycji, a nastepnie
 * udostepnia dane w odpowiedzi JSON na potrzeby interfejsu sklepu.
 *
 * @scope
 * - Inicjalizacja funkcji pomocniczych koszyka.
 * - Pobranie aktywnego identyfikatora koszyka.
 * - Odczyt podsumowania (count, total, items).
 * - Odczyt podgladowej listy pozycji (preview_items).
 * - Zwrot odpowiedzi JSON ze stanem operacji i danymi koszyka.
 *
 * @behavior
 * - Brak dostepu do koszyka: odpowiedz 500 z komunikatem bledu.
 * - Sukces: odpowiedz 200 z pelnym podsumowaniem i podgladem pozycji.
 */

require_once __DIR__ . '/helpers.php';

$cartId = cartGetOrCreateActiveId();
if (!$cartId) {
    cartSendJson([
        'success' => false,
        'message' => 'Nie udalo sie odczytac koszyka.'
    ], 500);
}

$summary = cartGetSummary($cartId);
$previewItems = cartGetItems($cartId, 4);

cartSendJson([
    'success' => true,
    'count' => (int)$summary['count'],
    'total' => (float)$summary['total'],
    'items' => $summary['items'],
    'preview_items' => $previewItems
]);
