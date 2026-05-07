<?php
/**
 * @fileoverview orders.php
 *
 * @description
 * Plik odpowiada za administracyjna obsluge zamowien w panelu pracownika.
 * Umozliwia przegladanie i wyszukiwanie zamowien, edycje statusow zamowienia,
 * platnosci i dostawy oraz przypisanie pracownika i kuriera.
 *
 * @scope
 * - Weryfikacja sesji pracownika i uprawnien roli admin.
 * - Pobranie slownikow statusow, listy kurierow i aktywnych pracownikow.
 * - Obsluga aktualizacji zamowienia (POST action=update_order) w transakcji.
 * - Pobranie szczegolow edytowanego zamowienia i jego pozycji.
 * - Pobranie listy zamowien (pelnej lub filtrowanej wyszukiwarka).
 *
 * @behavior
 * - Brak sesji: przekierowanie do logowania pracownika.
 * - Brak roli admin: odpowiedz 403.
 * - Sukces aktualizacji: commit transakcji i przekierowanie z updated=1.
 * - Blad walidacji/zapisu: rollback i prezentacja komunikatow bledow.
 */

session_start();
require_once __DIR__ . '/../../db/connection.php';
require_once __DIR__ . '/access_denied.php';

if (!isset($_SESSION['worker_id'])) {
    header('Location: login.php');
    exit();
}

$workerId = (int)($_SESSION['worker_id'] ?? 0);
$workerName = $_SESSION['worker_name'] ?? 'Pracownik';
$workerRole = $_SESSION['worker_role'] ?? '';

if (!in_array($workerRole, ['admin', 'magazyn'], true)) {
    renderEmployeeAccessDenied('Nie masz uprawnien do zarzadzania zamowieniami.');
}

$errors = [];
$editingOrder = null;
$editingOrderItems = [];
$search = trim($_GET['q'] ?? '');
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function getStatusMap(mysqli $conn, string $tableName): array
{
    $map = [];
    $result = mysqli_query($conn, 'SELECT id, nazwa FROM ' . $tableName . ' ORDER BY id ASC');
    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $map[(int)$row['id']] = (string)$row['nazwa'];
    }

    return $map;
}

$orderStatuses = getStatusMap($conn, 'statusy_zamowienia');
$paymentStatuses = getStatusMap($conn, 'statusy_platnosci');
$deliveryStatuses = getStatusMap($conn, 'statusy_dostawy');

$couriersById = [];
$couriersResult = mysqli_query($conn, 'SELECT id, nazwa, aktywny FROM kurierzy WHERE aktywny = 1 ORDER BY nazwa ASC');
while ($couriersResult && ($courierRow = mysqli_fetch_assoc($couriersResult))) {
    $courierRow['id'] = (int)$courierRow['id'];
    $couriersById[$courierRow['id']] = $courierRow;
}

