<?php
/**
 * @fileoverview employee_login.php
 *
 * @description
 * Endpoint odpowiada za uwierzytelnienie pracownika na podstawie danych formularza
 * logowania. Po poprawnej weryfikacji tworzy sesje pracownika i przekierowuje
 * uzytkownika do panelu pracowniczego.
 *
 * @scope
 * - Odczyt danych POST: email, haslo, remember-me.
 * - Walidacja danych logowania wobec tabeli pracownicy (tylko aktywni pracownicy).
 * - Ustawienie zmiennych sesyjnych identyfikujacych zalogowanego pracownika.
 * - Opcjonalne ustawienie cookie remember_worker.
 *
 * @behavior
 * - Sukces: zapis sesji/cookie i przekierowanie do panelu pracownika.
 * - Blad logowania: przekierowanie do formularza logowania z kodem bledu.
 */

session_start();
require_once '../../db/connection.php';
$config = require '../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember-me']);

    $pepper = $config['pepper'];
    $passwordHash = hash('sha512', $pepper . $password);

    $sql = "SELECT id, imie, nazwisko, email, rola FROM pracownicy WHERE email = '$email' AND haslo_hash_sha512 = '$passwordHash' AND aktywny = 1 LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $worker = mysqli_fetch_assoc($result);


        $_SESSION['worker_id'] = $worker['id'];
        $_SESSION['worker_name'] = $worker['imie'] . ' ' . $worker['nazwisko'];
        $_SESSION['worker_role'] = $worker['rola'];

        if ($remember) {
            setcookie('remember_worker', base64_encode($worker['email'] . '|' . $passwordHash), time() + (86400 * 30), "/");
        }

        header("Location: ../../public/employee/employee_panel.php");
        exit();
    } else {
        header("Location: ../../public/employee/login.php?error=invalid_credentials");
        exit();
    }
}
?>
