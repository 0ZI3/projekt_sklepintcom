/*
 * @fileoverview databaseDump.sql
 *
 * @description
 * Glowny skrypt inicjalizacyjny bazy danych projektu SklepIntCom.
 * Plik tworzy kompletna strukture relacyjna, warstwe integralnosci, zestaw danych
 * startowych oraz obiekty pomocnicze (widoki i triggery) wspierajace logike sklepu.
 *
 * Skrypt zostal zaprojektowany do uruchomienia w srodowisku deweloperskim i testowym
 * jako deterministyczny punkt startowy: odtworzenie schematu, zaladowanie danych,
 * przygotowanie warstwy raportowej i audytowej oraz przywrocenie ograniczen kluczy obcych.
 *
 * @scope
 * - Tworzenie i wybor bazy danych: DROP DATABASE, CREATE DATABASE, USE.
 * - Definicje tabel domenowych:
 *   klienci, pracownicy, logowania_uzytkownikow,
 *   kategorie, produkty, zdjecia_produktow,
 *   koszyki, pozycje_koszyka,
 *   zamowienia, pozycje_zamowienia,
 *   platnosci, dostawy,
 *   statusy_zamowienia, statusy_dostawy, statusy_platnosci,
 *   kurierzy, logi_systemowe.
 * - Ograniczenia i relacje:
 *   klucze glowne, unikalne, indeksy oraz klucze obce z politykami ON DELETE/ON UPDATE.
 * - Logika integralnosci biznesowej na poziomie DB:
 *   triggery walidujace i triggery audytowe.
 * - Dane referencyjne i testowe:
 *   statusy, kurierzy, produkty, koszyki, zamowienia, platnosci, dostawy, logi.
 * - Warstwa raportowa:
 *   widoki administracyjne do panelu i analiz operacyjnych.
 *
 * @behavior
 * 1) Reset i przygotowanie kontekstu:
 *    - Istniejaca baza o tej samej nazwie jest usuwana.
 *    - Tworzona jest nowa baza z kodowaniem utf8mb4 i kolacja utf8mb4_polish_ci.
 *    - FOREIGN_KEY_CHECKS ustawiane sa na 0, aby umozliwic bezpieczne odtwarzanie obiektow
 *      bez zaleznosci kolejnosciowych.
 *
 * 2) Definicja modelu danych:
 *    - Tabele sa tworzone z naciskiem na spojnosc domenowa i wydajnosc odczytu.
 *    - Indeksy pomocnicze obejmuja pola wyszukiwania, raportowania i czesto filtrowane statusy.
 *    - Relacje miedzy encjami odzwierciedlaja przeplyw e-commerce:
 *      klient -> koszyk -> zamowienie -> platnosc/dostawa.
 *
 * 3) Egzekwowanie regul biznesowych:
 *    - Trigger trg_zamowienia_pracownik_require_insert oraz
 *      trg_zamowienia_pracownik_require_update blokuja przejscie zamowienia do statusow
 *      realizacyjnych bez przypisanego pracownika.
 *    - Mechanizm SIGNAL SQLSTATE '45000' wymusza twarda walidacje po stronie bazy.
 *
 * 4) Zaladowanie danych poczatkowych:
 *    - Uzupelniane sa slowniki statusow i przewoznikow.
 *    - Importowane sa przykladowe rekordy operacyjne (klienci, produkty, koszyki,
 *      zamowienia, pozycje, platnosci, dostawy) dla natychmiastowego testowania aplikacji.
 *    - Ustawiane sa hashe hasel testowych dla wybranych kont klientow i pracownikow.
 *
 * 5) Warstwa analityczno-raportowa:
 *    - Widoki vw_admin_* konsoliduja dane z wielu tabel i redukuja zlozonosc zapytan
 *      po stronie API/panelu administracyjnego.
 *    - Widoki wykresowe agreguja metryki 90-dniowe dla sprzedazy, kategorii,
 *      top produktow i koszykow.
 *
 * 6) Audyt zdarzen i sledzenie zmian:
 *    - Triggery trg_log_* automatycznie dopisuja wpisy do logi_systemowe przy
 *      rejestracji, zmianach klientow, zmianach statusow zamowien, platnosci,
 *      dostaw i aktualizacjach stanow magazynowych.
 *    - Poziom typu logu (info/warning/security) odzwierciedla charakter zdarzenia.
 *
 * 7) Finalizacja:
 *    - FOREIGN_KEY_CHECKS wraca do 1 po utworzeniu wszystkich obiektow.
 *    - Skrypt pozostawia baze w stanie gotowym do uruchomienia aplikacji.
 */

DROP DATABASE IF EXISTS SklepIntCom;
CREATE DATABASE SklepIntCom DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_polish_ci;
USE SklepIntCom;

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE klienci (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  imie VARCHAR(100) NOT NULL,
  nazwisko VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  haslo_hash_sha512 CHAR(128) NULL,
  telefon VARCHAR(32) NULL,
  ulica VARCHAR(150) NULL,
  dom VARCHAR(20) NULL,
  numer VARCHAR(20) NULL,
  miasto VARCHAR(120) NULL,
  kod_pocztowy VARCHAR(20) NULL,
  kraj VARCHAR(80) NOT NULL DEFAULT 'PL',
  data_rejestracji DATETIME NOT NULL,
  status ENUM('aktywny','zablokowany') NOT NULL DEFAULT 'aktywny',
  PRIMARY KEY (id),
  UNIQUE KEY uq_klienci_email (email),
  KEY idx_klienci_email (email)
);

CREATE TABLE pracownicy (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  imie VARCHAR(100) NOT NULL,
  nazwisko VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  haslo_hash_sha512 CHAR(128) NULL,
  rola ENUM('admin','magazyn') NOT NULL,
  aktywny TINYINT(1) NOT NULL DEFAULT 1,
  data_zatrudnienia DATE NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pracownicy_email (email),
  KEY idx_pracownicy_rola (rola)
);

CREATE TABLE logowania_uzytkownikow (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  klient_id INT UNSIGNED NOT NULL,
  data_logowania DATETIME NOT NULL,
  ip VARCHAR(45) NOT NULL,
  user_agent VARCHAR(255) NOT NULL,
  czy_sukces TINYINT(1) NOT NULL,
  powod VARCHAR(120) NULL,
  PRIMARY KEY (id),
  KEY idx_logowania_uzytkownikow_klient (klient_id),
  KEY idx_logowania_uzytkownikow_data (data_logowania),
  KEY idx_logowania_uzytkownikow_sukces_data (czy_sukces, data_logowania)
);

CREATE TABLE kategorie (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nazwa VARCHAR(150) NOT NULL,
  opis TEXT NULL,
  nadrzedna_id INT UNSIGNED NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kategorie_nazwa (nazwa),
  KEY idx_kategorie_nadrzedna (nadrzedna_id)
);

CREATE TABLE produkty (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kategoria_id INT UNSIGNED NOT NULL,
  nazwa VARCHAR(200) NOT NULL,
  opis TEXT NULL,
  cena DECIMAL(10,2) NOT NULL,
  stan_magazynowy INT UNSIGNED NOT NULL DEFAULT 0,
  sku VARCHAR(64) NOT NULL,
  promocja_proc DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  aktywny TINYINT(1) NOT NULL DEFAULT 1,
  data_dodania DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_produkty_sku (sku),
  KEY idx_produkty_kategoria (kategoria_id)
);

CREATE TABLE koszyki (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  klient_id INT UNSIGNED NULL,
  session_token CHAR(64) NULL,
  status ENUM('aktywny','zamowiony','porzucony','wygasl') NOT NULL DEFAULT 'aktywny',
  utworzono_at DATETIME NOT NULL,
  zaktualizowano_at DATETIME NOT NULL,
  wygasa_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_koszyki_klient_status (klient_id, status),
  KEY idx_koszyki_session_status (session_token, status)
);

CREATE TABLE pozycje_koszyka (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  koszyk_id INT UNSIGNED NOT NULL,
  produkt_id INT UNSIGNED NOT NULL,
  ilosc INT UNSIGNED NOT NULL DEFAULT 1,
  cena_bazowa DECIMAL(10,2) NOT NULL,
  promocja_proc DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  cena_koncowa DECIMAL(10,2) NOT NULL,
  utworzono_at DATETIME NOT NULL,
  zaktualizowano_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_koszyk_produkt (koszyk_id, produkt_id),
  KEY idx_pozycje_koszyka_koszyk (koszyk_id),
  KEY idx_pozycje_koszyka_produkt (produkt_id)
);

CREATE TABLE zdjecia_produktow (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  produkt_id INT UNSIGNED NOT NULL,
  nazwa_pliku VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_zdjecia_produkt_id (produkt_id),
  CONSTRAINT fk_zdjecia_produkty FOREIGN KEY (produkt_id) REFERENCES produkty(id) ON DELETE CASCADE
);

CREATE TABLE statusy_zamowienia (
  id TINYINT UNSIGNED NOT NULL,
  nazwa VARCHAR(40) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_statusy_zamowienia_nazwa (nazwa)
);

CREATE TABLE statusy_dostawy (
  id TINYINT UNSIGNED NOT NULL,
  nazwa VARCHAR(40) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_statusy_dostawy_nazwa (nazwa)
);

CREATE TABLE statusy_platnosci (
  id TINYINT UNSIGNED NOT NULL,
  nazwa VARCHAR(40) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_statusy_platnosci_nazwa (nazwa)
);

CREATE TABLE kurierzy (
  id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nazwa VARCHAR(100) NOT NULL,
  cena_dostawy DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  aktywny TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kurierzy_nazwa (nazwa)
);

CREATE TABLE zamowienia (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  klient_id INT UNSIGNED NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  pracownik_id INT UNSIGNED NULL,
  data_zamowienia DATETIME NOT NULL,
  kwota_brutto DECIMAL(12,2) NOT NULL,
  uwagi VARCHAR(255) NULL,
  notatki_wewnetrzne VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_zamowienia_klient (klient_id),
  KEY idx_zamowienia_status (status_id),
  KEY idx_zamowienia_pracownik (pracownik_id)
);

CREATE TABLE pozycje_zamowienia (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  zamowienie_id INT UNSIGNED NOT NULL,
  produkt_id INT UNSIGNED NOT NULL,
  ilosc INT UNSIGNED NOT NULL DEFAULT 1,
  cena_jednostkowa DECIMAL(10,2) NOT NULL,
  rabat_proc DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (id),
  KEY idx_pozycje_zamowienia_zamowienie (zamowienie_id),
  KEY idx_pozycje_zamowienia_produkt (produkt_id)
);

CREATE TABLE platnosci (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  zamowienie_id INT UNSIGNED NOT NULL,
  typ ENUM('karta','przelew','blik','pobranie') NOT NULL,
  kwota DECIMAL(12,2) NOT NULL,
  data_platnosci DATETIME NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_platnosci_zamowienie (zamowienie_id),
  KEY idx_platnosci_zamowienie (zamowienie_id),
  KEY idx_platnosci_status (status_id)
);

CREATE TABLE dostawy (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  zamowienie_id INT UNSIGNED NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  kurier_id TINYINT UNSIGNED NOT NULL,
  numer_przesylki VARCHAR(100) NULL,
  koszt DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  przewidywana_dostawa DATE NULL,
  data_wysylki DATETIME NULL,
  data_dostarczenia DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_dostawy_zamowienie (zamowienie_id),
  UNIQUE KEY uq_dostawy_numer (numer_przesylki),
  KEY idx_dostawy_zamowienie (zamowienie_id),
  KEY idx_dostawy_status (status_id),
  KEY idx_dostawy_kurier (kurier_id)
);

CREATE TABLE logi_systemowe (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pracownik_id INT UNSIGNED NULL,
  klient_id INT UNSIGNED NULL,
  typ ENUM('info','warning','error','security') NOT NULL DEFAULT 'info',
  opis TEXT NOT NULL,
  data_zdarzenia DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_logi_typ_data (typ, data_zdarzenia),
  KEY idx_logi_pracownik (pracownik_id),
  KEY idx_logi_klient (klient_id)
);

ALTER TABLE logowania_uzytkownikow
  ADD CONSTRAINT fk_logowania_uzytkownikow_klient
  FOREIGN KEY (klient_id) REFERENCES klienci(id)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE kategorie
  ADD CONSTRAINT fk_kategorie_nadrzedna
  FOREIGN KEY (nadrzedna_id) REFERENCES kategorie(id)
  ON DELETE SET NULL
  ON UPDATE CASCADE;

