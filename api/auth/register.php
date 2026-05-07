<?php
/**
 * @fileoverview register.php
 *
 * @description
 * Endpoint odpowiada za rejestracje nowego klienta sklepu.
 * Odbiera dane formularza, wykonuje walidacje biznesowe i techniczne,
 * normalizuje dane osobowe, zapisuje nowego klienta w bazie oraz przekierowuje
 * uzytkownika na odpowiednia strone w zaleznosci od wyniku operacji.
 *
 * @scope
 * - Obsluga wyłącznie zadan POST dla formularza rejestracji.
 * - Walidacja wymaganych pol, adresu email, hasla i akceptacji regulaminu.
 * - Normalizacja imienia i nazwiska do spójnego formatu zapisu.
 * - Weryfikacja unikalnosci adresu email.
 * - Utworzenie konta klienta z hashem hasla (pepper + SHA-512).
 *
 * @behavior
 * - Sukces: zapis klienta i przekierowanie do logowania z parametrem registered=1.
 * - Blad walidacji lub zapisu: przekierowanie z odpowiednim kodem bledu.
 */

session_start();
require_once '../../db/connection.php';
$config = require '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/customer/register.php');
    exit();
}

$imie = trim($_POST['imie'] ?? '');
$nazwisko = trim($_POST['nazwisko'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';
$acceptedTerms = isset($_POST['terms']);

function normalizeName(string $value): string
{
    $singleSpaced = preg_replace('/\s+/', ' ', trim($value));
    $singleSpaced = $singleSpaced ?? '';
    $lowerCase = mb_strtolower($singleSpaced, 'UTF-8');
    return mb_convert_case($lowerCase, MB_CASE_TITLE, 'UTF-8');
}

if (
    $imie === '' ||
    $nazwisko === '' ||
    $email === '' ||
    $password === '' ||
    $passwordConfirm === '' ||
    !$acceptedTerms
) {
    header('Location: ../../public/customer/register.php?error=validation');
    exit();
}

$imie = normalizeName($imie);
$nazwisko = normalizeName($nazwisko);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../../public/customer/register.php?error=invalid_email');
    exit();
}

if ($password !== $passwordConfirm) {
    header('Location: ../../public/customer/register.php?error=password_mismatch');
    exit();
}

if (strlen($password) < 8 || !preg_match('/[^a-zA-Z0-9]/', $password)) {
    header('Location: ../../public/customer/register.php?error=weak_password');
    exit();
}

$emailLower = mb_strtolower($email, 'UTF-8');

$checkStmt = mysqli_prepare($conn, 'SELECT id FROM klienci WHERE email = ? LIMIT 1');
if (!$checkStmt) {
    header('Location: ../../public/customer/login.php?error=registration_failed');
    exit();
}

mysqli_stmt_bind_param($checkStmt, 's', $emailLower);
mysqli_stmt_execute($checkStmt);
$existingResult = mysqli_stmt_get_result($checkStmt);
$emailExists = $existingResult && mysqli_num_rows($existingResult) > 0;
mysqli_stmt_close($checkStmt);

if ($emailExists) {
    header('Location: ../../public/customer/register.php?error=email_exists');
    exit();
}

$pepper = $config['pepper'];
$passwordHash = hash('sha512', $pepper . $password);

$insertStmt = mysqli_prepare(
    $conn,
    "INSERT INTO klienci (imie, nazwisko, email, haslo_hash_sha512, data_rejestracji, status) VALUES (?, ?, ?, ?, NOW(), 'aktywny')"
);

if (!$insertStmt) {
    header('Location: ../../public/customer/login.php?error=registration_failed');
    exit();
}

mysqli_stmt_bind_param($insertStmt, 'ssss', $imie, $nazwisko, $emailLower, $passwordHash);
$ok = mysqli_stmt_execute($insertStmt);
mysqli_stmt_close($insertStmt);

if ($ok) {
    header('Location: ../../public/customer/login.php?registered=1');
    exit();
}

header('Location: ../../public/customer/login.php?error=registration_failed');
exit();
