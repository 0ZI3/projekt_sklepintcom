<?php
/**
 * @fileoverview products.php
 *
 * @description
 * Plik odpowiada za administracyjne zarzadzanie produktami w panelu pracownika.
 * Umozliwia przegladanie i wyszukiwanie produktow, dodawanie i edycje danych,
 * zmiane statusu aktywnosci, usuwanie produktu oraz obsluge uploadu zdjec.
 *
 * @scope
 * - Weryfikacja sesji pracownika i uprawnien roli admin.
 * - Pobranie listy kategorii i produktow (pelnej lub filtrowanej).
 * - Obsluga akcji POST: save_product, toggle_active, delete_product.
 * - Walidacja danych produktu i zapis zmian do tabeli produkty.
 * - Upload i konwersja obrazow produktu do formatu WebP oraz zapis w bazie.
 *
 * @behavior
 * - Brak sesji: przekierowanie do logowania pracownika.
 * - Brak roli admin: odpowiedz 403.
 * - Sukces operacji: przekierowanie z odpowiednim parametrem statusu (created/updated/deleted/status).
 * - Blad walidacji/zapisu: pozostanie w widoku z komunikatami bledow.
 */

session_start();
require_once __DIR__ . '/../../db/connection.php';
require_once __DIR__ . '/access_denied.php';

if (!isset($_SESSION['worker_id'])) {
    header('Location: login.php');
    exit();
}

$workerName = $_SESSION['worker_name'] ?? 'Pracownik';
$workerRole = $_SESSION['worker_role'] ?? '';

if (!in_array($workerRole, ['admin', 'magazyn'], true)) {
    renderEmployeeAccessDenied('Nie masz uprawnien do zarzadzania produktami.');
}

$errors = [];
$editingProduct = null;
$search = trim($_GET['q'] ?? '');
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

$categories = [];
$catResult = mysqli_query($conn, 'SELECT id, nazwa FROM kategorie ORDER BY nazwa ASC');
while ($catResult && ($cat = mysqli_fetch_assoc($catResult))) {
    $categories[] = $cat;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function productInput(string $key, array $editingProduct): string
{
    if (isset($_POST[$key])) {
        return trim((string)$_POST[$key]);
    }

    return isset($editingProduct[$key]) ? (string)$editingProduct[$key] : '';
}

function convertToWebp(string $tmpName, string $targetPath): bool
{
    if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
        $imageBinary = @file_get_contents($tmpName);
        if ($imageBinary !== false) {
            $imageResource = @imagecreatefromstring($imageBinary);
            if ($imageResource !== false) {
                $saved = @imagewebp($imageResource, $targetPath, 82);
                imagedestroy($imageResource);
                if ($saved) {
                    return true;
                }
            }
        }
    }

    if (class_exists('Imagick')) {
        try {
            $imagick = new Imagick();
            $imagick->readImage($tmpName);
            $imagick->setImageFormat('webp');
            $imagick->setImageCompressionQuality(82);
            $saved = $imagick->writeImage($targetPath);
            $imagick->clear();
            $imagick->destroy();
            if ($saved) {
                return true;
            }
        } catch (Throwable $e) {
        }
    }

    if (function_exists('exec')) {
        $input = escapeshellarg($tmpName);
        $output = escapeshellarg($targetPath);
        $command = 'cwebp -quiet -q 82 ' . $input . ' -o ' . $output . ' 2>&1';
        $lines = [];
        $exitCode = 1;
        @exec($command, $lines, $exitCode);
        if ($exitCode === 0 && is_file($targetPath) && filesize($targetPath) > 0) {
            return true;
        }
    }

    return false;
}

