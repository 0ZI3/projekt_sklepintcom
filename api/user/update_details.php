<?php
/**
 * @fileoverview update_details.php
 *
 * @description
 * Endpoint API odpowiada za aktualizacje danych kontaktowo-adresowych
 * zalogowanego klienta. Waliduje i normalizuje dane formularza, zapisuje je
 * w bazie danych oraz wykonuje bezpieczne przekierowanie po zakonczeniu operacji.
 *
 * @scope
 * - Weryfikacja sesji klienta i metody HTTP POST.
 * - Normalizacja wybranych pol adresowych (kapitalizacja poczatku/konca).
 * - Walidacja danych (telefon, adres, miasto, kraj, kod pocztowy).
 * - Aktualizacja rekordu klienta w tabeli klienci przez prepared statement.
 * - Kontrola bezpiecznego redirectu po zapisie.
 *
 * @behavior
 * - Brak sesji lub niepoprawna metoda: przekierowanie do odpowiedniego widoku.
 * - Blad walidacji/zapisu: przekierowanie z kodem bledu i parametrem redirect.
 * - Sukces: zapis danych i przekierowanie na wskazana sciezke wewnetrzna.
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/customer/login.php');
    exit;
}

require_once '../../db/connection.php';

function capitalizeFirstLetter(string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        $first = mb_substr($value, 0, 1, 'UTF-8');
        $rest = mb_substr($value, 1, null, 'UTF-8');
        return mb_strtoupper($first, 'UTF-8') . $rest;
    }

    return strtoupper(substr($value, 0, 1)) . substr($value, 1);
}

function uppercaseLastLetterIfAlpha(string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        $last = mb_substr($value, -1, 1, 'UTF-8');
        if (!preg_match('/\p{L}/u', $last)) {
            return $value;
        }

        $start = mb_substr($value, 0, mb_strlen($value, 'UTF-8') - 1, 'UTF-8');
        return $start . mb_strtoupper($last, 'UTF-8');
    }

    $last = substr($value, -1);
    if (!preg_match('/[A-Za-z]/', $last)) {
        return $value;
    }

    return substr($value, 0, -1) . strtoupper($last);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/customer/fill_details.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$telefon = trim($_POST['telefon'] ?? '');
$ulica = capitalizeFirstLetter($_POST['ulica'] ?? '');
$dom = uppercaseLastLetterIfAlpha($_POST['dom'] ?? '');
$numer = trim($_POST['numer'] ?? '');
$miasto = capitalizeFirstLetter($_POST['miasto'] ?? '');
$kodPocztowy = trim($_POST['kod_pocztowy'] ?? '');
$kraj = capitalizeFirstLetter($_POST['kraj'] ?? 'Polska');
$redirect = trim($_POST['redirect'] ?? '../../public/customer/customer_panel.php');
$encodedRedirect = rawurlencode($redirect);

if ($telefon === '' || $ulica === '' || $dom === '' || $miasto === '' || $kodPocztowy === '' || $kraj === '') {
    header('Location: ../../public/customer/fill_details.php?error=missing_fields&redirect=' . $encodedRedirect);
    exit;
}

if (!preg_match('/^\+?[0-9\s\-()]{7,20}$/', $telefon)) {
    header('Location: ../../public/customer/fill_details.php?error=invalid_phone&redirect=' . $encodedRedirect);
    exit;
}

if (!preg_match('/^[\p{L}0-9\s\.-\/]{2,150}$/u', $ulica)) {
    header('Location: ../../public/customer/fill_details.php?error=invalid_address&redirect=' . $encodedRedirect);
    exit;
}

if (!preg_match('/^[0-9A-Za-z\-\/]{1,20}$/', $dom) || ($numer !== '' && !preg_match('/^[0-9A-Za-z\-\/]{1,20}$/', $numer))) {
    header('Location: ../../public/customer/fill_details.php?error=invalid_address&redirect=' . $encodedRedirect);
    exit;
}

if (!preg_match('/^[\p{L}\s\-\.]{2,120}$/u', $miasto) || !preg_match('/^[\p{L}\s\-\.]{2,120}$/u', $kraj)) {
    header('Location: ../../public/customer/fill_details.php?error=invalid_address&redirect=' . $encodedRedirect);
    exit;
}

if (!preg_match('/^[0-9]{2}-[0-9]{3}$/', $kodPocztowy)) {
    header('Location: ../../public/customer/fill_details.php?error=invalid_postcode&redirect=' . $encodedRedirect);
    exit;
}

$sql = 'UPDATE klienci
        SET telefon = ?,
            ulica = ?,
            dom = ?,
            numer = ?,
            miasto = ?,
            kod_pocztowy = ?,
            kraj = ?
        WHERE id = ?
        LIMIT 1';

 $stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    header('Location: ../../public/customer/fill_details.php?error=save_failed&redirect=' . $encodedRedirect);
    exit;
}

mysqli_stmt_bind_param($stmt, 'sssssssi', $telefon, $ulica, $dom, $numer, $miasto, $kodPocztowy, $kraj, $userId);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    header('Location: ../../public/customer/fill_details.php?error=save_failed&redirect=' . $encodedRedirect);
    exit;
}

// Pozwalamy przekierowac tylko na sciezki wewnetrzne.
if (strpos($redirect, 'http://') === 0 || strpos($redirect, 'https://') === 0) {
    $redirect = '../../public/customer/customer_panel.php';
}

header('Location: ' . $redirect);
exit;
