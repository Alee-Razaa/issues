<?php
defined('ABSPATH') || exit;

/**
 * Handle MINDBODY client registration
 */
add_action('wp_ajax_hw_mindbody_signup', 'hw_handle_mindbody_signup');
add_action('wp_ajax_nopriv_hw_mindbody_signup', 'hw_handle_mindbody_signup');

function hw_handle_mindbody_signup() {
    if (!check_ajax_referer('hw_mindbody_signup', 'nonce', false)) {
        wp_send_json_error('Invalid security token');
    }

    $required_fields = ['firstName', 'lastName', 'email', 'password'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_send_json_error("Missing required field: $field");
        }
    }

    // Validate UK postcode and phone
    if (!empty($_POST['postalCode']) && !preg_match('/^[A-Z]{1,2}[0-9][A-Z0-9]? ?[0-9][A-Z]{2}$/i', $_POST['postalCode'])) {
        wp_send_json_error('Please enter a valid UK postcode');
    }

    if (!empty($_POST['phone']) && !preg_match('/^(?:(?:\+44)|(?:0))(?:(?:(?:\d{10})|(?:\d{9})))$/', $_POST['phone'])) {
        wp_send_json_error('Please enter a valid UK mobile number');
    }

    // Sanitize input
    $first_name = sanitize_text_field($_POST['firstName']);
    $last_name = sanitize_text_field($_POST['lastName']);
    $email = sanitize_email($_POST['email']);
    $password = sanitize_text_field($_POST['password']);
    $phone = sanitize_text_field($_POST['phone'] ?? '');

    // Build address array
    $address = [];
    if (!empty($_POST['address1'])) {
        $address = [
            'AddressLine1' => sanitize_text_field($_POST['address1']),
            'City' => sanitize_text_field($_POST['city'] ?? ''),
            'State' => 'NA',
            'PostalCode' => sanitize_text_field($_POST['postalCode'] ?? ''),
        ];
    }

    $birth_date = !empty($_POST['birthDate']) ? $_POST['birthDate'] : date('Y-m-d', strtotime('-18 years'));
    $referred_by = !empty($_POST['referredBy']) ? $_POST['referredBy'] : 'Website';

    // ========== 1️⃣ Add client to MINDBODY ==========
    $result = hw_add_mindbody_client($first_name, $last_name, $email, $password, $address, $phone, $birth_date, $referred_by);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    $mindbody_client_id = $result->Client->Id ?? null;
    if (!$mindbody_client_id) {
        wp_send_json_error('Mindbody signup succeeded, but no client ID returned.');
    }

    // ========== 2️⃣ Create WooCommerce Customer ==========
    if (email_exists($email)) {
        wp_send_json_error('This email is already registered on the site.');
    }

    $user_id = wp_insert_user([
        'user_login' => $email,
        'user_email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'user_pass' => $password,
        'role' => 'customer', // WooCommerce-specific role
    ]);

    if (is_wp_error($user_id)) {
        wp_send_json_error('Failed to create WooCommerce user: ' . $user_id->get_error_message());
    }
    update_user_meta($user_id, 'remaining_yoga_classes', 0);
    update_user_meta($user_id, 'remaining_pilates_classes', 0);
    update_user_meta($user_id, 'remaining_equipments_classes', 0);
    update_user_meta($user_id, 'remaining_treatment_classes', 0);
    update_user_meta($user_id, 'remaining_reformer_classes', 0);
    // ========== 3️⃣ Store Mindbody Client ID & WooCommerce Billing Info ==========
    update_user_meta($user_id, 'mindbody_client_id', $mindbody_client_id);

    // WooCommerce-specific billing fields
    update_user_meta($user_id, 'billing_first_name', $first_name);
    update_user_meta($user_id, 'billing_last_name', $last_name);
    update_user_meta($user_id, 'billing_email', $email);
    update_user_meta($user_id, 'billing_phone', $phone);
    update_user_meta($user_id, 'billing_address_1', $address['AddressLine1'] ?? '');
    update_user_meta($user_id, 'billing_city', $address['City'] ?? '');
    update_user_meta($user_id, 'billing_postcode', $address['PostalCode'] ?? '');
    update_user_meta($user_id, 'billing_country', 'UK');

    // ========== 4️⃣ Auto Login WooCommerce Customer ==========
    wp_set_auth_cookie($user_id);
    wp_set_current_user($user_id);

    // ========== 5️⃣ Return Success Response ==========
    wp_send_json_success('Account created successfully! You are now logged in.');
}

/**
 * Function to add client to MINDBODY (you already had this, slightly refactored for consistency)
 */
function hw_add_mindbody_client($first_name, $last_name, $email, $password, $address = [], $phone = '', $birth_date = '', $referred_by = '') {
    $api_key = get_option('mindbody_api_key');

    if (empty($api_key)) {
        return new WP_Error('missing_credentials', 'MINDBODY API credentials are not configured');
    }

    $body = [
        'FirstName' => $first_name,
        'LastName' => $last_name,
        'Email' => $email,
        'Password' => $password,
        'Country' => 'UK',
        'BirthDate' => $birth_date ?: date('Y-m-d', strtotime('-18 years')),
        'ReferredBy' => $referred_by ?: 'Website',
    ];

    if (!empty($address)) {
        $address['State'] = 'NA'; // Required for UK
        $body = array_merge($body, $address);
    }

    if (!empty($phone)) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '+44' . substr($phone, 1);
        }
        $body['MobilePhone'] = $phone;
    }

    $response = wp_remote_post('https://api.mindbodyonline.com/public/v6/client/addclient', [
        'body' => json_encode($body),
        'headers' => [
            'API-Key' => $api_key,
            'SiteId' => get_option('mindbody_site_id', '-1'),
            'Content-Type' => 'application/json',
        ],
    ]);

    error_log('MINDBODY API Request Body: ' . print_r($body, true));
    error_log('MINDBODY API Response: ' . print_r($response, true));

    if (is_wp_error($response)) {
        return $response;
    }

    $response_body = json_decode(wp_remote_retrieve_body($response));
    $status_code = wp_remote_retrieve_response_code($response);

    if ($status_code !== 200) {
        return new WP_Error(
            'mindbody_error',
            isset($response_body->Message) ? $response_body->Message : 'Unknown error occurred while adding client to MINDBODY'
        );
    }

    return $response_body;
}


// Add settings fields for MINDBODY API credentials
add_action('admin_init', 'hw_register_mindbody_settings');

function hw_register_mindbody_settings() {
    register_setting('general', 'mindbody_api_key');
    register_setting('general', 'mindbody_site_id');

    add_settings_section(
        'hw_mindbody_settings',
        'MINDBODY API Settings',
        'hw_mindbody_settings_section_callback',
        'general'
    );

    add_settings_field(
        'mindbody_api_key',
        'API Key',
        'hw_mindbody_api_key_callback',
        'general',
        'hw_mindbody_settings'
    );

    add_settings_field(
        'mindbody_site_id',
        'Site ID',
        'hw_mindbody_site_id_callback',
        'general',
        'hw_mindbody_settings'
    );
}

function hw_mindbody_settings_section_callback() {
    echo '<p>Enter your MINDBODY API credentials below:</p>';
}

function hw_mindbody_api_key_callback() {
    $api_key = get_option('mindbody_api_key');
    echo '<input type="text" name="mindbody_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
}

function hw_mindbody_site_id_callback() {
    $site_id = get_option('mindbody_site_id');
    echo '<input type="text" name="mindbody_site_id" value="' . esc_attr($site_id) . '" class="regular-text">';
}
