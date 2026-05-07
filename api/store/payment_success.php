<?php
/**
 * @fileoverview payment_success.php
 *
 * @description
 * Plik odpowiada za przygotowanie kontekstu strony potwierdzenia platnosci.
 * Zapewnia dostep tylko dla zalogowanego klienta oraz odczytuje identyfikator
 * zamowienia przekazany w parametrze zapytania.
 *
 * @scope
 * - Inicjalizacja sesji aplikacji (jesli nie jest aktywna).
 * - Kontrola dostepu na podstawie sesji user_id.
 * - Odczyt parametru order z query string i normalizacja do int.
 * - Udostepnienie danych wejsciowych do dalszego renderowania widoku.
 *
 * @behavior
 * - Brak sesji klienta: przekierowanie do logowania klienta.
 * - Aktywna sesja: przygotowanie zmiennej orderId dla strony sukcesu platnosci.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: customer/login.php');
    exit;
}

$orderId = isset($_GET['order']) ? (int)$_GET['order'] : 0;
$stripeError = null;

if ($orderId <= 0 && (string)($_GET['stripe'] ?? '') === 'success') {
    require_once __DIR__ . '/../../db/connection.php';
    require_once __DIR__ . '/../cart/helpers.php';

    $stripeSessionId = trim((string)($_GET['session_id'] ?? ''));
    if ($stripeSessionId === '') {
        $stripeError = 'Brak identyfikatora sesji Stripe.';
    } else {
        $config = require __DIR__ . '/../../config.php';
        $stripeSecretKey = (string)($config['stripe_secret_key'] ?? '');

        if ($stripeSecretKey === '') {
            $stripeError = 'Brak klucza Stripe w konfiguracji.';
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/checkout/sessions/' . urlencode($stripeSessionId));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $stripeSecretKey . ':');
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $curlErrNo = curl_errno($ch);
            $curlErrMsg = curl_error($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curlErrNo !== 0) {
                $stripeError = 'Błąd połączenia ze Stripe: ' . $curlErrMsg;
            } else {
                $sessionData = json_decode((string)$response, true);
                if (!is_array($sessionData) || $httpCode < 200 || $httpCode >= 300) {
                    $stripeMessage = $sessionData['error']['message'] ?? 'Nie udało się pobrać sesji Stripe.';
                    $stripeError = $stripeMessage;
                } else {
                    $paymentStatus = (string)($sessionData['payment_status'] ?? '');
                    $sessionMetadata = $sessionData['metadata'] ?? [];
                    $metaUserId = (int)($sessionMetadata['user_id'] ?? 0);
                    $metaCartId = (int)($sessionMetadata['cart_id'] ?? 0);
                    $metaDeliveryMethod = (string)($sessionMetadata['delivery_method'] ?? '');
                    $metaPickupCourierId = (int)($sessionMetadata['pickup_courier_id'] ?? 0);

                    if ($paymentStatus !== 'paid') {
                        $stripeError = 'Płatność nie została jeszcze potwierdzona przez Stripe.';
                    } elseif ($metaUserId !== (int)$_SESSION['user_id']) {
                        $stripeError = 'Sesja Stripe nie jest powiązana z zalogowanym klientem.';
                    } else {
                        $sessionIdEsc = mysqli_real_escape_string($conn, $stripeSessionId);
                        $existingOrderResult = mysqli_query(
                            $conn,
                            "SELECT id FROM zamowienia WHERE notatki_wewnetrzne LIKE '%stripe_session_id:$sessionIdEsc%' LIMIT 1"
                        );
                        if ($existingOrderResult && ($existingRow = mysqli_fetch_assoc($existingOrderResult))) {
                            $orderId = (int)$existingRow['id'];
                        } else {
                            $cartId = $metaCartId > 0 ? $metaCartId : cartGetOrCreateActiveId();
                            $cartIdInt = (int)$cartId;

                            if ($cartIdInt <= 0) {
                                $stripeError = 'Nie udało się odczytać koszyka dla płatności.';
                            } else {
                                $summary = cartGetSummary($cartIdInt);
                                if (empty($summary['items'])) {
                                    $stripeError = 'Koszyk jest pusty.';
                                } else {
                                    $deliveryMethod = $metaDeliveryMethod;
                                    $deliveryMethods = [];
                                    $couriersResult = mysqli_query($conn, "SELECT id, nazwa, cena_dostawy FROM kurierzy WHERE aktywny = 1 ORDER BY nazwa ASC");
                                    while ($couriersResult && ($courierRow = mysqli_fetch_assoc($couriersResult))) {
                                        $courierId = (int)$courierRow['id'];
                                        $deliveryMethods[$courierId] = [
                                            'label' => (string)$courierRow['nazwa'],
                                            'cost' => (float)$courierRow['cena_dostawy']
                                        ];
                                    }

                                    $productsTotal = (float)$summary['total'];
                                    if ($deliveryMethod === 'pickup') {
                                        $deliveryCost = 0.0;
                                        $courierId = $metaPickupCourierId > 0 ? $metaPickupCourierId : (int)array_key_first($deliveryMethods);
                                        $etaDate = mysqli_real_escape_string($conn, date('Y-m-d'));
                                    } else {
                                        $courierId = (int)$deliveryMethod;
                                        if ($courierId <= 0 || !isset($deliveryMethods[$courierId])) {
                                            $stripeError = 'Wybrany kurier jest nieprawidłowy.';
                                        } else {
                                            $deliveryCost = (float)$deliveryMethods[$courierId]['cost'];
                                            $etaDate = mysqli_real_escape_string($conn, date('Y-m-d', strtotime('+3 days')));
                                        }
                                    }

                                    if ($stripeError === null && $courierId <= 0) {
                                        $stripeError = 'Nie udało się dobrać kuriera dla zamówienia.';
                                    }

                                    if ($stripeError === null) {
                                        $total = round($productsTotal + $deliveryCost, 2);

                                        mysqli_begin_transaction($conn);

                                        try {
                                            $lockedProducts = [];
                                            foreach ($summary['items'] as $item) {
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

                                            $userId = (int)$_SESSION['user_id'];
                                            $sessionNote = trim((string)($_SESSION['order_note'] ?? ''));
                                            $noteSql = 'NULL';
                                            if ($sessionNote !== '') {
                                                $noteEsc = mysqli_real_escape_string($conn, $sessionNote);
                                                $noteSql = "'$noteEsc'";
                                            }
                                            $insertOrderSql = "INSERT INTO zamowienia (klient_id, status_id, pracownik_id, data_zamowienia, kwota_brutto, uwagi, notatki_wewnetrzne)
                                                               VALUES ($userId, 1, NULL, NOW(), $total, $noteSql, NULL)";

                                            if (!mysqli_query($conn, $insertOrderSql)) {
                                                throw new Exception('Nie udało się utworzyć zamówienia.');
                                            }

                                            $orderId = (int)mysqli_insert_id($conn);

                                            foreach ($summary['items'] as $item) {
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

                                            $paymentTypeEsc = mysqli_real_escape_string($conn, 'karta');
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

                                            if ($cartIdInt > 0) {
                                                mysqli_query($conn, "UPDATE koszyki SET status = 'zamowiony', zaktualizowano_at = NOW() WHERE id = $cartIdInt LIMIT 1");
                                            }

                                            mysqli_commit($conn);
                                            unset($_SESSION['order_note']);
                                        } catch (Exception $e) {
                                            mysqli_rollback($conn);
                                            $stripeError = $e->getMessage();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
