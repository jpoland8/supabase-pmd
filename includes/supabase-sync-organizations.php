<?php

function sync_organization_to_supabase($post_id, $post, $update) {
    // Ensure this is not an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Get the post type
    $post_type = $post->post_type;

    if ($post_type !== 'organization') {
        return;
    }

    // Prepare the data based on post type
    $data = array(
        'post_id' => $post_id,
        'title' => $post->post_title,
        'date' => $post->post_date,
        'monthly_report_emails' => get_post_meta($post_id, 'monthly_report_emails', true),
        'website' => get_post_meta($post_id, 'website', true),
        'contact_title' => get_post_meta($post_id, 'default_trans_dept_contact_contact_title', true),
        'contact_name' => get_post_meta($post_id, 'default_trans_dept_contact_contact_name', true),
        'contact_email' => get_post_meta($post_id, 'default_trans_dept_contact_contact_email', true),
        'phone' => get_post_meta($post_id, 'default_trans_dept_contact_phone', true),
        'priority_pickup' => get_post_meta($post_id, 'pickup_settings_priority_pickup', true) ? 'Yes' : 'No',
        'donation_routing' => get_post_meta($post_id, 'pickup_settings_donation_routing', true),
        'skip_pickup_dates' => get_post_meta($post_id, 'pickup_settings_skip_pickup_dates', true) ? 'Yes' : 'No',
        'pickup_dates' => get_post_meta($post_id, 'pickup_settings_pickup_dates', true),
        'minimum_scheduling_interval' => get_post_meta($post_id, 'pickup_settings_minimum_scheduling_interval', true),
        'step_one_notice' => get_post_meta($post_id, 'pickup_settings_step_one_notice', true),
        'provide_additional_details' => get_post_meta($post_id, 'pickup_settings_provide_additional_details', true) ? 'Yes' : 'No',
        'allow_user_photo_uploads' => get_post_meta($post_id, 'pickup_settings_allow_user_photo_uploads', true) ? 'Yes' : 'No',
        'user_photo_uploads_required' => get_post_meta($post_id, 'pickup_settings_user_photo_uploads_required', true) ? 'Yes' : 'No',
        'pause_pickups' => get_post_meta($post_id, 'pickup_settings_pause_pickups', true) ? 'Yes' : 'No',
        'realtor_ad_standard_banner' => get_post_meta($post_id, 'pickup_settings_realtor_ad_standard_banner', true),
        'realtor_ad_medium_banner' => get_post_meta($post_id, 'pickup_settings_realtor_ad_medium_banner', true),
        'realtor_ad_link' => get_post_meta($post_id, 'pickup_settings_realtor_ad_link', true),
        'realtor_description' => get_post_meta($post_id, 'pickup_settings_realtor_description', true)
    );

    // Replace missing fields with placeholder text
    foreach ($data as $key => $value) {
        if (empty($value)) {
            $data[$key] = "Couldn't Retrieve";
        }
    }

    // Check if post already exists in Supabase
    $url = rtrim(SUPABASE_FUNCTION_URL, '/') . '/rest/v1/organizations?post_id=eq.' . $post_id;
    $response = wp_remote_get($url, array(
        'headers' => array(
            'apikey' => SUPABASE_SECRET_KEY
        )
    ));

    // Log request and response for debugging
    error_log('Check existing URL: ' . $url);
    error_log('Check existing response: ' . print_r($response, true));

    if (is_wp_error($response)) {
        error_log('Error checking existing data in Supabase: ' . $response->get_error_message());
        return;
    }

    $response_body = wp_remote_retrieve_body($response);
    $existing_data = json_decode($response_body, true);

    if (!empty($existing_data)) {
        // Update existing record
        $url = rtrim(SUPABASE_FUNCTION_URL, '/') . '/rest/v1/organizations?post_id=eq.' . $post_id;
        $method = 'PATCH';
    } else {
        // Create new record
        $url = rtrim(SUPABASE_FUNCTION_URL, '/') . '/rest/v1/organizations';
        $method = 'POST';
    }

    // Send data to Supabase
    $response = wp_remote_request($url, array(
        'method'    => $method,
        'headers'   => array(
            'Content-Type' => 'application/json',
            'apikey' => SUPABASE_SECRET_KEY // Include the secret API key here
        ),
        'body'      => json_encode($data),
    ));

    // Log request and response for debugging
    error_log('Request URL: ' . $url);
    error_log('Request Body: ' . json_encode($data));
    error_log('Response: ' . print_r($response, true));

    // Handle response
    if (is_wp_error($response)) {
        error_log('Error sending data to Supabase: ' . $response->get_error_message());
    } else {
        $response_body = wp_remote_retrieve_body($response);
        error_log('Data sent to Supabase successfully: ' . $response_body);

        if (wp_remote_retrieve_response_code($response) != 200) {
            error_log('Supabase API error: ' . $response_body);
        }
    }
}

function delete_organization_from_supabase($post_id) {
    // Check if the post is an organization
    $post_type = get_post_type($post_id);
    if ($post_type !== 'organization') {
        return;
    }

    // Always using the "organizations" table
    $url = rtrim(SUPABASE_FUNCTION_URL, '/') . '/rest/v1/organizations?post_id=eq.' . $post_id;

    // Send delete request to Supabase
    $response = wp_remote_request($url, array(
        'method'    => 'DELETE',
        'headers'   => array(
            'apikey' => SUPABASE_SECRET_KEY // Include the secret API key here
        ),
    ));

    // Log request and response for debugging
    error_log('Request URL: ' . $url);
    error_log('Response: ' . print_r($response, true));

    // Handle response
    if (is_wp_error($response)) {
        error_log('Error deleting data from Supabase: ' . $response->get_error_message());
    } else {
        $response_body = wp_remote_retrieve_body($response);
        error_log('Data deleted from Supabase successfully: ' . $response_body);
    }
}
?>

