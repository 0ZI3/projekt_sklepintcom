<?php
/**
 * @fileoverview customers.php
 *
 * @description
 * Plik odpowiada za obsluge widoku administracyjnego klientow.
 * Umozliwia administratorowi przegladanie, filtrowanie i edycje danych klienta
 * wraz z walidacja danych formularza i zapisem zmian w bazie.
 *
 * @scope
 * - Weryfikacja sesji pracownika i uprawnien roli admin.
 * - Pobranie listy klientow (pelnej lub filtrowanej po wyszukiwarce).
 * - Pobranie danych konkretnego klienta do trybu edycji.
 * - Walidacja danych aktualizacji klienta przesylanych metoda POST.
 * - Aktualizacja rekordu klienta w tabeli klienci.
 *
 * @behavior
 * - Brak sesji: przekierowanie do logowania pracownika.
 * - Brak uprawnien admina: odpowiedz 403.
 * - Sukces aktualizacji: przekierowanie z parametrem updated=1.
 * - Blad walidacji/zapisu: pozostanie w widoku z komunikatami bledow.
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
    renderEmployeeAccessDenied('Sekcja klienci jest dostepna tylko dla administratora.');
}

$errors = [];
$editingClient = null;
$search = trim($_GET['q'] ?? '');
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_client') {
    $clientId = (int)($_POST['client_id'] ?? 0);
    $imie = trim($_POST['imie'] ?? '');
    $nazwisko = trim($_POST['nazwisko'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $miasto = trim($_POST['miasto'] ?? '');
    $kodPocztowy = trim($_POST['kod_pocztowy'] ?? '');
    $kraj = trim($_POST['kraj'] ?? '');
    $status = trim($_POST['status'] ?? 'aktywny');

    if ($clientId <= 0) {
        $errors[] = 'Niepoprawne ID klienta.';
    }
    if ($imie === '') {
        $errors[] = 'Imie jest wymagane.';
    }
    if ($nazwisko === '') {
        $errors[] = 'Nazwisko jest wymagane.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Podaj poprawny adres email.';
    }
    if ($kraj === '') {
        $errors[] = 'Kraj jest wymagany.';
    }
    if (!in_array($status, ['aktywny', 'zablokowany'], true)) {
        $errors[] = 'Niepoprawny status klienta.';
    }

    if (!$errors) {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE klienci SET imie = ?, nazwisko = ?, email = ?, telefon = ?, miasto = ?, kod_pocztowy = ?, kraj = ?, status = ? WHERE id = ? LIMIT 1'
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssssssssi', $imie, $nazwisko, $email, $telefon, $miasto, $kodPocztowy, $kraj, $status, $clientId);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header('Location: customers.php?updated=1');
                exit;
            }

            mysqli_stmt_close($stmt);
            $errors[] = 'Nie udalo sie zapisac zmian.';
        } else {
            $errors[] = 'Blad przygotowania zapytania do bazy.';
        }
    }

    $editId = $clientId;
}

if ($editId > 0) {
    $editStmt = mysqli_prepare(
        $conn,
        'SELECT id, imie, nazwisko, email, telefon, miasto, kod_pocztowy, kraj, status FROM klienci WHERE id = ? LIMIT 1'
    );

    if ($editStmt) {
        mysqli_stmt_bind_param($editStmt, 'i', $editId);
        mysqli_stmt_execute($editStmt);
        $editResult = mysqli_stmt_get_result($editStmt);
        $editingClient = $editResult ? mysqli_fetch_assoc($editResult) : null;
        mysqli_stmt_close($editStmt);
    }
}

$clients = [];
if ($search !== '') {
    $like = '%' . $search . '%';
    $listStmt = mysqli_prepare(
        $conn,
        'SELECT id, imie, nazwisko, email, telefon, miasto, status, data_rejestracji FROM klienci WHERE imie LIKE ? OR nazwisko LIKE ? OR email LIKE ? ORDER BY id DESC'
    );

    if ($listStmt) {
        mysqli_stmt_bind_param($listStmt, 'sss', $like, $like, $like);
        mysqli_stmt_execute($listStmt);
        $listResult = mysqli_stmt_get_result($listStmt);
        while ($listResult && ($row = mysqli_fetch_assoc($listResult))) {
            $clients[] = $row;
        }
        mysqli_stmt_close($listStmt);
    }
} else {
    $listResult = mysqli_query($conn, 'SELECT id, imie, nazwisko, email, telefon, miasto, status, data_rejestracji FROM klienci ORDER BY id DESC');
    while ($listResult && ($row = mysqli_fetch_assoc($listResult))) {
        $clients[] = $row;
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
