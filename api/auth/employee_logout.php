<?php
/**
 * @fileoverview employee_logout.php
 *
 * @description
 * Endpoint odpowiada za bezpieczne wylogowanie pracownika z panelu administracyjnego.
 * Usuwa dane sesyjne zwiazane z zalogowanym pracownikiem oraz uniewaznia cookie
 * zapamietanego logowania, jesli zostalo ustawione.
 *
 * @scope
 * - Inicjalizacja sesji aplikacji.
 * - Usuniecie zmiennych sesyjnych worker_id, worker_name i worker_role.
 * - Usuniecie cookie remember_worker.
 *
 * @behavior
 * - Zawsze przekierowuje uzytkownika na strone logowania pracownika.
 */

session_start();

unset($_SESSION['worker_id']);
unset($_SESSION['worker_name']);
unset($_SESSION['worker_role']);


if (isset($_COOKIE['remember_worker'])) {
    setcookie('remember_worker', '', time() - 3600, '/');
}

header("Location: ../../public/employee/login.php");
exit();
?>
