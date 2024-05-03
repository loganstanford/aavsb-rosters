<?php

function handle_roster_submission() {
    // Verify the nonce sent in the AJAX request for security
    if (!check_ajax_referer('submit_roster_nonce', 'security', false)) {
        wp_send_json_error(array('message' => 'Nonce verification failed.'));
        return;
    }

    // Sanitize and validate input data
    $course_type = sanitize_text_field($_POST['course_type']);
    $providerNumber = sanitize_text_field($_POST['provider_number']);
    $courseNumber = sanitize_text_field($_POST['course_number']);

    // Insert the roster post
    $post_id = wp_insert_post(array(
        'post_title'  => '50-' . $providerNumber . ' - 20-' . $courseNumber,
        'post_status' => 'publish',
        'post_type'   => 'roster',
        'post_author' => get_current_user_id(),
    ));


    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => $post_id->get_error_message()));
        return;
    }

    if (isset($providerNumber) && isset($courseNumber)) {
        update_post_meta($post_id, 'course_type', $course_type);
        update_post_meta($post_id, 'provider_number', $providerNumber);
        update_post_meta($post_id, 'course_number', $courseNumber);
    }

    // Store all attendees in a single post meta entry
    if (isset($_POST['attendees']) && is_array($_POST['attendees'])) {
        $attendees_data = array_map(function($attendee) {
            return array(
                'license_number' => sanitize_text_field($attendee['license_number']),
                'state' => sanitize_text_field($attendee['state']),
                'date_of_completion' => sanitize_text_field($attendee['date_of_completion']),
                'profession' => sanitize_text_field($attendee['profession']),
                'medical_hours' => sanitize_text_field($attendee['medical_hours']),
                'non_medical_hours' => sanitize_text_field($attendee['non_medical_hours']),
            );
        }, $_POST['attendees']);

        // Encode the array as JSON and save it
        update_post_meta($post_id, 'attendees', json_encode($attendees_data));
    }


    $file_url = export_roster_to_excel($_POST['attendees'], $providerNumber, $courseNumber, $course_type);

    // Return the file URL for download
    wp_send_json_success(array('file_url' => $file_url));
}

add_action('wp_ajax_submit_roster', 'handle_roster_submission'); // handles logged-in users
add_action('wp_ajax_nopriv_submit_roster', 'handle_roster_submission'); // handles non-logged-in users



add_action('wp_ajax_save_roster_progress', 'handle_save_roster_progress');

function handle_save_roster_progress() {
    // Check for nonce and validate data here

    // Create or update the roster with 'In Progress' status
    $post_id = wp_insert_post(array(
        'post_title'  => 'In Progress Roster', // You'll want to set this to something meaningful
        'post_status' => 'draft', // Or a custom status if you've registered one
        'post_type'   => 'roster', // Or whatever your CPT is
        'post_author' => get_current_user_id(),
        // Other necessary fields
    ));

    // Save other form fields as post meta or in a custom table
    update_post_meta($post_id, 'provider_number', sanitize_text_field($_POST['provider_number']));
    // Repeat for other meta fields...

    if (is_wp_error($post_id)) {
        wp_send_json_error('Failed to save progress.');
    } else {
        wp_send_json_success('Progress saved.');
    }
}

function handle_roster_update() {
    if (!check_ajax_referer('update_roster_nonce', 'security', false)) {
        wp_send_json_error(array('message' => 'Nonce verification failed.'));
        return;
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid Roster ID.'));
        return;
    }

    $course_type = sanitize_text_field($_POST['course_type']);
    $providerNumber = sanitize_text_field($_POST['provider_number']);
    $courseNumber = sanitize_text_field($_POST['course_number']);

    // Update basic post meta
    update_post_meta($post_id, 'course_type', $course_type);
    update_post_meta($post_id, 'provider_number', $providerNumber);
    update_post_meta($post_id, 'course_number', $courseNumber);

    // Update attendees
    if (isset($_POST['attendees']) && is_array($_POST['attendees'])) {
        $attendees_data = array_map(function($attendee) {
            return array(
                'license_number' => sanitize_text_field($attendee['license_number']),
                'state' => sanitize_text_field($attendee['state']),
                'date_of_completion' => sanitize_text_field($attendee['date_of_completion']),
                'profession' => sanitize_text_field($attendee['profession']),
                'medical_hours' => sanitize_text_field($attendee['medical_hours']),
                'non_medical_hours' => sanitize_text_field($attendee['non_medical_hours']),
            );
        }, $_POST['attendees']);
        update_post_meta($post_id, 'attendees', json_encode($attendees_data));
    }

    wp_send_json_success(array('message' => 'Roster updated successfully.'));
}