$workersById = [];
$workersResult = mysqli_query($conn, 'SELECT id, imie, nazwisko, rola FROM pracownicy WHERE aktywny = 1 ORDER BY rola ASC, nazwisko ASC, imie ASC');
while ($workersResult && ($workerRow = mysqli_fetch_assoc($workersResult))) {
    $workerRow['id'] = (int)$workerRow['id'];
    $workersById[$workerRow['id']] = $workerRow;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_order') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderStatusId = (int)($_POST['status_id'] ?? 0);
    $paymentStatusId = (int)($_POST['payment_status_id'] ?? 0);
    $deliveryStatusId = (int)($_POST['delivery_status_id'] ?? 0);
    $assignedWorkerIdRaw = (int)($_POST['assigned_worker_id'] ?? -1);
    $courierId = (int)($_POST['kurier_id'] ?? 0);
    $trackingNumber = trim((string)($_POST['numer_przesylki'] ?? ''));
    $notes = trim((string)($_POST['uwagi'] ?? ''));
    $internalNotes = trim((string)($_POST['notatki_wewnetrzne'] ?? ''));

    if ($orderId <= 0) {
        $errors[] = 'Niepoprawne ID zamowienia.';
    }
    if (!isset($orderStatuses[$orderStatusId])) {
        $errors[] = 'Niepoprawny status zamowienia.';
    }
    if (!isset($paymentStatuses[$paymentStatusId])) {
        $errors[] = 'Niepoprawny status platnosci.';
    }
    if (!isset($deliveryStatuses[$deliveryStatusId])) {
        $errors[] = 'Niepoprawny status dostawy.';
    }
    if ($assignedWorkerIdRaw !== 0 && !isset($workersById[$assignedWorkerIdRaw])) {
        $errors[] = 'Wybierz poprawnego pracownika.';
    }
    if (in_array($orderStatusId, [3, 4, 5], true) && $assignedWorkerIdRaw === 0) {
        $errors[] = 'Dla statusu realizacji/wysylki/dostarczenia przypisz pracownika.';
    }
    if ($courierId <= 0 || !isset($couriersById[$courierId])) {
        $errors[] = 'Kurier jest wymagany.';
    }

    if (!$errors) {
        $assignedWorkerId = $assignedWorkerIdRaw === 0 ? null : $assignedWorkerIdRaw;

        mysqli_begin_transaction($conn);

        $ok = true;

        if ($assignedWorkerId === null) {
            $orderStmt = mysqli_prepare(
                $conn,
                'UPDATE zamowienia SET status_id = ?, pracownik_id = NULL, uwagi = ?, notatki_wewnetrzne = ? WHERE id = ? LIMIT 1'
            );
            if ($orderStmt) {
                mysqli_stmt_bind_param($orderStmt, 'issi', $orderStatusId, $notes, $internalNotes, $orderId);
                $ok = $ok && mysqli_stmt_execute($orderStmt);
                mysqli_stmt_close($orderStmt);
            } else {
                $ok = false;
            }
        } else {
            $orderStmt = mysqli_prepare(
                $conn,
                'UPDATE zamowienia SET status_id = ?, pracownik_id = ?, uwagi = ?, notatki_wewnetrzne = ? WHERE id = ? LIMIT 1'
            );
            if ($orderStmt) {
                mysqli_stmt_bind_param($orderStmt, 'iissi', $orderStatusId, $assignedWorkerId, $notes, $internalNotes, $orderId);
                $ok = $ok && mysqli_stmt_execute($orderStmt);
                mysqli_stmt_close($orderStmt);
            } else {
                $ok = false;
            }
        }

        $paymentStmt = mysqli_prepare($conn, 'UPDATE platnosci SET status_id = ? WHERE zamowienie_id = ? LIMIT 1');
        if ($paymentStmt) {
            mysqli_stmt_bind_param($paymentStmt, 'ii', $paymentStatusId, $orderId);
            $ok = $ok && mysqli_stmt_execute($paymentStmt);
            mysqli_stmt_close($paymentStmt);
        } else {
            $ok = false;
        }

        $deliveryStmt = mysqli_prepare(
            $conn,
            'UPDATE dostawy SET status_id = ?, kurier_id = ?, numer_przesylki = ? WHERE zamowienie_id = ? LIMIT 1'
        );
        if ($deliveryStmt) {
            $trackingValue = $trackingNumber !== '' ? $trackingNumber : null;
            mysqli_stmt_bind_param($deliveryStmt, 'iisi', $deliveryStatusId, $courierId, $trackingValue, $orderId);
            $ok = $ok && mysqli_stmt_execute($deliveryStmt);
            mysqli_stmt_close($deliveryStmt);
        } else {
            $ok = false;
        }

        if ($ok) {
            mysqli_commit($conn);
            header('Location: orders.php?updated=1');
            exit;
        }

        mysqli_rollback($conn);
        $errors[] = 'Nie udalo sie zaktualizowac zamowienia.';
        $editId = $orderId;
    }
}

