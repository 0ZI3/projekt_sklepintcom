<?php
/**
 * @fileoverview triggers.php
 *
 * @description
 * Plik odpowiada za podglad logow systemowych w panelu administracyjnym.
 * Umozliwia filtrowanie wpisow po typie zdarzenia i wyszukiwanie tekstowe,
 * a nastepnie przygotowuje liste logow do prezentacji w widoku pracownika.
 *
 * @scope
 * - Weryfikacja sesji pracownika i uprawnien roli admin.
 * - Walidacja parametru filtra typu zdarzenia (typ).
 * - Budowanie dynamicznego zapytania SQL z filtrami i wyszukiwaniem.
 * - Pobranie i ograniczenie listy logow do najnowszych 200 rekordow.
 * - Przygotowanie danych pomocniczych (nazwy klienta i pracownika).
 *
 * @behavior
 * - Brak sesji: przekierowanie do logowania pracownika.
 * - Brak roli admin: odpowiedz 403.
 * - Poprawne zapytanie: zwrot przefiltrowanej listy logow do widoku.
 * - Niepoprawny typ filtra: fallback do wartosci all.
 */

session_start();
require_once __DIR__ . '/../../db/connection.php';
require_once __DIR__ . '/access_denied.php';

if (!isset($_SESSION['worker_id'])) {
    header('Location: login.php');
    exit();
}

$workerName = $_SESSION['worker_name'] ?? 'Pracownik';
$workerRole = $_SESSION['worker_role'] ?? '';

if ($workerRole !== 'admin') {
    renderEmployeeAccessDenied('Sekcja logow jest dostepna tylko dla administratora.');
}

$allowedTypes = ['all', 'info', 'warning', 'error', 'security'];
$typeFilter = strtolower(trim((string)($_GET['typ'] ?? 'all')));
if (!in_array($typeFilter, $allowedTypes, true)) {
    $typeFilter = 'all';
}

$search = trim((string)($_GET['q'] ?? ''));

$logs = [];
$sql = 'SELECT ls.id, ls.pracownik_id, ls.klient_id, ls.typ, ls.opis, ls.data_zdarzenia,
               CONCAT(k.imie, " ", k.nazwisko) AS klient_nazwa,
               CONCAT(p.imie, " ", p.nazwisko) AS pracownik_nazwa
        FROM logi_systemowe ls
        LEFT JOIN klienci k ON k.id = ls.klient_id
        LEFT JOIN pracownicy p ON p.id = ls.pracownik_id';
$where = [];
$params = [];
$types = '';

if ($typeFilter !== 'all') {
    $where[] = 'ls.typ = ?';
    $params[] = $typeFilter;
    $types .= 's';
}

if ($search !== '') {
    $where[] = '(ls.opis LIKE ?
        OR CAST(ls.id AS CHAR) LIKE ?
        OR CAST(ls.klient_id AS CHAR) LIKE ?
        OR CAST(ls.pracownik_id AS CHAR) LIKE ?
        OR CONCAT(k.imie, " ", k.nazwisko) LIKE ?
        OR CONCAT(p.imie, " ", p.nazwisko) LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssssss';
}

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY ls.id DESC LIMIT 200';

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    if ($params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $logs[] = $row;
    }
    mysqli_stmt_close($stmt);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
