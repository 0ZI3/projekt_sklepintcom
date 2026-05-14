# SklepIntCom - dokumentacja projektu

## 1. Opis projektu

SklepIntCom to aplikacja e-commerce oparta o PHP + MySQL (bez frameworka),  
z podziałem na:

- część publiczną sklepu dla klienta,
- panel klienta (konto, dane, historia zamówień),
- panel pracowniczy (dashboard, produkty, zamówienia, klienci, analityka),
- API koszyka i checkout,
- integracje płatności Stripe Checkout.

login do konta admina - admin@sklepint.local hasło - Admin

---

## 2. Stos technologiczny

- **Backend:** PHP (styl proceduralny + funkcje pomocnicze)
- **Baza danych:** MariaDB
- **Dostęp do bazy:** `mysqli`
- **Frontend:** HTML, CSS (Tailwind), JavaScript
- **Płatności online:** Stripe Checkout (HTTP API przez `cURL`)
- **Środowisko:** Apache + PHP + MariaDB (XAMPP)

---

## 3. Architektura i struktura katalogów

### 3.1 Katalogi główne

- `public/` – publiczny web root (strony dostępne dla przeglądarki)
- `api/` – logika backendowa i endpointy JSON/HTML
- `db/` – inicjalizacja połączenia z bazą
- `sql/` – zrzut struktury i danych bazy
- `docs/` – dokumentacja

### 3.2 Konfiguracja

Plik `config.php` zawiera:

- parametry połączenia z bazą (`db_host`, `db_name`, `db_user`, `db_pass`),
- `pepper` do hashowania haseł,
- ustawienia Stripe (`stripe_secret_key`, `stripe_currency`).

Plik `db/connection.php`:

- ładuje konfigurację,
- tworzy połączenie `mysqli`,
- ustawia kodowanie `utf8mb4`.

---

## 4. Role i uprawnienia

### 4.1 Klient

- rejestracja i logowanie,
- przeglądanie produktów i kategorii,
- obsługa koszyka,
- checkout i płatność,
- zarządzanie kontem,
- historia zamówień.

### 4.2 Pracownik

Autoryzacja oparta o sesję (`worker_id`, `worker_name`, `worker_role`).

Panel pracownika:

- dashboard,
- zarządzanie produktami,
- zarządzanie zamówieniami,
- zarządzanie klientami,
- analityka i wykresy,
- logi systemowe.

---

## 5. Przepływy biznesowe

### 5.1 Rejestracja klienta

**Plik:** `api/auth/register.php`

1. Odczyt danych formularza
2. Walidacja danych
3. Walidacja hasła (min. 8 znaków + znak specjalny)
4. Sprawdzenie unikalności email
5. Hash: `sha512(pepper + hasło)`
6. Zapis do `klienci`
7. Przekierowanie

---

### 5.2 Logowanie klienta

**Plik:** `api/auth/login.php`

1. Weryfikacja danych
2. Sprawdzenie statusu konta
3. Logowanie próby
4. Ustawienie sesji
5. Cookie `remember_user` (opcjonalnie)

---

### 5.3 Koszyk

**Moduł:** `api/cart/*`

Funkcjonalności:

- koszyk gościa i użytkownika,
- token sesji (`cart_session_token`),
- scalanie koszyków po logowaniu,
- kontrola stanów magazynowych,
- podsumowanie koszyka.

---

### 5.4 Checkout i zamówienie

**Plik:** `api/store/payment.php`

Metody:

- `karta` → Stripe
- `pobranie` → odbiór osobisty

Walidacje:

- sesja klienta,
- dane adresowe,
- koszyk i dostawa,
- blokada produktów (`FOR UPDATE`).

---

### 5.5 Stripe

**Plik:** `api/stripe/create_checkout_session.php`

1. Weryfikacja użytkownika
2. Odczyt koszyka
3. Obliczenie kwoty
4. Utworzenie sesji Stripe
5. Zwrot JSON / redirect

---

## 6. API

### 6.1 Autoryzacja

- `POST api/auth/register.php`
- `POST api/auth/login.php`
- `POST api/auth/logout.php`
- `POST api/auth/employee_login.php`
- `POST api/auth/employee_logout.php`

---

### 6.2 Koszyk (JSON)

- `POST api/cart/add.php`
- `POST api/cart/update.php`
- `POST api/cart/remove.php`
- `GET api/cart/count.php`
- `GET api/cart/details.php`

---

### 6.3 Produkty i kategorie

- `GET api/categories.php`
- `GET api/products.php`
- `POST api/fetch_more_products.php`

---

### 6.4 Płatności