if ($editId > 0) {
    $editStmt = mysqli_prepare(
        $conn,
        'SELECT z.id, z.klient_id, z.status_id, z.pracownik_id, z.data_zamowienia, z.kwota_brutto, z.uwagi, z.notatki_wewnetrzne, k.imie, k.nazwisko, k.email, p.status_id AS payment_status_id, d.status_id AS delivery_status_id, d.kurier_id, kr.nazwa AS kurier, d.numer_przesylki FROM zamowienia z JOIN klienci k ON k.id = z.klient_id LEFT JOIN platnosci p ON p.zamowienie_id = z.id LEFT JOIN dostawy d ON d.zamowienie_id = z.id LEFT JOIN kurierzy kr ON kr.id = d.kurier_id WHERE z.id = ? LIMIT 1'
    );

    if ($editStmt) {
        mysqli_stmt_bind_param($editStmt, 'i', $editId);
        mysqli_stmt_execute($editStmt);
        $editResult = mysqli_stmt_get_result($editStmt);
        $editingOrder = $editResult ? mysqli_fetch_assoc($editResult) : null;
        mysqli_stmt_close($editStmt);
    }

    if ($editingOrder) {
        $itemsStmt = mysqli_prepare(
            $conn,
            'SELECT pz.ilosc, pz.cena_jednostkowa, pz.rabat_proc, pr.nazwa AS produkt_nazwa, pr.sku FROM pozycje_zamowienia pz JOIN produkty pr ON pr.id = pz.produkt_id WHERE pz.zamowienie_id = ? ORDER BY pz.id ASC'
        );

        if ($itemsStmt) {
            mysqli_stmt_bind_param($itemsStmt, 'i', $editId);
            mysqli_stmt_execute($itemsStmt);
            $itemsResult = mysqli_stmt_get_result($itemsStmt);
            while ($itemsResult && ($itemRow = mysqli_fetch_assoc($itemsResult))) {
                $editingOrderItems[] = $itemRow;
            }
            mysqli_stmt_close($itemsStmt);
        }
    }
}

$orders = [];
if ($search !== '') {
    $like = '%' . $search . '%';
    $listStmt = mysqli_prepare(
        $conn,
        'SELECT z.id, z.data_zamowienia, z.kwota_brutto, z.status_id, z.pracownik_id, k.imie, k.nazwisko, k.email, sz.nazwa AS status_nazwa, sp.nazwa AS payment_status_nazwa, sd.nazwa AS delivery_status_nazwa, w.imie AS worker_imie, w.nazwisko AS worker_nazwisko FROM zamowienia z JOIN klienci k ON k.id = z.klient_id LEFT JOIN statusy_zamowienia sz ON sz.id = z.status_id LEFT JOIN platnosci p ON p.zamowienie_id = z.id LEFT JOIN statusy_platnosci sp ON sp.id = p.status_id LEFT JOIN dostawy d ON d.zamowienie_id = z.id LEFT JOIN statusy_dostawy sd ON sd.id = d.status_id LEFT JOIN pracownicy w ON w.id = z.pracownik_id WHERE z.id LIKE ? OR k.imie LIKE ? OR k.nazwisko LIKE ? OR k.email LIKE ? ORDER BY z.id DESC'
    );

    if ($listStmt) {
        mysqli_stmt_bind_param($listStmt, 'ssss', $like, $like, $like, $like);
        mysqli_stmt_execute($listStmt);
        $listResult = mysqli_stmt_get_result($listStmt);
        while ($listResult && ($row = mysqli_fetch_assoc($listResult))) {
            $orders[] = $row;
        }
        mysqli_stmt_close($listStmt);
    }
} else {
    $listResult = mysqli_query(
        $conn,
        'SELECT z.id, z.data_zamowienia, z.kwota_brutto, z.status_id, z.pracownik_id, k.imie, k.nazwisko, k.email, sz.nazwa AS status_nazwa, sp.nazwa AS payment_status_nazwa, sd.nazwa AS delivery_status_nazwa, w.imie AS worker_imie, w.nazwisko AS worker_nazwisko FROM zamowienia z JOIN klienci k ON k.id = z.klient_id LEFT JOIN statusy_zamowienia sz ON sz.id = z.status_id LEFT JOIN platnosci p ON p.zamowienie_id = z.id LEFT JOIN statusy_platnosci sp ON sp.id = p.status_id LEFT JOIN dostawy d ON d.zamowienie_id = z.id LEFT JOIN statusy_dostawy sd ON sd.id = d.status_id LEFT JOIN pracownicy w ON w.id = z.pracownik_id ORDER BY z.id DESC'
    );
    while ($listResult && ($row = mysqli_fetch_assoc($listResult))) {
        $orders[] = $row;
    }
}
