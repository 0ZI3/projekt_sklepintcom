/**
 * @fileoverview register_validation.js
 *
 * @description
 * Skrypt odpowiada za klientowa walidacje hasla w formularzu rejestracji.
 * Na biezaco sprawdza spelnienie wymagan bezpieczenstwa hasla oraz zgodnosc
 * pola potwierdzenia hasla, a nastepnie prezentuje komunikaty zwrotne.
 *
 * @scope
 * - Walidacja dlugosci hasla (minimum 8 znakow).
 * - Walidacja obecnosci co najmniej jednego znaku specjalnego.
 * - Walidacja zgodnosci hasla i potwierdzenia hasla.
 * - Ustawienie komunikatow UI i native validity przez setCustomValidity.
 *
 * @behavior
 * - Reakcja na zdarzenie input w polach hasla i potwierdzenia.
 * - Dynamiczna aktualizacja komunikatow o stanie walidacji.
 * - Blokada wysylki formularza przy niespelnionych warunkach.
 */

const passwordInput = document.getElementById("password");
const passwordConfirmInput = document.getElementById("password_confirm");
const passwordHint = document.getElementById("password-hint");
const passwordMatch = document.getElementById("password-match");

if (passwordInput && passwordConfirmInput && passwordHint && passwordMatch) {
  const specialCharRegex = /[^a-zA-Z0-9]/;

  function validatePasswords() {
    const password = passwordInput.value;
    const confirm = passwordConfirmInput.value;
    const hasMinLength = password.length >= 8;
    const hasSpecialChar = specialCharRegex.test(password);

    if (password.length === 0) {
      passwordHint.textContent =
        "Haslo musi miec minimum 8 znakow i przynajmniej 1 znak specjalny.";
      passwordHint.className = "mt-2 text-xs text-gray-500";
    } else if (!hasMinLength) {
      passwordHint.textContent = "Haslo jest za krotkie (minimum 8 znakow).";
      passwordHint.className = "mt-2 text-xs text-red-600";
    } else if (!hasSpecialChar) {
      passwordHint.textContent =
        "Haslo musi zawierac co najmniej 1 znak specjalny.";
      passwordHint.className = "mt-2 text-xs text-red-600";
    } else {
      passwordHint.textContent = "Haslo spelnia wymagania.";
      passwordHint.className = "mt-2 text-xs text-emerald-600";
    }

    if (confirm.length === 0) {
      passwordMatch.textContent = "Wpisz to samo haslo ponownie.";
      passwordMatch.className = "mt-2 text-xs text-gray-500";
    } else if (password !== confirm) {
      passwordMatch.textContent = "Hasla nie sa takie same.";
      passwordMatch.className = "mt-2 text-xs text-red-600";
    } else {
      passwordMatch.textContent = "Hasla sa zgodne.";
      passwordMatch.className = "mt-2 text-xs text-emerald-600";
    }

    const passwordValid = hasMinLength && hasSpecialChar;
    passwordInput.setCustomValidity(
      passwordValid ? "" : "Haslo musi miec min. 8 znakow i znak specjalny.",
    );

    const passwordsMatch = confirm.length > 0 && password === confirm;
    passwordConfirmInput.setCustomValidity(
      passwordsMatch ? "" : "Hasla musza byc identyczne.",
    );
  }

  passwordInput.addEventListener("input", validatePasswords);
  passwordConfirmInput.addEventListener("input", validatePasswords);
}
