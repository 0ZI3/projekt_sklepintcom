<?php
/**
 * @fileoverview logout.php
 *
 * @description
 * Endpoint odpowiada za wylogowanie klienta sklepu.
 * Usuwa dane sesyjne uzytkownika, niszczy aktywna sesje oraz uniewaznia cookie
 * zapamietanego logowania, jezeli zostalo ustawione.
 *
 * @scope
 * - Inicjalizacja i zamkniecie sesji uzytkownika.
 * - Usuniecie zmiennych sesyjnych user_id i user_name.
 * - Usuniecie cookie remember_user.
 *
 * @behavior
 * - Zawsze przekierowuje uzytkownika na strone glowna sklepu.
 */

session_start();
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
session_destroy();

if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, "/");
}

header("Location: ../../public/index.php");
exit();
?>
