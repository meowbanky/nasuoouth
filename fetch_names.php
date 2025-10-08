<?php
require_once 'class/DataBaseHandler.php'; // Adjust path as necessary
$dbHandler = new DataBaseHandler();

if (isset($_GET['term'])) {
    $term = $_GET['term'];
    $results = $dbHandler->fetchNames($term);

    $suggestions = [];
    foreach ($results as $row) {
        $suggestions[] = [
            'label' => $row['Lname'] . ', ' . $row['Fname'].' - '.$row['staff_id'], // The text to display in the autocomplete dropdown
            'value' => $row['staff_id'], // The value to be put in the textbox when this entry is selected
        ];
    }

    echo json_encode($suggestions);
}
