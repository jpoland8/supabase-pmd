<?php
/*
Plugin Name: DB Sync to Supabase
Description: Syncs various post types to Supabase when they are created, updated, or deleted.
Version: 1.0
Author: Jake Poland
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

require_once plugin_dir_path(__FILE__) . 'includes/supabase-sync-donations.php';
require_once plugin_dir_path(__FILE__) . 'includes/supabase-sync-transportation-departments.php';
require_once plugin_dir_path(__FILE__) . 'includes/supabase-sync-organizations.php';
require_once plugin_dir_path(__FILE__) . 'includes/supabase-sync-stores.php';


// Hook into post save and delete actions for donations
add_action('save_post_donation', 'sync_post_to_supabase', 10, 3);
add_action('trashed_post', 'delete_post_from_supabase');
add_action('before_delete_post', 'delete_post_from_supabase');

// Hook into post save and delete actions for transportation departments
add_action('save_post_trans_dept', 'sync_transportation_department_to_supabase', 10, 3);
add_action('trashed_post', 'delete_transportation_department_from_supabase');
add_action('before_delete_post', 'delete_transportation_department_from_supabase');

// Hook into post save and delete actions for organizations
add_action('save_post_organization', 'sync_organization_to_supabase', 10, 3);
add_action('trashed_post', 'delete_organization_from_supabase');
add_action('before_delete_post', 'delete_organization_from_supabase');

// Hook into post save and delete actions for stores
add_action('save_post_store', 'sync_store_to_supabase', 10, 3);
add_action('trashed_post', 'delete_store_from_supabase');
add_action('before_delete_post', 'delete_store_from_supabase');
?>
