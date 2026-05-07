<?php
/**
 * @fileoverview payment.php
 *
 * @description
 * Plik odpowiada za obsluge procesu checkout dla zalogowanego klienta.
 * Przygotowuje podsumowanie koszyka i metody dostawy/platnosci, waliduje dane,
 * a dla wybranej metody finalizuje zamowienie lokalnie lub przekierowuje do Stripe.
 *
 * @scope
 * - Weryfikacja sesji klienta i kompletosci danych wysylkowych.
 * - Pobranie aktywnego koszyka oraz podsumowania pozycji.
 * - Pobranie aktywnych kurierow i przygotowanie metod dostawy.
 * - Walidacja formularza checkout (dostawa, platnosc, stan koszyka).
 * - Obsluga finalizacji: transakcja SQL dla pobrania i przekierowanie do Stripe dla karty.
 *
 * @behavior
 * - Brak sesji lub danych klienta: przekierowanie do logowania/uzupelnienia danych.
 * - Pusty koszyk: przekierowanie do koszyka.
 * - Platnosc karta: przekierowanie do utworzenia sesji Stripe Checkout.
 * - Inne metody: zapis zamowienia, platnosci i dostawy oraz aktualizacja stanow magazynowych.
 */

require_once __DIR__ . '/../cart/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: customer/login.php');
    exit;
}

require_once __DIR__ . '/../../db/connection.php';

$userId = (int)$_SESSION['user_id'];
$cartId = cartGetOrCreateActiveId();
$summary = $cartId ? cartGetSummary($cartId) : ['items' => [], 'count' => 0, 'total' => 0.0];

if (empty($summary['items'])) {
    header('Location: cart.php');
    exit;
}

$customerResult = mysqli_query(
    $conn,
    "SELECT imie, nazwisko, email, telefon, ulica, dom, numer, miasto, kod_pocztowy, kraj FROM klienci WHERE id = $userId LIMIT 1"
);
$customer = $customerResult ? mysqli_fetch_assoc($customerResult) : null;

if (!$customer) {
    header('Location: customer/login.php');
    exit;
}

$hasShippingData =
    !empty(trim((string)$customer['telefon'])) &&
    !empty(trim((string)$customer['ulica'])) &&
    !empty(trim((string)$customer['dom'])) &&
    !empty(trim((string)$customer['miasto'])) &&
    !empty(trim((string)$customer['kod_pocztowy'])) &&
    !empty(trim((string)$customer['kraj']));

if (!$hasShippingData) {
    header('Location: customer/fill_details.php?redirect=../../public/payment.php');
    exit;
}

$paymentMethods = [
    'karta' => 'Karta płatnicza',
    'pobranie' => 'Płatność przy odbiorze'
];

$deliveryMethods = [];
$couriersResult = mysqli_query($conn, "SELECT id, nazwa, cena_dostawy FROM kurierzy WHERE aktywny = 1 ORDER BY nazwa ASC");
while ($couriersResult && ($courierRow = mysqli_fetch_assoc($couriersResult))) {
    $courierId = (int)$courierRow['id'];
    $deliveryMethods[$courierId] = [
        'courier_id' => $courierId,
        'label' => (string)$courierRow['nazwa'],
        'cost' => (float)$courierRow['cena_dostawy']
    ];
}

$pickupCourierId = null;
foreach ($deliveryMethods as $cid => $cinfo) {
    $name = strtolower($cinfo['label']);
    if (strpos($name, 'punkt') !== false || strpos($name, 'odbior') !== false) {
        $pickupCourierId = $cid;
        break;
    }
}

if (empty($deliveryMethods)) {
    $errors = ['Brak aktywnych kurierow w bazie danych.'];
}

$firstDeliveryId = !empty($deliveryMethods) ? (int)array_key_first($deliveryMethods) : 0;

