<?php
/**
 * @fileoverview helpers.php
 *
 * @description
 * Plik zawiera funkcje pomocnicze dla modulu koszyka zakupowego.
 * Odpowiada za obsluge sesji koszyka, operacje na pozycjach koszyka,
 * wyliczanie cen i podsumowan oraz zwracanie odpowiedzi API w formacie JSON.
 *
 * @scope
 * - Narzedzia wspolne dla endpointow koszyka (add, update, remove, details, count).
 * - Tworzenie i wyszukiwanie aktywnego koszyka dla goscia i zalogowanego klienta.
 * - Scalanie koszyka goscia z koszykiem klienta po zalogowaniu.
 * - Dodawanie, aktualizacja i usuwanie pozycji koszyka z kontrola stanu magazynowego.
 * - Pobieranie listy pozycji oraz agregacja podsumowania (count, total).
 *
 * @behavior
 * - Zapewnia spojnosc danych koszyka i znacznik aktualizacji (cartTouch).
 * - Dba o walidacje danych wejsciowych i zwraca komunikaty bledow biznesowych.
 * - Udostepnia gotowe struktury danych dla odpowiedzi JSON endpointow koszyka.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../db/connection.php';

function cartSendJson($payload, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function cartGetRequestData() {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function cartEnsureSessionToken() {
    if (empty($_SESSION['cart_session_token'])) {
        $_SESSION['cart_session_token'] = hash('sha256', session_id() . '|' . bin2hex(random_bytes(16)));
    }

    return $_SESSION['cart_session_token'];
}

function cartCalculateFinalPrice($basePrice, $promotionPercent) {
    $basePrice = (float)$basePrice;
    $promotionPercent = (float)$promotionPercent;

    if ($promotionPercent <= 0) {
        return round($basePrice, 2);
    }

    return round($basePrice * (1 - ($promotionPercent / 100)), 2);
}

function cartFindActiveByClientId($clientId) {
    global $conn;

    $clientId = (int)$clientId;
    if ($clientId <= 0) {
        return null;
    }

    $sql = "SELECT id FROM koszyki WHERE klient_id = $clientId AND status = 'aktywny' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    return (int)$row['id'];
}

function cartFindActiveBySessionToken($sessionToken) {
    global $conn;

    $sessionToken = mysqli_real_escape_string($conn, $sessionToken);
    $sql = "SELECT id FROM koszyki WHERE session_token = '$sessionToken' AND status = 'aktywny' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    return (int)$row['id'];
}

function cartCreate($clientId = null, $sessionToken = null) {
    global $conn;

    $clientSql = $clientId !== null ? (int)$clientId : 'NULL';
    $sessionSql = $sessionToken !== null ? ("'" . mysqli_real_escape_string($conn, $sessionToken) . "'") : 'NULL';

    $sql = "INSERT INTO koszyki (klient_id, session_token, status, utworzono_at, zaktualizowano_at)
            VALUES ($clientSql, $sessionSql, 'aktywny', NOW(), NOW())";

    if (!mysqli_query($conn, $sql)) {
        return null;
    }

    return (int)mysqli_insert_id($conn);
}

function cartGetProductSnapshot($productId) {
    global $conn;

    $productId = (int)$productId;
    if ($productId <= 0) {
        return null;
    }

    $sql = "SELECT id, cena, promocja_proc, stan_magazynowy, aktywny
            FROM produkty
            WHERE id = $productId
            LIMIT 1";

    $result = mysqli_query($conn, $sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }

    return mysqli_fetch_assoc($result);
}

function cartGetItemQty($cartId, $productId) {
    global $conn;

    $cartId = (int)$cartId;
    $productId = (int)$productId;

    $sql = "SELECT id, ilosc FROM pozycje_koszyka WHERE koszyk_id = $cartId AND produkt_id = $productId LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }

    return mysqli_fetch_assoc($result);
}

function cartTouch($cartId) {
    global $conn;

    $cartId = (int)$cartId;
    mysqli_query($conn, "UPDATE koszyki SET zaktualizowano_at = NOW() WHERE id = $cartId");
}

function cartGetItemCount($cartId) {
    global $conn;

    $cartId = (int)$cartId;
    if ($cartId <= 0) {
        return 0;
    }

    $sql = "SELECT COALESCE(SUM(ilosc), 0) AS total_items FROM pozycje_koszyka WHERE koszyk_id = $cartId";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return isset($row['total_items']) ? (int)$row['total_items'] : 0;
}

function cartMergeGuestIntoClient($guestCartId, $clientCartId) {
    global $conn;

    $guestCartId = (int)$guestCartId;
    $clientCartId = (int)$clientCartId;

    if ($guestCartId <= 0 || $clientCartId <= 0 || $guestCartId === $clientCartId) {
        return;
    }

    $sql = "SELECT produkt_id, ilosc, cena_bazowa, promocja_proc, cena_koncowa
            FROM pozycje_koszyka
            WHERE koszyk_id = $guestCartId";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $productId = (int)$row['produkt_id'];
        $qty = (int)$row['ilosc'];
        $product = cartGetProductSnapshot($productId);

        if (!$product || (int)$product['aktywny'] !== 1) {
            continue;
        }

        $stock = (int)$product['stan_magazynowy'];
        if ($stock <= 0) {
            continue;
        }

        $existing = cartGetItemQty($clientCartId, $productId);
        if ($existing) {
            $positionId = (int)$existing['id'];
            $newQty = (int)$existing['ilosc'] + $qty;
            if ($newQty > $stock) {
                $newQty = $stock;
            }

            mysqli_query($conn, "UPDATE pozycje_koszyka SET ilosc = $newQty, zaktualizowano_at = NOW() WHERE id = $positionId");
            continue;
        }

        if ($qty > $stock) {
            $qty = $stock;
        }

        $base = (float)$row['cena_bazowa'];
        $promo = (float)$row['promocja_proc'];
        $final = (float)$row['cena_koncowa'];

        mysqli_query(
            $conn,
            "INSERT INTO pozycje_koszyka (koszyk_id, produkt_id, ilosc, cena_bazowa, promocja_proc, cena_koncowa, utworzono_at, zaktualizowano_at)
             VALUES ($clientCartId, $productId, $qty, $base, $promo, $final, NOW(), NOW())"
        );
    }

    mysqli_query($conn, "UPDATE koszyki SET status = 'porzucony', zaktualizowano_at = NOW() WHERE id = $guestCartId");
    mysqli_query($conn, "DELETE FROM pozycje_koszyka WHERE koszyk_id = $guestCartId");
    cartTouch($clientCartId);
}

function cartGetOrCreateActiveId() {
    $sessionToken = cartEnsureSessionToken();
    $clientId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    if ($clientId > 0) {
        $clientCartId = cartFindActiveByClientId($clientId);
        $guestCartId = cartFindActiveBySessionToken($sessionToken);

        if ($clientCartId === null) {
            if ($guestCartId !== null) {
                global $conn;
                $guestCartIdInt = (int)$guestCartId;
                mysqli_query($conn, "UPDATE koszyki SET klient_id = $clientId, zaktualizowano_at = NOW() WHERE id = $guestCartIdInt");
                return $guestCartIdInt;
            }

            return cartCreate($clientId, $sessionToken);
        }

        if ($guestCartId !== null && $guestCartId !== $clientCartId) {
            cartMergeGuestIntoClient($guestCartId, $clientCartId);
        }

        return $clientCartId;
    }

    $guestCartId = cartFindActiveBySessionToken($sessionToken);
    if ($guestCartId !== null) {
        return $guestCartId;
    }

    return cartCreate(null, $sessionToken);
}

function cartAddProduct($productId, $quantity = 1) {
    global $conn;

    $productId = (int)$productId;
    $quantity = (int)$quantity;

    if ($productId <= 0 || $quantity <= 0) {
        return ['success' => false, 'message' => 'Niepoprawne dane produktu.'];
    }

    $cartId = cartGetOrCreateActiveId();
    if (!$cartId) {
        return ['success' => false, 'message' => 'Nie udalo sie utworzyc koszyka.'];
    }

    $product = cartGetProductSnapshot($productId);
    if (!$product || (int)$product['aktywny'] !== 1) {
        return ['success' => false, 'message' => 'Produkt jest niedostepny.'];
    }

    $stock = (int)$product['stan_magazynowy'];
    $existing = cartGetItemQty($cartId, $productId);
    $currentQty = $existing ? (int)$existing['ilosc'] : 0;
    $newQty = $currentQty + $quantity;

    if ($newQty > $stock) {
        return ['success' => false, 'message' => 'Brak wystarczajacego stanu magazynowego.'];
    }

    $basePrice = (float)$product['cena'];
    $promo = (float)$product['promocja_proc'];
    $finalPrice = cartCalculateFinalPrice($basePrice, $promo);

    if ($existing) {
        $positionId = (int)$existing['id'];
        $sql = "UPDATE pozycje_koszyka
                SET ilosc = $newQty,
                    cena_bazowa = $basePrice,
                    promocja_proc = $promo,
                    cena_koncowa = $finalPrice,
                    zaktualizowano_at = NOW()
                WHERE id = $positionId";
    } else {
        $sql = "INSERT INTO pozycje_koszyka
                (koszyk_id, produkt_id, ilosc, cena_bazowa, promocja_proc, cena_koncowa, utworzono_at, zaktualizowano_at)
                VALUES
                ($cartId, $productId, $quantity, $basePrice, $promo, $finalPrice, NOW(), NOW())";
    }

    if (!mysqli_query($conn, $sql)) {
        return ['success' => false, 'message' => 'Nie udalo sie dodac produktu do koszyka.'];
    }

    cartTouch($cartId);
    return [
        'success' => true,
        'message' => 'Dodano do koszyka.',
        'count' => cartGetItemCount($cartId)
    ];
}

function cartSetProductQuantity($productId, $quantity) {
    global $conn;

    $productId = (int)$productId;
    $quantity = (int)$quantity;

    if ($productId <= 0) {
        return ['success' => false, 'message' => 'Niepoprawny produkt.'];
    }

    $cartId = cartGetOrCreateActiveId();
    if (!$cartId) {
        return ['success' => false, 'message' => 'Nie udalo sie odczytac koszyka.'];
    }

    $existing = cartGetItemQty($cartId, $productId);
    if (!$existing) {
        return ['success' => false, 'message' => 'Pozycja nie istnieje w koszyku.'];
    }

    if ($quantity <= 0) {
        $positionId = (int)$existing['id'];
        mysqli_query($conn, "DELETE FROM pozycje_koszyka WHERE id = $positionId");
        cartTouch($cartId);

        return [
            'success' => true,
            'message' => 'Usunieto pozycje z koszyka.',
            'count' => cartGetItemCount($cartId)
        ];
    }

    $product = cartGetProductSnapshot($productId);
    if (!$product || (int)$product['aktywny'] !== 1) {
        return ['success' => false, 'message' => 'Produkt jest niedostepny.'];
    }

    if ($quantity > (int)$product['stan_magazynowy']) {
        return ['success' => false, 'message' => 'Brak wystarczajacego stanu magazynowego.'];
    }

    $basePrice = (float)$product['cena'];
    $promo = (float)$product['promocja_proc'];
    $finalPrice = cartCalculateFinalPrice($basePrice, $promo);
    $positionId = (int)$existing['id'];

    $sql = "UPDATE pozycje_koszyka
            SET ilosc = $quantity,
                cena_bazowa = $basePrice,
                promocja_proc = $promo,
                cena_koncowa = $finalPrice,
                zaktualizowano_at = NOW()
            WHERE id = $positionId";

    if (!mysqli_query($conn, $sql)) {
        return ['success' => false, 'message' => 'Nie udalo sie zaktualizowac pozycji.'];
    }

    cartTouch($cartId);
    return [
        'success' => true,
        'message' => 'Zaktualizowano ilosc.',
        'count' => cartGetItemCount($cartId)
    ];
}

function cartRemoveProduct($productId) {
    return cartSetProductQuantity($productId, 0);
}

function cartGetItems($cartId, $limit = null) {
    global $conn;

    $cartId = (int)$cartId;
    if ($cartId <= 0) {
        return [];
    }

    $limitSql = '';
    if ($limit !== null) {
        $limit = (int)$limit;
        if ($limit > 0) {
            $limitSql = " LIMIT $limit";
        }
    }

    $sql = "SELECT
                pk.produkt_id,
                pk.ilosc,
                pk.cena_koncowa,
                p.nazwa,
                p.stan_magazynowy,
                COALESCE(zp.nazwa_pliku, '') AS nazwa_pliku
            FROM pozycje_koszyka pk
            INNER JOIN produkty p ON p.id = pk.produkt_id
            LEFT JOIN (
                SELECT produkt_id, MIN(nazwa_pliku) AS nazwa_pliku
                FROM zdjecia_produktow
                GROUP BY produkt_id
            ) zp ON zp.produkt_id = p.id
            WHERE pk.koszyk_id = $cartId
            ORDER BY pk.zaktualizowano_at DESC" . $limitSql;

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return [];
    }

    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $qty = (int)$row['ilosc'];
        $price = (float)$row['cena_koncowa'];

        $items[] = [
            'product_id' => (int)$row['produkt_id'],
            'name' => (string)$row['nazwa'],
            'qty' => $qty,
            'unit_price' => round($price, 2),
            'line_total' => round($price * $qty, 2),
            'stock' => (int)$row['stan_magazynowy'],
            'image' => (string)$row['nazwa_pliku']
        ];
    }

    return $items;
}

function cartGetSummary($cartId) {
    $items = cartGetItems($cartId);
    $itemCount = 0;
    $total = 0.0;

    foreach ($items as $item) {
        $itemCount += (int)$item['qty'];
        $total += (float)$item['line_total'];
    }

    return [
        'items' => $items,
        'count' => $itemCount,
        'total' => round($total, 2)
    ];
}
