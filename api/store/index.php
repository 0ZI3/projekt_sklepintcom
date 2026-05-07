<?php
/**
 * @fileoverview index.php
 *
 * @description
 * Plik odpowiada za przygotowanie danych strony glownej sklepu.
 * Obsluguje automatyczne odtworzenie sesji klienta lub pracownika na podstawie
 * cookie remember-*, a nastepnie laduje kategorie i zestaw produktow do wyswietlenia.
 *
 * @scope
 * - Inicjalizacja sesji i polaczenia z baza danych.
 * - Odtwarzanie sesji klienta z cookie remember_user.
 * - Odtwarzanie sesji pracownika z cookie remember_worker.
 * - Pobranie drzewa kategorii i losowej listy produktow na strone glowna.
 * - Przygotowanie danych wejsciowych dla warstwy prezentacji.
 *
 * @behavior
 * - Brak aktywnej sesji i poprawne cookie: automatyczne zalogowanie do sesji.
 * - Brak lub niepoprawne cookie: brak zmiany sesji.
 * - Po inicjalizacji: zaladowanie kategorii i produktow rekomendowanych.
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
$displayedIds = [];
$products = getRandomProducts(8, $displayedIds, 'random');
