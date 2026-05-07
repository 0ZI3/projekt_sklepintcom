<?php
/**
 * @fileoverview charts.php
 *
 * @description
 * Plik odpowiada za przygotowanie kontekstu dostepu do widoku wykresow
 * w panelu pracowniczym. Weryfikuje sesje pracownika i uprawnienia roli admin,
 * a nastepnie udostepnia dane potrzebne do renderowania strony.
 *
 * @scope
 * - Inicjalizacja sesji i dostepu do bazy danych.
 * - Kontrola autoryzacji pracownika (worker_id).
 * - Kontrola uprawnien administracyjnych (worker_role = admin).
 * - Przygotowanie danych pracownika do wykorzystania w widoku.
 *
 * @behavior
 * - Brak sesji pracownika: przekierowanie do strony logowania.
 * - Brak roli admin: odpowiedz 403 i komunikat o braku uprawnien.
 * - Poprawna autoryzacja: przygotowanie danych dla widoku wykresow.
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

if (!in_array($workerRole, ['admin', 'magazyn'], true)) {
    renderEmployeeAccessDenied('Nie masz uprawnien do sekcji wykresow.');
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
