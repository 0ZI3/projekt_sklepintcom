<?php
/**
 * @fileoverview panel.php
 *
 * @description
 * Plik odpowiada za przygotowanie kontekstu sesji dla panelu klienta.
 * Weryfikuje, czy uzytkownik jest zalogowany, i udostepnia podstawowe dane
 * potrzebne do renderowania widoku panelu.
 *
 * @scope
 * - Inicjalizacja sesji klienta.
 * - Kontrola dostepu do panelu na podstawie user_id.
 * - Przygotowanie nazwy uzytkownika do wyswietlenia w interfejsie.
 *
 * @behavior
 * - Brak sesji: przekierowanie na strone logowania klienta.
 * - Aktywna sesja: ustawienie danych potrzebnych dla widoku panelu.
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Kliencie';