add_action('wp_ajax_update_roster', 'handle_roster_update');

function handle_export_roster() {
    if (!wp_verify_nonce($_POST['security'], 'export_roster_nonce')) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
        return;
    }

    $roster_id = isset($_POST['roster_id']) ? intval($_POST['roster_id']) : null;
    if (!$roster_id) {
        wp_send_json_error(['message' => 'Invalid roster ID']);
        return;
    }

    $attendees_json = get_post_meta($roster_id, 'attendees', true);
    $attendees = json_decode($attendees_json, true);  // Decode the JSON string into an array
    $provider_number = get_post_meta($roster_id, 'provider_number', true);
    $course_number = get_post_meta($roster_id, 'course_number', true);
    $course_type = get_post_meta($roster_id, 'course_type', true);

    $file_url = export_roster_to_excel($attendees, $provider_number, $course_number, $course_type);
    if ($file_url) {
        wp_send_json_success(['file_url' => $file_url]);
    } else {
        wp_send_json_error(['message' => 'Failed to export the roster']);
    }
}
add_action('wp_ajax_export_roster', 'handle_export_roster');


function export_roster_to_excel($attendees, $providerNumber, $courseNumber, $courseType) {
        // Initialize PHPExcel object (or PhpSpreadsheet if using the newer library)
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
    
        // Initialize row count
        $rowCount = $sheet->getHighestRow();
    
        // Loop through each attendee and add rows to the sheet
        foreach ($attendees as $attendee) {
            error_log('Attendee before processing: ' . print_r($attendee, true)); // Log the raw attendee data.
            // Create a new row array with required formatting
            $row = [
                $providerNumber,                                                            // A: Provider Number
                $courseNumber,                                                              // B: Course Number
                '',                                                                         // C: Empty
                '',                                                                         // D: Empty
                $attendee['license_number'],                                                // E: License Number
                date('m/d/Y', strtotime($attendee['date_of_completion'])),                  // F: Date of Completion
                "State={$attendee['state']}",                                               // G: State
                "profession=" . ($attendee['profession'] === 'Veterinarian' ? 'V' : 'VT'),  // H: Profession
            ];
    
            if ($courseType === 'multi') {
                $row[] = "CVEM";                                                            // I: Medical subject code
                $row[] = $attendee['medical_hours'];                                        // J: Medical Hours
                $row[] = "CVEN";                                                            // K: Non-Medical subject code
                $row[] = $attendee['non_medical_hours'];                                    // L: Non-Medical Hours
            }
    
            // Append the row to the sheet
            $sheet->fromArray($row, NULL, 'A' . $rowCount);
    
            // Increment the row count
            $rowCount++;
        }
    
        // Write the file to a temporary location
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), 'roster') . '.xlsx';
        $writer->save($temp_file);
    
        // Write the file to the WordPress uploads directory (or another appropriate directory)
        $upload_dir = wp_upload_dir(); // Get WordPress upload directory.
        
        // Create filename using providerNumber and courseNumber
        $filename = "50-{$providerNumber}-20-{$courseNumber}_roster.xlsx";
        $file_path = $upload_dir['path'] . '/' . $filename;
    
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($file_path);
    
        // Generate the URL to download the file
        $file_url = $upload_dir['url'] . '/' . $filename;

        return $file_url;
}