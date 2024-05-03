<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function generate_roster_excel($post_id) {
    // Check if PhpSpreadsheet is loaded
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        return false;
    }

    // Create new spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Retrieve roster data from the database
    // Assuming you have saved the data in the post meta
    $attendees_data = get_post_meta($post_id, 'attendee_data', true);
    
    if (empty($attendees_data)) {
        return false; // Return false if there is no data
    }

    // Set the header names for each column
    $headers = ['License Number', 'State', 'Date of Completion', 'Profession', 'Medical Hours', 'Non-Medical Hours'];
    $column = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($column . '1', $header);
        $column++;
    }

    // Populate data
    $rowNumber = 2; // Start from the second row after the header
    foreach ($attendees_data as $attendee) {
        $sheet->fromArray(array_values($attendee), NULL, 'A' . $rowNumber);
        $rowNumber++;
    }

    // Write the file to a temporary location
    $writer = new Xlsx($spreadsheet);
    $temp_file = tempnam(sys_get_temp_dir(), 'roster') . '.xlsx';
    $writer->save($temp_file);

    // Optionally, move the file to a more permanent location or directly provide a download link

    return $temp_file; // Return the path to the file
}