<?php

function sync_post_to_supabase($post_id, $post, $update) {
    // Ensure this is not an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Get the post type
    $post_type = $post->post_type;

    // Prepare the data based on post type
    $data = array(
        'post_id' => $post_id,
        'title' => $post->post_title,
        'date' => $post->post_date,
        'donor_name' => get_post_meta($post_id, 'donor_name', true),
        'donor_company' => get_post_meta($post_id, 'address_company', true),
        'donor_address' => get_post_meta($post_id, 'address_street', true),
        'donor_city' => get_post_meta($post_id, 'address_city', true),
        'donor_state' => get_post_meta($post_id, 'address_state', true),
        'donor_zip' => get_post_meta($post_id, 'address_zip', true),
        'donor_phone' => get_post_meta($post_id, 'donor_phone', true),
        'donor_email' => get_post_meta($post_id, 'donor_email', true),
        'donation_address' => get_post_meta($post_id, 'pickup_address_street', true) ?: get_post_meta($post_id, 'address_street', true),
        'donation_city' => get_post_meta($post_id, 'pickup_address_city', true) ?: get_post_meta($post_id, 'address_city', true),
        'donation_state' => get_post_meta($post_id, 'pickup_address_state', true) ?: get_post_meta($post_id, 'address_state', true),
        'donation_zip' => get_post_meta($post_id, 'pickup_address_zip', true) ?: get_post_meta($post_id, 'address_zip', true),
        'donation_desc' => get_post_meta($post_id, 'pickup_description', true),
        'pickup_date1' => get_post_meta($post_id, 'pickup_times_0_pick_up_time', true),
        'pickup_date2' => get_post_meta($post_id, 'pickup_times_1_pick_up_time', true),
        'pickup_date3' => get_post_meta($post_id, 'pickup_times_2_pick_up_time', true),
        'organization' => get_post_meta($post_id, 'organization', true),
        'priority_pickup' => get_post_meta($post_id, 'fee_based', true) == '1' ? 'YES' : 'NO',
        'referer' => get_post_meta($post_id, 'referer', true),
        'preferred_code' => get_post_meta($post_id, 'preferred_code', true),
    );

    // Replace missing fields with placeholder text
    foreach ($data as $key => $value) {
        if (empty($value)) {
            $data[$key] = "Couldn't Retrieve";
        }
    }

    // Check if post already exists in Supabase
    $url = rtrim(SUPABASE_FUNCTION_URL, '/') . '/rest/v1/donations?post_id=eq.' . $post_id;
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
        $url = rtrim(SUPABASE_FUNCTION_URL, '/') . '/rest/v1/donations?post_id=eq.' . $post_id;
        $method = 'PATCH';
    } else {
        // Create new record
        $url = rtrim(SUPABASE_FUNCTION_URL, '/') . '/rest/v1/donations';
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

function delete_post_from_supabase($post_id) {
    // Check if the post is a donation
    $post_type = get_post_type($post_id);
    if ($post_type !== 'donation') {
        return;
    }

    // Always using the "donations" table
    $url = rtrim(SUPABASE_FUNCTION_URL, '/') . '/rest/v1/donations?post_id=eq.' . $post_id;

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
