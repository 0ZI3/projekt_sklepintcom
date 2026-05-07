<?php
/**
 * @fileoverview count.php
 *
 * @description
 * Endpoint API odpowiada za zwracanie liczby pozycji w aktywnym koszyku.
 * Pobiera identyfikator aktualnego koszyka i oblicza laczna liczbe elementow,
 * a nastepnie zwraca wynik w formacie JSON.
 *
 * @scope
 * - Inicjalizacja dostepu do funkcji pomocniczych koszyka.
 * - Pobranie lub utworzenie aktywnego identyfikatora koszyka.
 * - Odczyt liczby pozycji przez funkcje cartGetItemCount.
 * - Zwrot odpowiedzi JSON ze statusem operacji i wartoscia count.
 *
 * @behavior
 * - Gdy koszyk istnieje: zwraca rzeczywista liczbe pozycji.
 * - Gdy koszyk nie istnieje: zwraca count = 0.
 */

require_once __DIR__ . '/helpers.php';

$cartId = cartGetOrCreateActiveId();
$count = $cartId ? cartGetItemCount($cartId) : 0;

cartSendJson([
    'success' => true,
    'count' => $count
]);