ALTER TABLE produkty
  ADD CONSTRAINT fk_produkty_kategoria
  FOREIGN KEY (kategoria_id) REFERENCES kategorie(id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;

ALTER TABLE koszyki
  ADD CONSTRAINT fk_koszyki_klient
  FOREIGN KEY (klient_id) REFERENCES klienci(id)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE pozycje_koszyka
  ADD CONSTRAINT fk_pozycje_koszyka_koszyk
  FOREIGN KEY (koszyk_id) REFERENCES koszyki(id)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT fk_pozycje_koszyka_produkt
  FOREIGN KEY (produkt_id) REFERENCES produkty(id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;

ALTER TABLE zamowienia
  ADD CONSTRAINT fk_zamowienia_klient
  FOREIGN KEY (klient_id) REFERENCES klienci(id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE,
  ADD CONSTRAINT fk_zamowienia_status
  FOREIGN KEY (status_id) REFERENCES statusy_zamowienia(id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE,
  ADD CONSTRAINT fk_zamowienia_pracownik
  FOREIGN KEY (pracownik_id) REFERENCES pracownicy(id)
  ON DELETE SET NULL
  ON UPDATE CASCADE;

ALTER TABLE pozycje_zamowienia
  ADD CONSTRAINT fk_pozycje_zamowienia_zamowienie
  FOREIGN KEY (zamowienie_id) REFERENCES zamowienia(id)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT fk_pozycje_zamowienia_produkt
  FOREIGN KEY (produkt_id) REFERENCES produkty(id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;

ALTER TABLE platnosci
  ADD CONSTRAINT fk_platnosci_zamowienie
  FOREIGN KEY (zamowienie_id) REFERENCES zamowienia(id)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT fk_platnosci_status
  FOREIGN KEY (status_id) REFERENCES statusy_platnosci(id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;

ALTER TABLE dostawy
  ADD CONSTRAINT fk_dostawy_zamowienie
  FOREIGN KEY (zamowienie_id) REFERENCES zamowienia(id)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT fk_dostawy_status
  FOREIGN KEY (status_id) REFERENCES statusy_dostawy(id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE,
  ADD CONSTRAINT fk_dostawy_kurier
  FOREIGN KEY (kurier_id) REFERENCES kurierzy(id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;

ALTER TABLE logi_systemowe
  ADD CONSTRAINT fk_logi_pracownik
  FOREIGN KEY (pracownik_id) REFERENCES pracownicy(id)
  ON DELETE SET NULL
  ON UPDATE CASCADE,
  ADD CONSTRAINT fk_logi_klient
  FOREIGN KEY (klient_id) REFERENCES klienci(id)
  ON DELETE SET NULL
  ON UPDATE CASCADE;

DELIMITER //

CREATE TRIGGER trg_zamowienia_pracownik_require_insert
BEFORE INSERT ON zamowienia
FOR EACH ROW
BEGIN
  IF NEW.status_id IN (3,4,5) AND NEW.pracownik_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'pracownik_id nie moze byc NULL dla statusu 3/4/5';
  END IF;
END//

CREATE TRIGGER trg_zamowienia_pracownik_require_update
BEFORE UPDATE ON zamowienia
FOR EACH ROW
BEGIN
  IF NEW.status_id IN (3,4,5) AND NEW.pracownik_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'pracownik_id nie moze byc NULL dla statusu 3/4/5';
  END IF;
END//

DELIMITER ;

INSERT INTO statusy_zamowienia (id, nazwa) VALUES
(1,'Nowe'),
(2,'Opłacone'),
(3,'W realizacji'),
(4,'Wysłane'),
(5,'Dostarczone');

INSERT INTO statusy_dostawy (id, nazwa) VALUES
(1,'Przygotowanie'),
(2,'Wysłane'),
(3,'Dostarczone');

INSERT INTO statusy_platnosci (id, nazwa) VALUES
(1,'Oczekuje'),
(2,'Przyjęta'),
(3,'Odrzucona'),
(4,'Zwrot');

INSERT INTO kurierzy (id, nazwa, cena_dostawy, aktywny) VALUES
(1,'InPost',12.00,1),
(2,'DPD',15.00,1),
(3,'DHL',20.00,1),
(4,'Punkt odbioru',9.99,1),
(5,'GLS',16.50,1),
(6,'UPS',18.00,1),
(7,'FedEx',21.00,1);

INSERT INTO pracownicy (id, imie, nazwisko, email, haslo_hash_sha512, rola, aktywny, data_zatrudnienia) VALUES
(1,'Admin','Sklepu','admin@sklepint.local',NULL,'admin',1,'2024-11-04'),
(2,'Marek','Jaskólski','marek.mag@sklepint.local',NULL,'magazyn',1,'2025-03-18'),
(3,'Anna','Kowalska','anna.mag@sklepint.local',NULL,'magazyn',1,'2025-09-02'),
(4,'Łukasz','Nowak','lukasz.nowak@sklepint.local',NULL,'magazyn',1,'2025-12-10'),
(5,'Paweł','Kowalski','pawel.mag@sklepint.local',NULL,'magazyn',1,'2026-02-03');

INSERT INTO kategorie (id, nazwa, opis, nadrzedna_id) VALUES
(1,'Elektronika','Sprzęt i akcesoria elektroniczne',NULL),
(2,'Komputery','Komputery i podzespoły',1),
(12,'Podzespoły','Podzespoły komputerowe i części zamienne',2),
(3,'Laptopy','Laptopy do pracy i gamingu',2),
(4,'Monitory','Monitory biurowe i gamingowe',2),
(5,'Peryferia','Myszy, klawiatury, słuchawki',2),
(6,'Sieci','Urządzenia sieciowe',1),
(7,'Routery','Routery domowe i firmowe',6),
(8,'Switche','Switche niezarządzalne i zarządzalne',6),
(9,'Access Pointy','Punkty dostępowe Wi‑Fi',6),
(10,'Okablowanie','Patchcordy, przejściówki, adaptery',1),
(11,'Druk i skan','Drukarki i urządzenia wielofunkcyjne',1);

INSERT INTO produkty (id, kategoria_id, nazwa, opis, cena, stan_magazynowy, sku, promocja_proc, aktywny, data_dodania) VALUES
(1,3,'Laptop 14\" Biznes','Specyfikacja: Intel Core i5, 16 GB RAM, SSD 512 GB, ekran 14\" FHD, Wi-Fi 6, Windows 11 Pro. Zastosowanie: praca biurowa, spotkania online, systemy ERP i praca mobilna.',3299.00,18,'LAP-BIZ-14',10.00,1,'2026-02-02 09:15:00'),
(2,3,'Laptop 15.6\" Gaming','Specyfikacja: Intel Core i7, 32 GB RAM, SSD 1 TB, NVIDIA RTX 4060, ekran 15.6\" 165 Hz. Zastosowanie: gry AAA, streaming, montaz i obrobka wideo.',5899.00,7,'LAP-GAM-156',5.00,1,'2026-02-05 11:20:00'),
(3,2,'Mini PC','Specyfikacja: AMD Ryzen 5, 16 GB RAM, SSD 512 GB, 2x HDMI, obudowa SFF. Zastosowanie: biuro, recepcja, domowe stanowisko pracy przy niskim poborze energii.',1799.00,12,'PC-MINI-R5',0.00,1,'2026-02-07 14:05:00'),
(4,4,'Monitor 24\" 144Hz','Specyfikacja: matryca IPS 24\", rozdzielczosc FHD, odswiezanie 144 Hz, wejscia DP i HDMI. Zastosowanie: dynamiczne gry FPS, multimedia i codzienna praca.',899.00,25,'MON-24-144',15.00,1,'2026-02-09 10:40:00'),
(5,4,'Monitor 27\" QHD','Specyfikacja: matryca IPS 27\", rozdzielczosc QHD, regulacja wysokosci, port USB-C. Zastosowanie: praca wielookienkowa, nauka, grafika i rozrywka.',1399.00,10,'MON-27-QHD',0.00,1,'2026-02-10 13:30:00'),
(6,5,'Klawiatura mechaniczna TKL','Specyfikacja: format TKL, przelaczniki hot-swap, podswietlenie RGB, lacznosc przewodowa USB-C. Zastosowanie: granie, szybkie pisanie i dlugie sesje pracy.',299.00,40,'KEY-TKL-MECH',20.00,1,'2026-02-12 08:55:00'),
(7,5,'Mysz bezprzewodowa','Specyfikacja: lacznosc 2.4 GHz i Bluetooth, regulowane DPI 800-2400, ciche przyciski. Zastosowanie: praca biurowa, mobilna i codzienne uzytkowanie.',79.00,120,'MOU-WL-SIL',0.00,1,'2026-02-12 09:10:00'),
(8,5,'Słuchawki USB-C z ANC','Specyfikacja: aktywna redukcja halasu ANC, mikrofon, zlacze USB-C, konstrukcja nauszna. Zastosowanie: wideokonferencje, praca zdalna i odsluch muzyki.',249.00,35,'AUD-USBC-ANC',25.00,1,'2026-02-13 16:45:00'),
(9,7,'Router Wi‑Fi 6','Specyfikacja: standard Wi-Fi 6 AX3000, WPA3, OFDMA, 4x LAN Gigabit. Zastosowanie: dom i male biuro, stabilna siec dla wielu urzadzen.',399.00,28,'NET-RTR-AX3000',0.00,1,'2026-02-15 12:05:00'),
(10,8,'Switch 8-port Gigabit','Specyfikacja: 8x port RJ45 1 GbE, obudowa metalowa, chlodzenie fanless. Zastosowanie: szybka rozbudowa sieci przewodowej w domu i biurze.',129.00,60,'NET-SW-8G',12.50,1,'2026-02-16 09:35:00'),
(11,9,'Access Point Wi‑Fi 6','Specyfikacja: punkt dostepowy Wi-Fi 6, zasilanie PoE, montaz sufitowy/scienny. Zastosowanie: biura i lokale uslugowe wymagajace stabilnego roamingu.',449.00,14,'NET-AP-W6',0.00,1,'2026-02-17 15:25:00'),
(12,10,'Patchcord Cat.6 2m','Specyfikacja: kabel UTP kat. 6, dlugosc 2 m, wtyki RJ45, kolor szary. Zastosowanie: polaczenia komputer-router-switch w sieciach gigabitowych.',12.90,300,'CAB-CAT6-2',0.00,1,'2026-02-18 08:20:00'),
(13,10,'Adapter USB-C → Ethernet','Specyfikacja: adapter USB-C do LAN 1 GbE, obudowa aluminiowa, plug and play. Zastosowanie: laptopy bez portu RJ45, stabilne polaczenie przewodowe.',69.00,55,'ADP-USBC-LAN',0.00,1,'2026-02-18 08:25:00'),
(14,11,'Drukarka laser mono','Specyfikacja: druk monochromatyczny do 30 str./min, Wi-Fi, automatyczny dupleks. Zastosowanie: biuro i dom, szybki druk dokumentow tekstowych.',699.00,9,'PRN-LSR-MONO',15.00,1,'2026-02-20 11:10:00'),
(15,3,'Laptop Ultrabook 13\"','Specyfikacja: Apple M2, 8 GB RAM, SSD 256 GB, ekran 13\" Retina, lekka obudowa. Zastosowanie: praca mobilna, nauka, codzienne zadania i multimedia.',4599.00,10,'LAP-ULT-13',0.00,1,'2026-03-01 10:00:00'),
(16,3,'Laptop Stacja Robocza 17\"','Specyfikacja: Intel Core i9-13900HX, 64 GB RAM, SSD 2 TB, NVIDIA RTX A5000, ekran 17\". Zastosowanie: CAD, render 3D, obrobka materialow 4K i projekty inzynierskie.',14200.00,3,'LAP-WRK-17',5.00,1,'2026-03-02 12:30:00'),
(17,3,'Laptop Edukacyjny 11\"','Specyfikacja: Intel Celeron N4500, 4 GB RAM, eMMC 128 GB, ekran 11\", ChromeOS. Zastosowanie: nauka szkolna, lekcje online, aplikacje webowe i praca ucznia.',1199.00,45,'LAP-EDU-11',0.00,1,'2026-03-03 09:15:00'),
(18,12,'Płyta Główna Z790','Specyfikacja: chipset Z790, socket LGA1700, obsluga DDR5, PCIe 5.0, Wi-Fi 6E. Zastosowanie: budowa wydajnego komputera gamingowego i stacji roboczej.',1250.00,12,'PC-MB-Z790',10.00,1,'2026-03-04 14:45:00'),
(19,12,'Zasilacz 750W Gold','Specyfikacja: moc 750 W, certyfikat 80 Plus Gold, okablowanie w pelni modularne. Zastosowanie: wydajne zestawy PC, niski pobor i stabilne zasilanie pod obciazeniem.',449.00,20,'PC-PSU-750W',0.00,1,'2026-03-05 11:20:00'),
(20,12,'Karta Graficzna RTX 4070','Specyfikacja: NVIDIA RTX 4070, 12 GB GDDR6X, chlodzenie Dual Fan, ray tracing. Zastosowanie: gaming 1440p, tworzenie tresci, renderowanie GPU.',2899.00,8,'PC-GPU-4070',8.00,1,'2026-03-06 16:50:00'),
(21,4,'Monitor 32\" 4K','Specyfikacja: matryca VA 32\", rozdzielczosc 4K, HDR10, wejscia 2x HDMI i DisplayPort. Zastosowanie: arkusze, grafika, filmy i praca na duzej przestrzeni roboczej.',1699.00,15,'MON-32-4K',0.00,1,'2026-03-07 10:10:00'),
(22,4,'Monitor Przenośny 15.6\"','Specyfikacja: ekran 15.6\" FHD IPS, zasilanie i obraz przez USB-C, Mini HDMI, etui. Zastosowanie: drugi ekran w podrozy, praca hybrydowa i prezentacje.',749.00,25,'MON-15-TRV',0.00,1,'2026-03-08 13:40:00'),
(23,4,'Monitor Panoramiczny 34\"','Specyfikacja: ekran 34\" Curved UltraWide, rozdzielczosc QHD, odswiezanie 144 Hz. Zastosowanie: granie, montaz, analiza danych i praca wielookienkowa.',2299.00,6,'MON-34-UW',0.00,1,'2026-03-09 09:55:00'),
(24,5,'Mysz Gamingowa PRO','Specyfikacja: sensor 25 000 DPI, masa 63 g, lacznosc bezprzewodowa o niskim opoznieniu. Zastosowanie: e-sport, dynamiczne gry i precyzyjne sterowanie.',499.00,30,'MOU-GAM-PRO',0.00,1,'2026-03-10 15:20:00'),
(25,5,'Klawiatura Niski Profil','Specyfikacja: niski profil, przelaczniki liniowe, obudowa aluminiowa, Bluetooth i 2.4 GHz. Zastosowanie: biuro premium, praca mobilna i stanowiska wielourzadzeniowe.',699.00,18,'KEY-LOW-PROF',0.00,1,'2026-03-11 08:30:00'),
(26,5,'Słuchawki Studyjne','Specyfikacja: konstrukcja otwarta, impedancja 250 Ohm, welurowe pady, szeroka scena. Zastosowanie: miks, mastering, krytyczny odsluch i dlugie sesje audio.',599.00,22,'AUD-STU-HI',0.00,1,'2026-03-12 11:45:00'),
(27,5,'Mikrofon USB Kardioidalny','Specyfikacja: charakterystyka kardioidalna, lacznosc USB, statyw biurkowy, filtr pop, wyjscie sluchawkowe. Zastosowanie: podcasty, streaming i spotkania online.',329.00,40,'AUD-MIC-USB',0.00,1,'2026-03-13 14:10:00'),
(28,5,'Podkładka pod mysz XXL','Specyfikacja: powierzchnia 900x400 mm, gladkie poszycie, obszywane krawedzie, antyposlizgowy spod. Zastosowanie: stanowiska gamingowe i biurowe z klawiatura i myszka.',89.00,100,'ACC-PAD-XXL',0.00,1,'2026-03-14 17:05:00'),
(29,7,'Router LTE/5G','Specyfikacja: modem LTE/5G, slot SIM, Wi-Fi 6, 4 anteny zewnetrzne. Zastosowanie: internet awaryjny, dom bez swiatlowodu i praca mobilna.',899.00,15,'NET-RTR-5G',0.00,1,'2026-03-15 12:30:00'),
(30,7,'System Mesh (2-pack)','Specyfikacja: zestaw 2 jednostek mesh, dual-band, zasieg do 300 m2, roaming. Zastosowanie: jednolita siec Wi-Fi w mieszkaniu i domu wielopokojowym.',549.00,20,'NET-MSH-DUO',0.00,1,'2026-03-16 10:50:00'),
(31,8,'Switch 16-port PoE','Specyfikacja: 16x Gigabit PoE+, 2x SFP, obudowa rack 19\". Zastosowanie: monitoring IP, AP, telefony VoIP i male szafy rackowe.',1149.00,10,'NET-SW-16POE',0.00,1,'2026-03-17 09:20:00'),
(32,8,'Switch Zarządzalny L2','Specyfikacja: 24x port Gigabit, zarzadzanie L2, VLAN, QoS, panel webowy. Zastosowanie: segmentacja sieci firmowej i kontrola ruchu uzytkownikow.',899.00,7,'NET-SW-24L2',0.00,1,'2026-03-18 15:40:00'),
(33,9,'Access Point Sufitowy','Specyfikacja: standard AC1200, pasmo 2.4/5 GHz, montaz sufitowy, obudowa dyskretna. Zastosowanie: biura, hotele i lokale z wieloma klientami Wi-Fi.',299.00,35,'NET-AP-AC1200',0.00,1,'2026-03-19 11:15:00'),
(34,10,'Kabel HDMI 2.1 3m','Specyfikacja: HDMI 2.1, dlugosc 3 m, przepustowosc dla 8K 60 Hz i 4K 120 Hz. Zastosowanie: konsole, TV i monitory o wysokiej czestotliwosci odswiezania.',49.00,150,'CAB-HDMI-21-3',0.00,1,'2026-03-20 08:45:00'),
(35,10,'Zestaw Narzędzi Sieciowych','Specyfikacja: zaciskarka RJ45, tester kabli, nozyk, komplet wtykow i akcesoria serwisowe. Zastosowanie: instalacja i konserwacja okablowania LAN.',129.00,50,'ACC-TOOL-NET',0.00,1,'2026-03-21 13:25:00'),
(36,10,'Stacja Dokująca USB-C','Specyfikacja: wyjscia HDMI i DisplayPort, 3x USB 3.0, Power Delivery do 100 W. Zastosowanie: rozszerzenie portow laptopa i wygodna praca na biurku.',349.00,40,'ADP-USBC-DOCK',0.00,1,'2026-03-22 10:00:00'),
(37,11,'Urządzenie Wielofunkcyjne Ink','Specyfikacja: druk kolorowy, skaner, kopiarka, lacznosc Wi-Fi i USB. Zastosowanie: dom, mala firma i codzienne drukowanie dokumentow.',429.00,15,'PRN-INK-AIO',0.00,1,'2026-03-23 09:50:00'),
(38,11,'Skaner Dokumentów','Specyfikacja: skanowanie dwustronne, automatyczny podajnik ADF, interfejs USB 3.0. Zastosowanie: digitalizacja faktur, umow i dokumentow archiwalnych.',999.00,5,'PRN-SCN-DS',0.00,1,'2026-03-24 16:10:00'),
(39,11,'Toner do drukarki czarny','Specyfikacja: toner czarny, wydajnosc do 2500 stron A4, zamiennik premium. Zastosowanie: ekonomiczny druk dokumentow biurowych i domowych.',149.00,80,'ACC-TON-BLK',0.00,1,'2026-03-25 12:35:00'),
(40,3,'Laptop 2-w-1 Dotykowy','Specyfikacja: AMD Ryzen 7, 16 GB RAM, SSD 1 TB, ekran dotykowy 14\" obracany 360 stopni. Zastosowanie: notatki, prezentacje, praca kreatywna i mobilna.',4299.00,12,'LAP-FLX-14',0.00,1,'2026-03-26 14:00:00'),
(41,12,'Chłodzenie Procesora','Specyfikacja: konstrukcja wiezowa, 4 cieplowody, wentylator 120 mm PWM. Zastosowanie: obnizenie temperatur CPU i cichsza praca zestawu PC.',189.00,40,'PC-FAN-AIR',0.00,1,'2026-03-27 11:45:00'),
(42,12,'Dysk SSD NVMe 1TB','Specyfikacja: pojemnosc 1 TB, interfejs NVMe PCIe 4.0 x4, odczyt do 5000 MB/s. Zastosowanie: system operacyjny, gry, szybka praca aplikacji i transfer plikow.',399.00,60,'PC-SSD-1TB',0.00,1,'2026-03-28 09:20:00'),
(43,12,'Pamięć RAM DDR5 32GB','Specyfikacja: 32 GB 2x16 GB DDR5, taktowanie 6000 MHz, opoznienia CL30, RGB. Zastosowanie: gaming, wielozadaniowosc i obrobka projektow kreatywnych.',599.00,25,'PC-RAM-32DDR5',0.00,1,'2026-03-29 13:10:00'),
(44,5,'Kamera Internetowa 4K','Specyfikacja: rozdzielczosc 4K, autofocus, mikrofony stereo, mechaniczna zaslepka prywatnosci. Zastosowanie: wideokonferencje, streaming i nagrania online.',349.00,30,'ACC-CAM-4K',0.00,1,'2026-03-30 15:55:00'),
(45,3,'Laptop Rugged 11\"','Specyfikacja: Intel Core i5, 8 GB RAM, obudowa wzmocniona, klasa odpornosci IP53, ekran 11\". Zastosowanie: praca terenowa, serwis, magazyn i mobilne raportowanie.',3699.00,5,'LAP-RUG-11',0.00,1,'2026-03-31 08:30:00'),
(46,4,'Monitor Gamingowy 27\"','Specyfikacja: matryca IPS 27\", odswiezanie 240 Hz, czas reakcji 1 ms, G-Sync Compatible. Zastosowanie: gry turniejowe, e-sport i plynna rozgrywka bez tearingu.',1899.00,10,'MON-27-240',0.00,1,'2026-04-01 10:15:00'),
(47,10,'Kabel DisplayPort 1.4 2m','Specyfikacja: standard DisplayPort 1.4, dlugosc 2 m, wsparcie 4K 144 Hz, styki pozlacane. Zastosowanie: polaczenie karty graficznej z monitorem wysokiej klasy.',39.00,100,'CAB-DP-14-2',0.00,1,'2026-04-01 11:20:00'),
(48,5,'Głośniki 2.1','Specyfikacja: zestaw 2.1, subwoofer drewniany, moc 50 W RMS, regulacja basu. Zastosowanie: muzyka, filmy i gry w pokoju domowym lub biurze.',299.00,20,'AUD-SPK-21',0.00,1,'2026-04-01 12:45:00'),
(49,6,'Router VPN Hardware','Specyfikacja: dedykowana brama VPN, wsparcie IPsec i OpenVPN, 5 portow Ethernet. Zastosowanie: bezpieczny dostep zdalny i laczenie oddzialow firmy.',649.00,12,'NET-VPN-GW',0.00,1,'2026-04-01 14:30:00'),
(50,11,'Drukarka Etykiet','Specyfikacja: druk termiczny etykiet, obsluga formatow kurierskich, wysoka szybkosc wydruku. Zastosowanie: e-commerce, magazyn i przygotowanie paczek.',549.00,18,'PRN-LBL-THERM',0.00,1,'2026-04-01 16:05:00'),
(51,10,'Organizer Kabli 1m','Specyfikacja: rzep samoprzylepny, rolka 5 m, mozliwosc wielokrotnego uzycia. Zastosowanie: porzadkowanie przewodow przy biurku, RTV i stanowiskach komputerowych.',15.00,200,'ACC-ORG-CAB',0.00,1,'2026-04-01 17:15:00'),
(52,3,'Laptop Biznesowy 15\"','Specyfikacja: Intel Core i5-1335U, 16 GB RAM, SSD 512 GB, ekran 15\", czytnik linii papilarnych. Zastosowanie: praca biurowa, CRM, arkusze i bezpieczna praca firmowa.',3599.00,20,'LAP-BIZ-15',0.00,1,'2026-04-01 18:20:00'),
(53,5,'Kontroler do gier','Specyfikacja: lacznosc bezprzewodowa, kompatybilnosc PC/Console, analogi precyzyjne, ergonomiczny chwyt. Zastosowanie: gry akcji, sportowe i wielogodzinna rozgrywka.',249.00,45,'ACC-PAD-GAME',0.00,1,'2026-04-01 19:30:00'),
(54,5,'Lampka na Monitor','Specyfikacja: montaz na krawedzi monitora, regulacja barwy i jasnosci, zasilanie USB. Zastosowanie: oswietlenie blatu bez odbic i komfort pracy wieczorem.',129.00,60,'ACC-LMP-MON',0.00,1,'2026-04-01 20:45:00');

INSERT INTO zdjecia_produktow (produkt_id, nazwa_pliku) VALUES
(1,'1-1.webp'),
(1,'1-2.webp'),
(2,'2-1.webp'),
(2,'2-2.webp'),
(3,'3-1.webp'),
(3,'3-2.webp'),
(4,'4-1.webp'),
(4,'4-2.webp'),
(5,'5-1.webp'),
(5,'5-2.webp'),
(6,'6-1.webp'),
(7,'7-1.webp'),
(8,'8-1.webp'),
(8,'8-2.webp'),
(9,'9-1.webp'),
(10,'10-1.webp'),
(11,'11-1.webp'),
(12,'12-1.webp'),
(13,'13-1.webp'),
(14,'14-1.webp'),
(15,'15-1.webp'),
(15,'15-2.webp'),
(16,'16-1.webp'),
(16,'16-2.webp'),
(17,'17-1.webp'),
(17,'17-2.webp'),
(18,'18-1.webp'),
(18,'18-2.webp'),
(19,'19-1.webp'),
(20,'20-1.webp'),
(20,'20-2.webp'),
(21,'21-1.webp'),
(21,'21-2.webp'),
(22,'22-1.webp'),
(23,'23-1.webp'),
(23,'23-2.webp'),
(24,'24-1.webp'),
(25,'25-1.webp'),
(26,'26-1.webp'),
(26,'26-2.webp'),
(27,'27-1.webp'),
(28,'28-1.webp'),
(29,'29-1.webp'),
(30,'30-1.webp'),
(31,'31-1.webp'),
(32,'32-1.webp'),
(33,'33-1.webp'),
(34,'34-1.webp'),
(35,'35-1.webp'),
(36,'36-1.webp'),
(37,'37-1.webp'),
(38,'38-1.webp'),
(39,'39-1.webp'),
(40,'40-1.webp'),
(40,'40-2.webp'),
(41,'41-1.webp'),
(42,'42-1.webp'),
(43,'43-1.webp'),
(44,'44-1.webp'),
(45,'45-1.webp'),
(45,'45-2.webp'),
(46,'46-1.webp'),
(46,'46-2.webp'),
(47,'47-1.webp'),
(48,'48-1.webp'),
(49,'49-1.webp'),
(50,'50-1.webp'),
(51,'51-1.webp'),
(52,'52-1.webp'),
(52,'52-2.webp'),
(53,'53-1.webp'),
(54,'54-1.webp');

INSERT INTO klienci (id, imie, nazwisko, email, haslo_hash_sha512, telefon, ulica, dom, numer, miasto, kod_pocztowy, kraj, data_rejestracji, status) VALUES
(1,'Jan','Kowalski','jan.kowalski@example.com',NULL,'500100100','Marszalkowska','12','8','Warszawa','00-001','PL','2026-02-01 10:12:00','aktywny'),
(2,'Anna','Nowak','anna.nowak@example.com',NULL,'500100101','Dluga','21A','4','Krakow','30-002','PL','2026-02-03 16:44:00','aktywny'),
(3,'Piotr','Zielinski','piotr.zielinski@example.com',NULL,'500100102','Grunwaldzka','7','15','Gdansk','80-003','PL','2026-02-05 09:31:00','aktywny'),
(4,'Katarzyna','Wisniewska','k.wisniewska@example.com',NULL,'500100103','Swiety Marcin','55','2','Poznan','60-004','PL','2026-02-09 13:02:00','aktywny'),
(5,'Michal','Wojcik','m.wojcik@example.com',NULL,'500100104','Legnicka','103','18','Wroclaw','50-005','PL','2026-02-12 18:20:00','aktywny'),
(6,'Tomasz','Lewandowski','t.lewandowski@example.com',NULL,'500100105','Wojska Polskiego','9','1','Szczecin','70-007','PL','2026-02-18 08:08:00','aktywny'),
(7,'Ewa','Mazur','ewa.mazur@example.com',NULL,'500100106','Piotrkowska','101','7','Lodz','90-008','PL','2026-02-21 12:30:00','aktywny'),
(8,'Marek','Dabrowski','marek.dabrowski@example.com',NULL,'500100107','Kosciuszki','44','5','Katowice','40-009','PL','2026-02-24 09:15:00','aktywny'),
(9,'Aleksandra','Krawczyk','aleksandra.krawczyk@example.com',NULL,'500100108','Kolejowa','3','11','Lublin','20-010','PL','2026-02-26 14:20:00','aktywny'),
(10,'Damian','Pawlowski','damian.pawlowski@example.com',NULL,'500100109','Sienkiewicza','8','2','Bydgoszcz','85-011','PL','2026-02-28 18:05:00','aktywny'),
(11,'Karolina','Piotrowska','karolina.piotrowska@example.com',NULL,'500100110','Narutowicza','17','9','Bialystok','15-012','PL','2026-03-02 10:42:00','aktywny'),
(12,'Rafal','Grabowski','rafal.grabowski@example.com',NULL,'500100111','Pomorska','66','4','Torun','87-013','PL','2026-03-04 08:55:00','aktywny'),
(13,'Sylwia','Zajac','sylwia.zajac@example.com',NULL,'500100112','Kwiatowa','5','6','Rzeszow','35-014','PL','2026-03-08 16:10:00','aktywny'),
(14,'Krzysztof','Sikora','krzysztof.sikora@example.com',NULL,'500100113','Jagiellonska','29','3','Gdynia','81-015','PL','2026-03-12 11:18:00','aktywny'),
(15,'Adam','Nowicki','adam.nowicki@example.com',NULL,'500200001','Pulawska','10','2','Warszawa','00-010','PL','2026-03-01 10:12:00','aktywny'),
(16,'Natalia','Kaczmarek','n.kaczmarek@example.com',NULL,'500200002','Dluga','22','4','Krakow','30-010','PL','2026-03-02 11:30:00','aktywny'),
(17,'Sebastian','Piasecki','s.piasecki@example.com',NULL,'500200003','Polna','15','5','Poznan','60-010','PL','2026-03-03 09:45:00','aktywny'),
(18,'Magdalena','Krol','m.krol@example.com',NULL,'500200004','Legnicka','31','1','Wroclaw','50-010','PL','2026-03-04 14:20:00','aktywny'),
(19,'Patryk','Michalak','p.michalak@example.com',NULL,'500200005','Dluga','8','6','Gdansk','80-010','PL','2026-03-05 16:05:00','aktywny'),
(20,'Weronika','Baran','w.baran@example.com',NULL,'500200006','Piotrkowska','90','2','Lodz','90-010','PL','2026-03-06 08:15:00','aktywny'),
(21,'Kamil','Pietrzak','k.pietrzak@example.com',NULL,'500200007','Wyszynskiego','12','8','Szczecin','70-010','PL','2026-03-07 13:22:00','aktywny'),
(22,'Joanna','Zawadzka','j.zawadzka@example.com',NULL,'500200008','Narutowicza','40','7','Lublin','20-020','PL','2026-03-08 18:40:00','aktywny'),
(23,'Marcin','Lis','m.lis@example.com',NULL,'500200009','Chorzowska','4','3','Katowice','40-020','PL','2026-03-09 10:10:00','aktywny'),
(24,'Oliwia','Witkowska','o.witkowska@example.com',NULL,'500200010','Lipowa','17','2','Bialystok','15-020','PL','2026-03-10 12:55:00','aktywny'),
(25,'Dawid','Walczak','d.walczak@example.com',NULL,'500200011','Mickiewicza','6','9','Rzeszow','35-020','PL','2026-03-11 09:05:00','aktywny'),
(26,'Paulina','Stepien','p.stepien@example.com',NULL,'500200012','Pomorska','66','4','Torun','87-020','PL','2026-03-12 17:35:00','aktywny'),
(27,'Grzegorz','Jaworski','g.jaworski@example.com',NULL,'500200013','Morska','9','1','Gdynia','81-020','PL','2026-03-13 11:50:00','aktywny'),
(28,'Agnieszka','Sadowska','a.sadowska@example.com',NULL,'500200014','Ozimska','77','5','Opole','45-020','PL','2026-03-14 15:25:00','aktywny'),
(29,'Lukasz','Wieczorek','l.wieczorek@example.com',NULL,'500200015','Zeromskiego','18','2','Radom','26-020','PL','2026-03-15 08:45:00','aktywny'),
(30,'Kinga','Duda','k.duda@example.com',NULL,'500200016','Kolejowa','51','7','Plock','09-020','PL','2026-03-16 19:10:00','aktywny'),
(31,'Mateusz','Szewczyk','m.szewczyk@example.com',NULL,'500200017','Hetmanska','23','3','Elblag','82-020','PL','2026-03-17 13:05:00','aktywny'),
(32,'Karol','Urban','k.urban@example.com',NULL,'500200018','Zwyciestwa','11','1','Koszalin','75-020','PL','2026-03-18 09:30:00','aktywny'),
(33,'Sylwia','Adamska','s.adamska@example.com',NULL,'500200019','Witosa','2','4','Legnica','59-020','PL','2026-03-19 16:45:00','aktywny'),
(34,'Tadeusz','Kubiak','t.kubiak@example.com',NULL,'500200020','Kupiecka','19','2','Zielona Gora','65-020','PL','2026-03-20 12:20:00','aktywny'),
(35,'Iwona','Bak','i.bak@example.com',NULL,'500200021','Wilenska','14','6','Olsztyn','10-020','PL','2026-03-21 10:00:00','aktywny'),
(36,'Norbert','Kurek','n.kurek@example.com',NULL,'500200022','Haffnera','8','5','Sopot','81-021','PL','2026-03-22 14:35:00','aktywny'),
(37,'Emilia','Pawlak','e.pawlak@example.com',NULL,'500200023','Dworcowa','32','1','Bytom','41-020','PL','2026-03-23 18:05:00','aktywny'),
(38,'Robert','Kozlowski','r.kozlowski@example.com',NULL,'500200024','Tarnowska','13','3','Gliwice','44-020','PL','2026-03-24 11:15:00','aktywny'),
(39,'Monika','Czarnecka','m.czarnecka@example.com',NULL,'500200025','Lwowska','27','8','Tarnow','33-020','PL','2026-03-25 09:55:00','aktywny');

INSERT INTO logowania_uzytkownikow (id, klient_id, data_logowania, ip, user_agent, czy_sukces, powod) VALUES
(1,1,'2026-03-20 08:12:00','192.168.1.11','Mozilla/5.0',1,NULL),
(2,2,'2026-03-20 09:01:00','192.168.1.12','Mozilla/5.0',1,NULL),
(3,3,'2026-03-20 09:30:00','192.168.1.13','Mozilla/5.0',0,'Bledne haslo'),
(4,3,'2026-03-20 09:31:00','192.168.1.13','Mozilla/5.0',1,NULL),
(5,7,'2026-03-21 07:55:00','192.168.1.17','Mozilla/5.0',1,NULL),
(6,8,'2026-03-21 08:03:00','192.168.1.18','Mozilla/5.0',1,NULL),
(7,9,'2026-03-21 08:20:00','192.168.1.19','Mozilla/5.0',0,'Nieistniejacy email'),
(8,10,'2026-03-22 10:22:00','192.168.1.20','Mozilla/5.0',1,NULL),
(9,11,'2026-03-22 11:42:00','192.168.1.21','Mozilla/5.0',1,NULL),
(10,12,'2026-03-23 13:18:00','192.168.1.22','Mozilla/5.0',1,NULL),
(11,13,'2026-03-24 16:03:00','192.168.1.23','Mozilla/5.0',0,'Konto zablokowane tymczasowo'),
(12,14,'2026-03-25 18:27:00','192.168.1.24','Mozilla/5.0',1,NULL),
(13,5,'2026-03-28 09:11:00','192.168.1.15','Mozilla/5.0',1,NULL),
(14,6,'2026-03-29 12:45:00','192.168.1.16','Mozilla/5.0',1,NULL),
(15,8,'2026-04-01 07:59:00','192.168.1.18','Mozilla/5.0',1,NULL),
(16,9,'2026-04-02 08:05:00','192.168.1.19','Mozilla/5.0',1,NULL),
(17,10,'2026-04-03 09:33:00','192.168.1.20','Mozilla/5.0',1,NULL),
(18,11,'2026-04-04 14:15:00','192.168.1.21','Mozilla/5.0',1,NULL),
(19,12,'2026-04-06 19:49:00','192.168.1.22','Mozilla/5.0',0,'Bledne haslo'),
(20,12,'2026-04-06 19:51:00','192.168.1.22','Mozilla/5.0',1,NULL);

INSERT INTO koszyki (id, klient_id, session_token, status, utworzono_at, zaktualizowano_at, wygasa_at) VALUES
(1,1,NULL,'aktywny','2026-01-15 10:05:00','2026-01-16 09:20:00','2026-01-22 10:05:00'),
(2,2,NULL,'porzucony','2026-01-18 12:40:00','2026-01-20 08:15:00','2026-01-25 12:40:00'),
(3,NULL,'sess-0003','aktywny','2026-01-22 19:11:00','2026-01-23 18:02:00','2026-01-29 19:11:00'),
(4,3,NULL,'porzucony','2026-01-27 08:50:00','2026-01-30 14:30:00','2026-02-03 08:50:00'),
(5,4,NULL,'aktywny','2026-02-01 13:10:00','2026-02-02 09:12:00','2026-02-08 13:10:00'),
(6,NULL,'sess-0006','porzucony','2026-02-03 16:05:00','2026-02-05 20:44:00','2026-02-10 16:05:00'),
(7,5,NULL,'aktywny','2026-02-07 09:30:00','2026-02-08 10:22:00','2026-02-14 09:30:00'),
(8,2,NULL,'porzucony','2026-02-11 18:50:00','2026-02-14 21:31:00','2026-02-18 18:50:00'),
(9,NULL,'sess-0009','aktywny','2026-02-15 12:12:00','2026-02-16 12:20:00','2026-02-22 12:12:00'),
(10,6,NULL,'porzucony','2026-02-18 07:45:00','2026-02-20 09:05:00','2026-02-25 07:45:00'),
(11,1,NULL,'aktywny','2026-02-22 17:20:00','2026-02-23 10:18:00','2026-03-01 17:20:00'),
(12,NULL,'sess-0012','porzucony','2026-02-24 11:55:00','2026-02-27 08:45:00','2026-03-03 11:55:00'),
(13,3,NULL,'aktywny','2026-03-01 09:40:00','2026-03-02 09:48:00','2026-03-08 09:40:00'),
(14,4,NULL,'porzucony','2026-03-04 14:05:00','2026-03-06 14:21:00','2026-03-11 14:05:00'),
(15,NULL,'sess-0015','aktywny','2026-03-08 20:15:00','2026-03-09 19:55:00','2026-03-15 20:15:00'),
(16,5,NULL,'porzucony','2026-03-10 10:10:00','2026-03-12 08:12:00','2026-03-17 10:10:00'),
(17,2,NULL,'aktywny','2026-03-13 18:34:00','2026-03-14 16:00:00','2026-03-20 18:34:00'),
(18,NULL,'sess-0018','porzucony','2026-03-16 21:00:00','2026-03-19 13:09:00','2026-03-23 21:00:00'),
(19,6,NULL,'aktywny','2026-03-21 11:25:00','2026-03-22 12:44:00','2026-03-28 11:25:00'),
(20,1,NULL,'porzucony','2026-03-24 07:58:00','2026-03-26 09:17:00','2026-03-31 07:58:00'),
(21,NULL,'sess-0021','aktywny','2026-03-27 16:42:00','2026-03-28 17:02:00','2026-04-03 16:42:00'),
(22,3,NULL,'porzucony','2026-03-30 13:21:00','2026-04-01 08:40:00','2026-04-06 13:21:00'),
(23,4,NULL,'aktywny','2026-04-03 10:16:00','2026-04-04 12:05:00','2026-04-10 10:16:00'),
(24,NULL,'sess-0024','porzucony','2026-04-06 19:09:00','2026-04-08 07:35:00','2026-04-13 19:09:00'),
(25,1,NULL,'porzucony','2026-03-28 08:10:00','2026-03-29 09:15:00','2026-04-04 08:10:00'),
(26,NULL,'sess-0026','porzucony','2026-03-28 10:40:00','2026-03-29 11:02:00','2026-04-04 10:40:00'),
(27,5,NULL,'porzucony','2026-03-28 15:05:00','2026-03-29 16:25:00','2026-04-04 15:05:00'),
(28,2,NULL,'aktywny','2026-03-28 18:30:00','2026-03-29 19:10:00','2026-04-04 18:30:00'),
(29,NULL,'sess-0029','aktywny','2026-04-07 09:00:00','2026-04-08 09:50:00','2026-04-14 09:00:00'),
(30,6,NULL,'aktywny','2026-04-07 11:22:00','2026-04-08 12:40:00','2026-04-14 11:22:00'),
(31,3,NULL,'porzucony','2026-04-07 13:10:00','2026-04-08 14:05:00','2026-04-14 13:10:00'),
(32,NULL,'sess-0032','aktywny','2026-04-07 20:44:00','2026-04-08 21:18:00','2026-04-14 20:44:00'),
(33,15,NULL,'aktywny','2026-04-01 10:00:00','2026-04-02 11:00:00','2026-04-08 10:00:00'),
(34,16,NULL,'porzucony','2026-04-01 12:00:00','2026-04-03 13:00:00','2026-04-08 12:00:00'),
(35,17,NULL,'aktywny','2026-04-02 09:30:00','2026-04-03 10:20:00','2026-04-09 09:30:00'),
(36,NULL,'sess-extra-1','aktywny','2026-04-02 15:10:00','2026-04-03 16:00:00','2026-04-09 15:10:00'),
(37,NULL,'sess-extra-2','porzucony','2026-04-03 18:45:00','2026-04-05 08:10:00','2026-04-10 18:45:00');

INSERT INTO pozycje_koszyka (id, koszyk_id, produkt_id, ilosc, cena_bazowa, promocja_proc, cena_koncowa, utworzono_at, zaktualizowano_at) VALUES
(1,1,1,1,3299.00,10.00,2969.10,'2026-01-15 10:07:00','2026-01-16 09:20:00'),
(2,2,12,3,12.90,0.00,12.90,'2026-01-18 12:44:00','2026-01-20 08:15:00'),
(3,3,7,2,79.00,0.00,79.00,'2026-01-22 19:13:00','2026-01-23 18:02:00'),
(4,4,9,1,399.00,0.00,399.00,'2026-01-27 08:52:00','2026-01-30 14:30:00'),
(5,5,14,1,699.00,15.00,594.15,'2026-02-01 13:15:00','2026-02-02 09:12:00'),
(6,6,4,1,899.00,15.00,764.15,'2026-02-03 16:11:00','2026-02-05 20:44:00'),
(7,7,10,2,129.00,12.50,112.88,'2026-02-07 09:36:00','2026-02-08 10:22:00'),
(8,8,25,1,699.00,0.00,699.00,'2026-02-11 18:53:00','2026-02-14 21:31:00'),
(9,9,21,1,1699.00,0.00,1699.00,'2026-02-15 12:14:00','2026-02-16 12:20:00'),
(10,10,31,1,1149.00,0.00,1149.00,'2026-02-18 07:50:00','2026-02-20 09:05:00'),
(11,11,42,1,399.00,0.00,399.00,'2026-02-22 17:24:00','2026-02-23 10:18:00'),
(12,12,35,1,129.00,0.00,129.00,'2026-02-24 12:00:00','2026-02-27 08:45:00'),
(13,13,29,1,899.00,0.00,899.00,'2026-03-01 09:43:00','2026-03-02 09:48:00'),
(14,14,33,2,299.00,0.00,299.00,'2026-03-04 14:12:00','2026-03-06 14:21:00'),
(15,15,46,1,1899.00,0.00,1899.00,'2026-03-08 20:17:00','2026-03-09 19:55:00'),
(16,16,28,1,89.00,0.00,89.00,'2026-03-10 10:14:00','2026-03-12 08:12:00'),
(17,17,54,1,129.00,0.00,129.00,'2026-03-13 18:40:00','2026-03-14 16:00:00'),
(18,18,36,1,349.00,0.00,349.00,'2026-03-16 21:07:00','2026-03-19 13:09:00'),
(19,19,20,1,2899.00,8.00,2667.08,'2026-03-21 11:30:00','2026-03-22 12:44:00'),
(20,20,47,2,39.00,0.00,39.00,'2026-03-24 08:02:00','2026-03-26 09:17:00'),
(21,21,26,1,599.00,0.00,599.00,'2026-03-27 16:49:00','2026-03-28 17:02:00'),
(22,22,50,1,549.00,0.00,549.00,'2026-03-30 13:25:00','2026-04-01 08:40:00'),
(23,23,52,1,3599.00,0.00,3599.00,'2026-04-03 10:22:00','2026-04-04 12:05:00'),
(24,24,11,1,449.00,0.00,449.00,'2026-04-06 19:15:00','2026-04-08 07:35:00'),
(25,25,44,1,349.00,0.00,349.00,'2026-03-28 08:14:00','2026-03-29 09:15:00'),
(26,26,34,2,49.00,0.00,49.00,'2026-03-28 10:44:00','2026-03-29 11:02:00'),
(27,27,53,1,249.00,0.00,249.00,'2026-03-28 15:11:00','2026-03-29 16:25:00'),
(28,28,46,1,1899.00,0.00,1899.00,'2026-03-28 18:36:00','2026-03-29 19:10:00'),
(29,29,30,1,549.00,0.00,549.00,'2026-04-07 09:08:00','2026-04-08 09:50:00'),
(30,30,49,1,649.00,0.00,649.00,'2026-04-07 11:30:00','2026-04-08 12:40:00'),
(31,31,39,2,149.00,0.00,149.00,'2026-04-07 13:16:00','2026-04-08 14:05:00'),
(32,32,5,1,1399.00,0.00,1399.00,'2026-04-07 20:49:00','2026-04-08 21:18:00');

INSERT INTO zamowienia (id, klient_id, status_id, pracownik_id, data_zamowienia, kwota_brutto, uwagi, notatki_wewnetrzne) VALUES
(1,1,5,2,'2026-02-06 12:15:00',528.00,'Prośba o fakturę VAT','Zweryfikowano NIP i dane do faktury, wysylka zakonczona bez uwag.'),
(2,2,4,3,'2026-02-10 18:05:00',899.00,NULL,'Platnosc karta potwierdzona, paczka przekazana kurierowi o 14:20.'),
(3,3,3,3,'2026-02-16 09:22:00',141.90,'Odbiór w punkcie','Klient prosil o kontakt SMS przy gotowosci odbioru w punkcie.'),
(4,4,2,NULL,'2026-02-19 14:40:00',699.00,NULL,'Oczekuje na przypisanie magazyniera, towar kompletny na stanie.'),
(5,5,5,2,'2026-02-22 11:10:00',79.00,'Zostawić u sąsiada jeśli nie ma nikogo','Dostarczono, potwierdzenie odbioru od sasiada nr lok. 18.'),
(6,6,1,NULL,'2026-02-28 20:18:00',3299.00,'Pilne – wysłać dziś','Priorytet high value, wymagane podwojne zabezpieczenie paczki.'),
(7,7,2,NULL,'2026-03-03 10:25:00',1699.00,NULL,'Platnosc przyjeta automatycznie, czeka na kompletacje.'),
(8,8,3,4,'2026-03-05 15:42:00',1149.00,'Prosze o szybka wysylke','Przydzielono do magazynu i rozpoczęto kompletacje.'),
(9,9,4,3,'2026-03-08 09:18:00',764.15,NULL,'Zamowienie gotowe do odbioru przez kuriera.'),
(10,10,5,2,'2026-03-12 12:12:00',399.00,NULL,'Dostarczono bez problemow, klient zadowolony.'),
(11,11,1,NULL,'2026-03-15 17:35:00',129.00,'Prosze o kontakt przed dostawa','Nowe zamowienie - oczekuje na platnosc.'),
(12,12,2,NULL,'2026-03-18 08:27:00',594.15,NULL,'Platnosc potwierdzona, czeka na przypisanie pracownika.'),
(13,13,3,5,'2026-03-22 13:04:00',1899.00,NULL,'Kompletacja zakonczona, oczekuje na etykiete przewozowa.'),
(14,14,4,4,'2026-03-26 19:46:00',549.00,NULL,'Wyslano do punktu odbioru.'),
(15,7,5,3,'2026-03-29 11:09:00',449.00,NULL,'Dostarczono nastepnego dnia roboczego.'),
(16,8,1,NULL,'2026-04-02 09:57:00',349.00,NULL,'Nowe zamowienie - oczekuje na potwierdzenie platnosci.'),
(17,9,2,NULL,'2026-04-05 14:21:00',2667.08,NULL,'Platnosc przyjeta, oczekuje na magazyn.'),
(18,10,3,2,'2026-04-08 16:50:00',1399.00,'Faktura na firme','W realizacji, wymagane dodatkowe zabezpieczenie.'),
(19,1,1,NULL,'2026-01-05 10:15:00',299.00,NULL,'Zamowienie potwierdzone, gotowe do wysylki.'),
(20,2,2,NULL,'2026-01-06 12:40:00',899.00,NULL,'Platnosc uiszczona kartem, oczekuje na kompletacje.'),
(21,3,3,2,'2026-01-07 09:20:00',129.00,NULL,'W realizacji, etykieta do druku.'),
(22,4,4,3,'2026-01-08 14:10:00',1599.00,NULL,'Zamowienie rozdzielone na dwie paczki.'),
(23,5,5,2,'2026-01-09 16:50:00',79.00,NULL,'Dostarczono bez problemow, klient potwierdził.'),
(24,6,2,NULL,'2026-01-10 11:05:00',3499.00,NULL,'High value, wymaga dodatkowego ubezpieczenia.'),
(25,7,3,4,'2026-01-11 13:45:00',999.00,NULL,'Przekazane kurierowi DPD.'),
(26,8,4,3,'2026-01-12 08:30:00',459.00,NULL,'Zamowienie skorygowane - klient zmieniл adres.'),
(27,9,5,2,'2026-01-13 17:20:00',1299.00,NULL,'Dostarczono na adres firmowy.'),
(28,10,1,NULL,'2026-01-14 10:10:00',199.00,NULL,'Czeka na platnosc - sent reminder.'),
(29,11,2,NULL,'2026-01-15 15:00:00',599.00,NULL,'Paczka czeka u kuriera na zabranie.'),
(30,12,3,5,'2026-01-16 12:00:00',899.00,NULL,'Wysylka potwierdzona przesyłką rekomendowana.'),
(31,13,4,2,'2026-01-17 09:55:00',2499.00,NULL,'Wyslano na adres przychodni medycznej.'),
(32,14,5,3,'2026-01-18 14:25:00',149.00,NULL,'Dostarczono i odebrано przez klienta.'),
(33,15,2,NULL,'2026-01-19 18:40:00',699.00,NULL,'Oczekuje na wybranie przez odbiorcę w punkcie.'),
(34,16,3,4,'2026-01-20 11:35:00',349.00,NULL,'Zamowienie anulowane - brak odpowiedzi klienta.'),
(35,17,4,5,'2026-01-21 16:15:00',2199.00,NULL,'Wymagana faktura - dane poporawione.'),
(36,18,5,2,'2026-01-22 13:05:00',499.00,NULL,'Dostarczono - klient prosił notatkę w paczce.'),
(37,19,1,NULL,'2026-01-23 08:45:00',89.00,NULL,'Nowe zamowienie, wymagane potwierdzenie.'),
(38,20,2,NULL,'2026-01-24 19:20:00',1599.00,NULL,'Platnosc przyjeta, zaplanowana wysylka jutro.');

INSERT INTO pozycje_zamowienia (id, zamowienie_id, produkt_id, ilosc, cena_jednostkowa, rabat_proc) VALUES
(1,1,9,1,399.00,0.00),
(2,1,12,10,12.90,0.00),
(3,2,4,1,899.00,0.00),
(4,3,10,1,129.00,0.00),
(5,3,12,1,12.90,0.00),
(6,4,14,1,699.00,0.00),
(7,5,7,1,79.00,0.00),
(8,6,1,1,3299.00,0.00),
(9,7,21,1,1699.00,0.00),
(10,8,31,1,1149.00,0.00),
(11,9,4,1,899.00,15.00),
(12,10,42,1,399.00,0.00),
(13,11,35,1,129.00,0.00),
(14,12,14,1,699.00,15.00),
(15,13,46,1,1899.00,0.00),
(16,14,30,1,549.00,0.00),
(17,15,11,1,449.00,0.00),
(18,16,44,1,349.00,0.00),
(19,17,20,1,2899.00,8.00),
(20,18,5,1,1399.00,0.00),
(21,19,1,1,3299.00,10.00),
(22,20,7,2,79.00,0.00),
(23,21,10,1,129.00,12.50),
(24,22,4,1,899.00,15.00),
(25,23,21,1,1699.00,0.00);

INSERT INTO platnosci (id, zamowienie_id, typ, kwota, data_platnosci, status_id) VALUES
(1,1,'przelew',528.00,'2026-02-06 12:18:00',2),
(2,2,'karta',899.00,'2026-02-10 18:06:00',2),
(3,3,'pobranie',141.90,'2026-02-16 09:22:00',1),
(4,4,'przelew',699.00,'2026-02-19 15:02:00',2),
(5,5,'blik',79.00,'2026-02-22 11:11:00',2),
(6,6,'karta',3299.00,'2026-02-28 20:19:00',1),
(7,7,'karta',1699.00,'2026-03-03 10:27:00',2),
(8,8,'przelew',1149.00,'2026-03-05 16:00:00',2),
(9,9,'blik',764.15,'2026-03-08 09:19:00',2),
(10,10,'karta',399.00,'2026-03-12 12:13:00',2),
(11,11,'przelew',129.00,'2026-03-15 17:36:00',1),
(12,12,'karta',594.15,'2026-03-18 08:28:00',2),
(13,13,'karta',1899.00,'2026-03-22 13:05:00',2),
(14,14,'przelew',549.00,'2026-03-26 19:47:00',2),
(15,15,'blik',449.00,'2026-03-29 11:11:00',2),
(16,16,'przelew',349.00,'2026-04-02 10:02:00',1),
(17,17,'karta',2667.08,'2026-04-05 14:22:00',2),
(18,18,'przelew',1399.00,'2026-04-08 16:52:00',2),
(19,19,'karta',299.00,'2026-01-05 10:20:00',2),
(20,20,'blik',899.00,'2026-01-06 12:45:00',2),
(21,21,'przelew',129.00,'2026-01-07 09:25:00',1),
(22,22,'karta',1599.00,'2026-01-08 14:15:00',2);

INSERT INTO dostawy (id, zamowienie_id, status_id, kurier_id, numer_przesylki, koszt, przewidywana_dostawa, data_wysylki, data_dostarczenia) VALUES
(1,1,3,1,'PK-0001',12.00,'2026-02-08','2026-02-07 09:10:00','2026-02-08 14:55:00'),
(2,2,2,2,'PK-0002',15.00,'2026-02-12','2026-02-11 10:30:00',NULL),
(3,3,1,1,NULL,12.00,'2026-02-19',NULL,NULL),
(4,4,1,3,NULL,20.00,'2026-02-22',NULL,NULL),
(5,5,3,2,'PK-0005',15.00,'2026-02-24','2026-02-23 08:40:00','2026-02-24 16:12:00'),
(6,6,1,3,NULL,20.00,'2026-03-02',NULL,NULL),
(7,7,1,1,NULL,12.00,'2026-03-06',NULL,NULL),
(8,8,1,3,NULL,20.00,'2026-03-08',NULL,NULL),
(9,9,2,2,'PK-0009',15.00,'2026-03-10','2026-03-09 11:20:00',NULL),
(10,10,3,1,'PK-0010',12.00,'2026-03-14','2026-03-13 08:50:00','2026-03-14 15:35:00'),
(11,11,1,4,NULL,9.99,'2026-03-18',NULL,NULL),
(12,12,1,2,NULL,15.00,'2026-03-21',NULL,NULL),
(13,13,1,6,NULL,18.00,'2026-03-25',NULL,NULL),
(14,14,2,4,'PK-0014',9.99,'2026-03-29','2026-03-27 09:35:00',NULL),
(15,15,3,5,'PK-0015',16.50,'2026-03-30','2026-03-29 14:22:00','2026-03-30 17:48:00'),
(16,16,1,1,NULL,12.00,'2026-04-05',NULL,NULL),
(17,17,1,7,NULL,21.00,'2026-04-09',NULL,NULL),
(18,18,1,3,NULL,20.00,'2026-04-12',NULL,NULL),
(19,19,1,1,'PK-X001',12.00,'2026-01-07',NULL,NULL),
(20,20,2,2,'PK-X002',15.00,'2026-01-08',NULL,NULL),
(21,21,3,3,'PK-X003',20.00,'2026-01-09',NULL,NULL),
(22,22,2,1,'PK-X004',12.00,'2026-01-10',NULL,NULL);

INSERT INTO logi_systemowe (id, pracownik_id, klient_id, typ, opis, data_zdarzenia) VALUES
(1,1,NULL,'info','Zaimportowano tabele','2026-03-31 09:00:00'),
(2,2,7,'info','Zamowienie #7 przyjete do realizacji','2026-03-03 10:30:00'),
(3,4,8,'info','Rozpoczeto kompletacje zamowienia #8','2026-03-05 16:20:00'),
(4,3,9,'info','Nadano przesylke dla zamowienia #9','2026-03-09 11:25:00'),
(5,2,10,'info','Dostarczono zamowienie #10','2026-03-14 15:40:00'),
(6,NULL,12,'warning','Nieudane logowanie klienta: rafal.grabowski@example.com','2026-04-06 19:49:00');

-- Hashe haseł
UPDATE `klienci` SET `haslo_hash_sha512` = '3262e21ac5ac2aad54626948a95aaea01939a7cf2b026e2f660cc17a18a123cdeb2fc59430c9e34faa07b112f6f5a42430820dec78663a59b254a3a04e79ecfe' WHERE `id` = 1;
UPDATE `klienci` SET `haslo_hash_sha512` = '485c7424d1581a45c183072e21bbf1a5f430c0174438103ea4fb18c972e34d56898e1a643dc381fea72e5fe1478140658fa290330271a03aebc1941b286634cf' WHERE `id` = 2;
UPDATE `klienci` SET `haslo_hash_sha512` = 'a9c91a8bb522500f32be30c11a1d61b8654addae3030def6044e76be1736ba81b2c7dc0ef3ed8abbcb0af786bfa3853ecc387ef8ade020ab6c6e05b63fd0a71d' WHERE `id` = 3;
UPDATE `klienci` SET `haslo_hash_sha512` = '9099fe6510bbb42506926645b4a56d145036b0ff8b2b61fca5a393923ed98998f9fe3354629f67821c9f998b2377ab514e691837d717eea41dc7d9a2a0324ebf' WHERE `id` = 4;
UPDATE `klienci` SET `haslo_hash_sha512` = '8f87b6038aad68998c71256e07d09d3ec4965944c0958e3123519035201c90f95e07192981ca05260ec9349c030fa511f0d900ab08fb28e8317177d0546b0b51' WHERE `id` = 5;
UPDATE `klienci` SET `haslo_hash_sha512` = '168667f5a1bc24b5eae1abcf4a6c7ad2f47f4794e199c61647a988899f30e50162bb8ea4802ca6d55c317d3347d4f72ba114735264d6e00c0ee511ce7b1ff551' WHERE `id` = 6;
UPDATE `klienci` SET `haslo_hash_sha512` = '3653f57e90dac95e2b732cce504bc9d77cbb7db27520216a2e4cccce14d4aafe03825864835517b716f74012819362a88395f6850575ee4f4a8ef6bdcb5a162d' WHERE `id` = 7;
UPDATE `klienci` SET `haslo_hash_sha512` = '198281b3604f7c0fca0cf1acf82ac1d6c1d81aba3abf0b026a308416383fe30b65c5b9ae64c3be51f54143019b044d9f4ee794cf8634d27629a5ece938cc6a45' WHERE `id` = 8;
UPDATE `klienci` SET `haslo_hash_sha512` = 'cb5ddc64739a64a04cb53683352c73222fcc3967386594097637c88a76a1949b6d8080bf81ed3abe791b7146ea7f9997e00772cd75a5044c6ac9c4e75c0514fc' WHERE `id` = 9;
UPDATE `klienci` SET `haslo_hash_sha512` = '673cf1b5a993699dcadff605244b4707f45b063d58f275babcd55bcc43130067dc1c709caa3ed09edd68b93f3536e4560a6574f5ac8294a31b46a7f6c8fc4955' WHERE `id` = 10;
UPDATE `klienci` SET `haslo_hash_sha512` = 'de78f81fed5275c0e6160f7b43a3d07cb6ec7bdd1bf11ff6a802890519b69248a4810521d1604d8375fc8eb19f3254c751a431c30a73a1598c9c66abd0175034' WHERE `id` = 11;
UPDATE `klienci` SET `haslo_hash_sha512` = 'ce7b0217707f751ff34891a8a2cc1bec7ca062b34d8187e00b7c0d0d6368c80f1869e60df9c40c40eacf7a78857c6508efbf1521be8c8e3e411ddf4fce1a9475' WHERE `id` = 12;
UPDATE `klienci` SET `haslo_hash_sha512` = 'efdeaccb45c4c6e4312575286a62bd9dfb93126693e15b0d19c89ac7de81817ccdd5c4f97e2129b8810956f93161e756afdabc8116e453c2f9d302f7b1df96fa' WHERE `id` = 13;
UPDATE `klienci` SET `haslo_hash_sha512` = '63973966a60e3494f15b7adf0cf92665472b12359859b92d76f7c1d5e3b5a293a840bd9648c93e442ff0cc009262c5c22914932453f978753654fdc4ff7f73d5' WHERE `id` = 14;
UPDATE `klienci` SET `haslo_hash_sha512` = '439ad21aee983e526afca14b01ab04c4ed7619cd0406aa594feb094d7725302343ef7222664de4e93d98f0e15adffa6042c5d2f868863ccf36f12b51523e904c' WHERE `id` = 15;
UPDATE `klienci` SET `haslo_hash_sha512` = 'd863dc12a4eb60996095541b89f6766d8c6c489ebd1c7c575c14b76d2131feff120a6c2112b331b85d3ca26ca016720be00cea85c50996332aa5d7c840270102' WHERE `id` = 16;
UPDATE `klienci` SET `haslo_hash_sha512` = '46d109c8c0d9c043cd33f0bd927e3fe49bbbe5c334e7eb25e24364d65a17d1cf9c000408bb4392b8cac98da021c5a99878288c15ed52e0f154d51e78fcdd94f3' WHERE `id` = 17;
UPDATE `klienci` SET `haslo_hash_sha512` = 'f22ec99074da8341a37434e106563b12316968c39d5599f9d506e81fb3b78447f7b8992f57f0d9982121b3d605b888bd412088095e12875c507b7dd976b28a6b' WHERE `id` = 18;
UPDATE `klienci` SET `haslo_hash_sha512` = '3933de734c32c8f4d6c32c6766a997b08652e63279e7bb37a35cdf752a9fb249fa58caadf3fdb3f8d7af4dbb5227ac40bf2cf647d0c4483a09c8a232b7aec8c5' WHERE `id` = 19;
UPDATE `klienci` SET `haslo_hash_sha512` = 'baed5ced32eabf6350af60815fcaafa17483d621287c038d810d845254236d26ba66016b1cc2cd3300420de1a701b0d9fed91cbbdd52ebf80ac72f7a3a407a7a' WHERE `id` = 20;
UPDATE `klienci` SET `haslo_hash_sha512` = '29ab7e3048f347af8cfb0f76b85f074ec847ff2b13cb24dc9615e4c633681eeef514b222b829b988e8fbe22538026ed0533254760dc33c60ee9691035bba5a96' WHERE `id` = 21;
UPDATE `klienci` SET `haslo_hash_sha512` = '077b311c05acba23603e9afb3722d47721176fd78a4a54b1afbc073a879a84a6651ac4da3eb75cf897d5760fd5308c54841463d22e61d2c837e41c827f2eb597' WHERE `id` = 22;
UPDATE `klienci` SET `haslo_hash_sha512` = '4f2c4836bf5079a0e238f6d72a34ad2dc62d51c871b0419a390fb8b1baf057a1ad6a3da56fbbfcd654041987721f027c6dadd6e122c18e171493e6d311b62908' WHERE `id` = 23;
UPDATE `klienci` SET `haslo_hash_sha512` = '4de7e8aa27677576316ddd91bbc631b48602f84c462c510ed3265538258dac7c9eed5e5c02c31c2deab7f73c18eac1f2392b2651ab488e88e3c6707ac9482364' WHERE `id` = 24;
UPDATE `klienci` SET `haslo_hash_sha512` = '5da29978207e8cc4c4d83b8c9a71b44452712bb9716d10a2070896d4720e36781e38dba9042dfe1b1432eef33d2109f214e8dc102aa70dd6084096e61c06f6c0' WHERE `id` = 25;
UPDATE `klienci` SET `haslo_hash_sha512` = '3812963188828709c8674f3d4b81a8d942b09412ba5dcd63db34dbc4db4c630c4034ea6e1677252d4b415bc973ba9016ca6ddf6d99205d8213e7d82fa2dfb9ee' WHERE `id` = 26;
UPDATE `klienci` SET `haslo_hash_sha512` = 'af2143eeea278246d42b707e4af295f99c0ff3af084dbf47b7282874775b807fa9d65d30e9c6c757aaee347b82a55249e6f610dda663b6ba0024b1fd290d9df9' WHERE `id` = 27;
UPDATE `klienci` SET `haslo_hash_sha512` = '7ef1804080b127e7f95c642fd19ed24bffcb78077f425f1c4cbbdfa95dd1b1461f2f79fcef1723ecc4f6457d7495a6831a992ccbccfd1a33aa9bdbf6d558deb2' WHERE `id` = 28;
UPDATE `klienci` SET `haslo_hash_sha512` = '75342bbfe8a56691a93b9e49493098256264b2ee71cbf407109a49f20d6cff28d8a90806fbf25e0a505329cc1fbbc50f7d604d858d7d262dcf1bd58ff9da78b4' WHERE `id` = 29;
UPDATE `klienci` SET `haslo_hash_sha512` = 'd530536ec793cd8b35b251a7592328acf0686b1eeb2837a1b01d7af2eb027a021852ca6b900c426428fadc5453533b1efefca0748e6552aa4ff44b75844e2c0b' WHERE `id` = 30;
UPDATE `klienci` SET `haslo_hash_sha512` = '25cc68149d9d8e4c8d8f6e679d344adbbdf5f06401007059206fea9cfa6a650e46cd870d2cec28c6341d100b363ca7d6c619387397e33f7148aa015fb3d5e276' WHERE `id` = 31;
UPDATE `klienci` SET `haslo_hash_sha512` = '70e2ae10d62ebb626163177a6c609eb4120eb366790101423effef024ec559fc4558544cd4ed2e272536baffd9c98b4491c1b02fb1747dda50ce461c34ac63f3' WHERE `id` = 32;
UPDATE `klienci` SET `haslo_hash_sha512` = 'efdeaccb45c4c6e4312575286a62bd9dfb93126693e15b0d19c89ac7de81817ccdd5c4f97e2129b8810956f93161e756afdabc8116e453c2f9d302f7b1df96fa' WHERE `id` = 33;
UPDATE `klienci` SET `haslo_hash_sha512` = 'a13f27526a8c845510b65ea82bcc95d9b28683708bd57a12bd076f8ab6ba5198848591d4119598de2de803391c07e58522e33699493edc2e0e2be38f1ddc1e87' WHERE `id` = 34;
UPDATE `klienci` SET `haslo_hash_sha512` = 'fa90fe0769b38bc4dd3da37a906299abbd108f07d831fb3f00de97773632c6c322d684126736a406045470135574b1d993ad59f712476a06c9bccff0e4c31dd6' WHERE `id` = 35;
UPDATE `klienci` SET `haslo_hash_sha512` = '63e703c39afa0a9c6b33bd5afbcf5e60418a0050a739b4330e3058e0b82318e6d425706c85280e8d091c9bc94327a2b0b347a863533fc4c8082eeac326eed272' WHERE `id` = 36;
UPDATE `klienci` SET `haslo_hash_sha512` = '8d1b94e726b3b59608bb755ef8de99a40dca2da862480211c933ab63e9898f1e154861c2eec3eae8ae34e03c0965c9d6e02a30d49d015769eb2c5a7ce7ebc7f0' WHERE `id` = 37;
UPDATE `klienci` SET `haslo_hash_sha512` = '460f9292b551deb3f2049b1e89d886c277619813f86de5017aa18f96ad171357bcb4fe2fa31688c992993237f58fbaf758dbc57c62a26ff170ecacc18e292b4d' WHERE `id` = 38;
UPDATE `klienci` SET `haslo_hash_sha512` = '1bbd1a6a3acb0b535597a4afa84d7d3e2a69267a4556a8af9fadbdc633dc5769e44b64b0042b9842fbd93d74ae4aa2d27f52b0526fc36a9edcbd1bd83350f924' WHERE `id` = 39;
UPDATE `pracownicy` SET `haslo_hash_sha512` = '473b69d4b2e929dca2970d8a876ec307fe869114ff3a678d241e898971cf777fa43dab4761ded4fc22b4d5bd9307272589a615d0ad6e978f939c6c903512e56d' WHERE `id` = 1;
UPDATE `pracownicy` SET `haslo_hash_sha512` = '198281b3604f7c0fca0cf1acf82ac1d6c1d81aba3abf0b026a308416383fe30b65c5b9ae64c3be51f54143019b044d9f4ee794cf8634d27629a5ece938cc6a45' WHERE `id` = 2;
UPDATE `pracownicy` SET `haslo_hash_sha512` = '485c7424d1581a45c183072e21bbf1a5f430c0174438103ea4fb18c972e34d56898e1a643dc381fea72e5fe1478140658fa290330271a03aebc1941b286634cf' WHERE `id` = 3;
UPDATE `pracownicy` SET `haslo_hash_sha512` = '6759d452411ba0266709cffd64275cd8fbacb5444b321e77de5ba3c766de4e03fd5b4f5b18e023224d385eeb0af3c5a509023b7765a4d3db0a6931e9d6aa6c41' WHERE `id` = 4;
UPDATE `pracownicy` SET `haslo_hash_sha512` = 'ecad48284fa927a35daa1c9d3251fc2e4f46dccc2c98dc2b5b41e2a5468cb5608a302d36b790ebc855e3c7c6446b7df5e967e2b2c48a40f3084c65fc467689d2' WHERE `id` = 5;

CREATE OR REPLACE VIEW vw_admin_klienci_statystyki_zamowien AS
SELECT
  k.id AS klient_id,
  CONCAT(k.imie, ' ', k.nazwisko) AS klient,
  k.email,
  COUNT(z.id) AS liczba_zamowien,
  ROUND(COALESCE(SUM(z.kwota_brutto), 0), 2) AS suma_wydatkow,
  MAX(z.data_zamowienia) AS ostatnie_zamowienie
FROM klienci k
LEFT JOIN zamowienia z ON z.klient_id = k.id
GROUP BY k.id, k.imie, k.nazwisko, k.email;

CREATE OR REPLACE VIEW vw_admin_zamowienia_pelne AS
SELECT
  z.id AS zamowienie_id,
  z.data_zamowienia,
  z.klient_id,
  CONCAT(k.imie, ' ', k.nazwisko) AS klient,
  sz.nazwa AS status_zamowienia,
  p.typ AS platnosc_typ,
  sp.nazwa AS status_platnosci,
  d.numer_przesylki,
  ROUND(COALESCE(SUM(pj.ilosc * pj.cena_jednostkowa * (1 - pj.rabat_proc / 100)), 0), 2) AS pozycje_total,
  z.kwota_brutto
FROM zamowienia z
LEFT JOIN klienci k ON k.id = z.klient_id
LEFT JOIN statusy_zamowienia sz ON sz.id = z.status_id
LEFT JOIN platnosci p ON p.zamowienie_id = z.id
LEFT JOIN statusy_platnosci sp ON sp.id = p.status_id
LEFT JOIN dostawy d ON d.zamowienie_id = z.id
LEFT JOIN pozycje_zamowienia pj ON pj.zamowienie_id = z.id
GROUP BY
  z.id,
  z.data_zamowienia,
  z.klient_id,
  k.imie,
  k.nazwisko,
  sz.nazwa,
  p.typ,
  sp.nazwa,
  d.numer_przesylki,
  z.kwota_brutto;

CREATE OR REPLACE VIEW vw_admin_produkty_stan_sprzedaz AS
SELECT
  pr.id AS produkt_id,
  pr.nazwa,
  pr.sku,
  pr.stan_magazynowy,
  k.nazwa AS kategoria,
  COALESCE(SUM(pz.ilosc), 0) AS sprzedane_sztuki,
  MAX(z.data_zamowienia) AS ostatnia_sprzedaz
FROM produkty pr
LEFT JOIN kategorie k ON k.id = pr.kategoria_id
LEFT JOIN pozycje_zamowienia pz ON pz.produkt_id = pr.id
LEFT JOIN zamowienia z ON z.id = pz.zamowienie_id
GROUP BY pr.id, pr.nazwa, pr.sku, pr.stan_magazynowy, k.nazwa;

CREATE OR REPLACE VIEW vw_admin_aktywne_koszyki AS
SELECT
  kos.id AS koszyk_id,
  kos.klient_id,
  CONCAT(k.imie, ' ', k.nazwisko) AS klient,
  kos.session_token,
  kos.status,
  COUNT(pk.id) AS liczba_pozycji,
  ROUND(COALESCE(SUM(pk.ilosc * pk.cena_koncowa), 0), 2) AS wartosc_koszyka,
  kos.utworzono_at,
  kos.zaktualizowano_at
FROM koszyki kos
LEFT JOIN klienci k ON k.id = kos.klient_id
LEFT JOIN pozycje_koszyka pk ON pk.koszyk_id = kos.id
WHERE kos.status IN ('aktywny', 'porzucony')
GROUP BY
  kos.id,
  kos.klient_id,
  k.imie,
  k.nazwisko,
  kos.session_token,
  kos.status,
  kos.utworzono_at,
  kos.zaktualizowano_at;

CREATE OR REPLACE VIEW vw_admin_wykres_sprzedaz_dzienna_90 AS
SELECT
  DATE(z.data_zamowienia) AS dzien,
  ROUND(COALESCE(SUM(pz.ilosc * pz.cena_jednostkowa * (1 - pz.rabat_proc / 100)), 0), 2) AS przychod,
  COALESCE(SUM(pz.ilosc), 0) AS sprzedane_sztuki
FROM zamowienia z
JOIN pozycje_zamowienia pz ON pz.zamowienie_id = z.id
WHERE z.data_zamowienia >= DATE_SUB(CURDATE(), INTERVAL 89 DAY)
  AND z.data_zamowienia < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
GROUP BY DATE(z.data_zamowienia);

CREATE OR REPLACE VIEW vw_admin_wykres_przychod_kategorie_90 AS
SELECT
  k.nazwa AS kategoria,
  ROUND(COALESCE(SUM(pz.ilosc * pz.cena_jednostkowa * (1 - pz.rabat_proc / 100)), 0), 2) AS przychod,
  COALESCE(SUM(pz.ilosc), 0) AS sprzedane_sztuki
FROM zamowienia z
JOIN pozycje_zamowienia pz ON pz.zamowienie_id = z.id
JOIN produkty pr ON pr.id = pz.produkt_id
JOIN kategorie k ON k.id = pr.kategoria_id
WHERE z.data_zamowienia >= DATE_SUB(CURDATE(), INTERVAL 89 DAY)
  AND z.data_zamowienia < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
GROUP BY k.id, k.nazwa;

CREATE OR REPLACE VIEW vw_admin_wykres_top_produkty_90 AS
SELECT
  pr.id AS produkt_id,
  pr.nazwa,
  pr.sku,
  COALESCE(SUM(pz.ilosc), 0) AS sprzedane_sztuki,
  ROUND(COALESCE(SUM(pz.ilosc * pz.cena_jednostkowa * (1 - pz.rabat_proc / 100)), 0), 2) AS przychod
FROM zamowienia z
JOIN pozycje_zamowienia pz ON pz.zamowienie_id = z.id
JOIN produkty pr ON pr.id = pz.produkt_id
WHERE z.data_zamowienia >= DATE_SUB(CURDATE(), INTERVAL 89 DAY)
  AND z.data_zamowienia < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
GROUP BY pr.id, pr.nazwa, pr.sku;

CREATE OR REPLACE VIEW vw_admin_wykres_koszyki_dzienne_90 AS
SELECT
  DATE(kos.zaktualizowano_at) AS dzien,
  SUM(CASE WHEN kos.status = 'aktywny' THEN 1 ELSE 0 END) AS aktywne_koszyki,
  SUM(CASE WHEN kos.status = 'porzucony' THEN 1 ELSE 0 END) AS porzucone_koszyki
FROM koszyki kos
WHERE kos.zaktualizowano_at >= DATE_SUB(CURDATE(), INTERVAL 89 DAY)
  AND kos.zaktualizowano_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
GROUP BY DATE(kos.zaktualizowano_at);

DELIMITER //

DROP TRIGGER IF EXISTS trg_log_klienci_insert//
CREATE TRIGGER trg_log_klienci_insert
AFTER INSERT ON klienci
FOR EACH ROW
BEGIN
  INSERT INTO logi_systemowe (pracownik_id, klient_id, typ, opis, data_zdarzenia)
  VALUES (NULL, NEW.id, 'security', CONCAT('Rejestracja klienta: ', NEW.email), NOW());
END//

DROP TRIGGER IF EXISTS trg_log_klienci_update//
CREATE TRIGGER trg_log_klienci_update
AFTER UPDATE ON klienci
FOR EACH ROW
BEGIN
  IF NOT (OLD.status <=> NEW.status) THEN
    INSERT INTO logi_systemowe (pracownik_id, klient_id, typ, opis, data_zdarzenia)
    VALUES (
      NULL,
      NEW.id,
      'warning',
      CONCAT('Zmiana statusu klienta z ', OLD.status, ' na ', NEW.status),
      NOW()
    );
  END IF;

  IF NOT (OLD.email <=> NEW.email) THEN
    INSERT INTO logi_systemowe (pracownik_id, klient_id, typ, opis, data_zdarzenia)
    VALUES (
      NULL,
      NEW.id,
      'security',
      CONCAT('Zmiana email klienta z ', OLD.email, ' na ', NEW.email),
      NOW()
    );
  END IF;
END//

DROP TRIGGER IF EXISTS trg_log_zamowienia_insert//
CREATE TRIGGER trg_log_zamowienia_insert
AFTER INSERT ON zamowienia
FOR EACH ROW
BEGIN
  DECLARE v_status_new VARCHAR(40);
  SELECT nazwa INTO v_status_new FROM statusy_zamowienia WHERE id = NEW.status_id LIMIT 1;

  INSERT INTO logi_systemowe (pracownik_id, klient_id, typ, opis, data_zdarzenia)
  VALUES (
    NEW.pracownik_id,
    NEW.klient_id,
    'info',
    CONCAT('Nowe zamowienie #', NEW.id, ', status=', COALESCE(v_status_new, CONCAT('#', NEW.status_id)), ', kwota=', NEW.kwota_brutto),
    NOW()
  );
END//

DROP TRIGGER IF EXISTS trg_log_zamowienia_update//
CREATE TRIGGER trg_log_zamowienia_update
AFTER UPDATE ON zamowienia
FOR EACH ROW
BEGIN
  DECLARE v_status_old VARCHAR(40);
  DECLARE v_status_new VARCHAR(40);
  DECLARE v_details TEXT DEFAULT '';
  SELECT nazwa INTO v_status_old FROM statusy_zamowienia WHERE id = OLD.status_id LIMIT 1;
  SELECT nazwa INTO v_status_new FROM statusy_zamowienia WHERE id = NEW.status_id LIMIT 1;

  IF NOT (OLD.status_id <=> NEW.status_id) OR NOT (OLD.pracownik_id <=> NEW.pracownik_id) THEN
    IF NOT (OLD.status_id <=> NEW.status_id) THEN
      SET v_details = CONCAT(
        v_details,
        'status ',
        COALESCE(v_status_old, CONCAT('#', OLD.status_id)),
        '->',
        COALESCE(v_status_new, CONCAT('#', NEW.status_id))
      );
    END IF;

    IF NOT (OLD.pracownik_id <=> NEW.pracownik_id) THEN
      IF v_details <> '' THEN
        SET v_details = CONCAT(v_details, ', ');
      END IF;
      SET v_details = CONCAT(
        v_details,
        'pracownik ',
        COALESCE(CAST(OLD.pracownik_id AS CHAR), 'NULL'),
        '->',
        COALESCE(CAST(NEW.pracownik_id AS CHAR), 'NULL')
      );
    END IF;

    INSERT INTO logi_systemowe (pracownik_id, klient_id, typ, opis, data_zdarzenia)
    VALUES (
      NEW.pracownik_id,
      NEW.klient_id,
      'info',
      CONCAT('Aktualizacja zamowienia #', NEW.id, ': ', v_details),
      NOW()
    );
  END IF;
END//

DROP TRIGGER IF EXISTS trg_log_platnosci_update//
CREATE TRIGGER trg_log_platnosci_update
AFTER UPDATE ON platnosci
FOR EACH ROW
BEGIN
  DECLARE v_status_old VARCHAR(40);
  DECLARE v_status_new VARCHAR(40);
  DECLARE v_details TEXT DEFAULT '';
  SELECT nazwa INTO v_status_old FROM statusy_platnosci WHERE id = OLD.status_id LIMIT 1;
  SELECT nazwa INTO v_status_new FROM statusy_platnosci WHERE id = NEW.status_id LIMIT 1;

  IF NOT (OLD.status_id <=> NEW.status_id) OR NOT (OLD.kwota <=> NEW.kwota) THEN
    IF NOT (OLD.status_id <=> NEW.status_id) THEN
      SET v_details = CONCAT(
        v_details,
        'status ',
        COALESCE(v_status_old, CONCAT('#', OLD.status_id)),
        '->',
        COALESCE(v_status_new, CONCAT('#', NEW.status_id))
      );
    END IF;

    IF NOT (OLD.kwota <=> NEW.kwota) THEN
      IF v_details <> '' THEN
        SET v_details = CONCAT(v_details, ', ');
      END IF;
      SET v_details = CONCAT(v_details, 'kwota ', OLD.kwota, '->', NEW.kwota);
    END IF;

    INSERT INTO logi_systemowe (pracownik_id, klient_id, typ, opis, data_zdarzenia)
    VALUES (
      NULL,
      NULL,
      IF(NEW.status_id = 3, 'warning', 'info'),
      CONCAT('Aktualizacja platnosci dla zamowienia #', NEW.zamowienie_id, ': ', v_details),
      NOW()
    );
  END IF;
END//

DROP TRIGGER IF EXISTS trg_log_dostawy_update//
CREATE TRIGGER trg_log_dostawy_update
AFTER UPDATE ON dostawy
FOR EACH ROW
BEGIN
  DECLARE v_status_old VARCHAR(40);
  DECLARE v_status_new VARCHAR(40);
  DECLARE v_details TEXT DEFAULT '';
  SELECT nazwa INTO v_status_old FROM statusy_dostawy WHERE id = OLD.status_id LIMIT 1;
  SELECT nazwa INTO v_status_new FROM statusy_dostawy WHERE id = NEW.status_id LIMIT 1;

  IF NOT (OLD.status_id <=> NEW.status_id) OR NOT (OLD.numer_przesylki <=> NEW.numer_przesylki) THEN
    IF NOT (OLD.status_id <=> NEW.status_id) THEN
      SET v_details = CONCAT(
        v_details,
        'status ',
        COALESCE(v_status_old, CONCAT('#', OLD.status_id)),
        '->',
        COALESCE(v_status_new, CONCAT('#', NEW.status_id))
      );
    END IF;

    IF NOT (OLD.numer_przesylki <=> NEW.numer_przesylki) THEN
      IF v_details <> '' THEN
        SET v_details = CONCAT(v_details, ', ');
      END IF;
      SET v_details = CONCAT(
        v_details,
        'numer ',
        COALESCE(OLD.numer_przesylki, 'NULL'),
        '->',
        COALESCE(NEW.numer_przesylki, 'NULL')
      );
    END IF;

    INSERT INTO logi_systemowe (pracownik_id, klient_id, typ, opis, data_zdarzenia)
    VALUES (
      NULL,
      NULL,
      'info',
      CONCAT('Aktualizacja dostawy dla zamowienia #', NEW.zamowienie_id, ': ', v_details),
      NOW()
    );
  END IF;
END//

DROP TRIGGER IF EXISTS trg_log_produkty_stock_update//
CREATE TRIGGER trg_log_produkty_stock_update
AFTER UPDATE ON produkty
FOR EACH ROW
BEGIN
  IF NOT (OLD.stan_magazynowy <=> NEW.stan_magazynowy) THEN
    INSERT INTO logi_systemowe (pracownik_id, klient_id, typ, opis, data_zdarzenia)
    VALUES (
      NULL,
      NULL,
      IF(NEW.stan_magazynowy <= 5, 'warning', 'info'),
      CONCAT(
        'Zmiana stanu magazynowego produktu #',
        NEW.id,
        ' (',
        NEW.sku,
        '): ',
        OLD.stan_magazynowy,
        '->',
        NEW.stan_magazynowy
      ),
      NOW()
    );
  END IF;
END//

DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;