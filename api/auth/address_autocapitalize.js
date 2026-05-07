/**
 * @fileoverview address_autocapitalize.js
 *
 * @description
 * Skrypt odpowiada za standaryzacje danych adresowych wpisywanych w formularzach.
 * Dziala na polach oznaczonych atrybutami data-* i automatycznie koryguje format
 * wartosci podczas interakcji uzytkownika oraz tuz przed wyslaniem formularza.
 *
 * @scope
 * - data-capitalize-first="true": kapitalizacja pierwszej litery wartosci.
 * - data-house-uppercase-last="true": kapitalizacja ostatniej litery (jesli jest litera).
 *
 * @behavior
 * - Zdarzenie blur: normalizacja wartosci pojedynczego pola po utracie fokusu.
 * - Zdarzenie submit: normalizacja wszystkich obslugiwanych pol przed wysylka.
 */

document.addEventListener("DOMContentLoaded", function () {
  const fields = Array.from(
    document.querySelectorAll('input[data-capitalize-first="true"]'),
  );
  const houseNumberFields = Array.from(
    document.querySelectorAll('input[data-house-uppercase-last="true"]'),
  );

  function capitalizeFirstLetter(value) {
    const trimmed = String(value || "").trim();
    if (!trimmed) {
      return "";
    }

    return trimmed.charAt(0).toUpperCase() + trimmed.slice(1);
  }

  function applyToField(field) {
    if (!field) {
      return;
    }

    field.value = capitalizeFirstLetter(field.value);
  }

  function uppercaseLastLetter(value) {
    const trimmed = String(value || "").trim();
    if (!trimmed) {
      return "";
    }

    const lastChar = trimmed.slice(-1);
    if (!/[a-zA-Z]/.test(lastChar)) {
      return trimmed;
    }

    return trimmed.slice(0, -1) + lastChar.toUpperCase();
  }

  function applyHouseNumberRule(field) {
    if (!field) {
      return;
    }

    field.value = uppercaseLastLetter(field.value);
  }

  fields.forEach(function (field) {
    field.addEventListener("blur", function () {
      applyToField(field);
    });
  });

  houseNumberFields.forEach(function (field) {
    field.addEventListener("blur", function () {
      applyHouseNumberRule(field);
    });
  });

  const allFields = fields.concat(houseNumberFields);
  const forms = Array.from(
    new Set(
      allFields
        .map(function (field) {
          return field.form;
        })
        .filter(Boolean),
    ),
  );

  forms.forEach(function (form) {
    form.addEventListener("submit", function () {
      fields.forEach(applyToField);
      houseNumberFields.forEach(applyHouseNumberRule);
    });
  });
});
