<?php
/**
 * @fileoverview orders.php
 *
 * @description
 * Endpoint odpowiada za pobranie historii zamowien zalogowanego klienta.
 * Przygotowuje dane zamowien wraz ze statusami, informacjami o platnosci i dostawie
 * oraz listami pozycji przypisanych do poszczegolnych zamowien.
 *
 * @scope
 * - Weryfikacja sesji klienta przed dostepem do danych.
 * - Pobranie listy zamowien klienta z danymi statusow i dostawy.
 * - Pobranie pozycji zamowien dla wszystkich odczytanych zamowien.
 * - Grupowanie pozycji wedlug identyfikatora zamowienia.
 * - Udostepnienie danych do dalszego renderowania widoku zamowien.
 *
 * @behavior
 * - Brak sesji: przekierowanie na strone logowania klienta.
 * - Dostepne zamowienia: zwrot danych zamowien i pozycji pogrupowanych per zamowienie.
 * - Brak zamowien: pozostawienie pustej listy bez bledu.
 */

session_start();
require_once __DIR__ . '/../../db/connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Kliencie';
$orders = [];
$orderItemsByOrder = [];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$ordersStmt = mysqli_prepare(
    $conn,
    'SELECT
        z.id,
        z.data_zamowienia,
        z.kwota_brutto,
        z.uwagi,
        sz.nazwa AS status_zamowienia,
        p.typ AS typ_platnosci,
        sp.nazwa AS status_platnosci,
        d.koszt AS koszt_dostawy,
        d.przewidywana_dostawa,
        d.numer_przesylki,
        sd.nazwa AS status_dostawy,
        k.nazwa AS kurier_nazwa
     FROM zamowienia z
     LEFT JOIN statusy_zamowienia sz ON sz.id = z.status_id
     LEFT JOIN platnosci p ON p.zamowienie_id = z.id
     LEFT JOIN statusy_platnosci sp ON sp.id = p.status_id
     LEFT JOIN dostawy d ON d.zamowienie_id = z.id
     LEFT JOIN statusy_dostawy sd ON sd.id = d.status_id
     LEFT JOIN kurierzy k ON k.id = d.kurier_id
     WHERE z.klient_id = ?
     ORDER BY z.id DESC'
);

if ($ordersStmt) {
    mysqli_stmt_bind_param($ordersStmt, 'i', $userId);
    mysqli_stmt_execute($ordersStmt);
    $ordersResult = mysqli_stmt_get_result($ordersStmt);

    while ($ordersResult && ($row = mysqli_fetch_assoc($ordersResult))) {
        $row['id'] = (int)$row['id'];
        $orders[] = $row;
    }

    mysqli_stmt_close($ordersStmt);
}

if (!empty($orders)) {
    $orderIds = array_map(static function (array $order): int {
        return (int)$order['id'];
    }, $orders);

    $orderIdsSql = implode(',', $orderIds);

    $itemsSql = "SELECT
                    pz.zamowienie_id,
                    pz.ilosc,
                    pz.cena_jednostkowa,
                    pz.rabat_proc,
                    pr.nazwa AS produkt_nazwa
                 FROM pozycje_zamowienia pz
                 INNER JOIN produkty pr ON pr.id = pz.produkt_id
                 WHERE pz.zamowienie_id IN ($orderIdsSql)
                 ORDER BY pz.zamowienie_id DESC, pz.id ASC";

    $itemsResult = mysqli_query($conn, $itemsSql);

    while ($itemsResult && ($item = mysqli_fetch_assoc($itemsResult))) {
        $orderId = (int)$item['zamowienie_id'];
        if (!isset($orderItemsByOrder[$orderId])) {
            $orderItemsByOrder[$orderId] = [];
        }
        $orderItemsByOrder[$orderId][] = $item;
    }
}
