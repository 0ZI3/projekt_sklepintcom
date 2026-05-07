<?php
/**
 * @fileoverview login.php
 *
 * @description
 * Endpoint odpowiada za logowanie klienta sklepu na podstawie danych formularza.
 * Weryfikuje dane uwierzytelniajace, zapisuje informacje o probie logowania
 * (sukces lub niepowodzenie), ustawia dane sesyjne oraz opcjonalnie cookie
 * zapamietanego logowania.
 *
 * @scope
 * - Odczyt danych POST: email, haslo, remember-me.
 * - Uwierzytelnienie klienta o statusie aktywnym w tabeli klienci.
 * - Rejestrowanie prob logowania w logowania_uzytkownikow.
 * - Ustawienie sesji user_id i user_name oraz opcjonalnego cookie remember_user.
 *
 * @behavior
 * - Sukces: zapis sesji/cookie i przekierowanie na strone glowna sklepu.
 * - Blad logowania: rejestracja nieudanej proby i przekierowanie na formularz logowania.
 */

session_start();
require_once '../../db/connection.php';
$config = require '../../config.php';

function logUserLoginAttempt(mysqli $conn, int $clientId, int $isSuccess, ?string $reason = null): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 255);

    $sql = 'INSERT INTO logowania_uzytkownikow (klient_id, data_logowania, ip, user_agent, czy_sukces, powod) VALUES (?, NOW(), ?, ?, ?, ?)';
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, 'issis', $clientId, $ip, $userAgent, $isSuccess, $reason);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember-me']);

    $pepper = $config['pepper'];
    $passwordHash = hash('sha512', $pepper . $password);

    $sql = "SELECT id, imie, nazwisko, email FROM klienci WHERE email = '$email' AND haslo_hash_sha512 = '$passwordHash' AND status = 'aktywny' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        logUserLoginAttempt($conn, (int)$user['id'], 1, null);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['imie'] . ' ' . $user['nazwisko'];

        if ($remember) {
            setcookie('remember_user', base64_encode($user['email'] . '|' . $passwordHash), time() + (86400 * 30), "/");
        }

        header("Location: ../../public/index.php");
        exit();
    } else {
        $clientResult = mysqli_query($conn, "SELECT id FROM klienci WHERE email = '$email' LIMIT 1");
        if ($clientResult && mysqli_num_rows($clientResult) > 0) {
            $client = mysqli_fetch_assoc($clientResult);
            logUserLoginAttempt($conn, (int)$client['id'], 0, 'invalid_credentials');
        }

        header("Location: ../../public/customer/login.php?error=invalid_credentials");
        exit();
    }
}
?>
