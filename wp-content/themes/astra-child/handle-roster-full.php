<?php

function handle_roster_submission() {
    // Verify the nonce sent in the AJAX request for security
    if (!check_ajax_referer('submit_roster_nonce', 'security', false)) {
        wp_send_json_error(array('message' => 'Nonce verification failed.'));
        return;
    }

    // Sanitize and validate input data
    $course_type = sanitize_text_field($_POST['course_type']);
    $provider_number = sanitize_text_field($_POST['provider_number']);
    $course_number = sanitize_text_field($_POST['course_number']);
    // ... include additional fields as necessary ...

    // Validate that required fields are not empty
    if (empty($course_type) || empty($provider_number) || empty($course_number)) {
        wp_send_json_error(array('message' => 'Required fields are missing.'));
        return;
    }

    // Insert or update the roster post
    $post_id = wp_insert_post(array(
        'post_type'   => 'roster', // Replace with your actual CPT
        'post_title'  => 'Roster - ' . $provider_number, // Consider adding date or unique identifier
        'post_status' => 'publish', // Or 'draft' if it should not be publicly visible yet
        'post_author' => get_current_user_id(),
    ));

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => $post_id->get_error_message()));
        return;
    }

    // Save attendees' details. 
    // Assuming attendees are sent as an array of associative arrays from the form
    if (isset($_POST['attendees']) && is_array($_POST['attendees'])) {
        foreach ($_POST['attendees'] as $attendee_data) {
            // Each $attendee_data is an associative array with attendee details
            // Sanitize each value here
            $attendee_data = array_map('sanitize_text_field', $attendee_data);
            
            // Save the attendee data. For example, you might save it as post meta or to a custom table.
            // For saving as post meta:
            add_post_meta($post_id, 'attendee_data', $attendee_data);

            // If you use a custom table, use $wpdb to insert the data safely
        }
    }

    // Generate and save Excel file to the server
    // Use a function that encapsulates the Excel export logic.
    // Assuming you have a function `generate_roster_excel` that returns the path to the Excel file.
    $excel_file_path = generate_roster_excel($post_id);
    
    if (!$excel_file_path) {
        wp_send_json_error(array('message' => 'Failed to generate Excel file.'));
        return;
    }

    // Respond with success and include the URL to the generated Excel file
    wp_send_json_success(array(
        'message' => 'Data saved successfully.',
        'excel_url' => $excel_file_path
    ));
}

add_action('wp_ajax_submit_roster', 'handle_roster_submission'); // handles logged-in users



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