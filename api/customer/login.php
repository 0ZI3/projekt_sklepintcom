<?php
/**
 * @fileoverview login.php
 *
 * @description
 * Plik odpowiada za przygotowanie flag stanu widoku logowania klienta.
 * Odczytuje parametry zapytania GET i mapuje je na zmienne sterujace komunikatami
 * interfejsu, np. blad logowania, sukces rejestracji lub blad rejestracji.
 *
 * @scope
 * - Odczyt parametru error z query string.
 * - Odczyt parametru registered z query string.
 * - Ustawienie flag logicznych dla warunkowego renderowania komunikatow.
 *
 * @behavior
 * - invalid_credentials: aktywacja komunikatu o blednych danych logowania.
 * - registered=1: aktywacja komunikatu o poprawnej rejestracji.
 * - registration_failed: aktywacja komunikatu o nieudanej rejestracji.
 */

$showInvalidCredentials = isset($_GET['error']) && $_GET['error'] === 'invalid_credentials';
$showRegistered = isset($_GET['registered']) && $_GET['registered'] === '1';
$showRegistrationFailed = isset($_GET['error']) && $_GET['error'] === 'registration_failed';
