<?php
/**
 * @fileoverview create_checkout_session.php
 *
 * @description
 * Endpoint API odpowiada za utworzenie sesji Stripe Checkout dla zalogowanego
 * klienta na podstawie zawartosci aktywnego koszyka i wybranej metody dostawy.
 * Waliduje dane wejsciowe, oblicza kwote platnosci, wywoluje API Stripe i zwraca
 * URL sesji lub wykonuje przekierowanie do Stripe.
 *
 * @scope
 * - Weryfikacja sesji klienta oraz konfiguracji klucza Stripe.
 * - Odczyt koszyka i walidacja metody dostawy (kurier/pickup).
 * - Wyliczenie lacznej kwoty zamowienia i przygotowanie danych checkout.
 * - Wywolanie Stripe API (/v1/checkout/sessions) przez cURL.
 * - Zwrot odpowiedzi JSON lub przekierowanie na URL sesji Stripe.
 *
 * @behavior
 * - Brak sesji lub niepoprawne dane: odpowiedz blad (4xx/5xx) w JSON.
 * - Sukces utworzenia sesji: odpowiedz z id i url checkout.
 * - Parametr redirect=1: natychmiastowe przekierowanie do Stripe Checkout.
 */

require_once __DIR__ . '/../../db/connection.php';
require_once __DIR__ . '/../cart/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function stripeJsonResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    stripeJsonResponse(401, [
        'ok' => false,
        'error' => 'Brak aktywnej sesji użytkownika.'
    ]);
}

$config = require __DIR__ . '/../../config.php';
$stripeSecretKey = (string)($config['stripe_secret_key'] ?? '');
$currency = strtolower((string)($config['stripe_currency'] ?? 'pln'));

if ($stripeSecretKey === '') {
    stripeJsonResponse(500, [
        'ok' => false,
        'error' => 'Brak klucza Stripe. Uzupełnij stripe_secret_key w config.php lub zmienną STRIPE_SECRET_KEY.'
    ]);
}

$userId = (int)$_SESSION['user_id'];
$cartId = cartGetOrCreateActiveId();
$summary = $cartId ? cartGetSummary($cartId) : ['items' => [], 'count' => 0, 'total' => 0.0];

if (empty($summary['items'])) {
    stripeJsonResponse(400, [
        'ok' => false,
        'error' => 'Koszyk jest pusty.'
    ]);
}

$deliveryMethodRaw = (string)($_POST['delivery_method'] ?? $_GET['delivery_method'] ?? '');
$deliveryMethod = trim($deliveryMethodRaw);

if ($deliveryMethod === '') {
    stripeJsonResponse(400, [
        'ok' => false,
        'error' => 'Brak wybranej metody dostawy.'
    ]);
}

$deliveryMethods = [];
$couriersResult = mysqli_query($conn, "SELECT id, nazwa, cena_dostawy FROM kurierzy WHERE aktywny = 1 ORDER BY nazwa ASC");
while ($couriersResult && ($courierRow = mysqli_fetch_assoc($couriersResult))) {
    $courierId = (int)$courierRow['id'];
    $deliveryMethods[$courierId] = [
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

if ($deliveryMethod === 'pickup') {
    $deliveryCost = 0.0;
    $deliveryLabel = 'Odbiór w sklepie';
} else {
    $deliveryCourierId = (int)$deliveryMethod;
    if ($deliveryCourierId <= 0 || !isset($deliveryMethods[$deliveryCourierId])) {
        stripeJsonResponse(400, [
            'ok' => false,
            'error' => 'Wybrana metoda dostawy jest nieprawidłowa.'
        ]);
    }
    $deliveryCost = (float)$deliveryMethods[$deliveryCourierId]['cost'];
    $deliveryLabel = (string)$deliveryMethods[$deliveryCourierId]['label'];
}

$productsTotal = (float)$summary['total'];
$total = round($productsTotal + $deliveryCost, 2);
$amountInMinorUnit = (int)round($total * 100);

if ($amountInMinorUnit <= 0) {
    stripeJsonResponse(400, [
        'ok' => false,
        'error' => 'Kwota płatności jest nieprawidłowa.'
    ]);
}

$customerResult = mysqli_query(
    $conn,
    "SELECT email FROM klienci WHERE id = $userId LIMIT 1"
);
$customerRow = $customerResult ? mysqli_fetch_assoc($customerResult) : null;
$customerEmail = (string)($customerRow['email'] ?? '');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$projectPath = preg_replace('#/api/stripe$#', '', rtrim($scriptDir, '/'));
$baseUrl = $scheme . '://' . $host . $projectPath;

$successUrl = $baseUrl . '/public/payment_success.php?stripe=success&session_id={CHECKOUT_SESSION_ID}';
$cancelUrl = $baseUrl . '/public/payment.php?stripe=cancel';

$data = [
    'success_url' => $successUrl,
    'cancel_url' => $cancelUrl,
    'mode' => 'payment',
    'payment_method_types[0]' => 'card',
    'line_items[0][price_data][currency]' => $currency,
    'line_items[0][price_data][product_data][name]' => 'Zamówienie SklepIntCom',
    'line_items[0][price_data][product_data][description]' => 'Produkty: ' . (int)$summary['count'] . ', dostawa: ' . $deliveryLabel,
    'line_items[0][price_data][unit_amount]' => $amountInMinorUnit,
    'line_items[0][quantity]' => 1,
    'metadata[user_id]' => (string)$userId,
    'metadata[cart_id]' => (string)(int)$cartId,
    'metadata[delivery_method]' => $deliveryMethod,
    'metadata[pickup_courier_id]' => $pickupCourierId !== null ? (string)$pickupCourierId : ''
];

if ($customerEmail !== '') {
    $data['customer_email'] = $customerEmail;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_USERPWD, $stripeSecretKey . ':');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$curlErrNo = curl_errno($ch);
$curlErrMsg = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlErrNo !== 0) {
    stripeJsonResponse(502, [
        'ok' => false,
        'error' => 'Błąd połączenia ze Stripe: ' . $curlErrMsg
    ]);
}

$decoded = json_decode((string)$response, true);
if (!is_array($decoded)) {
    stripeJsonResponse(502, [
        'ok' => false,
        'error' => 'Nieprawidłowa odpowiedź ze Stripe.'
    ]);
}

if ($httpCode < 200 || $httpCode >= 300 || !isset($decoded['id'], $decoded['url'])) {
    $stripeMessage = $decoded['error']['message'] ?? 'Nie udało się utworzyć sesji Stripe.';
    stripeJsonResponse(400, [
        'ok' => false,
        'error' => $stripeMessage,
        'stripe_response' => $decoded
    ]);
}

$shouldRedirect = (string)($_GET['redirect'] ?? '0') === '1';
if ($shouldRedirect) {
    header('Location: ' . $decoded['url']);
    exit;
}

stripeJsonResponse(200, [
    'ok' => true,
    'id' => $decoded['id'],
    'url' => $decoded['url']
]);
