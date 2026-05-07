<?php
/**
 * @fileoverview connection.php
 *
 * @description
 * Plik odpowiada za inicjalizacje polaczenia z baza danych MySQL.
 * Odczytuje parametry dostepowe z pliku konfiguracyjnego, tworzy polaczenie
 * mysqli i ustawia kodowanie utf8mb4 dla poprawnej obslugi znakow.
 *
 * @scope
 * - Odczyt konfiguracji bazy z config.php (db_host, db_name, db_user, db_pass).
 * - Utworzenie polaczenia przez mysqli_connect.
 * - Obsluga bledu polaczenia i zatrzymanie wykonania skryptu.
 * - Ustawienie zestawu znakow polaczenia na utf8mb4.
 *
 * @behavior
 * - Poprawna konfiguracja: zwraca aktywne polaczenie w zmiennej conn.
 * - Blad polaczenia: przerywa wykonanie i zwraca komunikat o bledzie.
 */

$config = require __DIR__ . '/../config.php';

$host = (string)($config['db_host'] ?? 'localhost');
$db   = (string)($config['db_name'] ?? '');
$user = (string)($config['db_user'] ?? 'root');
$pass = (string)($config['db_pass'] ?? '');

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Błąd połączenia: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>