function saveProductImages(mysqli $conn, int $productId, array $files, array &$errors): void
{
    if ($productId <= 0 || empty($files['name']) || !is_array($files['name'])) {
        return;
    }

    $targetDir = __DIR__ . '/../../public/assets/img';
    if (!is_dir($targetDir)) {
        $errors[] = 'Katalog na zdjecia produktow nie istnieje.';
        return;
    }

    $allowedExtensions = ['webp', 'jpg', 'jpeg', 'png'];
    $allowedMimeTypes = ['image/webp', 'image/jpeg', 'image/png'];

    $nextNumber = 1;
    $existingStmt = mysqli_prepare($conn, 'SELECT nazwa_pliku FROM zdjecia_produktow WHERE produkt_id = ?');
    if ($existingStmt) {
        mysqli_stmt_bind_param($existingStmt, 'i', $productId);
        mysqli_stmt_execute($existingStmt);
        $existingResult = mysqli_stmt_get_result($existingStmt);
        while ($existingResult && ($existing = mysqli_fetch_assoc($existingResult))) {
            $fileName = (string)$existing['nazwa_pliku'];
            if (preg_match('/^' . $productId . '-(\d+)\.[a-zA-Z0-9]+$/', $fileName, $match)) {
                $fileNumber = (int)$match[1];
                if ($fileNumber >= $nextNumber) {
                    $nextNumber = $fileNumber + 1;
                }
            }
        }
        mysqli_stmt_close($existingStmt);
    }

    $fileCount = count($files['name']);
    for ($i = 0; $i < $fileCount; $i++) {
        $uploadError = (int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($uploadError !== UPLOAD_ERR_OK) {
            $errors[] = 'Nie udalo sie przeslac jednego ze zdjec.';
            continue;
        }

        $tmpName = (string)($files['tmp_name'][$i] ?? '');
        $originalName = (string)($files['name'][$i] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            $errors[] = 'Dozwolone formaty zdjec to: webp, jpg, jpeg, png.';
            continue;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? (string)finfo_file($finfo, $tmpName) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($mimeType === '' || !in_array($mimeType, $allowedMimeTypes, true)) {
            $errors[] = 'Wgrany plik nie jest poprawnym obrazem.';
            continue;
        }

        $newFileName = $productId . '-' . $nextNumber . '.webp';
        $targetPath = $targetDir . '/' . $newFileName;

        if (!convertToWebp($tmpName, $targetPath)) {
            $errors[] = 'Nie udalo sie skonwertowac obrazu do WebP. Sprawdz GD, Imagick albo narzedzie cwebp na serwerze.';
            continue;
        }

        $imgStmt = mysqli_prepare($conn, 'INSERT INTO zdjecia_produktow (produkt_id, nazwa_pliku) VALUES (?, ?)');
        if ($imgStmt) {
            mysqli_stmt_bind_param($imgStmt, 'is', $productId, $newFileName);
            if (!mysqli_stmt_execute($imgStmt)) {
                @unlink($targetPath);
                $errors[] = 'Nie udalo sie zapisac informacji o zdjeciu w bazie.';
            } else {
                $nextNumber++;
            }
            mysqli_stmt_close($imgStmt);
        } else {
            @unlink($targetPath);
            $errors[] = 'Blad przygotowania zapytania zapisu zdjecia.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_active') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $newStatus = (int)($_POST['new_status'] ?? -1);

        if ($productId <= 0 || !in_array($newStatus, [0, 1], true)) {
            $errors[] = 'Niepoprawna akcja zmiany statusu produktu.';
        } else {
            $toggleStmt = mysqli_prepare($conn, 'UPDATE produkty SET aktywny = ? WHERE id = ? LIMIT 1');
            if ($toggleStmt) {
                mysqli_stmt_bind_param($toggleStmt, 'ii', $newStatus, $productId);
                if (mysqli_stmt_execute($toggleStmt)) {
                    mysqli_stmt_close($toggleStmt);
                    header('Location: products.php?status=1');
                    exit;
                }
                mysqli_stmt_close($toggleStmt);
                $errors[] = 'Nie udalo sie zmienic statusu produktu.';
            } else {
                $errors[] = 'Blad przygotowania zapytania aktualizacji statusu.';
            }
        }
    }

    if ($action === 'delete_product') {
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($productId <= 0) {
            $errors[] = 'Niepoprawne ID produktu do usuniecia.';
        } else {
            $imageNames = [];
            $imgListStmt = mysqli_prepare($conn, 'SELECT nazwa_pliku FROM zdjecia_produktow WHERE produkt_id = ?');
            if ($imgListStmt) {
                mysqli_stmt_bind_param($imgListStmt, 'i', $productId);
                mysqli_stmt_execute($imgListStmt);
                $imgListResult = mysqli_stmt_get_result($imgListStmt);
                while ($imgListResult && ($imgRow = mysqli_fetch_assoc($imgListResult))) {
                    $imageNames[] = (string)$imgRow['nazwa_pliku'];
                }
                mysqli_stmt_close($imgListStmt);
            }

            $deleteStmt = mysqli_prepare($conn, 'DELETE FROM produkty WHERE id = ? LIMIT 1');
            if ($deleteStmt) {
                mysqli_stmt_bind_param($deleteStmt, 'i', $productId);
                if (mysqli_stmt_execute($deleteStmt) && mysqli_stmt_affected_rows($deleteStmt) === 1) {
                    mysqli_stmt_close($deleteStmt);

                    foreach ($imageNames as $imageName) {
                        if ($imageName === '') {
                            continue;
                        }
                        $imagePath = $targetDir = __DIR__ . '/../../public/assets/img/' . basename($imageName);
                        if (is_file($imagePath)) {
                            @unlink($imagePath);
                        }
                    }

                    header('Location: products.php?deleted=1');
                    exit;
                }

                $deleteError = mysqli_stmt_errno($deleteStmt);
                mysqli_stmt_close($deleteStmt);
                if ($deleteError === 1451) {
                    $errors[] = 'Nie mozna usunac produktu, poniewaz jest powiazany z zamowieniami.';
                } else {
                    $errors[] = 'Nie udalo sie usunac produktu.';
                }
            } else {
                $errors[] = 'Blad przygotowania zapytania usuwania produktu.';
            }
        }
    }

    if ($action === 'save_product') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $categoryId = (int)($_POST['kategoria_id'] ?? 0);
        $nazwa = trim($_POST['nazwa'] ?? '');
        $opis = trim($_POST['opis'] ?? '');
        $cena = trim($_POST['cena'] ?? '');
        $stanMagazynowy = trim($_POST['stan_magazynowy'] ?? '0');
        $sku = trim($_POST['sku'] ?? '');
        $promocjaProc = trim($_POST['promocja_proc'] ?? '0');
        $aktywny = (int)($_POST['aktywny'] ?? 1);

        if ($categoryId <= 0) {
            $errors[] = 'Wybierz kategorie produktu.';
        }
        if ($nazwa === '') {
            $errors[] = 'Nazwa produktu jest wymagana.';
        }
        if ($sku === '') {
            $errors[] = 'SKU produktu jest wymagane.';
        }
        if (!is_numeric($cena) || (float)$cena < 0) {
            $errors[] = 'Cena musi byc liczba wieksza lub rowna 0.';
        }
        if (!ctype_digit((string)$stanMagazynowy)) {
            $errors[] = 'Stan magazynowy musi byc liczba calkowita >= 0.';
        }
        if (!is_numeric($promocjaProc) || (float)$promocjaProc < 0 || (float)$promocjaProc > 100) {
            $errors[] = 'Promocja musi byc liczba od 0 do 100.';
        }
        if (!in_array($aktywny, [0, 1], true)) {
            $errors[] = 'Niepoprawna wartosc pola aktywny.';
        }

        if (!$errors) {
            $cenaValue = (float)$cena;
            $stanValue = (int)$stanMagazynowy;
            $promocjaValue = (float)$promocjaProc;

            if ($productId > 0) {
                $saveStmt = mysqli_prepare(
                    $conn,
                    'UPDATE produkty SET kategoria_id = ?, nazwa = ?, opis = ?, cena = ?, stan_magazynowy = ?, sku = ?, promocja_proc = ?, aktywny = ? WHERE id = ? LIMIT 1'
                );

                if ($saveStmt) {
                    mysqli_stmt_bind_param(
                        $saveStmt,
                        'issdissii',
                        $categoryId,
                        $nazwa,
                        $opis,
                        $cenaValue,
                        $stanValue,
                        $sku,
                        $promocjaValue,
                        $aktywny,
                        $productId
                    );

                    if (mysqli_stmt_execute($saveStmt)) {
                        saveProductImages($conn, $productId, $_FILES['zdjecia'] ?? [], $errors);
                        mysqli_stmt_close($saveStmt);
                        if (!$errors) {
                            header('Location: products.php?updated=1');
                            exit;
                        }
                    } else {
                        $saveError = mysqli_stmt_errno($saveStmt);
                        mysqli_stmt_close($saveStmt);
                        if ($saveError === 1062) {
                            $errors[] = 'Podany SKU juz istnieje.';
                        } else {
                            $errors[] = 'Nie udalo sie zapisac zmian produktu.';
                        }
                    }
                } else {
                    $errors[] = 'Blad przygotowania zapytania aktualizacji produktu.';
                }
            } else {
                $saveStmt = mysqli_prepare(
                    $conn,
                    'INSERT INTO produkty (kategoria_id, nazwa, opis, cena, stan_magazynowy, sku, promocja_proc, aktywny, data_dodania) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
                );

                if ($saveStmt) {
                    mysqli_stmt_bind_param(
                        $saveStmt,
                        'issdissi',
                        $categoryId,
                        $nazwa,
                        $opis,
                        $cenaValue,
                        $stanValue,
                        $sku,
                        $promocjaValue,
                        $aktywny
                    );

                    if (mysqli_stmt_execute($saveStmt)) {
                        $newProductId = (int)mysqli_insert_id($conn);
                        saveProductImages($conn, $newProductId, $_FILES['zdjecia'] ?? [], $errors);
                        mysqli_stmt_close($saveStmt);
                        if (!$errors) {
                            header('Location: products.php?created=1');
                            exit;
                        }

                        $editId = $newProductId;
                        $errors[] = 'Produkt zapisany, ale wystapil problem podczas dodawania zdjec.';
                    } else {
                        $saveError = mysqli_stmt_errno($saveStmt);
                        mysqli_stmt_close($saveStmt);
                        if ($saveError === 1062) {
                            $errors[] = 'Podany SKU juz istnieje.';
                        } else {
                            $errors[] = 'Nie udalo sie dodac produktu.';
                        }
                    }
                } else {
                    $errors[] = 'Blad przygotowania zapytania dodawania produktu.';
                }
            }
        }

        if ($productId > 0) {
            $editId = $productId;
        }
    }
}

if ($editId > 0) {
    $editStmt = mysqli_prepare(
        $conn,
        'SELECT id, kategoria_id, nazwa, opis, cena, stan_magazynowy, sku, promocja_proc, aktywny FROM produkty WHERE id = ? LIMIT 1'
    );

    if ($editStmt) {
        mysqli_stmt_bind_param($editStmt, 'i', $editId);
        mysqli_stmt_execute($editStmt);
        $editResult = mysqli_stmt_get_result($editStmt);
        $editingProduct = $editResult ? mysqli_fetch_assoc($editResult) : null;
        mysqli_stmt_close($editStmt);
    }
}

$products = [];
if ($search !== '') {
    $like = '%' . $search . '%';
    $listStmt = mysqli_prepare(
        $conn,
        'SELECT p.id, p.nazwa, p.sku, p.cena, p.stan_magazynowy, p.promocja_proc, p.aktywny, p.data_dodania, k.nazwa AS kategoria_nazwa FROM produkty p LEFT JOIN kategorie k ON k.id = p.kategoria_id WHERE p.nazwa LIKE ? OR p.sku LIKE ? ORDER BY p.id DESC'
    );

    if ($listStmt) {
        mysqli_stmt_bind_param($listStmt, 'ss', $like, $like);
        mysqli_stmt_execute($listStmt);
        $listResult = mysqli_stmt_get_result($listStmt);
        while ($listResult && ($row = mysqli_fetch_assoc($listResult))) {
            $products[] = $row;
        }
        mysqli_stmt_close($listStmt);
    }
} else {
    $listResult = mysqli_query(
        $conn,
        'SELECT p.id, p.nazwa, p.sku, p.cena, p.stan_magazynowy, p.promocja_proc, p.aktywny, p.data_dodania, k.nazwa AS kategoria_nazwa FROM produkty p LEFT JOIN kategorie k ON k.id = p.kategoria_id ORDER BY p.id DESC'
    );
    while ($listResult && ($row = mysqli_fetch_assoc($listResult))) {
        $products[] = $row;
    }
}
