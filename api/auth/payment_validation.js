/**
 * @fileoverview payment_validation.js
 *
 * @description
 * Skrypt odpowiada za klientowa walidacje danych wysylki oraz synchronizacje
 * metod platnosci z wybrana metoda dostawy w formularzu platnosci.
 * Dodatkowo aktualizuje podsumowanie kosztow zamowienia po zmianie dostawy.
 *
 * @scope
 * - Walidacja danych wysylkowych (telefon, adres, kod pocztowy, miasto, kraj).
 * - Wyswietlanie komunikatow bledow i linku do poprawy danych klienta.
 * - Aktualizacja kosztu dostawy i lacznej wartosci zamowienia.
 * - Ograniczenie metody platnosci "pobranie" do dostawy typu pickup.
 *
 * @behavior
 * - Inicjalizacja po zaladowaniu DOM: ustawienie kosztow i stanu opcji platnosci.
 * - Zmiana metody dostawy: przeliczenie sum oraz synchronizacja opcji platnosci.
 * - Wyslanie formularza: blokada submit w przypadku bledow walidacji.
 */

document.addEventListener("DOMContentLoaded", function () {
  const paymentForm = document.getElementById("payment-form");
  const errorBox = document.getElementById("client-validation-error");

  const radios = Array.from(
    document.querySelectorAll('input[name="delivery_method"]'),
  );
  const paymentRadios = Array.from(
    document.querySelectorAll('input[name="payment_method"]'),
  );
  const cardRadio = paymentRadios.find(function (r) {
    return r.value === "karta";
  });
  const pickupPaymentRadio = paymentRadios.find(function (r) {
    return r.value === "pobranie";
  });
  const pickupPaymentLabel = pickupPaymentRadio
    ? pickupPaymentRadio.closest("label")
    : null;
  const deliveryCostEl = document.getElementById("delivery-cost");
  const totalEl = document.getElementById("total-value");
  const productsTotalRaw = parseFloat(
    document.getElementById("order-summary")?.dataset?.productsTotal ?? "0",
  );

  function textById(id) {
    const el = document.getElementById(id);
    return (el?.textContent || "").trim();
  }

  function formatPLN(value) {
    return value.toFixed(2).replace(".", ",") + " zł";
  }

  function updateTotals(cost) {
    const total = Math.round((productsTotalRaw + cost) * 100) / 100;
    if (deliveryCostEl) {
      deliveryCostEl.textContent = formatPLN(cost);
    }
    if (totalEl) {
      totalEl.textContent = formatPLN(total);
    }
  }

  function syncPaymentWithDelivery(deliveryRadio) {
    if (!pickupPaymentRadio) {
      return;
    }

    const isPickup = deliveryRadio && deliveryRadio.value === "pickup";
    pickupPaymentRadio.disabled = !isPickup;

    if (pickupPaymentLabel) {
      pickupPaymentLabel.classList.toggle("opacity-50", !isPickup);
      pickupPaymentLabel.classList.toggle("cursor-not-allowed", !isPickup);
    }

    if (!isPickup && pickupPaymentRadio.checked && cardRadio) {
      cardRadio.checked = true;
    }
  }

  function validateShippingData() {
    const phone = textById("ship-phone");
    const street = textById("ship-street");
    const house = textById("ship-house");
    const flat = textById("ship-flat");
    const city = textById("ship-city");
    const postcode = textById("ship-postcode");
    const country = textById("ship-country");

    const phoneRegex = /^\+?[0-9\s\-()]{7,20}$/;
    const postcodeRegex = /^[0-9]{2}-[0-9]{3}$/;
    const streetRegex = /^[^<>]{2,150}$/;
    const numberRegex = /^[0-9A-Za-z\-/]{1,20}$/;
    const cityCountryRegex = /^[^<>]{2,120}$/;

    const issues = [];

    if (!phoneRegex.test(phone)) {
      issues.push("Numer telefonu ma niepoprawny format.");
    }
    if (!postcodeRegex.test(postcode)) {
      issues.push("Kod pocztowy musi mieć format 00-000.");
    }
    if (!streetRegex.test(street)) {
      issues.push("Ulica ma niepoprawny format.");
    }
    if (!numberRegex.test(house)) {
      issues.push("Numer domu ma niepoprawny format.");
    }
    if (flat !== "" && !numberRegex.test(flat)) {
      issues.push("Numer lokalu ma niepoprawny format.");
    }
    if (!cityCountryRegex.test(city)) {
      issues.push("Miasto ma niepoprawny format.");
    }
    if (!cityCountryRegex.test(country)) {
      issues.push("Kraj ma niepoprawny format.");
    }

    return issues;
  }

  function renderValidationErrors(issues) {
    if (!errorBox) {
      return;
    }

    if (!issues.length) {
      errorBox.classList.add("hidden");
      errorBox.innerHTML = "";
      return;
    }

    const itemsHtml = issues
      .map(function (msg) {
        return "<p>" + msg + "</p>";
      })
      .join("");

    errorBox.innerHTML =
      itemsHtml +
      '<p class="mt-2"><a href="customer/fill_details.php?redirect=../../public/payment.php" class="font-semibold underline">Popraw dane wysylki</a></p>';
    errorBox.classList.remove("hidden");
  }

  radios.forEach(function (r) {
    r.addEventListener("change", function () {
      const cost = parseFloat(this.dataset.cost || "0");
      updateTotals(cost);
      syncPaymentWithDelivery(this);
    });
  });

  const initial =
    radios.find(function (r) {
      return r.checked;
    }) || radios[0];
  if (initial) {
    updateTotals(parseFloat(initial.dataset.cost || "0"));
    syncPaymentWithDelivery(initial);
  }

  const initialIssues = validateShippingData();
  renderValidationErrors(initialIssues);

  if (paymentForm) {
    paymentForm.addEventListener("submit", function (event) {
      const issues = validateShippingData();
      renderValidationErrors(issues);

      if (issues.length) {
        event.preventDefault();
        errorBox?.scrollIntoView({ behavior: "smooth", block: "center" });
      }
    });
  }
});
