<?php

function sync_store_to_supabase($post_id, $post, $update) {
    // Ensure this is not an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Get the post type
    $post_type = $post->post_type;

    if ($post_type !== 'store') {
        return;
    }

    // Prepare the data based on post type
    $data = array(
        'post_id' => $post_id,
        'title' => $post->post_title,
        'date' => $post->post_date,
        'trans_dept' => get_post_meta($post_id, 'trans_dept', true),
        'address_street' => get_post_meta($post_id, 'address_street', true),
        'address_city' => get_post_meta($post_id, 'address_city', true),
        'address_state' => get_post_meta($post_id, 'address_state', true),
        'address_zip_code' => get_post_meta($post_id, 'address_zip_code', true),
        'address_phone' => get_post_meta($post_id, 'address_phone', true),
        'formatted_address' => get_post_meta($post_id, 'formatted_address', true)
    );

    // Replace missing fields with placeholder text
    foreach ($data as $key => $value) {
        if (empty($value)) {
            $data[$key] = "Couldn't Retrieve";
        }
    }

    // Check if post already exists in Supabase
    $url = rtrim(SUPABASE_FUNCTION_URL, '/') . '/rest/v1/stores?post_id=eq.' . $post_id;
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
        $url = rtrim(SUPABASE_FUNCTION_URL, '/') . '/rest/v1/stores?post_id=eq.' . $post_id;
        $method = 'PATCH';
    } else {
        // Create new record
        $url = rtrim(SUPABASE_FUNCTION_URL, '/') . '/rest/v1/stores';
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

function delete_store_from_supabase($post_id) {
    // Check if the post is a store
    $post_type = get_post_type($post_id);
    if ($post_type !== 'store') {
        return;
    }

    // Always using the "stores" table
    $url = rtrim(SUPABASE_FUNCTION_URL, '/') . '/rest/v1/stores?post_id=eq.' . $post_id;

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

