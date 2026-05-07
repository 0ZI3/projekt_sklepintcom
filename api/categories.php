<?php
/**
 * @fileoverview categories.php
 *
 * @description
 * Plik odpowiada za pobieranie i budowanie drzewa kategorii produktowych.
 * Odczytuje kategorie z bazy danych i przeksztalca liste rekordow na strukture
 * hierarchiczna parent-child wykorzystywana w widokach sklepu.
 *
 * @scope
 * - Inicjalizacja polaczenia z baza danych.
 * - Pobranie kategorii (id, nazwa, nadrzedna_id) posortowanych alfabetycznie.
 * - Budowa struktury wewnetrznej wszystkich kategorii z polem podkategorie.
 * - Powiazanie kategorii nadrzednych z podrzednymi i zwrot gotowego drzewa.
 *
 * @behavior
 * - Dostepne dane: zwrot pelnego drzewa kategorii.
 * - Brak danych lub problem zapytania: zwrot pustej struktury drzewa.
 */

require_once __DIR__ . '/../db/connection.php';

function getCategoryTree() {
    global $conn;
    $sql = "SELECT id, nazwa, nadrzedna_id FROM kategorie ORDER BY nazwa ASC";
    $result = mysqli_query($conn, $sql);
    
    $allCategories = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['podkategorie'] = [];
            $allCategories[$row['id']] = $row;
        }
    }
    
    $tree = [];
    foreach ($allCategories as $id => &$category) {
        if ($category['nadrzedna_id'] == null) {
            $tree[$id] = &$category;
        } else {
            if (isset($allCategories[$category['nadrzedna_id']])) {
                $allCategories[$category['nadrzedna_id']]['podkategorie'][] = &$category;
            }
        }
    }
    return $tree;
}
?>