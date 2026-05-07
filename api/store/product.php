<?php
/**
 * @fileoverview product.php
 *
 * @description
 * Plik odpowiada za przygotowanie danych strony szczegolow produktu.
 * Odtwarza sesje uzytkownika/pracownika z cookie remember-*, pobiera dane produktu,
 * buduje breadcrumb kategorii oraz przygotowuje produkty powiazane i galerie zdjec.
 *
 * @scope
 * - Inicjalizacja sesji i polaczenia z baza danych.
 * - Odtwarzanie sesji klienta/pracownika na podstawie cookie.
 * - Pobranie drzewa kategorii i szczegolow produktu po identyfikatorze GET.
 * - Wyznaczenie produktow powiazanych z tej samej kategorii.
 * - Przygotowanie breadcrumb kategorii, listy obrazow i tytulu strony.
 *
 * @behavior
 * - Poprawny produkt: przygotowanie pelnego kontekstu danych dla widoku produktu.
 * - Brak produktu: przygotowanie bezpiecznych wartosci domyslnych.
 * - Aktywne cookie remember-*: automatyczne odtworzenie sesji uzytkownika/pracownika.
 */

session_start();
require_once __DIR__ . '/../../db/connection.php';

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    $config = require_once __DIR__ . '/../../config.php';
    $decoded = base64_decode($_COOKIE['remember_user']);
    $parts = explode('|', $decoded);
    if (count($parts) === 2) {
        $email = mysqli_real_escape_string($conn, $parts[0]);
        $hash = mysqli_real_escape_string($conn, $parts[1]);

        $sql = "SELECT id, imie, nazwisko FROM klienci WHERE email = '$email' AND haslo_hash_sha512 = '$hash' AND status = 'aktywny' LIMIT 1";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['imie'] . ' ' . $user['nazwisko'];
        }
    }
}

if (!isset($_SESSION['worker_id']) && isset($_COOKIE['remember_worker'])) {
    if (!isset($config)) {
        $config = require_once __DIR__ . '/../../config.php';
    }
    $decoded = base64_decode($_COOKIE['remember_worker']);
    $parts = explode('|', $decoded);
    if (count($parts) === 2) {
        $email = mysqli_real_escape_string($conn, $parts[0]);
        $hash = mysqli_real_escape_string($conn, $parts[1]);

        $sql = "SELECT id, imie, nazwisko, rola FROM pracownicy WHERE email = '$email' AND haslo_hash_sha512 = '$hash' AND aktywny = 1 LIMIT 1";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $worker = mysqli_fetch_assoc($result);
            $_SESSION['worker_id'] = $worker['id'];
            $_SESSION['worker_name'] = $worker['imie'] . ' ' . $worker['nazwisko'];
            $_SESSION['worker_role'] = $worker['rola'];
        }
    }
}

require_once __DIR__ . '/../categories.php';
require_once __DIR__ . '/../products.php';

$categoryTree = getCategoryTree();
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = getProductById($productId);
$relatedProducts = [];

if ($product) {
    $relatedProducts = getRandomProducts(4, [(int)$product['id']], 'random', [
        'category_id' => (int)$product['kategoria_id']
    ]);
}

$categoryBreadcrumb = [];
if ($product && (int)$product['kategoria_id'] > 0) {
    $categoryMap = getCategoryMap();
    $currentId = (int)$product['kategoria_id'];
    $visited = [];

    while ($currentId > 0 && isset($categoryMap[$currentId])) {
        if (isset($visited[$currentId])) {
            break;
        }

        $visited[$currentId] = true;
        $categoryBreadcrumb[] = [
            'id' => $currentId,
            'nazwa' => $categoryMap[$currentId]['nazwa']
        ];
        $currentId = $categoryMap[$currentId]['nadrzedna_id'] !== null ? (int)$categoryMap[$currentId]['nadrzedna_id'] : 0;
    }

    $categoryBreadcrumb = array_reverse($categoryBreadcrumb);
}

$productImages = [];
if ($product) {
    if (!empty($product['nazwy_plikow'])) {
        $productImages = array_values(array_filter(array_map('trim', explode(',', $product['nazwy_plikow']))));
    } elseif (!empty($product['nazwa_pliku'])) {
        $productImages = [$product['nazwa_pliku']];
    }
}

$pageTitle = $product ? ('SklepIntCom - ' . $product['nazwa']) : 'SklepIntCom - Produkt';
