<?php
/**
 * @fileoverview products.php
 *
 * @description
 * Plik odpowiada za przygotowanie danych listingu produktow sklepu.
 * Odtwarza sesje uzytkownika/pracownika z cookie remember-*, pobiera drzewo
 * kategorii, obsluguje filtry (kategoria, wyszukiwarka, promocje, nowosci)
 * i przygotowuje zestaw produktow do renderowania widoku.
 *
 * @scope
 * - Inicjalizacja sesji i polaczenia z baza danych.
 * - Odtwarzanie sesji klienta/pracownika na podstawie cookie.
 * - Odczyt i walidacja parametrow filtrowania z query string.
 * - Wyznaczenie naglowkow strony zaleznych od aktywnych filtrow.
 * - Pobranie listy produktow i informacji o pustych wynikach wyszukiwania.
 *
 * @behavior
 * - Aktywne cookie remember-*: automatyczne odtworzenie sesji.
 * - Niepoprawny filtr specjalny: fallback do pustego filtra.
 * - Brak produktow dla frazy: ustawienie flagi pustego wyniku wyszukiwania.
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
$selectedCategoryId = isset($_GET['kategoria']) ? (int)$_GET['kategoria'] : 0;
$searchQuery = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$specialFilter = isset($_GET['filtr']) ? trim((string)$_GET['filtr']) : '';
if (!in_array($specialFilter, ['promocje', 'nowosci'], true)) {
    $specialFilter = '';
}

$selectedCategoryName = '';
if ($selectedCategoryId > 0) {
    $selectedCategoryName = buildCategoryPath($selectedCategoryId, getCategoryMap());
}

$headingTitle = 'Wszystkie produkty';
$headingSubtitle = 'Przeglądaj pełną ofertę sklepu';
if ($specialFilter === 'promocje') {
    $headingTitle = 'Promocje';
    $headingSubtitle = 'Aktualnie przecenione produkty';
} elseif ($specialFilter === 'nowosci') {
    $headingTitle = 'Nowości';
    $headingSubtitle = 'Najnowsze produkty w ofercie';
} elseif ($searchQuery !== '' && $selectedCategoryName !== '') {
    $headingTitle = 'Kategoria: ' . $selectedCategoryName;
    $headingSubtitle = '';
} elseif ($searchQuery !== '') {
    $headingTitle = 'Produkty';
    $headingSubtitle = '';
} elseif ($selectedCategoryName !== '') {
    $headingTitle = 'Kategoria: ' . $selectedCategoryName;
    $headingSubtitle = 'Produkty z wybranej kategorii';
}

$displayedIds = [];
$products = getRandomProducts(8, $displayedIds, 'random', [
    'category_id' => $selectedCategoryId,
    'query' => $searchQuery,
    'special_filter' => $specialFilter
]);
$isEmptySearchResult = ($searchQuery !== '' && empty($products));
