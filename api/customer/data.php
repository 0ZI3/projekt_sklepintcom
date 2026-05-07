<?php
/**
 * @fileoverview data.php
 *
 * @description
 * Endpoint odpowiada za obsluge danych profilu klienta.
 * Udostepnia pobranie danych zalogowanego uzytkownika oraz aktualizacje danych
 * kontaktowo-adresowych po walidacji i normalizacji wartosci formularza.
 *
 * @scope
 * - Weryfikacja sesji zalogowanego klienta.
 * - Odczyt i wyswietlenie danych klienta z bazy.
 * - Walidacja danych wejściowych przesylanych metodą POST.
 * - Normalizacja wybranych pol adresowych (kapitalizacja i format).
 * - Aktualizacja rekordu klienta w tabeli klienci.
 *
 * @behavior
 * - Brak sesji: przekierowanie do logowania.
 * - Poprawne dane POST: zapis zmian i przekierowanie z parametrem updated=1.
 * - Blad walidacji/zapisu: pozostanie w trybie edycji z komunikatami bledow.
 */

session_start();
require_once __DIR__ . '/../../db/connection.php';

function capitalizeFirstLetter(string $value): string
{
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

function uppercaseLastLetterIfAlpha(string $value): string
{
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

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Kliencie';
$errors = [];
$isEditMode = isset($_GET['edit']) && $_GET['edit'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isEditMode = true;

    $imie = trim($_POST['imie'] ?? '');
    $nazwisko = trim($_POST['nazwisko'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $ulica = capitalizeFirstLetter($_POST['ulica'] ?? '');
    $dom = uppercaseLastLetterIfAlpha($_POST['dom'] ?? '');
    $numer = trim($_POST['numer'] ?? '');
    $miasto = capitalizeFirstLetter($_POST['miasto'] ?? '');
    $kodPocztowy = trim($_POST['kod_pocztowy'] ?? '');
    $kraj = capitalizeFirstLetter($_POST['kraj'] ?? '');

    if ($imie === '') {
        $errors[] = 'Podaj imie.';
    }
    if ($nazwisko === '') {
        $errors[] = 'Podaj nazwisko.';
    }
    if ($telefon === '') {
        $errors[] = 'Podaj numer telefonu.';
    }
    if ($ulica === '') {
        $errors[] = 'Podaj ulice.';
    }
    if ($dom === '') {
        $errors[] = 'Podaj numer domu.';
    }
    if ($miasto === '') {
        $errors[] = 'Podaj miasto.';
    }
    if ($kodPocztowy === '') {
        $errors[] = 'Podaj kod pocztowy.';
    }
    if ($kraj === '') {
        $errors[] = 'Podaj kraj.';
    }

    if ($telefon !== '' && !preg_match('/^\+?[0-9\s\-()]{7,20}$/', $telefon)) {
        $errors[] = 'Podaj poprawny numer telefonu.';
    }
    if ($ulica !== '' && !preg_match('/^[\p{L}0-9\s\.-\/]{2,150}$/u', $ulica)) {
        $errors[] = 'Podaj poprawna ulice.';
    }
    if ($dom !== '' && !preg_match('/^[0-9A-Za-z\-\/]{1,20}$/', $dom)) {
        $errors[] = 'Podaj poprawny numer domu.';
    }
    if ($numer !== '' && !preg_match('/^[0-9A-Za-z\-\/]{1,20}$/', $numer)) {
        $errors[] = 'Podaj poprawny numer lokalu.';
    }
    if ($miasto !== '' && !preg_match('/^[\p{L}\s\-\.]{2,120}$/u', $miasto)) {
        $errors[] = 'Podaj poprawne miasto.';
    }
    if ($kodPocztowy !== '' && !preg_match('/^[0-9]{2}-[0-9]{3}$/', $kodPocztowy)) {
        $errors[] = 'Podaj poprawny kod pocztowy (np. 00-000).';
    }
    if ($kraj !== '' && !preg_match('/^[\p{L}\s\-\.]{2,120}$/u', $kraj)) {
        $errors[] = 'Podaj poprawny kraj.';
    }

    if (!$errors) {
        $updateStmt = mysqli_prepare(
            $conn,
            'UPDATE klienci SET imie = ?, nazwisko = ?, telefon = ?, ulica = ?, dom = ?, numer = ?, miasto = ?, kod_pocztowy = ?, kraj = ? WHERE id = ? LIMIT 1'
        );

        if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, 'sssssssssi', $imie, $nazwisko, $telefon, $ulica, $dom, $numer, $miasto, $kodPocztowy, $kraj, $userId);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);

            header('Location: customer_data.php?updated=1');
            exit;
        }

        $errors[] = 'Nie udalo sie zapisac danych. Sprobuj ponownie.';
    }
}

$stmt = mysqli_prepare(
    $conn,
    'SELECT imie, nazwisko, email, telefon, ulica, dom, numer, miasto, kod_pocztowy, kraj FROM klienci WHERE id = ? LIMIT 1'
);

if (!$stmt) {
    http_response_code(500);
    exit('Wystapil blad podczas ladowania danych klienta.');
}

mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$clientData = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$clientData) {
    http_response_code(404);
    exit('Nie znaleziono danych klienta.');
}

function displayValue(?string $value): string
{
    $trimmed = trim((string)$value);
    return $trimmed !== '' ? htmlspecialchars($trimmed) : 'Brak danych';
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