- `POST api/stripe/create_checkout_session.php`
- `POST api/store/payment.php`

---

### 6.5 Panel klienta

- `api/customer/data.php`
- `api/customer/orders.php`
- `api/user/update_details.php`

---

### 6.6 Panel pracownika

- `api/employee/dashboard.php`
- `api/employee/products.php`
- `api/employee/orders.php`
- `api/employee/customers.php`
- `api/employee/charts_data.php`
- `api/employee/triggers.php`

---

## 7. Model danych

Plik: `sql/databaseDump.sql`

Główne tabele:

- `klienci`
- `pracownicy`
- `logowania_użytkowników`
- `kategorie`
- `produkty`
- `zdjęcia_produktów`
- `koszyki`
- `pozycje_koszyka`
- `zamówienia`
- `pozycje_zamówienia`
- `płatności`
- `dostawy`
- `statusy_zamówienia`
- `statusy_płatności`
- `statusy_dostawy`
- `kurierzy`
- `logi_systemowe`

---

## 8. Bezpieczeństwo

- kontrola sesji,
- role i autoryzacja,
- hashowanie + pepper,
- walidacja danych,
- transakcje SQL,
- blokady magazynowe.

---

## 9. Frontend

`public/` zawiera:

- listing produktów,
- szczegóły produktu,
- koszyk,
- checkout,
- panel klienta,
- panel pracownika.

---

## 10. Pełne drzewo struktury plików

```
├── 📁 api
│   ├── 📁 auth
│   │   ├── 📄 address_autocapitalize.js
│   │   ├── 🐘 employee_login.php
│   │   ├── 🐘 employee_logout.php
│   │   ├── 🐘 login.php
│   │   ├── 🐘 logout.php
│   │   ├── 📄 payment_validation.js
│   │   ├── 🐘 register.php
│   │   └── 📄 register_validation.js
│   ├── 📁 cart
│   │   ├── 🐘 add.php
│   │   ├── 🐘 count.php
│   │   ├── 🐘 details.php
│   │   ├── 🐘 helpers.php
│   │   ├── 🐘 remove.php
│   │   └── 🐘 update.php
│   ├── 📁 customer
│   │   ├── 🐘 data.php
│   │   ├── 🐘 login.php
│   │   ├── 🐘 orders.php
│   │   └── 🐘 panel.php
│   ├── 📁 employee
│   │   ├── 🐘 access_denied.php
│   │   ├── 📄 charts.js
│   │   ├── 🐘 charts.php
│   │   ├── 🐘 charts_data.php
│   │   ├── 🐘 customers.php
│   │   ├── 🐘 dashboard.php
│   │   ├── 🐘 orders.php
│   │   ├── 🐘 products.php
│   │   └── 🐘 triggers.php
│   ├── 📁 store
│   │   ├── 🐘 index.php
│   │   ├── 🐘 payment.php
│   │   ├── 🐘 payment_success.php
│   │   ├── 🐘 product.php
│   │   └── 🐘 products.php
│   ├── 📁 stripe
│   │   └── 🐘 create_checkout_session.php
│   ├── 📁 user
│   │   └── 🐘 update_details.php
│   ├── 🐘 categories.php
│   ├── 🐘 fetch_more_products.php
│   └── 🐘 products.php
├── 📁 db
│   └── 🐘 connection.php
├── 📁 public
│   ├── 📁 assets
│   │   ├── 📁 css
│   │   │   └── 🎨 app.css
│   │   └── 📁 img
│   │       └── 🖼️ products_photo
│   ├── 📁 customer
│   │   ├── 🐘 customer_data.php
│   │   ├── 🐘 customer_panel.php
│   │   ├── 🐘 fill_details.php
│   │   ├── 🐘 forgot_password.php
│   │   ├── 🐘 login.php
│   │   ├── 🐘 orders.php
│   │   └── 🐘 register.php
│   ├── 📁 employee
│   │   ├── 🐘 charts.php
│   │   ├── 🐘 customers.php
│   │   ├── 🐘 employee_panel.php
│   │   ├── 🐘 login.php
│   │   ├── 🐘 orders.php
│   │   ├── 🐘 products.php
│   │   └── 🐘 triggers.php
│   ├── 🐘 cart.php
│   ├── 🐘 index.php
│   ├── 🐘 payment.php
│   ├── 🐘 payment_success.php
│   ├── 🐘 privacy.php
│   ├── 🐘 product.php
│   ├── 🐘 products.php
│   └── 🐘 terms.php
├── 📁 sql
│   └── 📄 databaseDump.sql
├── 🐘 config.php
└── 🌐 index.html
```