$errors = $errors ?? [];
$selectedPayment = 'karta';
$selectedDelivery = $firstDeliveryId;
$orderNoteValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPayment = $_POST['payment_method'] ?? 'karta';
    $selectedDeliveryRaw = $_POST['delivery_method'] ?? (string)$firstDeliveryId;
    $selectedDelivery = $selectedDeliveryRaw;
    $orderNoteValue = trim((string)($_POST['order_note'] ?? ''));

    if (!isset($paymentMethods[$selectedPayment])) {
        $errors[] = 'Wybierz poprawną metodę płatności.';
    }

    if ($selectedDelivery === 'pickup') {
    } else {
        $sdInt = (int)$selectedDelivery;
        if ($sdInt <= 0 || !isset($deliveryMethods[$sdInt])) {
            $errors[] = 'Wybierz poprawną metodę dostawy.';
        }
    }

    if ($selectedPayment === 'pobranie' && $selectedDelivery !== 'pickup') {
        $errors[] = 'Płatność przy odbiorze jest dostępna wyłącznie dla odbioru w sklepie.';
    }

    $freshSummary = $cartId ? cartGetSummary($cartId) : ['items' => [], 'count' => 0, 'total' => 0.0];

    if (empty($freshSummary['items'])) {
        $errors[] = 'Twój koszyk jest pusty.';
    }

    if ($orderNoteValue !== '' && strlen($orderNoteValue) > 225) {
        $errors[] = 'Uwagi do zamówienia mogą mieć maksymalnie 225 znaków.';
    }

    if (empty($errors)) {
        $productsTotal = (float)$freshSummary['total'];

        if ($selectedDelivery === 'pickup') {
            $deliveryCost = 0.0;
            $courierId = $pickupCourierId ?? $firstDeliveryId;
            $total = round($productsTotal + $deliveryCost, 2);
            $etaDays = 0;
            $etaDate = mysqli_real_escape_string($conn, date('Y-m-d'));
        } else {
            $selCourierId = (int)$selectedDelivery;
            $courierStmt = mysqli_prepare($conn, 'SELECT cena_dostawy FROM kurierzy WHERE id = ? AND aktywny = 1 LIMIT 1');
            if ($courierStmt) {
                mysqli_stmt_bind_param($courierStmt, 'i', $selCourierId);
                mysqli_stmt_execute($courierStmt);
                $courierRes = mysqli_stmt_get_result($courierStmt);
                $courierRow = $courierRes ? mysqli_fetch_assoc($courierRes) : null;
                mysqli_stmt_close($courierStmt);
            } else {
                $courierRow = null;
            }

            if (!$courierRow) {
                $errors[] = 'Wybrany kurier jest niedostępny.';
            } else {
                $deliveryCost = (float)$courierRow['cena_dostawy'];
                $total = round($productsTotal + $deliveryCost, 2);
                $courierId = $selCourierId;
                $etaDays = 3;
                $etaDate = mysqli_real_escape_string($conn, date('Y-m-d', strtotime('+' . $etaDays . ' days')));
            }
        }
    }

    if (empty($errors) && $selectedPayment === 'karta') {
        $_SESSION['order_note'] = $orderNoteValue;
        $deliveryParam = rawurlencode((string)$selectedDelivery);
        header('Location: ../api/stripe/create_checkout_session.php?delivery_method=' . $deliveryParam . '&redirect=1');
        exit;
    }

    if (empty($errors)) {

        mysqli_begin_transaction($conn);

        try {
            $lockedProducts = [];
            foreach ($freshSummary['items'] as $item) {
                $productId = (int)$item['product_id'];
                $qty = (int)$item['qty'];

                $stockSql = "SELECT id, stan_magazynowy, aktywny FROM produkty WHERE id = $productId LIMIT 1 FOR UPDATE";
                $stockResult = mysqli_query($conn, $stockSql);
                $stockRow = $stockResult ? mysqli_fetch_assoc($stockResult) : null;

                if (!$stockRow || (int)$stockRow['aktywny'] !== 1) {
                    throw new Exception('Jeden z produktów jest już niedostępny.');
                }

                $currentStock = (int)$stockRow['stan_magazynowy'];
                if ($qty > $currentStock) {
                    throw new Exception('Niewystarczający stan magazynowy dla jednego z produktów.');
                }

                $lockedProducts[$productId] = [
                    'requested_qty' => $qty,
                    'current_stock' => $currentStock
                ];
            }

            $noteSql = 'NULL';
            if ($orderNoteValue !== '') {
                $noteEsc = mysqli_real_escape_string($conn, $orderNoteValue);
                $noteSql = "'$noteEsc'";
            }
            $insertOrderSql = "INSERT INTO zamowienia (klient_id, status_id, pracownik_id, data_zamowienia, kwota_brutto, uwagi, notatki_wewnetrzne)
                               VALUES ($userId, 1, NULL, NOW(), $total, $noteSql, NULL)";

            if (!mysqli_query($conn, $insertOrderSql)) {
                throw new Exception('Nie udało się utworzyć zamówienia.');
            }

            $orderId = (int)mysqli_insert_id($conn);

            foreach ($freshSummary['items'] as $item) {
                $productId = (int)$item['product_id'];
                $qty = (int)$item['qty'];
                $unitPrice = (float)$item['unit_price'];

                $insertPositionSql = "INSERT INTO pozycje_zamowienia (zamowienie_id, produkt_id, ilosc, cena_jednostkowa, rabat_proc)
                                      VALUES ($orderId, $productId, $qty, $unitPrice, 0.00)";

                if (!mysqli_query($conn, $insertPositionSql)) {
                    throw new Exception('Nie udało się dodać pozycji zamówienia.');
                }

                $previousStock = isset($lockedProducts[$productId]) ? (int)$lockedProducts[$productId]['current_stock'] : 0;
                $newStock = $previousStock - $qty;
                if ($newStock < 0) {
                    throw new Exception('Niewystarczający stan magazynowy dla jednego z produktów.');
                }

                $newActive = $newStock > 0 ? 1 : 0;
                $updateStockSql = "UPDATE produkty
                                   SET stan_magazynowy = $newStock,
                                       aktywny = $newActive
                                   WHERE id = $productId
                                   LIMIT 1";

                if (!mysqli_query($conn, $updateStockSql)) {
                    throw new Exception('Nie udało się zaktualizować stanu magazynowego produktu.');
                }
            }

            $paymentTypeEsc = mysqli_real_escape_string($conn, $selectedPayment);
            $insertPaymentSql = "INSERT INTO platnosci (zamowienie_id, typ, kwota, data_platnosci, status_id)
                                 VALUES ($orderId, '$paymentTypeEsc', $total, NOW(), 1)";

            if (!mysqli_query($conn, $insertPaymentSql)) {
                throw new Exception('Nie udało się zapisać płatności.');
            }

            $insertDeliverySql = "INSERT INTO dostawy (zamowienie_id, status_id, kurier_id, numer_przesylki, koszt, przewidywana_dostawa, data_wysylki, data_dostarczenia)
                                  VALUES ($orderId, 1, $courierId, NULL, $deliveryCost, '$etaDate', NULL, NULL)";

            if (!mysqli_query($conn, $insertDeliverySql)) {
                throw new Exception('Nie udało się zapisać dostawy.');
            }

            $cartIdInt = (int)$cartId;
            if ($cartIdInt > 0) {
                mysqli_query($conn, "UPDATE koszyki SET status = 'zamowiony', zaktualizowano_at = NOW() WHERE id = $cartIdInt LIMIT 1");
            }

            mysqli_commit($conn);
            header('Location: payment_success.php?order=' . $orderId);
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
        }
    }
}

if ($selectedDelivery !== 'pickup' && $selectedPayment === 'pobranie') {
    $selectedPayment = 'karta';
}

$selectedDelivery = isset($selectedDelivery) ? $selectedDelivery : (string)$firstDeliveryId;

$deliveryCost = 0.0;
if ($selectedDelivery === 'pickup') {
    $deliveryCost = 0.0;
} else {
    $stmtC = mysqli_prepare($conn, 'SELECT cena_dostawy FROM kurierzy WHERE id = ? AND aktywny = 1 LIMIT 1');
    if ($stmtC) {
        $sel = (int)$selectedDelivery;
        mysqli_stmt_bind_param($stmtC, 'i', $sel);
        mysqli_stmt_execute($stmtC);
        $resC = mysqli_stmt_get_result($stmtC);
        $rowC = $resC ? mysqli_fetch_assoc($resC) : null;
        mysqli_stmt_close($stmtC);
        if ($rowC) {
            $deliveryCost = (float)$rowC['cena_dostawy'];
        }
    }
}
$productsTotalValue = (float)$summary['total'];
$totalValue = round($productsTotalValue + $deliveryCost, 2);
$itemCount = (int)$summary['count'];
